import { Link } from "react-router-dom";
import { money } from "../lib/store";
import DishPhoto from "../components/DishPhoto.jsx";

export default function HomePage({ settings, items = [], onAddToPlate }) {
  const ready = items.filter((row) => row.is_available);
  const spotlight = ready[0];
  const extras = ready.slice(1, 5);

  return (
    <section className="page-enter space-y-8">
      <div className="grid items-start gap-6 lg:grid-cols-[1.1fr_0.9fr]">
        <div className="rounded-[2rem] border border-line bg-card px-6 py-8 shadow-soft sm:px-8">
          <h1 className="max-w-md text-4xl font-semibold leading-[1.05] text-ink sm:text-5xl">
            {settings.restaurant_name}
          </h1>
          <p className="mt-4 max-w-md text-base leading-relaxed text-muted">
            Today’s board is short on purpose so you can pick fast and eat well.
          </p>
          <div className="mt-8 flex flex-wrap gap-3">
            <Link to="/menu" className="tap inline-flex items-center rounded-full bg-pine px-6 text-sm font-semibold text-invert">
              See today’s board
            </Link>
            <Link to="/plate" className="tap inline-flex items-center rounded-full border border-line bg-paper px-6 text-sm font-semibold text-ink">
              Check your plate
            </Link>
          </div>
        </div>

        {spotlight ? (
          <article className="overflow-hidden rounded-[2rem] border border-line bg-card shadow-soft">
            {spotlight.image_url ? (
              <DishPhoto src={spotlight.image_url} name={spotlight.name} className="h-52 w-full rounded-none" />
            ) : (
              <div className="flex h-52 items-end bg-gradient-to-br from-blue-200 to-sky-100 p-6 dark:from-blue-900/40 dark:to-slate-900">
                <p className="text-sm font-medium text-ink">First dish on the board</p>
              </div>
            )}
            <div className="space-y-3 p-6">
              <p className="text-xs font-semibold uppercase tracking-[0.16em] text-clay">Start here</p>
              <div className="flex items-start justify-between gap-3">
                <h2 className="text-2xl font-semibold leading-tight text-ink">{spotlight.name}</h2>
                <p className="shrink-0 text-lg font-semibold text-ink">{money(settings.currency_symbol, spotlight.price)}</p>
              </div>
              <p className="text-sm leading-relaxed text-muted">{spotlight.description}</p>
              {onAddToPlate ? (
                <button type="button" onClick={() => onAddToPlate(spotlight)} className="tap h-12 w-full rounded-full bg-pine text-sm font-semibold text-invert">
                  Add to plate
                </button>
              ) : null}
            </div>
          </article>
        ) : (
          <article className="rounded-[2rem] border border-dashed border-line bg-card px-6 py-10 shadow-soft">
            <h2 className="text-xl font-semibold text-ink">The board is still being set</h2>
            <p className="mt-2 text-sm text-muted">Check the menu in a moment, or create an account so you are ready when the food is.</p>
          </article>
        )}
      </div>

      {extras.length ? (
        <div className="space-y-3">
          <h2 className="px-1 text-lg font-semibold text-ink">More from the board</h2>
          <div className="grid gap-3">
            {extras.map((item) => (
              <article key={item.id} className="grid grid-cols-[96px_1fr] items-center gap-4 rounded-[1.6rem] border border-line bg-card p-3 shadow-soft sm:grid-cols-[120px_1fr_auto]">
                <DishPhoto src={item.image_url} name={item.name} className="h-24 w-full" />
                <div className="min-w-0">
                  <p className="font-semibold text-ink">{item.name}</p>
                  <p className="mt-1 line-clamp-2 text-sm text-muted">{item.description}</p>
                  <p className="mt-2 text-sm font-semibold text-ink sm:hidden">{money(settings.currency_symbol, item.price)}</p>
                </div>
                <div className="col-span-2 flex items-center justify-between gap-3 sm:col-span-1 sm:flex-col sm:items-end">
                  <p className="hidden text-base font-semibold text-ink sm:block">{money(settings.currency_symbol, item.price)}</p>
                  {onAddToPlate && item.is_available ? (
                    <button type="button" onClick={() => onAddToPlate(item)} className="tap h-11 rounded-full bg-pine px-4 text-sm font-semibold text-invert">
                      Add to plate
                    </button>
                  ) : null}
                </div>
              </article>
            ))}
          </div>
        </div>
      ) : null}
    </section>
  );
}
