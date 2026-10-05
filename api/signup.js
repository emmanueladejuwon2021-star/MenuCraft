const { readyDb } = require("./db");
const { readToken } = require("./auth");
const { registerUser } = require("./accounts");
const { applyCors, readBody } = require("./http");

module.exports = async (req, res) => {
  applyCors(req, res);
  if (req.method === "OPTIONS") return res.status(204).end();
  if (req.method !== "POST") return res.status(405).json({ ok: false, message: "Please use the form to create an account." });
  try {
    const db = await readyDb();
    if (!db) return res.status(503).json({ ok: false, message: "The shared menu is not connected yet. Add the database details in Vercel, then try again." });
    const result = await registerUser(db, readBody(req), readToken(req));
    if (result.message) return res.status(result.status).json({ ok: false, message: result.message });
    return res.status(result.status).json(result.body);
  } catch (error) {
    console.error(error);
    return res.status(500).json({ ok: false, message: "Could not create the account. Please try again." });
  }
};
