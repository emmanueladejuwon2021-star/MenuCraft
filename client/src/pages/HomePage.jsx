import { Link } from "react-router-dom";

export default function HomePage({ settings, user }) {
  return (
    <section className="space-y-6">
      <div className="rounded-3xl border border-line bg-card p-6 shadow-soft md:p-10">
        <p className="text-sm font-medium text-clay">Nigerian kitchen menu</p>
        <h1 className="mt-2 max-w-xl text-3xl font-semibold leading-tight md:text-4xl">
          Welcome to {settings.restaurant_name}
        </h1>
        <p className="mt-3 max-w-2xl text-stone-600">
          See today&apos;s jollof, soups, small chops, and drinks. Staff can sign in to hide sold-out dishes and change prices in one tap.
        </p>
        <div className="mt-6 flex flex-wrap gap-3">
          <Link to="/menu" className="tap inline-flex items-center rounded-full bg-ink px-5 text-sm font-semibold text-white">
            View live menu
          </Link>
          {user ? (
            <Link to="/dishes" className="tap inline-flex items-center rounded-full border border-line px-5 text-sm font-medium">
              Open kitchen tools
            </Link>
          ) : (
            <Link to="/account" className="tap inline-flex items-center rounded-full border border-line px-5 text-sm font-medium">
              Create a kitchen account
            </Link>
          )}
        </div>
      </div>
      <div className="grid gap-3 sm:grid-cols-3">
        {[
          ["Live for guests", "Search dishes, filter by spicy or vegetarian, and see what is still available."],
          ["Fast for the kitchen", "Mark suya sold out or raise weekend prices without opening a long form."],
          ["Easy to share", "Copy a link or download a QR code for tables and WhatsApp."],
        ].map(([title, copy]) => (
          <article key={title} className="rounded-2xl border border-line bg-card p-4 shadow-soft">
            <h2 className="font-semibold">{title}</h2>
            <p className="mt-2 text-sm text-stone-600">{copy}</p>
          </article>
        ))}
      </div>
    </section>
  );
}
