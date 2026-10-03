const { readyDb } = require("./db");
const { applyCors } = require("./http");
const { readToken } = require("./auth");

module.exports = async (req, res) => {
  applyCors(req, res);
  if (req.method === "OPTIONS") return res.status(204).end();
  if (req.method !== "POST") return res.status(405).json({ ok: false, message: "Use sign out to leave." });

  const token = readToken(req);
  try {
    const db = await readyDb();
    if (db && token) {
      await db.execute({ sql: "DELETE FROM sessions WHERE token = ?", args: [token] });
    }
    return res.status(200).json({ ok: true });
  } catch (error) {
    console.error(error);
    return res.status(200).json({ ok: true });
  }
};
