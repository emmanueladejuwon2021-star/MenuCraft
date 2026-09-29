import { Link } from "react-router-dom";
import { money } from "../lib/store";
import { plateTotal } from "../lib/orders";

export default function PlatePage({ settings, user, plate, onQty, onClear }) {
  const total = plateTotal(plate);
  return (
    <section className="page-enter space-y-4">
      <div>
        <h1 className="text-xl font-semibold">Your plate</h1>
        <p className="mt-1 text-sm text-muted">Add food from the menu, then pay when you are ready.</p>
      </div>
      {plate.length === 0 ? (
        <div className="rounded-3xl border border-dashed border-line bg-card px-5 py-10 text-center">
          <p className="text-sm text-muted">Your plate is empty.</p>
          <Link to="/menu" className="tap mt-4 inline-flex rounded-full bg-ink px-5 text-sm font-semibold text-invert">Browse the menu</Link>
        </div>
      ) : (
        <div className="space-y-3">
          {plate.map((row) => (
            <article key={row.id} className="flex gap-3 rounded-2xl border border-line bg-card p-3 shadow-soft">
              {row.image_url ? <img src={row.image_url} alt="" className="h-16 w-16 rounded-xl object-cover" /> : <div className="h-16 w-16 rounded-xl bg-paper" />}
              <div className="min-w-0 flex-1">
                <p className="font-semibold">{row.name}</p>
                <p className="text-sm text-muted">{money(settings.currency_symbol, row.price)}</p>
                <div className="mt-2 flex items-center gap-2">
                  <button type="button" className="tap w-10 rounded-full border border-line" onClick={() => onQty(row.id, row.qty - 1)}>-</button>
                  <span className="w-6 text-center text-sm">{row.qty}</span>
                  <button type="button" className="tap w-10 rounded-full border border-line" onClick={() => onQty(row.id, row.qty + 1)}>+</button>
                </div>
              </div>
            </article>
          ))}
          <div className="rounded-2xl border border-line bg-card p-4">
            <p className="flex items-center justify-between font-semibold"><span>Total</span><span>{money(settings.currency_symbol, total)}</span></p>
            <div className="mt-3 flex flex-wrap gap-2">
              {user?.role === "guest" ? (
                <Link to="/pay" className="tap inline-flex rounded-full bg-pine px-5 text-sm font-semibold text-invert">Pay now</Link>
              ) : (
                <Link to="/guest-account" className="tap inline-flex rounded-full bg-ink px-5 text-sm font-semibold text-invert">Sign in as a guest to pay</Link>
              )}
              <button type="button" onClick={onClear} className="tap rounded-full border border-line px-4 text-sm">Clear plate</button>
            </div>
          </div>
        </div>
      )}
    </section>
  );
}
