import { Link } from "react-router-dom";

export default function HomePage({ settings, user }) {
  const staff = user?.role === "staff";
  return (
    <section className="page-enter space-y-6">
      <div className="rounded-3xl border border-line bg-card px-6 py-8 shadow-soft md:px-10">
        <p className="text-sm font-semibold tracking-wide text-clay">Nigerian kitchen</p>
        <h1 className="mt-2 text-3xl font-semibold leading-tight md:text-5xl">Welcome to {settings.restaurant_name}</h1>
        <p className="mt-3 max-w-2xl text-muted">Guests order and pay. Kitchen staff see those orders and cook them in order.</p>
      </div>
      <div className="grid gap-4 md:grid-cols-2">
        <Link to="/menu" className="rounded-3xl border border-line bg-card p-6 shadow-soft transition hover:-translate-y-0.5">
          <p className="text-sm font-semibold text-clay">Guest</p>
          <h2 className="mt-2 text-2xl font-semibold">I want to eat</h2>
          <p className="mt-2 text-sm leading-relaxed text-muted">Browse the menu, add food to your plate, create a guest account, and pay.</p>
          <span className="tap mt-6 inline-flex items-center rounded-full bg-ink px-5 text-sm font-semibold text-invert">Open the menu</span>
        </Link>
        <Link to={staff ? "/orders" : "/account"} className="rounded-3xl border border-line bg-card p-6 shadow-soft transition hover:-translate-y-0.5">
          <p className="text-sm font-semibold text-pine">Kitchen staff</p>
          <h2 className="mt-2 text-2xl font-semibold">{staff ? "See incoming orders" : "I run the kitchen"}</h2>
          <p className="mt-2 text-sm leading-relaxed text-muted">
            {staff ? `Signed in as ${user.name}. Open the order board and start cooking.` : "Sign in to see paid orders, update stock, and change prices."}
          </p>
          <span className="tap mt-6 inline-flex items-center rounded-full border border-line bg-paper px-5 text-sm font-semibold text-ink">
            {staff ? "Open orders" : "Staff sign in"}
          </span>
        </Link>
      </div>
    </section>
  );
}
