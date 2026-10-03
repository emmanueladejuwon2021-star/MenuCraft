const { readyDb } = require("./db");
const { loginUser } = require("./accounts");
const { applyCors, readBody } = require("./http");

module.exports = async (req, res) => {
  applyCors(req, res);
  if (req.method === "OPTIONS") return res.status(204).end();
  if (req.method !== "POST") return res.status(405).json({ ok: false, message: "Please use the form to sign in." });
  try {
    const db = await readyDb();
    if (!db) return res.status(503).json({ ok: false, message: "Turso is not connected on this Vercel project yet. Add TURSO_DATABASE_URL and TURSO_AUTH_TOKEN." });
    const result = await loginUser(db, readBody(req), req);
    if (result.message) return res.status(result.status).json({ ok: false, message: result.message });
    return res.status(result.status).json(result.body);
  } catch (error) {
    console.error(error);
    return res.status(500).json({ ok: false, message: "Could not sign in. Please try again." });
  }
};
