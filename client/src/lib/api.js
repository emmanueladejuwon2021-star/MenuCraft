export const ENV_API = (import.meta.env.VITE_API_URL || "").replace(/\/$/, "");
const SESSION_KEY = "menucraft-session-v1";

function onGitHubPages() {
  return typeof window !== "undefined" && window.location.hostname.endsWith("github.io");
}

export function apiBase() {
  if (ENV_API) return ENV_API;
  return "";
}

export function remoteApiOn() {
  return Boolean(ENV_API) || (typeof window !== "undefined" && !onGitHubPages());
}

export function shouldUseLocal(error) {
  const status = Number(error?.status || 0);
  return status === 0 || status === 503;
}

function sessionToken() {
  try {
    const session = JSON.parse(localStorage.getItem(SESSION_KEY) || "null");
    return session?.token || "";
  } catch {
    return "";
  }
}

export async function request(path, options = {}) {
  if (onGitHubPages() && !ENV_API) {
    const error = new Error("offline");
    error.status = 0;
    throw error;
  }
  try {
    const headers = { "Content-Type": "application/json", ...(options.headers || {}) };
    const token = sessionToken();
    if (token) headers.Authorization = `Bearer ${token}`;
    const response = await fetch(`${apiBase()}${path}`, {
      ...options,
      headers,
    });
    let payload = null;
    try {
      payload = await response.json();
    } catch {
      payload = null;
    }
    if (!response.ok) {
      const error = new Error(payload?.message || "Could not complete that action. Please try again.");
      error.status = response.status;
      throw error;
    }
    return payload;
  } catch (error) {
    if (error.status) throw error;
    const offline = new Error("offline");
    offline.status = 0;
    throw offline;
  }
}
