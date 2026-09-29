import { useEffect, useState } from "react";
import { Navigate, Route, Routes, useLocation, useNavigate } from "react-router-dom";
import NavBar from "./components/NavBar.jsx";
import Toast from "./components/Toast.jsx";
import HomePage from "./pages/HomePage.jsx";
import LiveMenu from "./pages/LiveMenu.jsx";
import DishManager from "./pages/DishManager.jsx";
import StockToggle from "./pages/StockToggle.jsx";
import QuickPricing from "./pages/QuickPricing.jsx";
import SharePage from "./pages/SharePage.jsx";
import AccountPage from "./pages/AccountPage.jsx";
import {
  bulkUpdatePrices,
  createCategory,
  createItem,
  loadMenu,
  removeCategory,
  removeItem,
  saveSettings,
  setItemStatus,
  updateItem,
} from "./lib/store.js";
import { createAccount, readSession, signIn, signOut, updateAccount } from "./lib/auth.js";
import { readTheme, toggleTheme } from "./lib/theme.js";
import ThemeToggle from "./components/ThemeToggle.jsx";
import ConfirmDialog from "./components/ConfirmDialog.jsx";

function Guard({ user, children }) {
  if (!user) return <Navigate to="/account" replace />;
  return children;
}

export default function App() {
  const navigate = useNavigate();
  const location = useLocation();
  const [user, setUser] = useState(readSession());
  const [menu, setMenu] = useState({
    source: "local",
    settings: { restaurant_name: "Iya Bisi Kitchen", currency_symbol: "₦" },
    categories: [],
    items: [],
  });
  const [toast, setToast] = useState(null);
  const [showForm, setShowForm] = useState(false);
  const [loading, setLoading] = useState(true);
  const [theme, setTheme] = useState(readTheme);
  const [confirmBox, setConfirmBox] = useState(null);
  const [toastTimer, setToastTimer] = useState(null);

  function notify(text, tone = "ok", extra = {}) {
    if (toastTimer) window.clearTimeout(toastTimer);
    setToast({ text, tone, ...extra });
    const timer = window.setTimeout(() => setToast(null), extra.onAction ? 6000 : 2400);
    setToastTimer(timer);
  }

  function handleTheme() {
    setTheme((current) => toggleTheme(current));
  }

  async function refresh() {
    setMenu(await loadMenu());
    setLoading(false);
  }

  useEffect(() => {
    refresh();
  }, []);

  async function handleCreate(input) {
    try {
      notify((await createItem(input)).message);
      await refresh();
    } catch (error) {
      notify(error.message || "Something went wrong. Changes were not saved.", "error");
    }
  }

  async function handleUpdate(id, input) {
    try {
      notify((await updateItem(id, input)).message);
      await refresh();
    } catch (error) {
      notify(error.message || "Something went wrong. Changes were not saved.", "error");
    }
  }

  function requestDelete(item) {
    const dish = item && item.id ? item : menu.items.find((row) => Number(row.id) === Number(item));
    if (!dish) return;
    setConfirmBox({
      title: `Remove ${dish.name}?`,
      body: "It will leave the live menu. You can tap Undo for a few seconds after that.",
      yesLabel: "Remove dish",
      onYes: () => handleDelete(dish),
    });
  }

  async function handleDelete(item) {
    try {
      const snapshot = { ...item };
      const result = await removeItem(item.id);
      await refresh();
      notify(result.message, "ok", {
        actionLabel: "Undo",
        onAction: async () => {
          setToast(null);
          await createItem({
            name: snapshot.name,
            description: snapshot.description,
            price: snapshot.price,
            category_id: snapshot.category_id,
            tags: snapshot.tags,
            prep_time: snapshot.prep_time,
            image_url: snapshot.image_url,
            is_available: snapshot.is_available,
          });
          await refresh();
          notify("Dish put back on the menu");
        },
      });
    } catch (error) {
      notify(error.message || "Something went wrong. Changes were not saved.", "error");
    }
  }

  function requestRemoveCategory(category) {
    setConfirmBox({
      title: `Remove ${category.name}?`,
      body: "This also removes every dish in that category.",
      yesLabel: "Remove category",
      onYes: () => handleRemoveCategory(category.id),
    });
  }

  async function handleRemoveCategory(id) {
    try {
      notify((await removeCategory(id)).message);
      await refresh();
    } catch (error) {
      notify(error.message || "Something went wrong. Changes were not saved.", "error");
    }
  }

  async function handleToggle(id, isAvailable) {
    const previous = menu.items;
    setMenu((current) => ({
      ...current,
      items: current.items.map((row) => (Number(row.id) === Number(id) ? { ...row, is_available: isAvailable } : row)),
    }));
    try {
      notify((await setItemStatus(id, isAvailable)).message);
    } catch {
      setMenu((current) => ({ ...current, items: previous }));
      notify("Something went wrong. Changes were not saved.", "error");
    }
  }

  function requestPrices(payload) {
    setConfirmBox({
      title: "Update these prices?",
      body: "Every dish in this category will change. You can still edit one dish later.",
      yesLabel: "Update prices",
      onYes: () => handlePrices(payload),
    });
  }

  async function handlePrices(payload) {
    const previous = menu.items;
    setMenu((current) => ({
      ...current,
      items: current.items.map((row) => {
        if (Number(row.category_id) !== Number(payload.categoryId)) return row;
        const next =
          payload.mode === "amount"
            ? Math.max(0, Number((row.price + payload.amount).toFixed(2)))
            : Math.max(0, Number((row.price * (1 + payload.amount / 100)).toFixed(2)));
        return { ...row, price: next };
      }),
    }));
    try {
      notify((await bulkUpdatePrices(payload)).message);
      await refresh();
    } catch {
      setMenu((current) => ({ ...current, items: previous }));
      notify("Something went wrong. Changes were not saved.", "error");
    }
  }

  async function handleSettings(settings) {
    try {
      notify((await saveSettings(settings)).message);
      await refresh();
    } catch (error) {
      notify(error.message || "Something went wrong. Changes were not saved.", "error");
    }
  }

  async function handleCategory(name) {
    try {
      notify((await createCategory(name)).message);
      await refresh();
    } catch (error) {
      notify(error.message || "Something went wrong. Changes were not saved.", "error");
    }
  }

  async function handleSignup(form) {
    try {
      const nextUser = await createAccount(form);
      setUser(nextUser);
      await saveSettings({ restaurant_name: nextUser.restaurant_name, currency_symbol: menu.settings.currency_symbol || "₦" });
      await refresh();
      notify("Account created. Welcome!");
      navigate("/dishes");
    } catch (error) {
      notify(error.message || "Could not create the account.", "error");
    }
  }

  async function handleSignIn(form) {
    try {
      const nextUser = await signIn(form);
      setUser(nextUser);
      notify(`Welcome back, ${nextUser.name}`);
      navigate("/dishes");
    } catch (error) {
      notify(error.message || "Could not sign you in.", "error");
    }
  }

  async function handleAccountUpdate(form) {
    try {
      const nextUser = await updateAccount(form);
      setUser(nextUser);
      await saveSettings({ restaurant_name: nextUser.restaurant_name, currency_symbol: menu.settings.currency_symbol || "₦" });
      await refresh();
      notify("Saved!");
    } catch (error) {
      notify(error.message || "Could not save account details.", "error");
    }
  }

  function handleSignOut() {
    signOut();
    setUser(null);
    notify("Signed out");
    navigate("/");
  }

  return (
    <div className="min-h-screen bg-paper pb-24 md:pb-8">
      <NavBar settings={menu.settings} source={menu.source} user={user} theme={theme} onToggleTheme={handleTheme} />
      <Toast toast={toast} />
      <ConfirmDialog box={confirmBox} onClose={() => setConfirmBox(null)} />
      <main className="mx-auto max-w-6xl px-4 py-4">
        <div className="mb-4 flex items-center justify-between gap-3 md:hidden">
          <div>
            <h1 className="text-lg font-semibold">{menu.settings.restaurant_name}</h1>
            <p className="text-xs text-muted">{user ? `Kitchen · ${user.name}` : "Guest menu"}</p>
          </div>
          <ThemeToggle theme={theme} onToggle={handleTheme} />
        </div>
        {loading && location.pathname !== "/" ? (
          <p className="text-sm text-muted">Loading menu…</p>
        ) : (
          <Routes>
            <Route path="/" element={<HomePage settings={menu.settings} user={user} />} />
            <Route path="/menu" element={<LiveMenu settings={menu.settings} categories={menu.categories} items={menu.items} onAddDish={null} />} />
            <Route path="/dishes" element={<Guard user={user}><DishManager settings={menu.settings} categories={menu.categories} items={menu.items} showForm={showForm} setShowForm={setShowForm} onCreate={handleCreate} onUpdate={handleUpdate} onDelete={requestDelete} onCreateCategory={handleCategory} onRemoveCategory={requestRemoveCategory} /></Guard>} />
            <Route path="/stock" element={<Guard user={user}><StockToggle items={menu.items} categories={menu.categories} onToggle={handleToggle} /></Guard>} />
            <Route path="/pricing" element={<Guard user={user}><QuickPricing settings={menu.settings} categories={menu.categories} items={menu.items} onApply={requestPrices} /></Guard>} />
            <Route path="/share" element={<Guard user={user}><SharePage settings={menu.settings} onSaveSettings={handleSettings} user={user} /></Guard>} />
            <Route path="/account" element={<AccountPage user={user} onCreate={handleSignup} onSignIn={handleSignIn} onUpdate={handleAccountUpdate} onSignOut={handleSignOut} />} />
          </Routes>
        )}
      </main>
    </div>
  );
}
