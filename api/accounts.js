const { hashPassword, checkPassword, makeToken, publicUser, readToken, findUserByToken } = require("./auth");
const { tooMany, clientKey } = require("./http");

async function claimFirstStaff(db) {
  const staff = await db.execute("SELECT COUNT(*) AS n FROM users WHERE role = 'staff'");
  if (Number(staff.rows[0].n) > 0) return false;
  const claimed = await db.execute("SELECT user_id FROM staff_claim WHERE id = 1");
  if (claimed.rows.length && Number(claimed.rows[0].user_id) > 0) return false;
  try {
    await db.execute("INSERT INTO staff_claim (id, user_id) VALUES (1, 0)");
    return true;
  } catch {
    return false;
  }
}

async function registerUser(db, body, token) {
  const name = String(body.name || "").trim();
  const email = String(body.email || "").trim().toLowerCase();
  const password = String(body.password || "");
  const restaurantName = String(body.restaurant_name || "").trim() || "Guest";
  const phone = String(body.phone || "").trim();
  if (!name) return { status: 400, message: "Please enter your name." };
  if (!email.includes("@") || email.length > 120) return { status: 400, message: "Please enter a valid email." };
  if (password.length < 6 || password.length > 200) return { status: 400, message: "Password should be at least 6 characters." };

  const existing = await db.execute({ sql: "SELECT id FROM users WHERE email = ?", args: [email] });
  if (existing.rows.length) return { status: 409, message: "An account with that email already exists." };

  let role = "guest";
  let claimed = false;
  if (body.role === "staff") {
    const caller = await findUserByToken(db, token);
    if (caller?.role === "staff") {
      role = "staff";
    } else {
      claimed = await claimFirstStaff(db);
      if (!claimed) {
        return { status: 401, message: "A kitchen account already exists. Sign in, or ask staff to create yours." };
      }
      role = "staff";
    }
  }

  try {
    const inserted = await db.execute({
      sql: "INSERT INTO users (name, email, password_hash, restaurant_name, role, phone) VALUES (?, ?, ?, ?, ?, ?) RETURNING id, name, email, phone, restaurant_name, role",
      args: [name, email, hashPassword(password), role === "guest" ? "Guest" : restaurantName, role, phone],
    });
    if (claimed) {
      await db.execute({ sql: "UPDATE staff_claim SET user_id = ? WHERE id = 1", args: [inserted.rows[0].id] });
    }
    const sessionToken = makeToken();
    await db.execute({ sql: "INSERT INTO sessions (token, user_id) VALUES (?, ?)", args: [sessionToken, inserted.rows[0].id] });
    const user = { ...publicUser(inserted.rows[0]), token: sessionToken };
    return { status: 201, body: { ok: true, user, token: sessionToken } };
  } catch (error) {
    if (claimed) {
      await db.execute("DELETE FROM staff_claim WHERE id = 1 AND user_id = 0");
    }
    if (String(error.message || "").includes("UNIQUE")) {
      return { status: 409, message: "An account with that email already exists." };
    }
    throw error;
  }
}

async function loginUser(db, body, req) {
  if (tooMany(`login:${clientKey(req)}`)) {
    return { status: 429, message: "Too many sign-in tries. Wait a few minutes and try again." };
  }
  const email = String(body.email || "").trim().toLowerCase();
  const password = String(body.password || "");
  if (!email || !password) return { status: 400, message: "Please enter your email and password." };
  const found = await db.execute({
    sql: "SELECT id, name, email, phone, restaurant_name, role, password_hash FROM users WHERE email = ?",
    args: [email],
  });
  if (!found.rows.length || !checkPassword(password, found.rows[0].password_hash)) {
    return { status: 401, message: "Email or password is not correct." };
  }
  const token = makeToken();
  await db.execute({ sql: "INSERT INTO sessions (token, user_id) VALUES (?, ?)", args: [token, found.rows[0].id] });
  const user = { ...publicUser(found.rows[0]), token };
  return { status: 200, body: { ok: true, user, token } };
}

module.exports = { registerUser, loginUser, readToken };
