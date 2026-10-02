import { useEffect, useState } from "react";

export default function AccountPage({ user, settings, onUpdate, onSignOut }) {
  const [form, setForm] = useState({
    name: user?.name || "",
    phone: user?.phone || "",
    restaurant_name: user?.restaurant_name || settings?.restaurant_name || "",
    currency_symbol: settings?.currency_symbol || "₦",
  });

  useEffect(() => {
    setForm({
      name: user?.name || "",
      phone: user?.phone || "",
      restaurant_name: user?.restaurant_name || settings?.restaurant_name || "",
      currency_symbol: settings?.currency_symbol || "₦",
    });
  }, [user?.name, user?.phone, user?.restaurant_name, settings?.restaurant_name, settings?.currency_symbol]);

  function change(key, value) {
    setForm((prev) => ({ ...prev, [key]: value }));
  }

  return (
    <section className="page-enter mx-auto max-w-lg space-y-4">
      <form
        className="space-y-3 rounded-2xl border border-line bg-card p-4 shadow-soft"
        onSubmit={(event) => {
          event.preventDefault();
          onUpdate(form);
        }}
      >
        <div>
          <h1 className="text-lg font-semibold text-ink">Account settings</h1>
          <p className="mt-1 text-sm text-muted">Your name, phone, and the name guests see on the menu.</p>
        </div>
        <label className="block text-sm font-medium text-ink">
          Your name
          <input className="field mt-1" value={form.name} onChange={(event) => change("name", event.target.value)} />
        </label>
        <label className="block text-sm font-medium text-ink">
          Email
          <input className="field mt-1" value={user?.email || ""} disabled />
        </label>
        <label className="block text-sm font-medium text-ink">
          Phone
          <input className="field mt-1" value={form.phone} onChange={(event) => change("phone", event.target.value)} placeholder="Kitchen contact" />
        </label>
        <label className="block text-sm font-medium text-ink">
          Restaurant name
          <input className="field mt-1" value={form.restaurant_name} onChange={(event) => change("restaurant_name", event.target.value)} />
        </label>
        <label className="block text-sm font-medium text-ink">
          Money symbol
          <input className="field mt-1" value={form.currency_symbol} onChange={(event) => change("currency_symbol", event.target.value)} placeholder="₦" />
        </label>
        <button className="tap h-11 w-full rounded-full bg-pine text-sm font-semibold text-invert">Save settings</button>
      </form>
      <button type="button" onClick={onSignOut} className="tap h-11 w-full rounded-full border border-line bg-card text-sm font-medium text-ink">
        Sign out
      </button>
    </section>
  );
}
