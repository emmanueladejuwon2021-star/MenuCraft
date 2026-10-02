import { useState } from "react";
import { Link } from "react-router-dom";

export default function AccountPage({ user, onCreate, onSignIn, onUpdate, onSignOut }) {
  const [mode, setMode] = useState("signin");
  const [form, setForm] = useState({
    name: user?.name || "",
    email: user?.email || "",
    password: "",
    restaurant_name: user?.restaurant_name || "",
  });

  function change(key, value) {
    setForm((prev) => ({ ...prev, [key]: value }));
  }

  return (
    <section className="page-enter mx-auto max-w-xl space-y-4">
      <div className="rounded-2xl border border-line bg-card p-5 shadow-soft">
        <h1 className="text-xl font-semibold text-ink">{user ? "Kitchen account" : "Staff sign in"}</h1>
        <p className="mt-1 text-sm text-muted">
          {user
            ? "This page is for kitchen staff only. Guests use Home and Menu."
            : "Guests do not need an account. This sign-in is only for people who change the menu."}
        </p>
        {!user && (
          <div className="mt-4 flex gap-2">
            <button type="button" onClick={() => setMode("signin")} className={`tap rounded-full px-4 text-sm ${mode === "signin" ? "bg-pine text-invert" : "border border-line text-ink"}`}>Sign in</button>
            <button type="button" onClick={() => setMode("signup")} className={`tap rounded-full px-4 text-sm ${mode === "signup" ? "bg-pine text-invert" : "border border-line text-ink"}`}>Create account</button>
          </div>
        )}
        <form
          className="mt-4 space-y-3"
          onSubmit={(event) => {
            event.preventDefault();
            if (user) onUpdate({ name: form.name, restaurant_name: form.restaurant_name });
            else if (mode === "signup") onCreate(form);
            else onSignIn({ email: form.email, password: form.password });
          }}
        >
          {(user || mode === "signup") && (
            <label className="block text-sm text-ink">Your name<input className="tap mt-1 w-full rounded-xl border border-line bg-paper px-3 text-ink" value={form.name} onChange={(event) => change("name", event.target.value)} /></label>
          )}
          <label className="block text-sm text-ink">Email<input type="email" disabled={Boolean(user)} className="tap mt-1 w-full rounded-xl border border-line bg-paper px-3 text-ink disabled:opacity-70" value={form.email} onChange={(event) => change("email", event.target.value)} /></label>
          {!user && (
            <label className="block text-sm text-ink">Password<input type="password" className="tap mt-1 w-full rounded-xl border border-line bg-paper px-3 text-ink" value={form.password} onChange={(event) => change("password", event.target.value)} /></label>
          )}
          {(user || mode === "signup") && (
            <label className="block text-sm text-ink">Restaurant name<input className="tap mt-1 w-full rounded-xl border border-line bg-paper px-3 text-ink" value={form.restaurant_name} onChange={(event) => change("restaurant_name", event.target.value)} /></label>
          )}
          <button className="tap rounded-full bg-pine px-5 text-sm font-semibold text-invert">{user ? "Save account" : mode === "signup" ? "Create kitchen account" : "Sign in to kitchen"}</button>
        </form>
        {user ? (
          <button type="button" onClick={onSignOut} className="tap mt-3 rounded-full border border-line px-4 text-sm text-ink">Sign out</button>
        ) : (
          <Link to="/menu" className="mt-4 inline-block text-sm text-muted">Just want food? Open the guest menu</Link>
        )}
      </div>
    </section>
  );
}
