import { useState } from "react";
import { Link, Navigate } from "react-router-dom";
import { money } from "../lib/store";
import { plateTotal } from "../lib/orders";
import PageHeader from "../components/PageHeader.jsx";

export default function PayPage({ settings, user, plate, onPaid }) {
  const [note, setNote] = useState("");
  const [card, setCard] = useState("");
  const [busy, setBusy] = useState(false);
  const total = plateTotal(plate);

  if (!user || user.role !== "guest") return <Navigate to="/guest-account" replace />;
  if (!plate.length) return <Navigate to="/plate" replace />;

  async function pay(event) {
    event.preventDefault();
    if (card.replace(/\s/g, "").length < 12) return;
    setBusy(true);
    await new Promise((resolve) => window.setTimeout(resolve, 900));
    onPaid({ note, payRef: `PAY-${Date.now().toString().slice(-8)}` });
    setBusy(false);
  }

  return (
    <section className="page-enter mx-auto max-w-lg space-y-5">
      <PageHeader kicker="Checkout" title="Pay for your plate" hint="Use any 12-digit test card. This marks the order paid so the kitchen can cook." />
      <div className="rounded-3xl border border-line bg-card p-4 shadow-soft">
        {plate.map((row) => (
          <p key={row.id} className="flex justify-between gap-3 py-2 text-sm text-ink">
            <span>{row.qty} × {row.name}</span>
            <span>{money(settings.currency_symbol, row.price * row.qty)}</span>
          </p>
        ))}
        <p className="mt-2 flex justify-between border-t border-line pt-3 font-semibold text-ink"><span>To pay</span><span>{money(settings.currency_symbol, total)}</span></p>
      </div>
      <form onSubmit={pay} className="space-y-3 rounded-3xl border border-line bg-card p-4 shadow-soft">
        <label className="block text-sm text-ink">Table or note<input value={note} onChange={(event) => setNote(event.target.value)} placeholder="Table 4, or pickup" className="tap mt-1 h-11 w-full rounded-2xl border border-line bg-paper px-3" /></label>
        <label className="block text-sm text-ink">Card number<input value={card} onChange={(event) => setCard(event.target.value)} placeholder="4242 4242 4242 4242" inputMode="numeric" className="tap mt-1 h-11 w-full rounded-2xl border border-line bg-paper px-3" /></label>
        <button disabled={busy} className="tap h-11 w-full rounded-full bg-pine text-sm font-semibold text-invert disabled:opacity-60">{busy ? "Taking payment…" : `Pay ${money(settings.currency_symbol, total)}`}</button>
      </form>
      <Link to="/plate" className="text-sm text-muted">Back to plate</Link>
    </section>
  );
}
