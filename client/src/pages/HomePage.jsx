import { Link } from "react-router-dom";
import PageHeader from "../components/PageHeader.jsx";

export default function HomePage({ settings }) {
  return (
    <section className="page-enter space-y-5">
      <PageHeader
        kicker="Nigerian kitchen"
        title={`Welcome to ${settings.restaurant_name}`}
        hint="See today's food, add dishes to your plate, and pay when you are ready."
      />
      <div className="rounded-3xl border border-line bg-card p-6 shadow-soft">
        <h2 className="text-2xl font-semibold text-ink">Ready to eat?</h2>
        <p className="mt-2 max-w-xl text-sm leading-relaxed text-muted">
          Open the live menu to browse jollof, soups, small chops, and drinks. Create a guest account only when you are ready to pay.
        </p>
        <div className="mt-6 flex flex-wrap gap-3">
          <Link to="/menu" className="tap inline-flex items-center rounded-full bg-ink px-6 text-sm font-semibold text-invert">
            Open the menu
          </Link>
          <Link to="/guest-account" className="tap inline-flex items-center rounded-full border border-line bg-paper px-6 text-sm font-semibold text-ink">
            Guest account
          </Link>
        </div>
      </div>
    </section>
  );
}
