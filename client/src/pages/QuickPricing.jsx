import { useEffect, useState } from "react";
import { money } from "../lib/store";
import { applyPrice } from "../lib/pricing";
import KitchenNav from "../components/KitchenNav";

export default function QuickPricing({ settings, categories, items, onApply }) {
  const [categoryId, setCategoryId] = useState(categories[0]?.id || "");
  const [mode, setMode] = useState("percent");
  const [amount, setAmount] = useState("10");
  const [ask, setAsk] = useState(false);

  useEffect(() => {
    if (!categories.length) return;
    if (!categories.some((category) => Number(category.id) === Number(categoryId))) {
      setCategoryId(categories[0].id);
    }
  }, [categories, categoryId]);

  const preview = items.filter((item) => Number(item.category_id) === Number(categoryId));
  const number = Number(amount);
  const ready = categoryId && Number.isFinite(number);
  const hint = mode === "percent"
    ? "10 raises every price by 10 percent. -10 lowers them by 10 percent."
    : "10 adds 10 to every price. -10 takes 10 off. Prices cannot go below zero.";

  return (
    <section className="page-enter space-y-3">
      <KitchenNav title="Quick prices" hint="Change every dish in one list." />
      <form
        className="space-y-3 rounded-2xl border border-line bg-card p-3 shadow-soft"
        onSubmit={(event) => {
          event.preventDefault();
          if (!ready || !preview.length) return;
          setAsk(true);
        }}
      >
        <label className="block text-sm font-medium text-ink">
          List
          <select value={categoryId} onChange={(event) => setCategoryId(event.target.value)} className="field mt-1">
            {categories.map((category) => (
              <option key={category.id} value={category.id}>{category.name}</option>
            ))}
          </select>
        </label>
        <div className="grid grid-cols-2 gap-2">
          <label className="block text-sm font-medium text-ink">
            Change
            <select value={mode} onChange={(event) => setMode(event.target.value)} className="field mt-1">
              <option value="percent">Percent</option>
              <option value="amount">Fixed amount</option>
            </select>
          </label>
          <label className="block text-sm font-medium text-ink">
            {mode === "percent" ? "Percent" : "Amount"}
            <input type="number" step="0.01" value={amount} onChange={(event) => setAmount(event.target.value)} className="field mt-1" />
          </label>
        </div>
        <p className="text-xs text-muted">{hint}</p>
        <button disabled={!ready || !preview.length} className="tap h-11 w-full rounded-full bg-pine text-sm font-semibold text-invert disabled:opacity-60">Review price change</button>
        {ask ? (
          <div className="rounded-2xl border border-line bg-paper p-3" role="alertdialog" aria-labelledby="price-title">
            <p id="price-title" className="font-semibold text-ink">Change {preview.length} {preview.length === 1 ? "dish" : "dishes"}?</p>
            <p className="mt-1 text-sm text-muted">This updates every price in the list. You can change them back the same way.</p>
            <div className="mt-3 grid grid-cols-2 gap-2">
              <button type="button" className="tap rounded-full border border-line text-sm font-medium text-ink" onClick={() => setAsk(false)}>Keep prices</button>
              <button type="button" className="tap rounded-full bg-pine text-sm font-semibold text-invert" onClick={() => { setAsk(false); onApply({ categoryId: Number(categoryId), mode, amount: number }); }}>Update prices</button>
            </div>
          </div>
        ) : null}
      </form>
      <div className="space-y-2">
        {preview.length === 0 ? (
          <p className="rounded-2xl border border-dashed border-line bg-card px-3 py-6 text-center text-sm text-muted">No dishes in this list.</p>
        ) : (
          preview.map((item) => (
            <div key={item.id} className="flex flex-wrap items-center justify-between gap-x-3 gap-y-1 rounded-xl border border-line bg-card px-3 py-2">
              <span className="min-w-0 break-words text-sm font-medium text-ink">{item.name}</span>
              <span className="shrink-0 text-xs text-ink">{money(settings.currency_symbol, item.price)} → {money(settings.currency_symbol, applyPrice(item.price, mode, number))}</span>
            </div>
          ))
        )}
      </div>
    </section>
  );
}
