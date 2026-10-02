import { useEffect, useState } from "react";
import { Link } from "react-router-dom";

export default function GuestAccount({ user, onCreate, onSignIn, onUpdate, onSignOut }) {
  const [mode, setMode] = useState("signin");
  const [form, setForm] = useState({
    name: user?.name || "",
    email: user?.email || "",
    phone: user?.phone || "",
    password: "",
  });

  useEffect(() => {
    setForm((prev) => ({
      ...prev,
      name: user?.name || "",
      email: user?.email || "",
      phone: user?.phone || "",
    }));
  }, [user?.name, user?.email, user?.phone]);

  function change(key, value) {
    setForm((prev) => ({ ...prev, [key]: value }));
  }

  if (user) {
    return (
      <section className="page-enter mx-auto max-w-lg space-y-3">
        <form
          className="space-y-3 rounded-2xl border border-line bg-card p-4 shadow-soft"
          onSubmit={(event) => {
            event.preventDefault();
            onUpdate({ name: form.name, phone: form.phone, restaurant_name: user.restaurant_name || "" });
          }}
        >
          <div>
            <h1 className="text-lg font-semibold text-ink">Your account</h1>
            <p className="mt-1 text-sm text-muted">Signed in as {user.email}. The kitchen uses this name on your ticket.</p>
          </div>
          <label className="block text-sm font-medium text-ink">Your name<input className="field mt-1" value={form.name} onChange={(event) => change("name", event.target.value)} /></label>
          <label className="block text-sm font-medium text-ink">Email<input className="field mt-1" value={user.email || ""} disabled /></label>
          <label className="block text-sm font-medium text-ink">Phone<input className="field mt-1" value={form.phone} onChange={(event) => change("phone", event.target.value)} placeholder="Optional" /></label>
          <button className="tap h-11 w-full rounded-full bg-pine text-sm font-semibold text-invert">Save details</button>
        </form>
        <button type="button" onClick={onSignOut} className="tap h-11 w-full rounded-full border border-line bg-card text-sm font-medium text-ink">Sign out</button>
      </section>
    );
  }

  return (
    <section className="page-enter mx-auto max-w-lg">
      <form
        className="space-y-3 rounded-2xl border border-line bg-card p-4 shadow-soft"
        onSubmit={(event) => {
          event.preventDefault();
          if (mode === "signup") onCreate(form);
          else onSignIn({ email: form.email, password: form.password });
        }}
      >
        <div>
          <h1 className="text-lg font-semibold text-ink">{mode === "signup" ? "Create your account" : "Sign in"}</h1>
          <p className="mt-1 text-sm text-muted">An account is optional. It keeps your name on the ticket so you can follow the kitchen.</p>
        </div>
        <div className="grid grid-cols-2 gap-2 rounded-full bg-paper p-1">
          <button type="button" onClick={() => setMode("signin")} className={`h-9 rounded-full text-sm font-medium ${mode === "signin" ? "bg-pine text-invert" : "text-muted"}`}>Sign in</button>
          <button type="button" onClick={() => setMode("signup")} className={`h-9 rounded-full text-sm font-medium ${mode === "signup" ? "bg-pine text-invert" : "text-muted"}`}>Create account</button>
        </div>
        {mode === "signup" && (
          <label className="block text-sm font-medium text-ink">Your name<input className="field mt-1" value={form.name} onChange={(event) => change("name", event.target.value)} /></label>
        )}
        <label className="block text-sm font-medium text-ink">Email<input type="email" className="field mt-1" value={form.email} onChange={(event) => change("email", event.target.value)} /></label>
        {mode === "signup" && (
          <label className="block text-sm font-medium text-ink">Phone<input className="field mt-1" value={form.phone} onChange={(event) => change("phone", event.target.value)} placeholder="Optional" /></label>
        )}
        <label className="block text-sm font-medium text-ink">Password<input type="password" className="field mt-1" value={form.password} onChange={(event) => change("password", event.target.value)} /></label>
        <button className="tap h-11 w-full rounded-full bg-pine text-sm font-semibold text-invert">{mode === "signup" ? "Create account" : "Sign in"}</button>
        <Link to="/menu" className="block text-center text-sm text-muted">Continue without an account</Link>
      </form>
    </section>
  );
}
