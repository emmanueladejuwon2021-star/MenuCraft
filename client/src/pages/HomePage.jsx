import { Link } from "react-router-dom";

export default function HomePage({ settings, user }) {
  return (
    <section className="page-enter space-y-6">
      <div className="rounded-3xl border border-line bg-card px-6 py-8 shadow-soft md:px-10">
        <p className="text-sm font-semibold tracking-wide text-clay">Iya Bisi style kitchen</p>
        <h1 className="mt-2 text-3xl font-semibold leading-tight md:text-5xl">Welcome to {settings.restaurant_name}</h1>
        <p className="mt-3 max-w-2xl text-muted">Choose how you want to use this site. Guests only see food and prices. Kitchen staff sign in to change the menu.</p>
      </div>
      <div className="grid gap-4 md:grid-cols-2">
        <Link to="/menu" className="rounded-3xl border border-line bg-card p-6 shadow-soft transition hover:-translate-y-0.5">
          <p className="text-sm font-semibold text-clay">Guest</p>
          <h2 className="mt-2 text-2xl font-semibold">I want to order</h2>
          <p className="mt-2 text-sm leading-relaxed text-muted">See jollof, soups, small chops, and drinks. No sign-in needed.</p>
          <span className="tap mt-6 inline-flex items-center rounded-full bg-ink px-5 text-sm font-semibold text-invert">Open the menu</span>
        </Link>
        <Link to={user ? "/dishes" : "/account"} className="rounded-3xl border border-line bg-card p-6 shadow-soft transition hover:-translate-y-0.5">
          <p className="text-sm font-semibold text-pine">Kitchen staff</p>
          <h2 className="mt-2 text-2xl font-semibold">{user ? "Back to the kitchen" : "I run the kitchen"}</h2>
          <p className="mt-2 text-sm leading-relaxed text-muted">
            {user ? `Signed in as ${user.name}. Add dishes, hide sold-out food, and change prices.` : "Sign in or create a kitchen account to manage dishes, stock, prices, and the share code."}
          </p>
          <span className="tap mt-6 inline-flex items-center rounded-full border border-line bg-paper px-5 text-sm font-semibold text-ink">
            {user ? "Open kitchen tools" : "Staff sign in"}
          </span>
        </Link>
      </div>
    </section>
  );
}
