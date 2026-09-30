const { readyDb } = require("./db");
const { hashPassword, makeToken, publicUser } = require("./auth");

module.exports = async (req, res) => {
  res.setHeader("Access-Control-Allow-Origin", "*");
  res.setHeader("Access-Control-Allow-Methods", "POST,OPTIONS");
  res.setHeader("Access-Control-Allow-Headers", "Content-Type, Authorization");
  if (req.method === "OPTIONS") return res.status(200).end();
  if (req.method !== "POST") return res.status(405).json({ ok: false, message: "Please use the form to create an account." });

  const name = String(req.body?.name || "").trim();
  const email = String(req.body?.email || "").trim().toLowerCase();
  const password = String(req.body?.password || "");
  const restaurantName = String(req.body?.restaurant_name || "").trim() || "Guest";
  const phone = String(req.body?.phone || "").trim();
  const role = req.body?.role === "guest" ? "guest" : "staff";

  if (!name) return res.status(400).json({ ok: false, message: "Please enter your name." });
  if (!email.includes("@")) return res.status(400).json({ ok: false, message: "Please enter a valid email." });
  if (password.length < 6) return res.status(400).json({ ok: false, message: "Password should be at least 6 characters." });

  try {
    const db = await readyDb();
    if (!db) return res.status(503).json({ ok: false, message: "The shared menu is not connected yet." });
    const existing = await db.execute({ sql: "SELECT id FROM users WHERE email = ?", args: [email] });
    if (existing.rows.length) return res.status(409).json({ ok: false, message: "An account with that email already exists." });
    const inserted = await db.execute({
      sql: "INSERT INTO users (name, email, password_hash, restaurant_name, role, phone) VALUES (?, ?, ?, ?, ?, ?) RETURNING id, name, email, phone, restaurant_name, role",
      args: [name, email, hashPassword(password), restaurantName, role, phone],
    });
    const token = makeToken();
    await db.execute({ sql: "INSERT INTO sessions (token, user_id) VALUES (?, ?)", args: [token, inserted.rows[0].id] });
    const user = { ...publicUser(inserted.rows[0]), token };
    return res.status(201).json({ ok: true, user, token });
  } catch (error) {
    if (String(error.message || "").includes("UNIQUE")) {
      return res.status(409).json({ ok: false, message: "An account with that email already exists." });
    }
    console.error(error);
    return res.status(500).json({ ok: false, message: "Could not create the account. Please try again." });
  }
};
