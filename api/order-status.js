const { readyDb } = require("./db");
const { readToken, findUserByToken } = require("./auth");
const { applyCors } = require("./http");

const ALLOWED = ["New", "Cooking", "Ready", "Served"];

function readBody(req) {
  if (req.body && typeof req.body === "object") return req.body;
  if (typeof req.body === "string") {
    try {
      return JSON.parse(req.body);
    } catch {
      return {};
    }
  }
  return {};
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

module.exports = async (req, res) => {
  applyCors(req, res);
  if (req.method === "OPTIONS") return res.status(204).end();
  if (req.method !== "POST" && req.method !== "PATCH") {
    return res.status(405).json({ ok: false, message: "Use the kitchen button to move an order." });
  }

  const body = readBody(req);
  const id = Number(body.id);
  const status = String(body.status || "").trim();
  if (!id) return res.status(400).json({ ok: false, message: "That order could not be found." });
  if (!ALLOWED.includes(status)) return res.status(400).json({ ok: false, message: "That order status is not valid." });

  try {
    const db = await readyDb();
    if (!db) return res.status(503).json({ ok: false, message: "The shared menu is not connected yet. Add the database details in Vercel, then try again." });
    const user = await findUserByToken(db, readToken(req));
    if (!user || user.role !== "staff") {
      return res.status(401).json({ ok: false, message: "Kitchen staff must sign in before changing an order." });
    }
    const updated = await db.execute({
      sql: "UPDATE orders SET status = ? WHERE id = ? RETURNING *",
      args: [status, id],
    });
    if (!updated.rows.length) return res.status(404).json({ ok: false, message: "That order could not be found." });
    return res.json({ ok: true, order: mapOrder(updated.rows[0]) });
  } catch (error) {
    console.error(error);
    return res.status(500).json({ ok: false, message: "Could not update the order. Please try again." });
  }
};
