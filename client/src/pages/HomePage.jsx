import { Link } from "react-router-dom";
import { money } from "../lib/store";

const HERO =
  "https://images.unsplash.com/photo-1604329760661-e91dc7a3026b?auto=format&fit=crop&w=1600&q=80";

export default function HomePage({ settings, items = [] }) {
  const featured = items.filter((row) => row.is_available).slice(0, 4);
  return (
    <section className="page-enter space-y-6">
      <div className="relative overflow-hidden rounded-[2rem] border border-line shadow-soft">
        <img src={HERO} alt="" className="h-72 w-full object-cover sm:h-96" />
        <div className="absolute inset-0 bg-gradient-to-t from-black/75 via-black/35 to-black/10" />
        <div className="absolute inset-x-0 bottom-0 p-6 sm:p-10">
          <p className="text-xs font-semibold uppercase tracking-[0.2em] text-amber-200">Nigerian kitchen</p>
          <h1 className="mt-2 max-w-xl text-3xl font-semibold leading-tight text-white sm:text-5xl">
            {settings.restaurant_name}
          </h1>
          <p className="mt-3 max-w-lg text-sm leading-relaxed text-white/85 sm:text-base">
            Party jollof, pepper soup, suya, and cold zobo. See what is ready today and fill your plate.
          </p>
          <div className="mt-6 flex flex-wrap gap-3">
            <Link to="/menu" className="tap inline-flex items-center rounded-full bg-white px-6 text-sm font-semibold text-stone-900">
              Open the menu
            </Link>
            <Link to="/guest-account" className="tap inline-flex items-center rounded-full border border-white/50 bg-white/10 px-6 text-sm font-semibold text-white">
              Guest account
            </Link>
          </div>
        </div>
      </div>

      <div className="grid gap-3 sm:grid-cols-3">
        {[
          ["1", "Browse", "Pick soups, rice, small chops, and drinks."],
          ["2", "Fill a plate", "Add what you want and change the amount."],
          ["3", "Pay", "Sign in as a guest and send the order to the kitchen."],
        ].map(([step, title, copy]) => (
          <article key={step} className="rounded-3xl border border-line bg-card p-5 shadow-soft">
            <p className="text-xs font-semibold uppercase tracking-[0.16em] text-clay">Step {step}</p>
            <h2 className="mt-2 text-lg font-semibold text-ink">{title}</h2>
            <p className="mt-2 text-sm leading-relaxed text-muted">{copy}</p>
          </article>
        ))}
      </div>

      {featured.length > 0 && (
        <div className="space-y-3">
          <div className="flex items-end justify-between gap-3 px-1">
            <h2 className="text-lg font-semibold text-ink">On the fire today</h2>
            <Link to="/menu" className="text-sm font-medium text-clay">See all</Link>
          </div>
          <div className="grid gap-3 sm:grid-cols-2">
            {featured.map((item) => (
              <Link key={item.id} to="/menu" className="overflow-hidden rounded-3xl border border-line bg-card shadow-soft">
                {item.image_url ? (
                  <img src={item.image_url} alt="" className="h-36 w-full object-cover" />
                ) : (
                  <div className="h-24 bg-gradient-to-br from-amber-100 to-orange-200 dark:from-amber-900/30 dark:to-stone-800" />
                )}
                <div className="flex items-start justify-between gap-3 p-4">
                  <div>
                    <p className="font-semibold text-ink">{item.name}</p>
                    <p className="mt-1 line-clamp-2 text-sm text-muted">{item.description}</p>
                  </div>
                  <p className="shrink-0 font-semibold text-ink">{money(settings.currency_symbol, item.price)}</p>
                </div>
              </Link>
            ))}
          </div>
        </div>
      )}
    </section>
  );
}
