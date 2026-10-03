const MAX_QTY = 20;
const MAX_LINES = 30;

function buildOrderLines(requested, menuRows) {
  if (!Array.isArray(requested) || requested.length === 0) {
    const error = new Error("Your plate is empty.");
    error.status = 400;
    throw error;
  }
  if (requested.length > MAX_LINES) {
    const error = new Error("That plate has too many dishes.");
    error.status = 400;
    throw error;
  }
  const byId = new Map((menuRows || []).map((row) => [Number(row.id), row]));
  const lines = [];
  for (const row of requested) {
    const item = byId.get(Number(row && row.id));
    if (!item) {
      const error = new Error("A dish on this plate is not on the menu.");
      error.status = 400;
      throw error;
    }
    const available = item.is_available === true || Number(item.is_available) === 1;
    if (!available) {
      const error = new Error(`${item.name} is sold out.`);
      error.status = 400;
      throw error;
    }
    const qty = Math.floor(Number(row.qty));
    if (!Number.isFinite(qty) || qty < 1 || qty > MAX_QTY) {
      const error = new Error("Each dish can be ordered from 1 to 20.");
      error.status = 400;
      throw error;
    }
    const price = Number(item.price);
    if (!Number.isFinite(price) || price < 0) {
      const error = new Error("A dish price on the menu is not valid.");
      error.status = 400;
      throw error;
    }
    lines.push({
      id: Number(item.id),
      name: String(item.name || ""),
      price,
      qty,
      image_url: String(item.image_url || ""),
    });
  }
  const total = Math.round(lines.reduce((sum, line) => sum + line.price * line.qty, 0) * 100) / 100;
  return { lines, total };
}

module.exports = { buildOrderLines, MAX_QTY, MAX_LINES };
