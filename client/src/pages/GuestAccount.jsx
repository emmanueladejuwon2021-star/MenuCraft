import { useState } from "react";

export default function GuestAccount({ user, onCreate, onSignIn, onUpdate, onSignOut }) {
  const [mode, setMode] = useState("signup");
  const [form, setForm] = useState({
    name: user?.name || "",
    email: user?.email || "",
    phone: user?.phone || "",
    password: "",
  });

  function change(key, value) {
    setForm((prev) => ({ ...prev, [key]: value }));
  }

  return (
    <section className="page-enter mx-auto max-w-lg space-y-5">
      <div className="overflow-hidden rounded-[2rem] border border-line bg-card shadow-soft">
        <div className="bg-gradient-to-br from-blue-200/70 to-transparent px-6 py-6 dark:from-blue-500/15">
          <p className="text-xs font-semibold uppercase tracking-[0.18em] text-clay">Guest</p>
          <h1 className="mt-1 text-2xl font-semibold text-ink">{user ? "Your account" : "Create your account"}</h1>
          <p className="mt-2 text-sm leading-relaxed text-muted">
            {user ? "Keep your name and phone so the kitchen can find your order." : "Save your name so you can send an order and follow the kitchen."}
          </p>
        </div>
        <div className="px-6 pb-6">
          {!user && (
            <div className="mt-2 grid grid-cols-2 gap-2 rounded-full bg-paper p-1">
              <button type="button" onClick={() => setMode("signup")} className={`tap rounded-full text-sm font-medium ${mode === "signup" ? "bg-pine text-invert" : "text-muted"}`}>Create account</button>
              <button type="button" onClick={() => setMode("signin")} className={`tap rounded-full text-sm font-medium ${mode === "signin" ? "bg-pine text-invert" : "text-muted"}`}>Sign in</button>
            </div>
          )}
          <form
            className="mt-5 space-y-3"
            onSubmit={(event) => {
              event.preventDefault();
              if (user) onUpdate({ name: form.name, phone: form.phone, restaurant_name: user.restaurant_name || "" });
              else if (mode === "signup") onCreate(form);
              else onSignIn({ email: form.email, password: form.password });
            }}
          >
            {(user || mode === "signup") && (
              <label className="block text-sm font-medium text-ink">Your name<input className="field mt-1" value={form.name} onChange={(event) => change("name", event.target.value)} /></label>
            )}
            <label className="block text-sm font-medium text-ink">Email<input type="email" disabled={Boolean(user)} className="field mt-1 disabled:opacity-70" value={form.email} onChange={(event) => change("email", event.target.value)} /></label>
            {(user || mode === "signup") && (
              <label className="block text-sm font-medium text-ink">Phone<input className="field mt-1" value={form.phone} onChange={(event) => change("phone", event.target.value)} /></label>
            )}
            {!user && (
              <label className="block text-sm font-medium text-ink">Password<input type="password" className="field mt-1" value={form.password} onChange={(event) => change("password", event.target.value)} /></label>
            )}
            <button className="tap mt-2 h-12 w-full rounded-full bg-pine text-sm font-semibold text-invert">{user ? "Save details" : mode === "signup" ? "Create guest account" : "Sign in"}</button>
          </form>
          {user ? (
            <button type="button" onClick={onSignOut} className="tap mt-3 h-12 w-full rounded-full border border-line text-sm font-medium text-ink">Sign out</button>
          ) : null}
        </div>
      </div>
    </section>
  );
}
