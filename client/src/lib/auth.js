import { request, shouldUseLocal } from "./api";

const ACCOUNTS_KEY = "menucraft-accounts-v1";
const SESSION_KEY = "menucraft-session-v1";

function hashSecret(email, password) {
  const raw = `${email.trim().toLowerCase()}::${password}`;
  try {
    return btoa(unescape(encodeURIComponent(raw)));
  } catch {
    return raw;
  }
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
    restaurant_name: account.restaurant_name,
  };
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
    const payload = await request("/api/signup", {
      method: "POST",
      body: JSON.stringify({ name: cleanName, email: cleanEmail, password, restaurant_name: cleanRestaurant }),
    });
    return writeSession(payload.user);
  } catch (error) {
    if (!shouldUseLocal(error)) throw error;
    const accounts = readAccounts();
    if (accounts.some((row) => row.email === cleanEmail)) {
      throw new Error("An account with that email already exists.");
    }
    const account = {
      id: Date.now(),
      name: cleanName,
      email: cleanEmail,
      restaurant_name: cleanRestaurant,
      password_hash: hashSecret(cleanEmail, password),
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
    return writeSession(payload.user);
  } catch (error) {
    if (!shouldUseLocal(error)) throw error;
    const account = readAccounts().find((row) => row.email === cleanEmail);
    if (!account || account.password_hash !== hashSecret(cleanEmail, password)) {
      throw new Error("Email or password is not correct.");
    }
    return writeSession(publicUser(account));
  }
}

export async function updateAccount(updates) {
  const session = readSession();
  if (!session) throw new Error("Please sign in first.");
  const next = {
    ...session,
    name: String(updates.name || session.name).trim(),
    restaurant_name: String(updates.restaurant_name || session.restaurant_name).trim(),
  };
  if (!next.name) throw new Error("Please enter your name.");
  if (!next.restaurant_name) throw new Error("Please enter your restaurant name.");
  try {
    const payload = await request("/api/account", {
      method: "PUT",
      body: JSON.stringify({ ...next, email: session.email }),
    });
    return writeSession(payload.user || next);
  } catch (error) {
    if (!shouldUseLocal(error)) throw error;
    const accounts = readAccounts().map((row) =>
      row.email === session.email ? { ...row, name: next.name, restaurant_name: next.restaurant_name } : row
    );
    writeAccounts(accounts);
    return writeSession(next);
  }
}

export function signOut() {
  writeSession(null);
}
