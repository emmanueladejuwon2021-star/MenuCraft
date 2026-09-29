const express = require("express");
const cors = require("cors");
const { readyDb, mapItem } = require("./db");

const app = express();

app.use(
  cors({
    origin(origin, callback) {
      if (!origin) return callback(null, true);
      const allowed =
        origin.includes("github.io") ||
        origin.includes("vercel.app") ||
        origin.includes("localhost") ||
        origin.includes("127.0.0.1");
      callback(null, allowed);
    },
    credentials: true,
  })
);
app.use(express.json({ limit: "1mb" }));

function fail(res, status, message) {
  return res.status(status).json({ ok: false, message });
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

app.get("/api/health", async (_req, res) => {
  const db = await readyDb();
  res.json({ ok: true, connected: Boolean(db) });
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
      categories: categoriesRes.rows.map((row) => ({
        id: Number(row.id),
        name: row.name,
        display_order: Number(row.display_order || 0),
      })),
      items: itemsRes.rows.map(mapItem),
    });
  } catch (error) {
    console.error(error);
    fail(res, 500, "Could not load the menu. Please try again.");
  }
});

app.get("/api/categories", withDb, async (req, res) => {
  try {
    const result = await req.db.execute("SELECT id, name, display_order FROM categories ORDER BY display_order, name");
    res.json({ ok: true, categories: result.rows });
  } catch (error) {
    console.error(error);
    fail(res, 500, "Could not load categories. Please try again.");
  }
});

app.post("/api/categories", withDb, async (req, res) => {
  const name = String(req.body?.name || "").trim();
  if (!name) return fail(res, 400, "Please enter a category name.");
  try {
    const orderRes = await req.db.execute("SELECT COALESCE(MAX(display_order), 0) + 1 AS next_order FROM categories");
    const inserted = await req.db.execute({
      sql: "INSERT INTO categories (name, display_order) VALUES (?, ?) RETURNING id, name, display_order",
      args: [name, Number(orderRes.rows[0].next_order)],
    });
    res.status(201).json({ ok: true, message: "Added to category list", category: inserted.rows[0] });
  } catch (error) {
    if (String(error.message || "").includes("UNIQUE")) return fail(res, 409, "That category already exists.");
    console.error(error);
    fail(res, 500, "Could not add the category. Please try again.");
  }
});

app.delete("/api/categories/:id", withDb, async (req, res) => {
  try {
    await req.db.execute({ sql: "DELETE FROM menu_items WHERE category_id = ?", args: [req.params.id] });
    await req.db.execute({ sql: "DELETE FROM categories WHERE id = ?", args: [req.params.id] });
    res.json({ ok: true, message: "Category removed" });
  } catch (error) {
    console.error(error);
    fail(res, 500, "Could not remove that category. Please try again.");
  }
});

app.post("/api/items", withDb, async (req, res) => {
  const body = req.body || {};
  const name = String(body.name || "").trim();
  const categoryId = Number(body.category_id);
  const price = Number(body.price);
  if (!name) return fail(res, 400, "Please enter a dish name.");
  if (!categoryId) return fail(res, 400, "Please choose a category.");
  if (Number.isNaN(price) || price < 0) return fail(res, 400, "Please enter a valid price.");
  const tags = Array.isArray(body.tags) ? body.tags.join(",") : String(body.tags || "");
  try {
    const inserted = await req.db.execute({
      sql: "INSERT INTO menu_items (category_id, name, description, price, image_url, is_available, tags, prep_time) VALUES (?, ?, ?, ?, ?, ?, ?, ?) RETURNING *",
      args: [categoryId, name, String(body.description || ""), price, String(body.image_url || ""), body.is_available === false ? 0 : 1, tags, Number(body.prep_time || 10)],
    });
    res.status(201).json({ ok: true, message: "Added to category", item: mapItem(inserted.rows[0]) });
  } catch (error) {
    console.error(error);
    fail(res, 500, "Could not add the dish. Please try again.");
  }
});

app.put("/api/items/:id", withDb, async (req, res) => {
  const body = req.body || {};
  const name = String(body.name || "").trim();
  const categoryId = Number(body.category_id);
  const price = Number(body.price);
  if (!name) return fail(res, 400, "Please enter a dish name.");
  if (!categoryId) return fail(res, 400, "Please choose a category.");
  if (Number.isNaN(price) || price < 0) return fail(res, 400, "Please enter a valid price.");
  const tags = Array.isArray(body.tags) ? body.tags.join(",") : String(body.tags || "");
  try {
    const updated = await req.db.execute({
      sql: "UPDATE menu_items SET category_id = ?, name = ?, description = ?, price = ?, image_url = ?, is_available = ?, tags = ?, prep_time = ? WHERE id = ? RETURNING *",
      args: [categoryId, name, String(body.description || ""), price, String(body.image_url || ""), body.is_available === false ? 0 : 1, tags, Number(body.prep_time || 10), req.params.id],
    });
    if (!updated.rows.length) return fail(res, 404, "That dish could not be found.");
    res.json({ ok: true, message: "Saved!", item: mapItem(updated.rows[0]) });
  } catch (error) {
    console.error(error);
    fail(res, 500, "Could not save the dish. Please try again.");
  }
});

app.patch("/api/items/:id/status", withDb, async (req, res) => {
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

app.post("/api/items/bulk-price-update", withDb, async (req, res) => {
  const categoryId = Number(req.body?.category_id);
  const mode = String(req.body?.mode || "percent");
  const amount = Number(req.body?.amount);
  if (!categoryId) return fail(res, 400, "Please choose a category.");
  if (Number.isNaN(amount)) return fail(res, 400, "Please enter an amount.");
  try {
    if (mode === "amount") {
      await req.db.execute({ sql: "UPDATE menu_items SET price = MAX(0, ROUND(price + ?, 2)) WHERE category_id = ?", args: [amount, categoryId] });
    } else {
      await req.db.execute({ sql: "UPDATE menu_items SET price = MAX(0, ROUND(price * (1 + ? / 100.0), 2)) WHERE category_id = ?", args: [amount, categoryId] });
    }
    const items = await req.db.execute({ sql: "SELECT * FROM menu_items WHERE category_id = ? ORDER BY name", args: [categoryId] });
    res.json({ ok: true, message: "Price updated successfully", items: items.rows.map(mapItem) });
  } catch (error) {
    console.error(error);
    fail(res, 500, "Something went wrong. Changes were not saved.");
  }
});

app.delete("/api/items/:id", withDb, async (req, res) => {
  try {
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

app.put("/api/settings", withDb, async (req, res) => {
  const name = String(req.body?.restaurant_name || "").trim();
  const symbol = String(req.body?.currency_symbol || "").trim() || "$";
  if (!name) return fail(res, 400, "Please enter a restaurant name.");
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

app.use("/api", (_req, res) => fail(res, 404, "That action is not available."));

module.exports = app;
