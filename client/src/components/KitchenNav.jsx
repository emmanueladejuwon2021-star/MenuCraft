import { NavLink } from "react-router-dom";

const LINKS = [
  { to: "/dishes", label: "Dishes" },
  { to: "/stock", label: "Stock" },
  { to: "/pricing", label: "Prices" },
  { to: "/orders", label: "Orders" },
];

export default function KitchenNav({ title, hint }) {
  return (
    <div className="space-y-3">
      {title && (
        <div>
          <h1 className="text-xl font-semibold text-ink">{title}</h1>
          {hint && <p className="mt-1 text-sm text-muted">{hint}</p>}
        </div>
      )}
      <div className="flex gap-2 overflow-x-auto pb-1">
        {LINKS.map((link) => (
          <NavLink
            key={link.to}
            to={link.to}
            className={({ isActive }) =>
              `tap h-10 shrink-0 rounded-full px-4 text-sm font-medium ${
                isActive ? "bg-pine text-invert" : "border border-line bg-card text-ink"
              }`
            }
          >
            {link.label}
          </NavLink>
        ))}
      </div>
    </div>
  );
}
