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
import GuestAccount from "./pages/GuestAccount.jsx";
import PlatePage from "./pages/PlatePage.jsx";
import PayPage from "./pages/PayPage.jsx";
import MyOrders from "./pages/MyOrders.jsx";
import OrdersBoard from "./pages/OrdersBoard.jsx";
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
import { createAccount, createGuestAccount, isStaff, readSession, signIn, signOut, updateAccount } from "./lib/auth.js";
import { addToPlate, myOrders, placeOrder, plateCount, readOrders, readPlate, setOrderStatus, setPlateQty, writePlate } from "./lib/orders.js";
import { readTheme, toggleTheme } from "./lib/theme.js";
import ThemeToggle from "./components/ThemeToggle.jsx";
import ConfirmDialog from "./components/ConfirmDialog.jsx";

function StaffGuard({ user, children }) {
  if (!isStaff(user)) return <Navigate to="/account" replace />;
  return children;
}

export default function App() {
  const navigate = useNavigate();
  const location = useLocation();
  const [user, setUser] = useState(readSession());
  const [menu, setMenu] = useState({
    source: "local",
    settings: { restaurant_name: "Iya Bisi Kitchen", currency_symbol: "\u20a6" },
    categories: [],
    items: [],
  });
  const [plate, setPlate] = useState(readPlate);
  const [orders, setOrders] = useState(readOrders);
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
      onYes: async () => {
        try {
          const snapshot = { ...dish };
          notify((await removeItem(dish.id)).message, "ok", {
            actionLabel: "Undo",
            onAction: async () => {
              setToast(null);
              await createItem(snapshot);
              await refresh();
              notify("Dish put back on the menu");
            },
          });
          await refresh();
        } catch (error) {
          notify(error.message || "Something went wrong. Changes were not saved.", "error");
        }
      },
    });
  }

  function requestRemoveCategory(category) {
    setConfirmBox({
      title: `Remove ${category.name}?`,
      body: "This also removes every dish in that category.",
      yesLabel: "Remove category",
      onYes: async () => {
        try {
          notify((await removeCategory(category.id)).message);
          await refresh();
        } catch (error) {
          notify(error.message || "Something went wrong. Changes were not saved.", "error");
        }
      },
    });
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
      body: "Every dish in this category will change.",
      yesLabel: "Update prices",
      onYes: async () => {
        try {
          notify((await bulkUpdatePrices(payload)).message);
          await refresh();
        } catch {
          notify("Something went wrong. Changes were not saved.", "error");
        }
      },
    });
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

  async function handleStaffSignup(form) {
    try {
      const nextUser = await createAccount(form);
      setUser(nextUser);
      await saveSettings({ restaurant_name: nextUser.restaurant_name, currency_symbol: menu.settings.currency_symbol || "\u20a6" });
      await refresh();
      notify("Kitchen account created.");
      navigate("/dishes");
    } catch (error) {
      notify(error.message || "Could not create the account.", "error");
    }
  }

  async function handleGuestSignup(form) {
    try {
      const nextUser = await createGuestAccount(form);
      setUser(nextUser);
      notify("Guest account created. You can order now.");
      navigate(plate.length ? "/plate" : "/menu");
    } catch (error) {
      notify(error.message || "Could not create the account.", "error");
    }
  }

  async function handleSignIn(form) {
    try {
      const nextUser = await signIn(form);
      setUser(nextUser);
      notify(`Welcome back, ${nextUser.name}`);
      if (nextUser.role === "staff") navigate("/orders");
      else navigate(plate.length ? "/plate" : "/menu");
    } catch (error) {
      notify(error.message || "Could not sign you in.", "error");
    }
  }

  async function handleAccountUpdate(form) {
    try {
      setUser(await updateAccount(form));
      if (form.restaurant_name) {
        await saveSettings({ restaurant_name: form.restaurant_name, currency_symbol: menu.settings.currency_symbol || "\u20a6" });
        await refresh();
      }
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

  function handleAddToPlate(dish) {
    setPlate(addToPlate(dish));
    notify(`${dish.name} added to your plate`);
  }

  function handlePaid({ note, payRef }) {
    placeOrder({ guest: user, items: plate, note, payRef });
    setPlate(readPlate());
    setOrders(readOrders());
    notify("Paid! The kitchen can see your order.");
    navigate("/my-orders");
  }

  function handleOrderStatus(id, status) {
    setOrderStatus(id, status);
    setOrders(readOrders());
    notify(status === "Cooking" ? "Kitchen has started this order" : status === "Ready" ? "Order is ready" : "Order marked served");
  }

  const guestOrders = user?.email ? myOrders(user.email) : [];

  return (
    <div className="min-h-screen bg-paper pb-24 md:pb-8">
      <NavBar settings={menu.settings} user={user} theme={theme} onToggleTheme={handleTheme} plateCount={plateCount(plate)} />
      <Toast toast={toast} />
      <ConfirmDialog box={confirmBox} onClose={() => setConfirmBox(null)} />
      <main className="mx-auto max-w-6xl px-4 py-4">
        <div className="mb-4 flex items-center justify-between gap-3 md:hidden">
          <div>
            <h1 className="text-lg font-semibold">{menu.settings.restaurant_name}</h1>
            <p className="text-xs text-muted">{user ? `${user.role === "staff" ? "Kitchen" : "Guest"} \u00b7 ${user.name}` : "Guest menu"}</p>
          </div>
          <ThemeToggle theme={theme} onToggle={handleTheme} />
        </div>
        {loading && location.pathname !== "/" ? (
          <p className="text-sm text-muted">Loading menu\u2026</p>
        ) : (
          <Routes>
            <Route path="/" element={<HomePage settings={menu.settings} items={menu.items} />} />
            <Route path="/menu" element={<LiveMenu settings={menu.settings} categories={menu.categories} items={menu.items} onAddToPlate={handleAddToPlate} />} />
            <Route path="/plate" element={<PlatePage settings={menu.settings} user={user} plate={plate} onQty={(id, qty) => setPlate(setPlateQty(id, qty))} onClear={() => setPlate(writePlate([]))} />} />
            <Route path="/pay" element={<PayPage settings={menu.settings} user={user} plate={plate} onPaid={handlePaid} />} />
            <Route path="/my-orders" element={<MyOrders settings={menu.settings} orders={guestOrders} />} />
            <Route path="/guest-account" element={<GuestAccount user={user?.role === "guest" ? user : null} onCreate={handleGuestSignup} onSignIn={(form) => handleSignIn(form)} onUpdate={handleAccountUpdate} onSignOut={handleSignOut} />} />
            <Route path="/dishes" element={<StaffGuard user={user}><DishManager settings={menu.settings} categories={menu.categories} items={menu.items} showForm={showForm} setShowForm={setShowForm} onCreate={handleCreate} onUpdate={handleUpdate} onDelete={requestDelete} onCreateCategory={handleCategory} onRemoveCategory={requestRemoveCategory} /></StaffGuard>} />
            <Route path="/stock" element={<StaffGuard user={user}><StockToggle items={menu.items} categories={menu.categories} onToggle={handleToggle} /></StaffGuard>} />
            <Route path="/pricing" element={<StaffGuard user={user}><QuickPricing settings={menu.settings} categories={menu.categories} items={menu.items} onApply={requestPrices} /></StaffGuard>} />
            <Route path="/orders" element={<StaffGuard user={user}><OrdersBoard settings={menu.settings} orders={orders} onStatus={handleOrderStatus} /></StaffGuard>} />
            <Route path="/share" element={<StaffGuard user={user}><SharePage settings={menu.settings} onSaveSettings={handleSettings} user={user} /></StaffGuard>} />
            <Route path="/account" element={<AccountPage user={user?.role === "staff" ? user : null} onCreate={handleStaffSignup} onSignIn={(form) => handleSignIn(form)} onUpdate={handleAccountUpdate} onSignOut={handleSignOut} />} />
          </Routes>
        )}
      </main>
    </div>
  );
}
