import { Link } from "react-router-dom";

export default function NotFound() {
  return (
    <section className="page-enter mx-auto max-w-lg rounded-[2rem] border border-dashed border-line bg-card px-6 py-12 text-center shadow-soft">
      <p className="text-xs font-semibold uppercase tracking-[0.18em] text-clay">Wrong turn</p>
      <h1 className="mt-2 text-2xl font-semibold text-ink">That page is not here</h1>
      <p className="mt-2 text-sm leading-relaxed text-muted">The link may be old, or the address was typed wrong. Use the menu or go back home.</p>
      <div className="mt-6 flex flex-wrap items-center justify-center gap-3">
        <Link to="/" className="tap inline-flex items-center justify-center rounded-full bg-ink px-5 text-sm font-semibold text-invert">
          Home
        </Link>
        <Link to="/menu" className="tap inline-flex items-center justify-center rounded-full border border-line bg-paper px-5 text-sm font-medium text-ink">
          Today’s board
        </Link>
      </div>
    </section>
  );
}
