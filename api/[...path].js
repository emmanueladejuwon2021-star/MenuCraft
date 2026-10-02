const app = require("./index");

function withApiPrefix(req) {
  const raw = req.url || "/";
  const queryIndex = raw.indexOf("?");
  const path = queryIndex === -1 ? raw : raw.slice(0, queryIndex);
  const query = queryIndex === -1 ? "" : raw.slice(queryIndex);
  if (path === "/api" || path.startsWith("/api/")) return;
  const next = path.startsWith("/") ? path : `/${path}`;
  req.url = `/api${next}${query}`;
}

module.exports = (req, res) => {
  withApiPrefix(req);
  if (req.method === "OPTIONS") {
    res.setHeader("Access-Control-Allow-Origin", "*");
    res.setHeader("Access-Control-Allow-Methods", "GET,POST,PUT,PATCH,DELETE,OPTIONS");
    res.setHeader("Access-Control-Allow-Headers", "Content-Type, Authorization");
    return res.status(204).end();
  }
  return app(req, res);
};
