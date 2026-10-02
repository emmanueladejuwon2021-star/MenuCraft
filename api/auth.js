const crypto = require("crypto");

function hashPassword(password) {
  const salt = crypto.randomBytes(16).toString("hex");
  const digest = crypto.pbkdf2Sync(String(password), salt, 120000, 32, "sha256").toString("hex");
  return `pbkdf2$${salt}$${digest}`;
}

function checkPassword(password, stored) {
  const value = String(stored || "");
  const parts = value.split("$");
  if (parts[0] === "pbkdf2" && parts.length === 3) {
    const digest = crypto.pbkdf2Sync(String(password), parts[1], 120000, 32, "sha256").toString("hex");
    try {
      return crypto.timingSafeEqual(Buffer.from(digest, "hex"), Buffer.from(parts[2], "hex"));
    } catch {
      return false;
    }
  }
  return false;
}

function makeToken() {
  return crypto.randomBytes(32).toString("hex");
}

function publicUser(row) {
  return {
    id: Number(row.id),
    name: row.name,
    email: row.email,
    phone: row.phone || "",
    restaurant_name: row.restaurant_name || "",
    role: row.role === "guest" ? "guest" : "staff",
  };
}

function readToken(req) {
  const header = String(req.headers.authorization || "");
  if (header.toLowerCase().startsWith("bearer ")) return header.slice(7).trim();
  return String(req.headers["x-menucraft-token"] || "").trim();
}

const SESSION_DAYS = 14;

async function findUserByToken(db, token) {
  if (!token) return null;
  const session = await db.execute({
    sql: "SELECT user_id, created_at FROM sessions WHERE token = ?",
    args: [token],
  });
  if (!session.rows.length) return null;
  const created = new Date(session.rows[0].created_at || 0).getTime();
  if (!Number.isFinite(created) || Date.now() - created > SESSION_DAYS * 24 * 60 * 60 * 1000) {
    await db.execute({ sql: "DELETE FROM sessions WHERE token = ?", args: [token] });
    return null;
  }
  const user = await db.execute({
    sql: "SELECT id, name, email, phone, restaurant_name, role FROM users WHERE id = ?",
    args: [session.rows[0].user_id],
  });
  return user.rows[0] ? publicUser(user.rows[0]) : null;
}

module.exports = { hashPassword, checkPassword, makeToken, publicUser, readToken, findUserByToken };
