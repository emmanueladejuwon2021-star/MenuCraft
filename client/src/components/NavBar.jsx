import { NavLink } from "react-router-dom";
import { Home, BookOpen, ClipboardList, QrCode, UserRound, ShoppingBag, ConciergeBell, Warehouse, BadgeDollarSign } from "lucide-react";
import ThemeToggle from "./ThemeToggle.jsx";

export default function NavBar({ settings, user, theme, onToggleTheme, plateCount = 0 }) {
  const staff = user?.role === "staff";
  const guest = user?.role === "guest";
  const tabs = staff
    ? [
        { to: "/menu", label: "Board", icon: BookOpen },
        { to: "/dishes", label: "Dishes", icon: ClipboardList },
        { to: "/stock", label: "Stock", icon: Warehouse },
        { to: "/pricing", label: "Prices", icon: BadgeDollarSign },
        { to: "/orders", label: "Orders", icon: ConciergeBell },
        { to: "/share", label: "Share", icon: QrCode },
        { to: "/account", label: "Account", icon: UserRound },
      ]
    : [
        { to: "/", label: "Home", icon: Home, end: true },
        { to: "/menu", label: "Menu", icon: BookOpen },
        { to: "/plate", label: plateCount ? `Plate ${plateCount}` : "Plate", icon: ShoppingBag },
        { to: "/my-orders", label: "Orders", icon: ConciergeBell },
        { to: "/guest-account", label: "Account", icon: UserRound },
      ];

  return (
    <>
      <header className="sticky top-0 z-40 border-b border-line bg-card/95 backdrop-blur">
        <div className="mx-auto flex max-w-6xl items-center gap-3 px-4 py-3">
          <div className="min-w-0 flex-1">
            <p className="truncate text-base font-semibold tracking-tight sm:text-lg">{settings.restaurant_name}</p>
            <p className="truncate text-xs text-muted">{staff ? `Kitchen · ${user.name}` : guest ? `Guest · ${user.name}` : "Guest menu"}</p>
          </div>
          <ThemeToggle theme={theme} onToggle={onToggleTheme} />
          <nav className="hidden items-center justify-end gap-1 lg:flex" aria-label="Main">
            {tabs.map((tab) => (
              <NavLink key={tab.to} to={tab.to} end={tab.end} className={({ isActive }) => `inline-flex h-10 items-center gap-2 rounded-full px-3 text-sm font-medium ${isActive ? "bg-pine text-invert" : "text-muted hover:bg-paper"}`}>
                <tab.icon size={16} />
                {tab.label}
              </NavLink>
            ))}
          </nav>
        </div>
      </header>
      <nav className="safe-bottom fixed inset-x-0 bottom-0 z-40 border-t border-line bg-card/95 backdrop-blur lg:hidden" aria-label="Main">
        <div className="nav-scroll flex gap-1 overflow-x-auto px-2 pt-1">
          {tabs.map((tab) => (
            <NavLink key={tab.to} to={tab.to} end={tab.end} className={({ isActive }) => `flex min-h-11 min-w-[4.5rem] shrink-0 flex-col items-center justify-center gap-1 rounded-2xl px-2 py-2 text-[11px] leading-none ${isActive ? "bg-paper font-semibold text-pine" : "text-muted"}`}>
              <tab.icon size={18} aria-hidden="true" />
              <span>{tab.label}</span>
            </NavLink>
          ))}
        </div>
      </nav>
    </>
  );
}
