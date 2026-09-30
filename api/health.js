const { readyDb } = require("./db");

module.exports = async (_req, res) => {
  res.setHeader("Access-Control-Allow-Origin", "*");
  const db = await readyDb();
  res.status(200).json({ ok: true, connected: Boolean(db) });
};
