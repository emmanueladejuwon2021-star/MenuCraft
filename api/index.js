const express = require("express");
const cors = require("cors");
const { readyDb, mapItem } = require("./db");
const { hashPassword, checkPassword, makeToken, publicUser, readToken, findUserByToken } = require("./auth");
const { applyPrice } = require("./pricing");
const { buildOrderLines, visibleOrders } = require("./order-build");
const { registerUser, loginUser } = require("./accounts");
const { cleanImageUrl } = require("./http");
const crypto = require("crypto");

const app = express();
const ORDER_STATUSES = ["New", "Cooking", "Ready", "Served"];

app.use(
  cors({
    origin(origin, callback) {
      if (!origin) return callback(null, true);
      let host = "";
      try {
        host = new URL(origin).hostname;
      } catch {
        return callback(null, false);
      }
      const allowed =
        host === "localhost" ||
        host === "127.0.0.1" ||
        host.endsWith(".github.io") ||
        host.endsWith(".vercel.app");
      callback(null, allowed);
    },
    credentials: true,
  })
);
app.use(express.json({ limit: "1mb" }));

function fail(res, status, message) {
  return res.status(status).json({ ok: false, message });
}

function readMoney(value) {
  const price = Number(value);
  if (!Number.isFinite(price) || price < 0 || price > 10000000) return null;
  return Math.round(price * 100) / 100;
}

function readLabel(value, max) {
  return String(value || "").trim().slice(0, max);
}

function readOrderId(value) {
  const id = Number(value);
  if (!Number.isInteger(id) || id < 1) return 0;
  return id;
}

function mapOrder(row) {
  let items = [];
  try {
    items = JSON.parse(row.items_json || "[]");
  } catch {
    items = [];
  }
  return {
    id: Number(row.id),
    guest_name: row.guest_name,
    guest_email: row.guest_email || "",
    phone: row.phone || "",
    note: row.note || "",
    items,
    total: Number(row.total),
    paid: Number(row.paid) === 1,
    pay_ref: row.pay_ref || "",
    view_token: row.view_token || "",
    status: row.status || "New",
    created_at: row.created_at,
  };
}

async function withDb(req, res, next) {
  try {
    const db = await readyDb();
    if (!db) {
      return fail(res, 503, "The shared menu is not connected yet. Add the database details in Vercel, then try again.");
    }
    req.db = db;
    next();
  } catch (error) {
    console.error(error);
    return fail(res, 500, "Could not open the menu store. Please try again.");
  }
}

async function requireStaff(req, res, next) {
  try {
    const user = await findUserByToken(req.db, readToken(req));
    if (!user || user.role !== "staff") {
      return fail(res, 401, "Kitchen staff must sign in before changing the menu.");
    }
    req.user = user;
    next();
  } catch (error) {
    console.error(error);
    return fail(res, 500, "Could not check the kitchen session. Please try again.");
  }
}

app.get("/api/health", async (_req, res) => {
  const db = await readyDb();
  res.json({ ok: true, connected: Boolean(db) });
});

app.post("/api/signup", withDb, async (req, res) => {
  try {
    const result = await registerUser(req.db, req.body || {}, readToken(req));
    if (result.message) return fail(res, result.status, result.message);
    return res.status(result.status).json(result.body);
  } catch (error) {
    console.error(error);
    return fail(res, 500, "Could not create the account. Please try again.");
  }
});

app.post("/api/login", withDb, async (req, res) => {
  try {
    const result = await loginUser(req.db, req.body || {}, req);
    if (result.message) return fail(res, result.status, result.message);
    return res.status(result.status).json(result.body);
  } catch (error) {
    console.error(error);
    return fail(res, 500, "Could not sign in. Please try again.");
  }
});

app.put("/api/account", withDb, async (req, res) => {
  try {
    const current = await findUserByToken(req.db, readToken(req));
    if (!current) return fail(res, 401, "Please sign in first.");
    const name = readLabel(req.body?.name || current.name, 80);
    const phone = readLabel(req.body?.phone || current.phone || "", 30);
    const restaurantName = readLabel(req.body?.restaurant_name || current.restaurant_name || "", 80);
    if (!name) return fail(res, 400, "Please enter your name.");
    await req.db.execute({
      sql: "UPDATE users SET name = ?, phone = ?, restaurant_name = ? WHERE id = ?",
      args: [name, phone, restaurantName, current.id],
    });
    res.json({ ok: true, user: { ...current, name, phone, restaurant_name: restaurantName } });
  } catch (error) {
    console.error(error);
    fail(res, 500, "Could not save your details. Please try again.");
  }
});

app.get("/api/menu", withDb, async (req, res) => {
  try {
    const [settingsRes, categoriesRes, itemsRes] = await Promise.all([
      req.db.execute("SELECT restaurant_name, currency_symbol FROM restaurant_settings WHERE id = 1"),
      req.db.execute("SELECT id, name, display_order FROM categories ORDER BY display_order, name"),
      req.db.execute("SELECT * FROM menu_items ORDER BY name"),
    ]);
    const settings = settingsRes.rows[0] || { restaurant_name: "My Restaurant", currency_symbol: "$" };
    res.json({
      ok: true,
      settings: { restaurant_name: settings.restaurant_name, currency_symbol: settings.currency_symbol },
      categories: categoriesRes.rows.map(mapCategory),
      items: itemsRes.rows.map(mapItem),
    });
  } catch (error) {
    console.error(error);
    fail(res, 500, "Could not load the menu. Please try again.");
  }
});

function mapCategory(row) {
  return {
    id: Number(row.id),
    name: row.name,
    display_order: Number(row.display_order || 0),
  };
}

app.get("/api/categories", withDb, async (req, res) => {
  try {
    const result = await req.db.execute("SELECT id, name, display_order FROM categories ORDER BY display_order, name");
    res.json({ ok: true, categories: result.rows.map(mapCategory) });
  } catch (error) {
    console.error(error);
    fail(res, 500, "Could not load categories. Please try again.");
  }
});

app.post("/api/categories", withDb, requireStaff, async (req, res) => {
  const name = readLabel(req.body?.name, 40);
  if (!name) return fail(res, 400, "Please enter a category name.");
  if (String(req.body?.name || "").trim().length > 40) return fail(res, 400, "Category names can be 40 characters.");
  try {
    const orderRes = await req.db.execute("SELECT COALESCE(MAX(display_order), 0) + 1 AS next_order FROM categories");
    const inserted = await req.db.execute({
      sql: "INSERT INTO categories (name, display_order) VALUES (?, ?) RETURNING id, name, display_order",
      args: [name, Number(orderRes.rows[0].next_order)],
    });
    res.status(201).json({ ok: true, message: "Added to category list", category: mapCategory(inserted.rows[0]) });
  } catch (error) {
    if (String(error.message || "").includes("UNIQUE")) return fail(res, 409, "That category already exists.");
    console.error(error);
    fail(res, 500, "Could not add the category. Please try again.");
  }
});

app.delete("/api/categories/:id", withDb, requireStaff, async (req, res) => {
  const id = Number(req.params.id);
  if (!id) return fail(res, 400, "That category could not be found.");
  try {
    const found = await req.db.execute({ sql: "SELECT id FROM categories WHERE id = ?", args: [id] });
    if (!found.rows.length) return fail(res, 404, "That category could not be found.");
    await req.db.execute({ sql: "DELETE FROM menu_items WHERE category_id = ?", args: [id] });
    await req.db.execute({ sql: "DELETE FROM categories WHERE id = ?", args: [id] });
    res.json({ ok: true, message: "Category removed" });
  } catch (error) {
    console.error(error);
    fail(res, 500, "Could not remove that category. Please try again.");
  }
});

app.post("/api/items", withDb, requireStaff, async (req, res) => {
  const body = req.body || {};
  const name = readLabel(body.name, 80);
  const categoryId = Number(body.category_id);
  const price = readMoney(body.price);
  if (!name) return fail(res, 400, "Please enter a dish name.");
  if (String(body.name || "").trim().length > 80) return fail(res, 400, "Dish names can be 80 characters.");
  if (!Number.isInteger(categoryId) || categoryId < 1) return fail(res, 400, "Please choose a category.");
  if (price === null) return fail(res, 400, "Please enter a valid price.");
  const tags = (Array.isArray(body.tags) ? body.tags.join(",") : String(body.tags || "")).slice(0, 120);
  const description = readLabel(body.description, 280);
  try {
    const category = await req.db.execute({ sql: "SELECT id FROM categories WHERE id = ?", args: [categoryId] });
    if (!category.rows.length) return fail(res, 400, "Please choose a category that exists.");
    const inserted = await req.db.execute({
      sql: "INSERT INTO menu_items (category_id, name, description, price, image_url, is_available, tags, prep_time) VALUES (?, ?, ?, ?, ?, ?, ?, ?) RETURNING *",
      args: [categoryId, name, description, price, cleanImageUrl(body.image_url), body.is_available === false ? 0 : 1, tags, Math.min(240, Math.max(1, Math.floor(Number(body.prep_time) || 10)))],
    });
    res.status(201).json({ ok: true, message: "Added to category", item: mapItem(inserted.rows[0]) });
  } catch (error) {
    console.error(error);
    fail(res, 500, "Could not add the dish. Please try again.");
  }
});

app.put("/api/items/:id", withDb, requireStaff, async (req, res) => {
  const body = req.body || {};
  const id = readOrderId(req.params.id);
  const name = readLabel(body.name, 80);
  const categoryId = Number(body.category_id);
  const price = readMoney(body.price);
  if (!id) return fail(res, 400, "That dish could not be found.");
  if (!name) return fail(res, 400, "Please enter a dish name.");
  if (String(body.name || "").trim().length > 80) return fail(res, 400, "Dish names can be 80 characters.");
  if (!Number.isInteger(categoryId) || categoryId < 1) return fail(res, 400, "Please choose a category.");
  if (price === null) return fail(res, 400, "Please enter a valid price.");
  const tags = (Array.isArray(body.tags) ? body.tags.join(",") : String(body.tags || "")).slice(0, 120);
  const description = readLabel(body.description, 280);
  try {
    const category = await req.db.execute({ sql: "SELECT id FROM categories WHERE id = ?", args: [categoryId] });
    if (!category.rows.length) return fail(res, 400, "Please choose a category that exists.");
    const updated = await req.db.execute({
      sql: "UPDATE menu_items SET category_id = ?, name = ?, description = ?, price = ?, image_url = ?, is_available = ?, tags = ?, prep_time = ? WHERE id = ? RETURNING *",
      args: [categoryId, name, description, price, cleanImageUrl(body.image_url), body.is_available === false ? 0 : 1, tags, Math.min(240, Math.max(1, Math.floor(Number(body.prep_time) || 10))), id],
    });
    if (!updated.rows.length) return fail(res, 404, "That dish could not be found.");
    res.json({ ok: true, message: "Saved!", item: mapItem(updated.rows[0]) });
  } catch (error) {
    console.error(error);
    fail(res, 500, "Could not save the dish. Please try again.");
  }
});

app.patch("/api/items/:id/status", withDb, requireStaff, async (req, res) => {
  const available = req.body?.is_available === false || req.body?.is_available === 0 ? 0 : 1;
  try {
    const updated = await req.db.execute({
      sql: "UPDATE menu_items SET is_available = ? WHERE id = ? RETURNING *",
      args: [available, req.params.id],
    });
    if (!updated.rows.length) return fail(res, 404, "That dish could not be found.");
    const item = mapItem(updated.rows[0]);
    res.json({ ok: true, message: item.is_available ? "Marked as in stock" : "Item hidden from menu", item });
  } catch (error) {
    console.error(error);
    fail(res, 500, "Something went wrong. Changes were not saved.");
  }
});

app.post("/api/items/bulk-price-update", withDb, requireStaff, async (req, res) => {
  const categoryId = Number(req.body?.category_id);
  const mode = String(req.body?.mode || "percent");
  const amount = Number(req.body?.amount);
  if (!Number.isInteger(categoryId) || categoryId < 1) return fail(res, 400, "Please choose a category.");
  if (mode !== "percent" && mode !== "amount") return fail(res, 400, "Choose percent or a fixed amount.");
  if (!Number.isFinite(amount)) return fail(res, 400, "Please enter an amount.");
  if (mode === "amount" && Math.abs(amount) > 10000000) return fail(res, 400, "That amount is too large.");
  try {
    const current = await req.db.execute({
      sql: "SELECT id, price FROM menu_items WHERE category_id = ?",
      args: [categoryId],
    });
    if (!current.rows.length) {
      return res.json({ ok: true, message: "No dishes in that list to update", items: [] });
    }
    if (mode === "percent" && (amount < -100 || amount > 500)) {
      return fail(res, 400, "Use a percent between -100 and 500.");
    }
    const nextPrices = current.rows.map((row) => applyPrice(row.price, mode, amount));
    const floored = nextPrices.some((price, index) => price === 0 && Number(current.rows[index].price) > 0);
    if (typeof req.db.batch === "function") {
      await req.db.batch(
        current.rows.map((row, index) => ({
          sql: "UPDATE menu_items SET price = ? WHERE id = ?",
          args: [nextPrices[index], row.id],
        })),
        "write"
      );
    } else {
      for (let index = 0; index < current.rows.length; index += 1) {
        const row = current.rows[index];
        await req.db.execute({
          sql: "UPDATE menu_items SET price = ? WHERE id = ?",
          args: [nextPrices[index], row.id],
        });
      }
    }
    const items = await req.db.execute({ sql: "SELECT * FROM menu_items WHERE category_id = ? ORDER BY name", args: [categoryId] });
    res.json({
      ok: true,
      message: floored ? "Prices updated. Some dishes stopped at zero." : "Price updated successfully",
      items: items.rows.map(mapItem),
    });
  } catch (error) {
    console.error(error);
    fail(res, 500, "Something went wrong. Changes were not saved.");
  }
});

app.delete("/api/items/:id", withDb, requireStaff, async (req, res) => {
  try {
    const found = await req.db.execute({ sql: "SELECT id FROM menu_items WHERE id = ?", args: [req.params.id] });
    if (!found.rows.length) return fail(res, 404, "That dish could not be found.");
    await req.db.execute({ sql: "DELETE FROM menu_items WHERE id = ?", args: [req.params.id] });
    res.json({ ok: true, message: "Dish removed" });
  } catch (error) {
    console.error(error);
    fail(res, 500, "Could not remove that dish. Please try again.");
  }
});

app.get("/api/settings", withDb, async (req, res) => {
  try {
    const result = await req.db.execute("SELECT restaurant_name, currency_symbol FROM restaurant_settings WHERE id = 1");
    res.json({ ok: true, settings: result.rows[0] });
  } catch (error) {
    console.error(error);
    fail(res, 500, "Could not load restaurant details. Please try again.");
  }
});

app.put("/api/settings", withDb, requireStaff, async (req, res) => {
  const name = readLabel(req.body?.restaurant_name, 80);
  const symbol = readLabel(req.body?.currency_symbol, 4) || "₦";
  if (!name) return fail(res, 400, "Please enter a restaurant name.");
  if (String(req.body?.restaurant_name || "").trim().length > 80) return fail(res, 400, "Restaurant names can be 80 characters.");
  try {
    await req.db.execute({
      sql: "UPDATE restaurant_settings SET restaurant_name = ?, currency_symbol = ? WHERE id = 1",
      args: [name, symbol],
    });
    res.json({ ok: true, message: "Saved!", settings: { restaurant_name: name, currency_symbol: symbol } });
  } catch (error) {
    console.error(error);
    fail(res, 500, "Could not save restaurant details. Please try again.");
  }
});

app.get("/api/orders", withDb, async (req, res) => {
  try {
    const user = await findUserByToken(req.db, readToken(req));
    const result = await req.db.execute("SELECT * FROM orders ORDER BY id DESC");
    const orders = visibleOrders(result.rows.map(mapOrder), user, req.query?.tickets);
    res.json({ ok: true, orders });
  } catch (error) {
    console.error(error);
    fail(res, 500, "Could not load orders. Please try again.");
  }
});

app.post("/api/orders", withDb, async (req, res) => {
  const requested = Array.isArray(req.body?.items) ? req.body.items : [];
  const guest = req.body?.guest || {};
  let session = null;
  try {
    session = await findUserByToken(req.db, readToken(req));
  } catch {
    session = null;
  }
  const name = String(guest.name || session?.name || "").trim().slice(0, 80);
  if (!name) return fail(res, 400, "Add your name so the kitchen can call the plate.");
  const email = String(guest.email || session?.email || "").trim().toLowerCase().slice(0, 120);
  const phone = String(guest.phone || session?.phone || "").trim().slice(0, 30);
  const note = String(req.body?.note || "").trim().slice(0, 240);
  try {
    const menu = await req.db.execute("SELECT id, name, price, is_available, image_url FROM menu_items");
    const built = buildOrderLines(requested, menu.rows);
    const payRef = `COUNTER-${Date.now().toString().slice(-8)}`;
    const viewToken = crypto.randomBytes(18).toString("hex");
    const inserted = await req.db.execute({
      sql: "INSERT INTO orders (guest_name, guest_email, phone, note, items_json, total, paid, pay_ref, status, created_at, view_token) VALUES (?, ?, ?, ?, ?, ?, 0, ?, 'New', ?, ?) RETURNING *",
      args: [name, email, phone, note, JSON.stringify(built.lines), built.total, payRef, new Date().toISOString(), viewToken],
    });
    res.status(201).json({ ok: true, order: mapOrder(inserted.rows[0]) });
  } catch (error) {
    if (error.status) return fail(res, error.status, error.message);
    console.error(error);
    fail(res, 500, "Could not send the order. Please try again.");
  }
});

app.post("/api/logout", withDb, async (req, res) => {
  const token = readToken(req);
  if (token) {
    try {
      await req.db.execute({ sql: "DELETE FROM sessions WHERE token = ?", args: [token] });
    } catch (error) {
      console.error(error);
    }
  }
  res.json({ ok: true });
});

app.post("/api/order-status", withDb, requireStaff, async (req, res) => {
  const status = String(req.body?.status || "").trim();
  const id = readOrderId(req.body?.id);
  if (!id) return fail(res, 400, "That order could not be found.");
  if (!ORDER_STATUSES.includes(status)) return fail(res, 400, "That order status is not valid.");
  try {
    const updated = await req.db.execute({
      sql: "UPDATE orders SET status = ? WHERE id = ? RETURNING *",
      args: [status, id],
    });
    if (!updated.rows.length) return fail(res, 404, "That order could not be found.");
    res.json({ ok: true, order: mapOrder(updated.rows[0]) });
  } catch (error) {
    console.error(error);
    fail(res, 500, "Could not update the order. Please try again.");
  }
});

app.patch("/api/orders/:id", withDb, requireStaff, async (req, res) => {
  const status = String(req.body?.status || "").trim();
  const id = readOrderId(req.params.id);
  if (!id) return fail(res, 400, "That order could not be found.");
  if (!ORDER_STATUSES.includes(status)) return fail(res, 400, "That order status is not valid.");
  try {
    const updated = await req.db.execute({
      sql: "UPDATE orders SET status = ? WHERE id = ? RETURNING *",
      args: [status, id],
    });
    if (!updated.rows.length) return fail(res, 404, "That order could not be found.");
    res.json({ ok: true, order: mapOrder(updated.rows[0]) });
  } catch (error) {
    console.error(error);
    fail(res, 500, "Could not update the order. Please try again.");
  }
});

app.use("/api", (_req, res) => fail(res, 404, "That action is not available."));

module.exports = app;
