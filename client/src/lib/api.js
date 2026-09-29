export const API_BASE = (import.meta.env.VITE_API_URL || "").replace(/\/$/, "") || "";

export function remoteApiOn() {
  return Boolean(API_BASE);
}

export function shouldUseLocal(error) {
  const status = Number(error?.status || 0);
  return status === 0 || status === 404 || status >= 500;
}

export async function request(path, options = {}) {
  if (!API_BASE) {
    const error = new Error("offline");
    error.status = 0;
    throw error;
  }
  try {
    const response = await fetch(`${API_BASE}${path}`, {
      credentials: "include",
      headers: { "Content-Type": "application/json", ...(options.headers || {}) },
      ...options,
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
