import { useEffect, useState } from "react";
import { Navigate, Route, Routes, useNavigate } from "react-router-dom";
import NavBar from "./components/NavBar.jsx";
import Toast from "./components/Toast.jsx";
import LiveMenu from "./pages/LiveMenu.jsx";
import DishManager from "./pages/DishManager.jsx";
import StockToggle from "./pages/StockToggle.jsx";
import QuickPricing from "./pages/QuickPricing.jsx";
import SharePage from "./pages/SharePage.jsx";
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

export default function App() {
  const navigate = useNavigate();
  const [menu, setMenu] = useState({
    source: "local",
    settings: { restaurant_name: "My Restaurant", currency_symbol: "$" },
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

  return (
    <div className="min-h-screen bg-paper pb-24 md:pb-8">
      <NavBar settings={menu.settings} source={menu.source} />
      <Toast toast={toast} />
      <main className="mx-auto max-w-6xl px-4 py-4">
        <div className="mb-4 md:hidden">
          <h1 className="text-lg font-semibold">{menu.settings.restaurant_name}</h1>
          <p className="text-xs text-stone-500">{menu.source === "live" ? "Live shared menu" : "Saved on this device"}</p>
        </div>
        {loading ? (
          <p className="text-sm text-stone-500">Loading menu…</p>
        ) : (
          <Routes>
            <Route path="/" element={<Navigate to="/menu" replace />} />
            <Route
              path="/menu"
              element={
                <LiveMenu
                  settings={menu.settings}
                  categories={menu.categories}
                  items={menu.items}
                  onAddDish={() => {
                    setShowForm(true);
                    navigate("/dishes");
                  }}
                />
              }
            />
            <Route
              path="/dishes"
              element={
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
              }
            />
            <Route
              path="/stock"
              element={<StockToggle items={menu.items} categories={menu.categories} onToggle={handleToggle} />}
            />
            <Route
              path="/pricing"
              element={
                <QuickPricing
                  settings={menu.settings}
                  categories={menu.categories}
                  items={menu.items}
                  onApply={handlePrices}
                />
              }
            />
            <Route
              path="/share"
              element={<SharePage settings={menu.settings} onSaveSettings={handleSettings} />}
            />
          </Routes>
        )}
      </main>
    </div>
  );
}
