const test = require("node:test");
const assert = require("node:assert/strict");
const { buildOrderLines, visibleOrders } = require("./order-build");

const menu = [
  { id: 1, name: "Jollof", price: 3200, is_available: 1, image_url: "" },
  { id: 2, name: "Zobo", price: 700, is_available: 0, image_url: "" },
];

test("uses the menu price, not the price sent by the browser", () => {
  const built = buildOrderLines([{ id: 1, price: 1, qty: 2 }], menu);
  assert.equal(built.lines[0].price, 3200);
  assert.equal(built.total, 6400);
});

test("rejects a sold-out dish", () => {
  assert.throws(() => buildOrderLines([{ id: 2, qty: 1 }], menu), /sold out/);
});

test("rejects an unknown dish and a blank plate", () => {
  assert.throws(() => buildOrderLines([{ id: 9, qty: 1 }], menu), /not on the menu/);
  assert.throws(() => buildOrderLines([], menu), /empty/);
});

test("rejects a quantity outside 1 to 20", () => {
  assert.throws(() => buildOrderLines([{ id: 1, qty: 21 }], menu), /1 to 20/);
});

test("does not show blank-email orders to a stranger", () => {
  const orders = [
    { id: 1, guest_email: "", view_token: "ticket-a" },
    { id: 2, guest_email: "ada@example.com", view_token: "ticket-b" },
  ];
  assert.deepEqual(visibleOrders(orders, null, "").map((row) => row.id), []);
  assert.deepEqual(visibleOrders(orders, null, "ticket-a").map((row) => row.id), [1]);
  assert.deepEqual(visibleOrders(orders, { role: "guest", email: "ada@example.com" }, "").map((row) => row.id), [2]);
  assert.equal(visibleOrders(orders, { role: "staff" }, "").length, 2);
});
