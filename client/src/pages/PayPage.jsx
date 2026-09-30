import { useState } from "react";
import { Link, Navigate } from "react-router-dom";
import { money } from "../lib/store";
import { plateTotal } from "../lib/orders";
import PageHeader from "../components/PageHeader.jsx";

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
    <section className="page-enter mx-auto max-w-lg space-y-5">
      <PageHeader
        kicker="Checkout"
        title="Send your order"
        hint="This is a kitchen demo. No card number is needed. The kitchen will see the order after you confirm."
      />
      <div className="rounded-3xl border border-line bg-card p-4 shadow-soft">
        {plate.map((row) => (
          <p key={row.id} className="flex justify-between gap-3 py-2 text-sm text-ink">
            <span>{row.qty} × {row.name}</span>
            <span>{money(settings.currency_symbol, row.price * row.qty)}</span>
          </p>
        ))}
        <p className="mt-2 flex justify-between border-t border-line pt-3 font-semibold text-ink"><span>To pay later at the counter</span><span>{money(settings.currency_symbol, total)}</span></p>
      </div>
      <form onSubmit={pay} className="space-y-3 rounded-3xl border border-line bg-card p-4 shadow-soft">
        <label className="block text-sm text-ink">Table or note<input value={note} onChange={(event) => setNote(event.target.value)} placeholder="Table 4, or pickup" className="tap mt-1 h-11 w-full rounded-2xl border border-line bg-paper px-3" /></label>
        {error ? <p className="text-sm text-rose-700">{error}</p> : null}
        <button disabled={busy} className="tap h-11 w-full rounded-full bg-pine text-sm font-semibold text-invert disabled:opacity-60">{busy ? "Sending to kitchen…" : `Confirm order · ${money(settings.currency_symbol, total)}`}</button>
      </form>
      <Link to="/plate" className="text-sm text-muted">Back to plate</Link>
    </section>
  );
}
