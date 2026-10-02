const app = require("./index");

function run(req, res, path) {
  const raw = req.url || "";
  const query = raw.includes("?") ? raw.slice(raw.indexOf("?")) : "";
  req.url = `${path}${query}`;
  if (req.method === "OPTIONS") {
    res.setHeader("Access-Control-Allow-Origin", "*");
    res.setHeader("Access-Control-Allow-Methods", "GET,POST,PUT,PATCH,DELETE,OPTIONS");
    res.setHeader("Access-Control-Allow-Headers", "Content-Type, Authorization");
    return res.status(204).end();
  }
  return app(req, res);
}

module.exports = { run };
