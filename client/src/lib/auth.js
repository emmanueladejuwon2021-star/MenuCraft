import { request, shouldUseLocal } from "./api";

const ACCOUNTS_KEY = "menucraft-accounts-v1";
const SESSION_KEY = "menucraft-session-v1";

function legacySecret(email, password) {
  const raw = `${email.trim().toLowerCase()}::${password}`;
  try {
    return btoa(unescape(encodeURIComponent(raw)));
  } catch {
    return raw;
  }
}

async function hashSecret(email, password) {
  const raw = `${email.trim().toLowerCase()}::${password}`;
  const bytes = new TextEncoder().encode(raw);
  const digest = await crypto.subtle.digest("SHA-256", bytes);
  const hex = Array.from(new Uint8Array(digest)).map((part) => part.toString(16).padStart(2, "0")).join("");
  return `sha256:${hex}`;
}

async function passwordMatches(account, email, password) {
  const next = await hashSecret(email, password);
  if (account.password_hash === next) return next;
  if (account.password_hash === legacySecret(email, password)) return next;
  return "";
}

function readAccounts() {
  try {
    return JSON.parse(localStorage.getItem(ACCOUNTS_KEY) || "[]");
  } catch {
    return [];
  }
}

function writeAccounts(accounts) {
  localStorage.setItem(ACCOUNTS_KEY, JSON.stringify(accounts));
}

export function readSession() {
  try {
    return JSON.parse(localStorage.getItem(SESSION_KEY) || "null");
  } catch {
    return null;
  }
}

function writeSession(session) {
  if (!session) localStorage.removeItem(SESSION_KEY);
  else localStorage.setItem(SESSION_KEY, JSON.stringify(session));
  return session;
}

function publicUser(account) {
  return {
    id: account.id,
    name: account.name,
    email: account.email,
    phone: account.phone || "",
    restaurant_name: account.restaurant_name || "",
    role: account.role === "guest" ? "guest" : "staff",
    token: account.token || "",
  };
}

export function isStaff(user) {
  return user?.role === "staff";
}

export function isGuest(user) {
  return user?.role === "guest";
}

function keepToken(session, user) {
  return {
    ...session,
    ...user,
    token: user?.token || session?.token || "",
    role: user?.role || session?.role || "guest",
  };
}

async function saveRemote(path, body) {
  const payload = await request(path, { method: "POST", body: JSON.stringify(body) });
  const user = payload.user || {};
  return writeSession(keepToken(user, { ...user, token: user.token || payload.token, role: user.role || body.role || "staff" }));
}

export async function createAccount({ name, email, password, restaurant_name }) {
  const cleanName = String(name || "").trim();
  const cleanEmail = String(email || "").trim().toLowerCase();
  const cleanRestaurant = String(restaurant_name || "").trim();
  if (!cleanName) throw new Error("Please enter your name.");
  if (!cleanEmail || !cleanEmail.includes("@")) throw new Error("Please enter a valid email.");
  if (String(password || "").length < 6) throw new Error("Password should be at least 6 characters.");
  if (!cleanRestaurant) throw new Error("Please enter your restaurant name.");
  try {
    return await saveRemote("/api/signup", {
      name: cleanName,
      email: cleanEmail,
      password,
      restaurant_name: cleanRestaurant,
      role: "staff",
    });
  } catch (error) {
    if (!shouldUseLocal(error)) throw error;
    const accounts = readAccounts();
    if (accounts.some((row) => row.email === cleanEmail)) throw new Error("An account with that email already exists.");
    const account = {
      id: Date.now(),
      name: cleanName,
      email: cleanEmail,
      restaurant_name: cleanRestaurant,
      role: "staff",
      password_hash: await hashSecret(cleanEmail, password),
    };
    accounts.push(account);
    writeAccounts(accounts);
    return writeSession(publicUser(account));
  }
}

export async function createGuestAccount({ name, email, password, phone }) {
  const cleanName = String(name || "").trim();
  const cleanEmail = String(email || "").trim().toLowerCase();
  const cleanPhone = String(phone || "").trim();
  if (!cleanName) throw new Error("Please enter your name.");
  if (!cleanEmail || !cleanEmail.includes("@")) throw new Error("Please enter a valid email.");
  if (String(password || "").length < 6) throw new Error("Password should be at least 6 characters.");
  try {
    return await saveRemote("/api/signup", {
      name: cleanName,
      email: cleanEmail,
      password,
      restaurant_name: "Guest",
      phone: cleanPhone,
      role: "guest",
    });
  } catch (error) {
    if (!shouldUseLocal(error)) throw error;
    const accounts = readAccounts();
    if (accounts.some((row) => row.email === cleanEmail)) throw new Error("An account with that email already exists.");
    const account = {
      id: Date.now(),
      name: cleanName,
      email: cleanEmail,
      phone: cleanPhone,
      restaurant_name: "",
      role: "guest",
      password_hash: await hashSecret(cleanEmail, password),
    };
    accounts.push(account);
    writeAccounts(accounts);
    return writeSession(publicUser(account));
  }
}

export async function signIn({ email, password }) {
  const cleanEmail = String(email || "").trim().toLowerCase();
  if (!cleanEmail || !password) throw new Error("Please enter your email and password.");
  try {
    const payload = await request("/api/login", {
      method: "POST",
      body: JSON.stringify({ email: cleanEmail, password }),
    });
    const user = payload.user || {};
    const role = user.role === "guest" ? "guest" : "staff";
    return writeSession(keepToken(user, { ...user, token: user.token || payload.token, role }));
  } catch (error) {
    if (!shouldUseLocal(error)) throw error;
    const accounts = readAccounts();
    const account = accounts.find((row) => row.email === cleanEmail);
    const nextHash = account ? await passwordMatches(account, cleanEmail, password) : "";
    if (!account || !nextHash) throw new Error("Email or password is not correct.");
    if (account.password_hash !== nextHash) {
      account.password_hash = nextHash;
      writeAccounts(accounts);
    }
    return writeSession(publicUser(account));
  }
}

export async function updateAccount(updates) {
  const session = readSession();
  if (!session?.token && !session?.email) throw new Error("Please sign in first.");
  const next = {
    ...session,
    name: String(updates.name || session.name).trim(),
    phone: String(updates.phone ?? session.phone ?? "").trim(),
    restaurant_name: String(updates.restaurant_name || session.restaurant_name || "").trim(),
  };
  if (!next.name) throw new Error("Please enter your name.");
  try {
    const payload = await request("/api/account", {
      method: "PUT",
      body: JSON.stringify({ name: next.name, phone: next.phone, restaurant_name: next.restaurant_name }),
    });
    return writeSession(keepToken(session, { ...(payload.user || next), token: session.token, role: session.role }));
  } catch (error) {
    if (!shouldUseLocal(error)) throw error;
    const accounts = readAccounts().map((row) =>
      row.email === session.email ? { ...row, name: next.name, phone: next.phone, restaurant_name: next.restaurant_name } : row
    );
    writeAccounts(accounts);
    return writeSession(next);
  }
}

export async function signOut() {
  const session = readSession();
  writeSession(null);
  if (!session?.token) return;
  try {
    await fetch("/api/logout", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        Authorization: `Bearer ${session.token}`,
      },
    });
  } catch {
    /* the browser session is already cleared */
  }
}
