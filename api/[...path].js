const app = require("./index");
const { applyCors } = require("./http");

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
  applyCors(req, res);
  if (req.method === "OPTIONS") return res.status(204).end();
  return app(req, res);
};
