import { useState } from "react";

export default function AccountPage({ user, onCreate, onSignIn, onUpdate, onSignOut }) {
  const [mode, setMode] = useState("signup");
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
    <section className="mx-auto max-w-xl space-y-4">
      <div className="rounded-2xl border border-line bg-card p-5 shadow-soft">
        <h1 className="text-xl font-semibold">{user ? "Account settings" : "Kitchen account"}</h1>
        <p className="mt-1 text-sm text-stone-600">
          {user
            ? "Update your name and restaurant. Guests can still view the menu without an account."
            : "Create an account to add dishes, hide sold-out food, and change prices."}
        </p>

        {!user && (
          <div className="mt-4 flex gap-2">
            <button
              type="button"
              onClick={() => setMode("signup")}
              className={`tap rounded-full px-4 text-sm ${mode === "signup" ? "bg-ink text-white" : "border border-line"}`}
            >
              Create account
            </button>
            <button
              type="button"
              onClick={() => setMode("signin")}
              className={`tap rounded-full px-4 text-sm ${mode === "signin" ? "bg-ink text-white" : "border border-line"}`}
            >
              Sign in
            </button>
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
            <label className="block text-sm">
              Your name
              <input className="tap mt-1 w-full rounded-xl border border-line bg-paper px-3" value={form.name} onChange={(event) => change("name", event.target.value)} />
            </label>
          )}
          <label className="block text-sm">
            Email
            <input
              type="email"
              disabled={Boolean(user)}
              className="tap mt-1 w-full rounded-xl border border-line bg-paper px-3 disabled:opacity-70"
              value={form.email}
              onChange={(event) => change("email", event.target.value)}
            />
          </label>
          {!user && (
            <label className="block text-sm">
              Password
              <input type="password" className="tap mt-1 w-full rounded-xl border border-line bg-paper px-3" value={form.password} onChange={(event) => change("password", event.target.value)} />
            </label>
          )}
          {(user || mode === "signup") && (
            <label className="block text-sm">
              Restaurant name
              <input className="tap mt-1 w-full rounded-xl border border-line bg-paper px-3" value={form.restaurant_name} onChange={(event) => change("restaurant_name", event.target.value)} />
            </label>
          )}
          <button className="tap rounded-full bg-pine px-5 text-sm font-semibold text-white">
            {user ? "Save account" : mode === "signup" ? "Create account" : "Sign in"}
          </button>
        </form>
        {user && (
          <button type="button" onClick={onSignOut} className="tap mt-3 rounded-full border border-line px-4 text-sm">
            Sign out
          </button>
        )}
      </div>
    </section>
  );
}
