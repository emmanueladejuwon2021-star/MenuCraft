import { money } from "../lib/store";
import KitchenNav from "../components/KitchenNav";

const NEXT = {
  New: { label: "Start cooking", status: "Cooking" },
  Cooking: { label: "Mark ready", status: "Ready" },
  Ready: { label: "Mark served", status: "Served" },
};

export default function OrdersBoard({ settings, orders, onStatus }) {
  return (
    <section className="page-enter space-y-5">
      <KitchenNav title="Kitchen orders" hint="New paid orders land here. Move each one as you cook." />
      {orders.length === 0 ? (
        <div className="rounded-3xl border border-dashed border-line bg-card px-5 py-12 text-center text-sm text-muted">No guest orders yet.</div>
      ) : (
        <div className="grid gap-3 md:grid-cols-2">
          {orders.map((order) => {
            const action = NEXT[order.status];
            return (
              <article key={order.id} className="rounded-3xl border border-line bg-card p-4 shadow-soft">
                <div className="flex items-start justify-between gap-3">
                  <div>
                    <p className="font-semibold text-ink">{order.guest_name}</p>
                    <p className="text-xs text-muted">{order.phone || order.guest_email}</p>
                  </div>
                  <span className="rounded-full bg-paper px-3 py-1 text-xs font-semibold text-clay">{order.status}</span>
                </div>
                <ul className="mt-3 space-y-1 text-sm text-ink">
                  {order.items.map((row) => (
                    <li key={row.id}>{row.qty} × {row.name}</li>
                  ))}
                </ul>
                {order.note && <p className="mt-2 text-sm text-muted">{order.note}</p>}
                <p className="mt-3 text-sm font-semibold text-ink">{money(settings.currency_symbol, order.total)} · Paid</p>
                {action && (
                  <button type="button" onClick={() => onStatus(order.id, action.status)} className="tap mt-3 h-11 w-full rounded-full bg-ink text-sm font-semibold text-invert">{action.label}</button>
                )}
              </article>
            );
          })}
        </div>
      )}
    </section>
  );
}
