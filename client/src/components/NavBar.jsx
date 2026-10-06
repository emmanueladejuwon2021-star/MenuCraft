import { NavLink } from "react-router-dom";
import { Home, BookOpen, ClipboardList, QrCode, UserRound, ShoppingBag, ConciergeBell, Warehouse, BadgeDollarSign } from "lucide-react";
import ThemeToggle from "./ThemeToggle.jsx";

export default function NavBar({ settings, user, theme, onToggleTheme, plateCount = 0 }) {
  const staff = user?.role === "staff";
  const guest = user?.role === "guest";
  const tabs = staff
    ? [
        { to: "/menu", label: "Board", short: "Board", icon: BookOpen },
        { to: "/dishes", label: "Dishes", short: "Dishes", icon: ClipboardList },
        { to: "/stock", label: "Stock", short: "Stock", icon: Warehouse },
        { to: "/pricing", label: "Prices", short: "Prices", icon: BadgeDollarSign, desktopOnly: true },
        { to: "/orders", label: "Orders", short: "Orders", icon: ConciergeBell },
        { to: "/share", label: "Share", short: "Share", icon: QrCode, desktopOnly: true },
        { to: "/account", label: "Account", short: "Account", icon: UserRound },
      ]
    : [
        { to: "/", label: "Home", short: "Home", icon: Home, end: true },
        { to: "/menu", label: "Menu", short: "Menu", icon: BookOpen },
        { to: "/plate", label: plateCount ? `Plate (${plateCount})` : "Plate", short: plateCount ? `Plate ${plateCount}` : "Plate", icon: ShoppingBag },
        { to: "/my-orders", label: "My orders", short: "Orders", icon: ConciergeBell },
        { to: "/guest-account", label: "Account", short: "Account", icon: UserRound },
      ];
  const phoneTabs = tabs.filter((tab) => !tab.desktopOnly);
  const extra = staff
    ? [
        { to: "/pricing", label: "Prices", icon: BadgeDollarSign },
        { to: "/share", label: "Share", icon: QrCode },
      ]
    : [];

  return (
    <>
      <header className="sticky top-0 z-40 border-b border-line bg-card/95 backdrop-blur">
        <div className="mx-auto flex max-w-6xl items-center gap-3 px-4 py-3">
          <div className="min-w-0 flex-1">
            <p className="truncate text-base font-semibold tracking-tight sm:text-lg">{settings.restaurant_name}</p>
            <p className="truncate text-xs text-muted">{staff ? `Kitchen · ${user.name}` : guest ? `Guest · ${user.name}` : "Guest menu"}</p>
          </div>
          {extra.map((tab) => (
            <NavLink key={tab.to} to={tab.to} aria-label={tab.label} className={({ isActive }) => `tap inline-flex items-center justify-center rounded-full lg:hidden ${isActive ? "bg-pine text-invert" : "text-muted"}`} style={{ width: 44 }}>
              <tab.icon size={18} />
            </NavLink>
          ))}
          <ThemeToggle theme={theme} onToggle={onToggleTheme} />
          <nav className="hidden items-center justify-end gap-1 lg:flex">
            {tabs.map((tab) => (
              <NavLink key={tab.to} to={tab.to} end={tab.end} className={({ isActive }) => `tap inline-flex items-center gap-2 rounded-full px-3 text-sm font-medium ${isActive ? "bg-pine text-invert" : "text-muted hover:bg-paper"}`}>
                <tab.icon size={16} />
                {tab.label}
              </NavLink>
            ))}
          </nav>
        </div>
      </header>
      <nav className="safe-bottom fixed inset-x-0 bottom-0 z-40 border-t border-line bg-card/95 backdrop-blur lg:hidden" aria-label="Main">
        <div className="grid grid-cols-5 gap-1 px-2 pt-1">
          {phoneTabs.map((tab) => (
            <NavLink key={tab.to} to={tab.to} end={tab.end} className={({ isActive }) => `tap flex flex-col items-center justify-center gap-1 rounded-2xl px-1 py-2 text-xs ${isActive ? "bg-paper font-semibold text-pine" : "text-muted"}`}>
              <tab.icon size={18} />
              <span className="max-w-full truncate leading-none">{tab.short}</span>
            </NavLink>
          ))}
        </div>
      </nav>
    </>
  );
}
