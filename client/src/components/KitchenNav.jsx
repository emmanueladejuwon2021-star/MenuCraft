import { NavLink } from "react-router-dom";

const LINKS = [
  { to: "/dishes", label: "Dishes" },
  { to: "/stock", label: "Stock" },
  { to: "/pricing", label: "Prices" },
];

export default function KitchenNav() {
  return (
    <div className="mb-4 flex gap-2">
      {LINKS.map((link) => (
        <NavLink
          key={link.to}
          to={link.to}
          className={({ isActive }) =>
            `tap rounded-full px-4 text-sm ${isActive ? "bg-ink text-white" : "border border-line bg-card"}`
          }
        >
          {link.label}
        </NavLink>
      ))}
    </div>
  );
}
