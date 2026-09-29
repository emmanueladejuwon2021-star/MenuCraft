import { NavLink } from "react-router-dom";
import { Home, BookOpen, ClipboardList, QrCode, UserRound } from "lucide-react";
import ThemeToggle from "./ThemeToggle.jsx";

const GUEST_TABS = [
  { to: "/", label: "Home", short: "Home", icon: Home, end: true },
  { to: "/menu", label: "Menu", short: "Menu", icon: BookOpen },
];

const STAFF_TABS = [
  { to: "/", label: "Home", short: "Home", icon: Home, end: true },
  { to: "/menu", label: "Guest menu", short: "Menu", icon: BookOpen },
  { to: "/dishes", label: "Kitchen", short: "Kitchen", icon: ClipboardList },
  { to: "/share", label: "Share", short: "Share", icon: QrCode },
  { to: "/account", label: "Account", short: "Account", icon: UserRound },
];

export default function NavBar({ settings, source, user, theme, onToggleTheme }) {
  const tabs = user ? STAFF_TABS : GUEST_TABS;
  return (
    <>
      <header className="sticky top-0 z-40 hidden border-b border-line bg-card/95 backdrop-blur md:block">
        <div className="mx-auto flex max-w-6xl items-center gap-4 px-4 py-3">
          <div className="min-w-0">
            <p className="truncate text-lg font-semibold">{settings.restaurant_name}</p>
            <p className="text-xs text-muted">{user ? `Kitchen · ${user.name}` : "Guest menu"}</p>
          </div>
          <nav className="ml-auto flex flex-wrap items-center justify-end gap-1">
            <ThemeToggle theme={theme} onToggle={onToggleTheme} />
            {tabs.map((tab) => (
              <NavLink
                key={tab.to}
                to={tab.to}
                end={tab.end}
                className={({ isActive }) => `tap inline-flex items-center gap-2 rounded-full px-3 text-sm font-medium ${isActive ? "bg-ink text-invert" : "text-muted hover:bg-paper"}`}
              >
                <tab.icon size={16} />
                {tab.label}
              </NavLink>
            ))}
          </nav>
        </div>
      </header>
      <nav className="fixed inset-x-0 bottom-0 z-40 border-t border-line bg-card/95 backdrop-blur md:hidden">
        <div className={`grid ${user ? "grid-cols-5" : "grid-cols-2"}`}>
          {tabs.map((tab) => (
            <NavLink
              key={tab.to}
              to={tab.to}
              end={tab.end}
              className={({ isActive }) => `tap flex flex-col items-center justify-center gap-1 px-1 py-2 text-[11px] ${isActive ? "font-semibold text-pine" : "text-muted"}`}
            >
              <tab.icon size={18} />
              <span className="leading-none">{tab.short}</span>
            </NavLink>
          ))}
        </div>
      </nav>
    </>
  );
}
