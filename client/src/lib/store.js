import { STARTER_DATA } from "./seed";
import { request, shouldUseLocal } from "./api";
import { applyPrice } from "./pricing";

const STORAGE_KEY = "menucraft-local-menu-v3";

function clone(value) {
  return JSON.parse(JSON.stringify(value));
}

function readLocal() {
  try {
    const raw = localStorage.getItem(STORAGE_KEY);
    if (!raw) return clone(STARTER_DATA);
    const parsed = JSON.parse(raw);
    if (!parsed.categories || !parsed.items || !parsed.settings) return clone(STARTER_DATA);
    return parsed;
  } catch {
    return clone(STARTER_DATA);
  }
}

function writeLocal(data) {
  localStorage.setItem(STORAGE_KEY, JSON.stringify(data));
  return data;
}

function nextId(rows) {
  return rows.reduce((max, row) => Math.max(max, Number(row.id) || 0), 0) + 1;
}

function normalizeItem(item) {
  return {
    ...item,
    id: Number(item.id),
    category_id: Number(item.category_id),
    price: Number(item.price),
    is_available: Boolean(item.is_available),
    tags: Array.isArray(item.tags)
      ? item.tags
      : String(item.tags || "")
          .split(",")
          .map((tag) => tag.trim())
          .filter(Boolean),
  };
}

export async function loadMenu() {
  try {
    const payload = await request("/api/menu");
    return {
      source: "live",
      settings: payload.settings,
      categories: payload.categories,
      items: payload.items.map(normalizeItem),
    };
  } catch {
    const local = readLocal();
    return {
      source: "local",
      settings: local.settings,
      categories: local.categories,
      items: local.items.map(normalizeItem),
    };
  }
}

export async function createCategory(name) {
  try {
    const payload = await request("/api/categories", {
      method: "POST",
      body: JSON.stringify({ name }),
    });
    return { source: "live", category: payload.category, message: payload.message || "Added to category" };
  } catch (error) {
    if (!shouldUseLocal(error)) throw error;
    const data = readLocal();
    if (data.categories.some((row) => row.name.toLowerCase() === name.toLowerCase())) {
      throw new Error("That category already exists.");
    }
    const category = { id: nextId(data.categories), name, display_order: data.categories.length + 1 };
    data.categories.push(category);
    writeLocal(data);
    return { source: "local", category, message: "Added to category" };
  }
}

export async function removeCategory(id) {
  try {
    const payload = await request(`/api/categories/${id}`, { method: "DELETE" });
    return { source: "live", message: payload.message || "Category removed" };
  } catch (error) {
    if (!shouldUseLocal(error)) throw error;
    const data = readLocal();
    data.categories = data.categories.filter((row) => Number(row.id) !== Number(id));
    data.items = data.items.filter((row) => Number(row.category_id) !== Number(id));
    writeLocal(data);
    return { source: "local", message: "Category removed" };
  }
}

export async function createItem(input) {
  try {
    const payload = await request("/api/items", {
      method: "POST",
      body: JSON.stringify(input),
    });
    return { source: "live", item: normalizeItem(payload.item), message: payload.message || "Added to category" };
  } catch (error) {
    if (!shouldUseLocal(error)) throw error;
    const data = readLocal();
    const item = normalizeItem({
      ...input,
      id: nextId(data.items),
      is_available: input.is_available !== false,
      tags: input.tags || [],
    });
    data.items.push(item);
    writeLocal(data);
    return { source: "local", item, message: "Added to category" };
  }
}

export async function updateItem(id, input) {
  try {
    const payload = await request(`/api/items/${id}`, {
      method: "PUT",
      body: JSON.stringify(input),
    });
    return { source: "live", item: normalizeItem(payload.item), message: payload.message || "Saved!" };
  } catch (error) {
    if (!shouldUseLocal(error)) throw error;
    const data = readLocal();
    const index = data.items.findIndex((row) => Number(row.id) === Number(id));
    if (index < 0) throw new Error("That dish could not be found.");
    data.items[index] = normalizeItem({ ...data.items[index], ...input, id });
    writeLocal(data);
    return { source: "local", item: data.items[index], message: "Saved!" };
  }
}

export async function setItemStatus(id, isAvailable) {
  try {
    const payload = await request(`/api/items/${id}/status`, {
      method: "PATCH",
      body: JSON.stringify({ is_available: isAvailable }),
    });
    return { source: "live", item: normalizeItem(payload.item), message: payload.message };
  } catch (error) {
    if (!shouldUseLocal(error)) throw error;
    const data = readLocal();
    const index = data.items.findIndex((row) => Number(row.id) === Number(id));
    if (index < 0) throw new Error("That dish could not be found.");
    data.items[index].is_available = Boolean(isAvailable);
    writeLocal(data);
    return {
      source: "local",
      item: normalizeItem(data.items[index]),
      message: isAvailable ? "Marked as in stock" : "Item hidden from menu",
    };
  }
}

export async function bulkUpdatePrices({ categoryId, mode, amount }) {
  try {
    const payload = await request("/api/items/bulk-price-update", {
      method: "POST",
      body: JSON.stringify({ category_id: categoryId, mode, amount }),
    });
    return {
      source: "live",
      items: payload.items.map(normalizeItem),
      message: payload.message || "Price updated successfully",
    };
  } catch (error) {
    if (!shouldUseLocal(error)) throw error;
    const data = readLocal();
    data.items = data.items.map((item) => {
      if (Number(item.category_id) !== Number(categoryId)) return item;
      return { ...item, price: applyPrice(item.price, mode, amount) };
    });
    writeLocal(data);
    return {
      source: "local",
      items: data.items.filter((item) => Number(item.category_id) === Number(categoryId)).map(normalizeItem),
      message: "Price updated successfully",
    };
  }
}

export async function removeItem(id) {
  try {
    const payload = await request(`/api/items/${id}`, { method: "DELETE" });
    return { source: "live", message: payload.message || "Dish removed" };
  } catch (error) {
    if (!shouldUseLocal(error)) throw error;
    const data = readLocal();
    data.items = data.items.filter((row) => Number(row.id) !== Number(id));
    writeLocal(data);
    return { source: "local", message: "Dish removed" };
  }
}

export async function saveSettings(settings) {
  try {
    const payload = await request("/api/settings", {
      method: "PUT",
      body: JSON.stringify(settings),
    });
    return { source: "live", settings: payload.settings, message: payload.message || "Saved!" };
  } catch (error) {
    if (!shouldUseLocal(error)) throw error;
    const data = readLocal();
    data.settings = {
      restaurant_name: settings.restaurant_name,
      currency_symbol: settings.currency_symbol || "₦",
    };
    writeLocal(data);
    return { source: "local", settings: data.settings, message: "Saved!" };
  }
}

export function money(symbol, value) {
  const amount = Number(value || 0).toLocaleString("en-NG", {
    minimumFractionDigits: Number(value) % 1 === 0 ? 0 : 2,
    maximumFractionDigits: 2,
  });
  return `${symbol}${amount}`;
}
