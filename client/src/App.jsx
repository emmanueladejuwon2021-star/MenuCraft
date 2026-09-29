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
  removeItem,
  saveSettings,
  setItemStatus,
  updateItem,
} from "./lib/store.js";
import { createAccount, readSession, signIn, signOut, updateAccount } from "./lib/auth.js";

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

  function notify(text, tone = "ok") {
    setToast({ text, tone });
    window.setTimeout(() => setToast(null), 2400);
  }

  async function refresh() {
    const next = await loadMenu();
    setMenu(next);
    setLoading(false);
  }

  useEffect(() => {
    refresh();
  }, []);

  async function handleCreate(input) {
    try {
      const result = await createItem(input);
      notify(result.message);
      await refresh();
    } catch (error) {
      notify(error.message || "Something went wrong. Changes were not saved.", "error");
    }
  }

  async function handleUpdate(id, input) {
    try {
      const result = await updateItem(id, input);
      notify(result.message);
      await refresh();
    } catch (error) {
      notify(error.message || "Something went wrong. Changes were not saved.", "error");
    }
  }

  async function handleDelete(id) {
    try {
      const result = await removeItem(id);
      notify(result.message);
      await refresh();
    } catch (error) {
      notify(error.message || "Something went wrong. Changes were not saved.", "error");
    }
  }

  async function handleToggle(id, isAvailable) {
    const previous = menu.items;
    setMenu((current) => ({
      ...current,
      items: current.items.map((item) => (Number(item.id) === Number(id) ? { ...item, is_available: isAvailable } : item)),
    }));
    try {
      const result = await setItemStatus(id, isAvailable);
      notify(result.message);
    } catch (error) {
      setMenu((current) => ({ ...current, items: previous }));
      notify("Something went wrong. Changes were not saved.", "error");
    }
  }

  async function handlePrices(payload) {
    if (!window.confirm("Update prices for this whole category?")) return;
    const previous = menu.items;
    setMenu((current) => ({
      ...current,
      items: current.items.map((item) => {
        if (Number(item.category_id) !== Number(payload.categoryId)) return item;
        const next =
          payload.mode === "amount"
            ? Math.max(0, Number((item.price + payload.amount).toFixed(2)))
            : Math.max(0, Number((item.price * (1 + payload.amount / 100)).toFixed(2)));
        return { ...item, price: next };
      }),
    }));
    try {
      const result = await bulkUpdatePrices(payload);
      notify(result.message);
      await refresh();
    } catch (error) {
      setMenu((current) => ({ ...current, items: previous }));
      notify("Something went wrong. Changes were not saved.", "error");
    }
  }

  async function handleSettings(settings) {
    try {
      const result = await saveSettings(settings);
      notify(result.message);
      await refresh();
    } catch (error) {
      notify(error.message || "Something went wrong. Changes were not saved.", "error");
    }
  }

  async function handleCategory(name) {
    try {
      const result = await createCategory(name);
      notify(result.message);
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
      <NavBar settings={menu.settings} source={menu.source} user={user} />
      <Toast toast={toast} />
      <main className="mx-auto max-w-6xl px-4 py-4">
        <div className="mb-4 md:hidden">
          <h1 className="text-lg font-semibold">{menu.settings.restaurant_name}</h1>
          <p className="text-xs text-stone-500">{user ? `Hi, ${user.name}` : "Guest view"}</p>
        </div>
        {loading && location.pathname !== "/" ? (
          <p className="text-sm text-stone-500">Loading menu…</p>
        ) : (
          <Routes>
            <Route path="/" element={<HomePage settings={menu.settings} user={user} />} />
            <Route
              path="/menu"
              element={
                <LiveMenu
                  settings={menu.settings}
                  categories={menu.categories}
                  items={menu.items}
                  onAddDish={
                    user
                      ? () => {
                          setShowForm(true);
                          navigate("/dishes");
                        }
                      : null
                  }
                />
              }
            />
            <Route
              path="/dishes"
              element={
                <Guard user={user}>
                  <DishManager
                    settings={menu.settings}
                    categories={menu.categories}
                    items={menu.items}
                    showForm={showForm}
                    setShowForm={setShowForm}
                    onCreate={handleCreate}
                    onUpdate={handleUpdate}
                    onDelete={handleDelete}
                    onCreateCategory={handleCategory}
                  />
                </Guard>
              }
            />
            <Route
              path="/stock"
              element={
                <Guard user={user}>
                  <StockToggle items={menu.items} categories={menu.categories} onToggle={handleToggle} />
                </Guard>
              }
            />
            <Route
              path="/pricing"
              element={
                <Guard user={user}>
                  <QuickPricing
                    settings={menu.settings}
                    categories={menu.categories}
                    items={menu.items}
                    onApply={handlePrices}
                  />
                </Guard>
              }
            />
            <Route path="/share" element={<SharePage settings={menu.settings} onSaveSettings={handleSettings} />} />
            <Route
              path="/account"
              element={
                <AccountPage
                  user={user}
                  onCreate={handleSignup}
                  onSignIn={handleSignIn}
                  onUpdate={handleAccountUpdate}
                  onSignOut={handleSignOut}
                />
              }
            />
          </Routes>
        )}
      </main>
    </div>
  );
}
