import { NavLink } from "react-router-dom";
import { BookOpen, ClipboardList, ToggleLeft, DollarSign, QrCode } from "lucide-react";

const TABS = [
  { to: "/menu", label: "Live Menu", icon: BookOpen },
  { to: "/dishes", label: "Dish Manager", icon: ClipboardList },
  { to: "/stock", label: "Stock Toggle", icon: ToggleLeft },
  { to: "/pricing", label: "Quick Pricing", icon: DollarSign },
  { to: "/share", label: "QR & Share", icon: QrCode },
];

export default function NavBar({ settings, source }) {
  return (
    <>
      <header className="sticky top-0 z-40 hidden border-b border-line bg-card/95 backdrop-blur md:block">
        <div className="mx-auto flex max-w-6xl items-center gap-4 px-4 py-3">
          <div className="min-w-0">
            <p className="truncate text-lg font-semibold">{settings.restaurant_name}</p>
            <p className="text-xs text-stone-500">
              {source === "live" ? "Live shared menu" : "Saved on this device"}
            </p>
          </div>
          <nav className="ml-auto flex gap-1">
            {TABS.map((tab) => (
              <NavLink
                key={tab.to}
                to={tab.to}
                className={({ isActive }) =>
                  `tap inline-flex items-center gap-2 rounded-full px-3 text-sm font-medium ${
                    isActive ? "bg-ink text-white" : "text-stone-600 hover:bg-paper"
                  }`
                }
              >
                <tab.icon size={16} />
                {tab.label}
              </NavLink>
            ))}
          </nav>
        </div>
      </header>

      <nav className="fixed inset-x-0 bottom-0 z-40 border-t border-line bg-card/95 backdrop-blur md:hidden">
        <div className="grid grid-cols-5">
          {TABS.map((tab) => (
            <NavLink
              key={tab.to}
              to={tab.to}
              className={({ isActive }) =>
                `tap flex flex-col items-center justify-center gap-1 px-1 py-2 text-[11px] ${
                  isActive ? "text-pine font-semibold" : "text-stone-500"
                }`
              }
            >
              <tab.icon size={18} />
              <span className="leading-none">{tab.label.split(" ")[0]}</span>
            </NavLink>
          ))}
        </div>
      </nav>
    </>
  );
}
