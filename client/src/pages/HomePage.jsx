import { Link } from "react-router-dom";
import PageHeader from "../components/PageHeader.jsx";

export default function HomePage({ settings, user }) {
  const staff = user?.role === "staff";
  return (
    <section className="page-enter space-y-5">
      <PageHeader kicker="Nigerian kitchen" title={`Welcome to ${settings.restaurant_name}`} hint="Guests order and pay. Kitchen staff cook from the order board." />
      <div className="grid gap-4 md:grid-cols-2">
        <Link to="/menu" className="rounded-3xl border border-line bg-card p-6 shadow-soft">
          <p className="text-xs font-semibold uppercase tracking-[0.16em] text-clay">Guest</p>
          <h2 className="mt-2 text-2xl font-semibold text-ink">I want to eat</h2>
          <p className="mt-2 text-sm leading-relaxed text-muted">Browse the menu, fill a plate, sign in as a guest, and pay.</p>
          <span className="tap mt-6 inline-flex items-center rounded-full bg-ink px-5 text-sm font-semibold text-invert">Open the menu</span>
        </Link>
        <Link to={staff ? "/orders" : "/account"} className="rounded-3xl border border-line bg-card p-6 shadow-soft">
          <p className="text-xs font-semibold uppercase tracking-[0.16em] text-pine">Kitchen</p>
          <h2 className="mt-2 text-2xl font-semibold text-ink">{staff ? "See incoming orders" : "I run the kitchen"}</h2>
          <p className="mt-2 text-sm leading-relaxed text-muted">{staff ? `Signed in as ${user.name}. Open the board and start cooking.` : "Sign in to see paid orders, stock, and prices."}</p>
          <span className="tap mt-6 inline-flex items-center rounded-full border border-line bg-paper px-5 text-sm font-semibold text-ink">{staff ? "Open orders" : "Staff sign in"}</span>
        </Link>
      </div>
    </section>
  );
}
