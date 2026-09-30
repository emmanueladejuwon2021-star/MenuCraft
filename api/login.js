const { readyDb } = require("./db");
const { checkPassword, makeToken, publicUser } = require("./auth");

module.exports = async (req, res) => {
  res.setHeader("Access-Control-Allow-Origin", "*");
  res.setHeader("Access-Control-Allow-Methods", "POST,OPTIONS");
  res.setHeader("Access-Control-Allow-Headers", "Content-Type, Authorization");
  if (req.method === "OPTIONS") return res.status(200).end();
  if (req.method !== "POST") return res.status(405).json({ ok: false, message: "Please use the form to sign in." });

  const email = String(req.body?.email || "").trim().toLowerCase();
  const password = String(req.body?.password || "");
  if (!email || !password) return res.status(400).json({ ok: false, message: "Please enter your email and password." });

  try {
    const db = await readyDb();
    if (!db) return res.status(503).json({ ok: false, message: "The shared menu is not connected yet." });
    const found = await db.execute({
      sql: "SELECT id, name, email, phone, restaurant_name, role, password_hash FROM users WHERE email = ?",
      args: [email],
    });
    if (!found.rows.length || !checkPassword(password, found.rows[0].password_hash)) {
      return res.status(401).json({ ok: false, message: "Email or password is not correct." });
    }
    const token = makeToken();
    await db.execute({ sql: "INSERT INTO sessions (token, user_id) VALUES (?, ?)", args: [token, found.rows[0].id] });
    const user = { ...publicUser(found.rows[0]), token };
    return res.status(200).json({ ok: true, user, token });
  } catch (error) {
    console.error(error);
    return res.status(500).json({ ok: false, message: "Could not sign in. Please try again." });
  }
};
