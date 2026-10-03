import { request, shouldUseLocal } from "./api";

const PLATE_KEY = "menucraft-plate-v1";
const ORDERS_KEY = "menucraft-orders-v1";
const TICKETS_KEY = "menucraft-tickets-v1";

function readJson(key, fallback) {
  try {
    return JSON.parse(localStorage.getItem(key) || JSON.stringify(fallback));
  } catch {
    return fallback;
  }
}

export function readPlate() {
  return readJson(PLATE_KEY, []);
}

export function writePlate(items) {
  localStorage.setItem(PLATE_KEY, JSON.stringify(items));
  return items;
}

export function addToPlate(dish) {
  const plate = readPlate();
  const found = plate.find((row) => Number(row.id) === Number(dish.id));
  if (found) found.qty += 1;
  else {
    plate.push({
      id: dish.id,
      name: dish.name,
      price: Number(dish.price),
      image_url: dish.image_url || "",
      qty: 1,
    });
  }
  return writePlate(plate);
}

export function setPlateQty(id, qty) {
  const next = readPlate()
    .map((row) => (Number(row.id) === Number(id) ? { ...row, qty } : row))
    .filter((row) => row.qty > 0);
  return writePlate(next);
}

export function clearPlate() {
  return writePlate([]);
}

export function plateTotal(items = readPlate()) {
  return items.reduce((sum, row) => sum + Number(row.price) * Number(row.qty), 0);
}

export function plateCount(items = readPlate()) {
  return items.reduce((sum, row) => sum + Number(row.qty), 0);
}

export function readOrders() {
  return readJson(ORDERS_KEY, []);
}

function writeOrders(orders) {
  localStorage.setItem(ORDERS_KEY, JSON.stringify(orders));
  return orders;
}

function rememberTicket(order) {
  if (!order?.view_token) return;
  const tickets = readJson(TICKETS_KEY, []).filter((token) => token !== order.view_token);
  tickets.unshift(order.view_token);
  localStorage.setItem(TICKETS_KEY, JSON.stringify(tickets.slice(0, 20)));
}

export async function loadOrders() {
  try {
    const tickets = readJson(TICKETS_KEY, []).slice(0, 20).join(",");
    const path = tickets ? `/api/orders?tickets=${encodeURIComponent(tickets)}` : "/api/orders";
    const payload = await request(path);
    return payload.orders || [];
  } catch (error) {
    if (!shouldUseLocal(error)) throw error;
    return readOrders();
  }
}

export async function placeOrder({ guest, items, note }) {
  try {
    const payload = await request("/api/orders", {
      method: "POST",
      body: JSON.stringify({ items, note, guest }),
    });
    clearPlate();
    rememberTicket(payload.order);
    return payload.order;
  } catch (error) {
    if (!shouldUseLocal(error)) throw error;
    const order = {
      id: Date.now(),
      guest_name: guest.name,
      guest_email: guest.email,
      phone: guest.phone || "",
      note: note || "",
      items: items.map((row) => ({ ...row })),
      total: plateTotal(items),
      paid: false,
      pay_ref: `COUNTER-${Date.now().toString().slice(-8)}`,
      status: "New",
      created_at: new Date().toISOString(),
    };
    writeOrders([order, ...readOrders()]);
    clearPlate();
    return order;
  }
}

export function myOrders(email, orders = readOrders()) {
  return orders.filter((row) => row.guest_email === email);
}

export async function setOrderStatus(id, status) {
  try {
    const payload = await request("/api/order-status", {
      method: "POST",
      body: JSON.stringify({ id, status }),
    });
    return payload.order;
  } catch (error) {
    if (!shouldUseLocal(error)) throw error;
    const orders = readOrders().map((row) => (Number(row.id) === Number(id) ? { ...row, status } : row));
    writeOrders(orders);
    return orders.find((row) => Number(row.id) === Number(id));
  }
}
