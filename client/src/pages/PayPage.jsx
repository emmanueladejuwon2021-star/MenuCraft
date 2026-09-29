import { useState } from "react";
import { Link, Navigate } from "react-router-dom";
import { money } from "../lib/store";
import { plateTotal } from "../lib/orders";

export default function PayPage({ settings, user, plate, onPaid }) {
  const [note, setNote] = useState("");
  const [card, setCard] = useState("");
  const [busy, setBusy] = useState(false);
  const total = plateTotal(plate);

  if (!user || user.role !== "guest") return <Navigate to="/guest-account" replace />;
  if (!plate.length) return <Navigate to="/plate" replace />;

  async function pay(event) {
    event.preventDefault();
    const digits = card.replace(/\s/g, "");
    if (digits.length < 12) {
      return;
    }
    setBusy(true);
    await new Promise((resolve) => window.setTimeout(resolve, 900));
    onPaid({
      note,
      payRef: `PAY-${Date.now().toString().slice(-8)}`,
    });
    setBusy(false);
  }

  return (
    <section className="page-enter mx-auto max-w-lg space-y-4">
      <div>
        <h1 className="text-xl font-semibold">Pay for your plate</h1>
        <p className="mt-1 text-sm text-muted">This marks the order as paid so the kitchen can start cooking. Use any 12-digit test card number.</p>
      </div>
      <div className="rounded-2xl border border-line bg-card p-4">
        {plate.map((row) => (
          <p key={row.id} className="flex justify-between py-1 text-sm">
            <span>{row.qty} × {row.name}</span>
            <span>{money(settings.currency_symbol, row.price * row.qty)}</span>
          </p>
        ))}
        <p className="mt-3 flex justify-between font-semibold"><span>To pay</span><span>{money(settings.currency_symbol, total)}</span></p>
      </div>
      <form onSubmit={pay} className="space-y-3 rounded-2xl border border-line bg-card p-4">
        <label className="block text-sm">Table or note
          <input value={note} onChange={(event) => setNote(event.target.value)} placeholder="Table 4, or pickup" className="tap mt-1 w-full rounded-xl border border-line bg-paper px-3" />
        </label>
        <label className="block text-sm">Card number
          <input value={card} onChange={(event) => setCard(event.target.value)} placeholder="4242 4242 4242 4242" inputMode="numeric" className="tap mt-1 w-full rounded-xl border border-line bg-paper px-3" />
        </label>
        <button disabled={busy} className="tap w-full rounded-full bg-pine text-sm font-semibold text-invert disabled:opacity-60">
          {busy ? "Taking payment…" : `Pay ${money(settings.currency_symbol, total)}`}
        </button>
      </form>
      <Link to="/plate" className="text-sm text-muted">Back to plate</Link>
    </section>
  );
}
