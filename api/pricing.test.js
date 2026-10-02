const test = require("node:test");
const assert = require("node:assert/strict");
const { applyPrice } = require("./pricing");

test("percent change rounds to cents and cannot go below zero", () => {
  assert.equal(applyPrice(1000, "percent", 10), 1100);
  assert.equal(applyPrice(1999, "percent", -50), 999.5);
  assert.equal(applyPrice(100, "percent", -200), 0);
});

test("fixed amount change floors at zero", () => {
  assert.equal(applyPrice(2500, "amount", 150), 2650);
  assert.equal(applyPrice(200, "amount", -500), 0);
});

test("bad input does not produce NaN", () => {
  assert.equal(applyPrice("nope", "percent", 10), 0);
  assert.equal(applyPrice(500, "percent", "nope"), 0);
});
