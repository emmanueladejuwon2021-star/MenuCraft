import { useState } from "react";
import { Link } from "react-router-dom";
import { money } from "../lib/store";
import { plateCount, plateTotal } from "../lib/orders";
import PageHeader from "../components/PageHeader.jsx";
import DishPhoto from "../components/DishPhoto.jsx";

const MAX_QTY = 20;

export default function PlatePage({ settings, user, plate, onQty, onClear }) {
  const [askClear, setAskClear] = useState(false);
  const total = plateTotal(plate);
  const portions = plateCount(plate);
  const guestName = user?.role === "guest" ? user.name : "";

  function changeQty(row, next) {
    onQty(row.id, Math.max(0, Math.min(MAX_QTY, next)));
  }

  return (
    <section className="page-enter space-y-5 pb-44 md:pb-8">
      <PageHeader kicker="Guest" title="Your plate" hint="Check the dishes, change amounts, then send the order. Pay at the counter later." />
      {plate.length === 0 ? (
        <div className="rounded-3xl border border-dashed border-line bg-card px-5 py-12 text-center">
          <p className="text-base font-semibold text-ink">Your plate is empty</p>
          <p className="mt-2 text-sm text-muted">Pick a dish from today’s board to start.</p>
          <Link to="/menu" className="tap mt-5 inline-flex items-center justify-center rounded-full bg-ink px-6 text-sm font-semibold text-invert">Open the board</Link>
        </div>
      ) : (
        <>
          <div className="space-y-3">
            {plate.map((row) => {
              const line = Number(row.price) * Number(row.qty);
              const atMax = Number(row.qty) >= MAX_QTY;
              return (
                <article key={row.id} className="overflow-hidden rounded-3xl border border-line bg-card shadow-soft">
                  <div className="flex gap-3 p-3 sm:p-4">
                    <DishPhoto src={row.image_url} name={row.name} className="h-24 w-24 shrink-0" />
                    <div className="min-w-0 flex-1">
                      <div className="flex items-start justify-between gap-3">
                        <p className="font-semibold leading-snug text-ink">{row.name}</p>
                        <p className="shrink-0 font-semibold text-ink">{money(settings.currency_symbol, line)}</p>
                      </div>
                      <p className="mt-1 text-sm text-muted">{money(settings.currency_symbol, row.price)} each</p>
                      <div className="mt-3 flex items-center justify-between gap-2">
                        <div className="inline-flex items-center rounded-full border border-line bg-paper" role="group" aria-label={`Amount of ${row.name}`}>
                          <button type="button" className="tap w-11 text-lg text-ink" onClick={() => changeQty(row, Number(row.qty) - 1)} aria-label={Number(row.qty) === 1 ? `Remove ${row.name}` : `Fewer ${row.name}`}>−</button>
                          <span className="w-8 text-center text-sm font-semibold text-ink" aria-live="polite">{row.qty}</span>
                          <button type="button" className="tap w-11 text-lg text-ink disabled:opacity-40" onClick={() => changeQty(row, Number(row.qty) + 1)} disabled={atMax} aria-label={`More ${row.name}`}>+</button>
                        </div>
                        <button type="button" className="tap rounded-full px-3 text-sm font-medium text-danger" onClick={() => onQty(row.id, 0)}>Remove</button>
                      </div>
                      {atMax ? <p className="mt-2 text-xs text-muted">20 is the most for one dish.</p> : null}
                    </div>
                  </div>
                </article>
              );
            })}
          </div>

          {askClear ? (
            <div className="rounded-3xl border border-line bg-card px-4 py-4 shadow-soft" role="alertdialog" aria-labelledby="clear-title">
              <p id="clear-title" className="font-semibold text-ink">Clear the whole plate?</p>
              <p className="mt-1 text-sm text-muted">Every dish comes off. You can add them again from the board.</p>
              <div className="mt-4 grid grid-cols-2 gap-2">
                <button type="button" className="tap rounded-full border border-line text-sm font-medium text-ink" onClick={() => setAskClear(false)}>Keep plate</button>
                <button type="button" className="tap rounded-full bg-ink text-sm font-semibold text-invert" onClick={() => { setAskClear(false); onClear(); }}>Clear plate</button>
              </div>
            </div>
          ) : null}

          <div className="fixed inset-x-3 bottom-[4.75rem] z-30 rounded-3xl border border-line bg-card/95 px-4 py-3 shadow-soft backdrop-blur md:sticky md:inset-auto md:bottom-4 md:z-10">
            <div className="mx-auto flex max-w-6xl flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
              <div>
                <p className="text-xs text-muted">{portions} {portions === 1 ? "portion" : "portions"}{guestName ? ` · ${guestName}` : ""}</p>
                <p className="text-lg font-semibold text-ink">{money(settings.currency_symbol, total)}</p>
              </div>
              <div className="grid grid-cols-2 gap-2 sm:flex sm:w-auto">
                <button type="button" onClick={() => setAskClear(true)} className="tap rounded-full border border-line px-4 text-sm text-ink">Clear</button>
                <Link to="/pay" className="tap inline-flex items-center justify-center rounded-full bg-pine px-5 text-sm font-semibold text-invert">Send order</Link>
              </div>
            </div>
          </div>
        </>
      )}
    </section>
  );
}
