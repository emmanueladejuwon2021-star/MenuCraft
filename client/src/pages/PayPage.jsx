import { useState } from "react";
import { Link, Navigate } from "react-router-dom";
import { money } from "../lib/store";
import { plateTotal } from "../lib/orders";

export default function PayPage({ settings, user, plate, onPaid }) {
  const [name, setName] = useState(user?.name || "");
  const [phone, setPhone] = useState(user?.phone || "");
  const [note, setNote] = useState("");
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState("");
  const total = plateTotal(plate);
  const signedGuest = user?.role === "guest";

  if (!plate.length) return <Navigate to="/plate" replace />;

  async function pay(event) {
    event.preventDefault();
    setError("");
    const guestName = (signedGuest ? user.name : name).trim();
    if (!guestName) {
      setError("Add your name so the kitchen can call the plate.");
      return;
    }
    setBusy(true);
    try {
      await onPaid({
        note,
        guest: {
          name: guestName,
          email: signedGuest ? user.email : "",
          phone: (signedGuest ? user.phone || phone : phone).trim(),
        },
      });
    } catch (err) {
      setError(err.message || "Could not send the order. Please try again.");
      setBusy(false);
    }
  }

  return (
    <section className="page-enter mx-auto max-w-lg space-y-5 pb-8">
      <div className="overflow-hidden rounded-[2rem] border border-line bg-card shadow-soft">
        <div className="bg-gradient-to-br from-blue-200/70 to-transparent px-6 py-6 dark:from-blue-500/15">
          <p className="text-xs font-semibold uppercase tracking-[0.18em] text-clay">Checkout</p>
          <h1 className="mt-1 text-2xl font-semibold text-ink">Send this plate</h1>
          <p className="mt-2 text-sm leading-relaxed text-muted">No card is needed. The kitchen gets the order now. You pay at the counter when you pick it up.</p>
        </div>
        <div className="space-y-4 px-6 pb-6">
          {signedGuest ? (
            <p className="text-sm text-ink">Sending as <span className="font-semibold">{user.name}</span></p>
          ) : (
            <p className="text-sm text-muted">No account needed. Leave a name for the ticket.</p>
          )}
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
            {!signedGuest && (
              <>
                <label className="block text-sm font-medium text-ink">Your name<input required value={name} onChange={(event) => setName(event.target.value)} placeholder="Name on the ticket" className="field mt-1" /></label>
                <label className="block text-sm font-medium text-ink">Phone (optional)<input value={phone} onChange={(event) => setPhone(event.target.value)} placeholder="If the kitchen should call you" className="field mt-1" /></label>
              </>
            )}
            <label className="block text-sm font-medium text-ink">Table or pickup note<input value={note} onChange={(event) => setNote(event.target.value)} placeholder="Table 4, or pickup" className="field mt-1" /></label>
            {error ? <p className="rounded-2xl border border-line bg-card px-3 py-3 text-sm text-danger">{error}</p> : null}
            <button disabled={busy} className="tap h-12 w-full rounded-full bg-pine text-sm font-semibold text-invert disabled:opacity-60">{busy ? "Sending to the kitchen…" : `Send order · ${money(settings.currency_symbol, total)}`}</button>
            <Link to="/plate" className="tap inline-flex h-11 w-full items-center justify-center rounded-full border border-line text-sm font-medium text-ink">Back to plate</Link>
          </form>
        </div>
      </div>
    </section>
  );
}
