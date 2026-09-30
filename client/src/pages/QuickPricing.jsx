import { useState } from "react";
import { money } from "../lib/store";
import KitchenNav from "../components/KitchenNav";

export default function QuickPricing({ settings, categories, items, onApply }) {
  const [categoryId, setCategoryId] = useState(categories[0]?.id || "");
  const [mode, setMode] = useState("percent");
  const [amount, setAmount] = useState("10");

  const preview = items.filter((item) => Number(item.category_id) === Number(categoryId));

  function nextPrice(price) {
    const value = Number(amount || 0);
    if (mode === "amount") return Math.max(0, Number((price + value).toFixed(2)));
    return Math.max(0, Number((price * (1 + value / 100)).toFixed(2)));
  }

  return (
    <section className="page-enter space-y-4">
      <KitchenNav title="Quick prices" hint="Raise or lower every dish in one list." />
      <div className="rounded-2xl border border-line bg-card p-4 shadow-soft">
        <div className="grid gap-3 md:grid-cols-3">
          <label className="text-sm text-ink">
            Category
            <select
              value={categoryId}
              onChange={(event) => setCategoryId(event.target.value)}
              className="tap mt-1 w-full rounded-xl border border-line bg-paper px-3 text-ink"
            >
              {categories.map((category) => (
                <option key={category.id} value={category.id}>
                  {category.name}
                </option>
              ))}
            </select>
          </label>
          <label className="text-sm text-ink">
            Change type
            <select
              value={mode}
              onChange={(event) => setMode(event.target.value)}
              className="tap mt-1 w-full rounded-xl border border-line bg-paper px-3 text-ink"
            >
              <option value="percent">Percent</option>
              <option value="amount">Fixed amount</option>
            </select>
          </label>
          <label className="text-sm text-ink">
            Amount
            <input
              type="number"
              step="0.01"
              value={amount}
              onChange={(event) => setAmount(event.target.value)}
              className="tap mt-1 w-full rounded-xl border border-line bg-paper px-3 text-ink"
            />
          </label>
        </div>
        <p className="mt-3 text-sm text-muted">
          Example: +10 for weekend peak, or -1.00 for happy hour.
        </p>
        <button
          type="button"
          onClick={() => onApply({ categoryId: Number(categoryId), mode, amount: Number(amount) })}
          className="tap mt-3 rounded-full bg-ink px-5 text-sm font-semibold text-invert"
        >
          Update prices
        </button>
      </div>

      <div className="space-y-2">
        {preview.map((item) => (
          <div key={item.id} className="flex items-center justify-between rounded-xl border border-line bg-card px-3 py-2 text-ink">
            <span>{item.name}</span>
            <span className="text-sm">
              {money(settings.currency_symbol, item.price)} → {money(settings.currency_symbol, nextPrice(item.price))}
            </span>
          </div>
        ))}
      </div>
    </section>
  );
}
