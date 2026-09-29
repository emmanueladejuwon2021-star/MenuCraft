import { Link } from "react-router-dom";
import { money } from "../lib/store";

export default function MyOrders({ settings, orders }) {
  return (
    <section className="page-enter space-y-4">
      <div>
        <h1 className="text-xl font-semibold">Your orders</h1>
        <p className="mt-1 text-sm text-muted">See when the kitchen starts your food and when it is ready.</p>
      </div>
      {orders.length === 0 ? (
        <div className="rounded-3xl border border-dashed border-line bg-card px-5 py-10 text-center">
          <p className="text-sm text-muted">No orders yet.</p>
          <Link to="/menu" className="tap mt-4 inline-flex rounded-full bg-ink px-5 text-sm font-semibold text-invert">Order food</Link>
        </div>
      ) : (
        <div className="space-y-3">
          {orders.map((order) => (
            <article key={order.id} className="rounded-2xl border border-line bg-card p-4 shadow-soft">
              <div className="flex items-start justify-between gap-3">
                <div>
                  <p className="font-semibold">{money(settings.currency_symbol, order.total)}</p>
                  <p className="text-xs text-muted">{new Date(order.created_at).toLocaleString()}</p>
                </div>
                <span className="rounded-full bg-paper px-3 py-1 text-xs font-semibold text-pine">{order.status}</span>
              </div>
              <p className="mt-2 text-sm text-muted">{order.items.map((row) => `${row.qty} × ${row.name}`).join(", ")}</p>
              <p className="mt-2 text-xs text-muted">{order.paid ? `Paid · ${order.pay_ref}` : "Not paid"}</p>
            </article>
          ))}
        </div>
      )}
    </section>
  );
}
