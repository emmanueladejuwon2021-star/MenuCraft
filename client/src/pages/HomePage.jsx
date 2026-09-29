import { Link } from "react-router-dom";

export default function HomePage({ settings, user }) {
  return (
    <section className="page-enter space-y-6">
      <div className="overflow-hidden rounded-3xl border border-line bg-card shadow-soft">
        <div className="bg-gradient-to-br from-amber-100/70 to-transparent px-6 py-8 dark:from-amber-900/20 md:px-10 md:py-12">
          <p className="text-sm font-semibold tracking-wide text-clay">Nigerian kitchen menu</p>
          <h1 className="mt-2 max-w-xl text-3xl font-semibold leading-tight md:text-5xl">
            Welcome to {settings.restaurant_name}
          </h1>
          <p className="mt-3 max-w-2xl text-base text-muted">
            See today&apos;s jollof, soups, small chops, and drinks. Staff can sign in to hide sold-out dishes and change prices in one tap.
          </p>
          <div className="mt-7 flex flex-wrap gap-3">
            <Link to="/menu" className="tap inline-flex items-center rounded-full bg-ink px-6 text-sm font-semibold text-invert">
              View live menu
            </Link>
            {user ? (
              <Link to="/dishes" className="tap inline-flex items-center rounded-full border border-line bg-card px-6 text-sm font-medium text-ink">
                Open kitchen tools
              </Link>
            ) : (
              <Link to="/account" className="tap inline-flex items-center rounded-full border border-line bg-card px-6 text-sm font-medium text-ink">
                Create a kitchen account
              </Link>
            )}
          </div>
        </div>
      </div>
      <div className="grid gap-3 sm:grid-cols-3">
        {[
          ["1. Browse", "Search dishes, filter by spicy or vegetarian, and see what is still available."],
          ["2. Update", "Mark suya sold out or raise weekend prices without a long form."],
          ["3. Share", "Copy a link or download a QR code for tables and WhatsApp."],
        ].map(([title, copy]) => (
          <article key={title} className="rounded-2xl border border-line bg-card p-5 shadow-soft">
            <h2 className="font-semibold">{title}</h2>
            <p className="mt-2 text-sm leading-relaxed text-muted">{copy}</p>
          </article>
        ))}
      </div>
    </section>
  );
}
