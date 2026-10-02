import { useMemo, useState } from "react";
import { Link } from "react-router-dom";
import { money } from "../lib/store";
import KitchenNav from "../components/KitchenNav";

const NEXT = {
  New: { label: "Start cooking", status: "Cooking" },
  Cooking: { label: "Mark ready", status: "Ready" },
  Ready: { label: "Mark served", status: "Served" },
};

const RANK = { New: 0, Cooking: 1, Ready: 2, Served: 3 };

function when(value) {
  const date = new Date(value);
  if (Number.isNaN(date.getTime())) return "Just now";
  return date.toLocaleString(undefined, { hour: "numeric", minute: "2-digit", month: "short", day: "numeric" });
}

function contact(order) {
  const email = order.guest_email || "";
  if (order.phone) return order.phone;
  if (email.startsWith("walkin:")) return "Walk-in";
  return email || "No phone";
}

export default function OrdersBoard({ settings, orders, onStatus }) {
  const [filter, setFilter] = useState("open");
  const [pendingId, setPendingId] = useState(null);
  const [error, setError] = useState("");

  const sorted = useMemo(() => {
    return [...orders].sort((a, b) => {
      const rank = (RANK[a.status] ?? 9) - (RANK[b.status] ?? 9);
      if (rank !== 0) return rank;
      return new Date(b.created_at || 0) - new Date(a.created_at || 0);
    });
  }, [orders]);

  const openCount = sorted.filter((order) => order.status !== "Served").length;
  const visible = sorted.filter((order) => {
    if (filter === "open") return order.status !== "Served";
    if (filter === "served") return order.status === "Served";
    return true;
  });

  async function advance(order, status) {
    if (pendingId) return;
    setError("");
    setPendingId(order.id);
    try {
      await onStatus(order.id, status);
    } catch (err) {
      setError(err.message || "Could not update that order.");
    } finally {
      setPendingId(null);
    }
  }

  return (
    <section className="page-enter space-y-5">
      <KitchenNav title="Kitchen orders" hint="New orders land at the top. Move each one as you cook. Pay is still at the counter." />
      <div className="flex flex-wrap items-center gap-2">
        {[
          ["open", `Open (${openCount})`],
          ["served", "Served"],
          ["all", "All"],
        ].map(([key, label]) => (
          <button key={key} type="button" onClick={() => setFilter(key)} className={`tap h-11 rounded-full px-4 text-sm font-medium ${filter === key ? "bg-pine text-invert" : "border border-line bg-card text-ink"}`}>
            {label}
          </button>
        ))}
      </div>
      {error ? <p className="rounded-2xl border border-line bg-card px-4 py-3 text-sm text-danger">{error}</p> : null}
      {visible.length === 0 ? (
        <div className="rounded-3xl border border-dashed border-line bg-card px-5 py-12 text-center">
          <p className="font-semibold text-ink">{filter === "served" ? "Nothing served yet" : "No open orders"}</p>
          <p className="mt-2 text-sm text-muted">Tickets show up here after a guest sends a plate.</p>
          <Link to="/share" className="tap mt-5 inline-flex items-center justify-center rounded-full bg-pine px-5 text-sm font-semibold text-invert">Share the menu</Link>
        </div>
      ) : (
        <div className="grid gap-3 md:grid-cols-2">
          {visible.map((order) => {
            const action = NEXT[order.status];
            const busy = pendingId === order.id;
            return (
              <article key={order.id} className="rounded-3xl border border-line bg-card p-4 shadow-soft">
                <div className="flex items-start justify-between gap-3">
                  <div className="min-w-0">
                    <p className="truncate font-semibold text-ink">{order.guest_name || "Guest"}</p>
                    <p className="truncate text-xs text-muted">{contact(order)} · {when(order.created_at)}</p>
                  </div>
                  <span className={`shrink-0 rounded-full px-3 py-1 text-xs font-semibold ${order.status === "Ready" ? "bg-pine text-invert" : "bg-paper text-clay"}`}>{order.status || "New"}</span>
                </div>
                {order.note ? <p className="mt-3 rounded-2xl bg-paper px-3 py-2 text-sm font-medium text-ink">Note: {order.note}</p> : null}
                <ul className="mt-3 space-y-1 text-sm text-ink">
                  {(order.items || []).map((row, index) => (
                    <li key={`${row.id}-${index}`}>{row.qty} × {row.name}</li>
                  ))}
                </ul>
                <p className="mt-3 text-sm font-semibold text-ink">{money(settings.currency_symbol, order.total)} · Pay at the counter</p>
                {action ? (
                  <button type="button" disabled={busy || pendingId} onClick={() => advance(order, action.status)} className="tap mt-3 h-12 w-full rounded-full bg-pine text-sm font-semibold text-invert disabled:opacity-60">
                    {busy ? "Saving…" : action.label}
                  </button>
                ) : (
                  <p className="mt-3 text-sm text-muted">Served</p>
                )}
              </article>
            );
          })}
        </div>
      )}
    </section>
  );
}
