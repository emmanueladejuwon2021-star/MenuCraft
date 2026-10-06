const hits = new Map();

function applyCors(req, res) {
  const origin = String(req.headers.origin || "");
  let host = "";
  try {
    host = origin ? new URL(origin).hostname : "";
  } catch {
    host = "";
  }
  const allowed =
    !origin ||
    host === "localhost" ||
    host === "127.0.0.1" ||
    host.endsWith(".vercel.app") ||
    host.endsWith(".github.io");
  if (allowed && origin) {
    res.setHeader("Access-Control-Allow-Origin", origin);
    res.setHeader("Vary", "Origin");
  }
  res.setHeader("Access-Control-Allow-Methods", "GET,POST,PUT,PATCH,DELETE,OPTIONS");
  res.setHeader("Access-Control-Allow-Headers", "Content-Type, Authorization");
}

function tooMany(key, limit = 8, windowMs = 10 * 60 * 1000) {
  const now = Date.now();
  const row = hits.get(key) || { n: 0, start: now };
  if (now - row.start > windowMs) {
    hits.set(key, { n: 1, start: now });
    return false;
  }
  row.n += 1;
  hits.set(key, row);
  return row.n > limit;
}

function clearHits(key) {
  hits.delete(key);
}

function clientKey(req) {
  const forwarded = String(req.headers["x-forwarded-for"] || "").split(",")[0].trim();
  return forwarded || req.socket?.remoteAddress || "unknown";
}

function cleanImageUrl(value) {
  const raw = String(value || "").trim();
  if (!raw) return "";
  if (raw.length > 500) return "";
  try {
    const url = new URL(raw);
    if (url.protocol !== "https:" && url.protocol !== "http:") return "";
    return url.toString();
  } catch {
    return "";
  }
}

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

module.exports = { applyCors, tooMany, clearHits, clientKey, cleanImageUrl, readBody };
