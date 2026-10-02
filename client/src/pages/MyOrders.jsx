import { Link } from "react-router-dom";
import { money } from "../lib/store";
import PageHeader from "../components/PageHeader.jsx";

const WORDS = {
  New: "Sent to the kitchen",
  Cooking: "On the fire",
  Ready: "Ready to pick up",
  Served: "Served",
};

function when(value) {
  const date = new Date(value);
  if (Number.isNaN(date.getTime())) return "Just now";
  return date.toLocaleString(undefined, { hour: "numeric", minute: "2-digit", month: "short", day: "numeric" });
}

export default function MyOrders({ settings, orders }) {
  const list = [...orders].sort((a, b) => new Date(b.created_at || 0) - new Date(a.created_at || 0));

  return (
    <section className="page-enter space-y-5 pb-28 md:pb-8">
      <PageHeader kicker="Guest" title="Your orders" hint="Newest first. Pay at the counter when you pick the food up." />
      <Link to="/menu" className="tap inline-flex h-12 w-full items-center justify-center rounded-full bg-ink px-5 text-sm font-semibold text-invert sm:w-auto">
        Order food
      </Link>
      {list.length === 0 ? (
        <div className="rounded-3xl border border-dashed border-line bg-card px-5 py-12 text-center">
          <p className="font-semibold text-ink">No orders yet</p>
          <p className="mt-2 text-sm text-muted">Open the board, add a dish to your plate, then send it.</p>
        </div>
      ) : (
        <div className="space-y-3">
          {list.map((order) => (
            <article key={order.id} className="rounded-3xl border border-line bg-card p-4 shadow-soft">
              <div className="flex items-start justify-between gap-3">
                <div>
                  <p className="font-semibold text-ink">{WORDS[order.status] || order.status || "Sent"}</p>
                  <p className="text-xs text-muted">{when(order.created_at)}</p>
                </div>
                <p className="shrink-0 font-semibold text-ink">{money(settings.currency_symbol, order.total)}</p>
              </div>
              {order.note ? <p className="mt-3 text-sm text-ink">Note: {order.note}</p> : null}
              <ul className="mt-3 space-y-1 text-sm text-muted">
                {(order.items || []).map((row, index) => (
                  <li key={`${row.id}-${index}`}>{row.qty} × {row.name}</li>
                ))}
              </ul>
              <p className="mt-3 text-xs text-muted">Pay at the counter{order.pay_ref ? ` · ticket ${order.pay_ref}` : ""}</p>
            </article>
          ))}
        </div>
      )}
    </section>
  );
}
