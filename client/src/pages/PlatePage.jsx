import { Link } from "react-router-dom";
import { money } from "../lib/store";
import { plateTotal } from "../lib/orders";
import PageHeader from "../components/PageHeader.jsx";

export default function PlatePage({ settings, user, plate, onQty, onClear }) {
  const total = plateTotal(plate);
  return (
    <section className="page-enter space-y-5 pb-28">
      <PageHeader kicker="Guest" title="Your plate" hint="Check the dishes, change amounts, then pay." />
      {plate.length === 0 ? (
        <div className="rounded-3xl border border-dashed border-line bg-card px-5 py-12 text-center">
          <p className="text-base font-semibold text-ink">Your plate is empty</p>
          <p className="mt-2 text-sm text-muted">Pick a dish from today's menu to start.</p>
          <Link to="/menu" className="tap mt-5 inline-flex items-center justify-center rounded-full bg-ink px-6 text-sm font-semibold text-invert">Browse the menu</Link>
        </div>
      ) : (
        <>
          <div className="space-y-3">
            {plate.map((row) => {
              const line = Number(row.price) * Number(row.qty);
              return (
                <article key={row.id} className="overflow-hidden rounded-3xl border border-line bg-card shadow-soft">
                  <div className="flex gap-3 p-3 sm:p-4">
                    {row.image_url ? <img src={row.image_url} alt="" className="h-24 w-24 shrink-0 rounded-2xl object-cover" /> : <div className="h-24 w-24 shrink-0 rounded-2xl bg-paper" />}
                    <div className="min-w-0 flex-1">
                      <div className="flex items-start justify-between gap-2">
                        <p className="font-semibold leading-snug text-ink">{row.name}</p>
                        <p className="shrink-0 font-semibold text-ink">{money(settings.currency_symbol, line)}</p>
                      </div>
                      <p className="mt-1 text-sm text-muted">{money(settings.currency_symbol, row.price)} each</p>
                      <div className="mt-3 flex items-center justify-between gap-2">
                        <div className="inline-flex items-center rounded-full border border-line bg-paper">
                          <button type="button" className="tap w-11 text-lg text-ink" onClick={() => onQty(row.id, row.qty - 1)}>−</button>
                          <span className="w-8 text-center text-sm font-semibold text-ink">{row.qty}</span>
                          <button type="button" className="tap w-11 text-lg text-ink" onClick={() => onQty(row.id, row.qty + 1)}>+</button>
                        </div>
                        <button type="button" className="text-sm font-medium text-red-600" onClick={() => onQty(row.id, 0)}>Remove</button>
                      </div>
                    </div>
                  </div>
                </article>
              );
            })}
          </div>
          <div className="fixed inset-x-0 bottom-16 z-30 border-t border-line bg-card/95 px-4 py-3 backdrop-blur md:sticky md:bottom-4 md:z-10 md:rounded-3xl md:border">
            <div className="mx-auto flex max-w-6xl items-center justify-between gap-3">
              <div>
                <p className="text-xs text-muted">{plate.length} on the plate</p>
                <p className="text-lg font-semibold text-ink">{money(settings.currency_symbol, total)}</p>
              </div>
              <div className="flex shrink-0 items-center gap-2">
                <button type="button" onClick={onClear} className="tap rounded-full border border-line px-4 text-sm text-ink">Clear</button>
                {user?.role === "guest" ? (
                  <Link to="/pay" className="tap inline-flex items-center justify-center rounded-full bg-pine px-5 text-sm font-semibold text-invert">Pay now</Link>
                ) : (
                  <Link to="/guest-account" className="tap inline-flex items-center justify-center rounded-full bg-ink px-4 text-sm font-semibold text-invert">Sign in to pay</Link>
                )}
              </div>
            </div>
          </div>
        </>
      )}
    </section>
  );
}
