import { useState } from "react";
import { Link, Navigate } from "react-router-dom";
import { money } from "../lib/store";
import { plateTotal } from "../lib/orders";

export default function PayPage({ settings, user, plate, onPaid }) {
  const [note, setNote] = useState("");
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState("");
  const total = plateTotal(plate);

  if (!user || user.role !== "guest") return <Navigate to="/guest-account" replace />;
  if (!plate.length) return <Navigate to="/plate" replace />;

  async function pay(event) {
    event.preventDefault();
    setError("");
    if (!plate.length) {
      setError("Your plate is empty. Add food before you send an order.");
      return;
    }
    setBusy(true);
    try {
      await onPaid({ note });
    } catch (err) {
      setError(err.message || "Could not send the order. Please try again.");
      setBusy(false);
    }
  }

  return (
    <section className="page-enter mx-auto max-w-lg space-y-5 pb-8">
      <div className="overflow-hidden rounded-[2rem] border border-line bg-card shadow-soft">
        <div className="bg-gradient-to-br from-orange-200/55 to-transparent px-6 py-6 dark:from-orange-500/10">
          <p className="text-xs font-semibold uppercase tracking-[0.18em] text-clay">Checkout</p>
          <h1 className="mt-1 text-2xl font-semibold text-ink">Send this plate</h1>
          <p className="mt-2 text-sm leading-relaxed text-muted">No card is needed. The kitchen gets the order now. You pay at the counter when you pick it up.</p>
        </div>
        <div className="space-y-4 px-6 pb-6">
          <p className="text-sm text-ink">Sending as <span className="font-semibold">{user.name}</span></p>
          <div className="rounded-2xl bg-paper px-4 py-3">
            {plate.map((row) => (
              <p key={row.id} className="flex justify-between gap-3 py-1.5 text-sm text-ink">
                <span>{row.qty} × {row.name}</span>
                <span>{money(settings.currency_symbol, row.price * row.qty)}</span>
              </p>
            ))}
            <p className="mt-2 flex justify-between border-t border-line pt-3 text-base font-semibold text-ink">
              <span>Total at the counter</span>
              <span>{money(settings.currency_symbol, total)}</span>
            </p>
          </div>
          <form onSubmit={pay} className="space-y-3">
            <label className="block text-sm font-medium text-ink">Table or pickup note<input value={note} onChange={(event) => setNote(event.target.value)} placeholder="Table 4, or pickup" className="field mt-1" /></label>
            {error ? <p className="rounded-2xl bg-rose-50 px-3 py-2 text-sm text-rose-800">{error}</p> : null}
            <button disabled={busy} className="tap h-12 w-full rounded-full bg-pine text-sm font-semibold text-invert disabled:opacity-60">{busy ? "Sending to the kitchen…" : `Send order · ${money(settings.currency_symbol, total)}`}</button>
          </form>
          <Link to="/plate" className="inline-block text-sm font-medium text-muted">Back to plate</Link>
        </div>
      </div>
    </section>
  );
}
