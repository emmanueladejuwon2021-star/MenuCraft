function applyPrice(price, mode, amount) {
  const current = Number(price);
  const delta = Number(amount);
  if (!Number.isFinite(current) || !Number.isFinite(delta)) return 0;
  const next = mode === "amount" ? current + delta : current * (1 + delta / 100);
  return Math.max(0, Math.round(next * 100) / 100);
}

module.exports = { applyPrice };
