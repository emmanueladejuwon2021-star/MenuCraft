import { useState } from "react";
import { ALL_TAGS } from "../lib/seed";

export default function DishForm({ categories, initial, onCancel, onSubmit }) {
  const [form, setForm] = useState({
    name: initial?.name || "",
    description: initial?.description || "",
    price: initial?.price ?? "",
    category_id: initial?.category_id || categories[0]?.id || "",
    tags: initial?.tags || [],
    prep_time: initial?.prep_time || 10,
    image_url: initial?.image_url || "",
    is_available: initial?.is_available !== false,
  });
  const [error, setError] = useState("");

  function toggleTag(tag) {
    setForm((prev) => ({
      ...prev,
      tags: prev.tags.includes(tag) ? prev.tags.filter((item) => item !== tag) : [...prev.tags, tag],
    }));
  }

  function handleSubmit(event) {
    event.preventDefault();
    setError("");
    if (!categories.length || !form.category_id) {
      setError("Add a category first, then save the dish.");
      return;
    }
    onSubmit({
      ...form,
      price: Number(form.price),
      category_id: Number(form.category_id),
      prep_time: Number(form.prep_time || 10),
      is_available: Boolean(form.is_available),
    });
  }

  return (
    <form onSubmit={handleSubmit} className="space-y-3 rounded-2xl border border-line bg-card p-4 shadow-soft">
      <div className="grid gap-3 md:grid-cols-2">
        <label className="block text-sm text-ink">
          Dish name
          <input required value={form.name} onChange={(event) => setForm({ ...form, name: event.target.value })} className="tap mt-1 w-full rounded-xl border border-line bg-paper px-3 text-ink" />
        </label>
        <label className="block text-sm text-ink">
          Price
          <input required type="number" min="0" step="0.01" value={form.price} onChange={(event) => setForm({ ...form, price: event.target.value })} className="tap mt-1 w-full rounded-xl border border-line bg-paper px-3 text-ink" />
        </label>
        <label className="block text-sm text-ink">
          Category
          {categories.length ? (
            <select required value={form.category_id} onChange={(event) => setForm({ ...form, category_id: event.target.value })} className="tap mt-1 w-full rounded-xl border border-line bg-paper px-3 text-ink">
              {categories.map((category) => (
                <option key={category.id} value={category.id}>{category.name}</option>
              ))}
            </select>
          ) : (
            <p className="mt-2 text-sm text-muted">No lists yet. Add a category above before you save a dish.</p>
          )}
        </label>
        <label className="block text-sm text-ink">
          Prep minutes
          <input type="number" min="1" value={form.prep_time} onChange={(event) => setForm({ ...form, prep_time: event.target.value })} className="tap mt-1 w-full rounded-xl border border-line bg-paper px-3 text-ink" />
        </label>
      </div>
      <label className="block text-sm text-ink">
        Photo link
        <input value={form.image_url} onChange={(event) => setForm({ ...form, image_url: event.target.value })} placeholder="Paste a photo link" className="tap mt-1 w-full rounded-xl border border-line bg-paper px-3 text-ink" />
      </label>
      <label className="block text-sm text-ink">
        Description
        <textarea rows={2} value={form.description} onChange={(event) => setForm({ ...form, description: event.target.value })} className="mt-1 w-full rounded-xl border border-line bg-paper px-3 py-2 text-ink" />
      </label>
      <button
        type="button"
        aria-pressed={form.is_available}
        onClick={() => setForm((prev) => ({ ...prev, is_available: !prev.is_available }))}
        className={`tap h-11 w-full rounded-full px-4 text-sm font-medium ${form.is_available ? "bg-pine text-invert" : "border border-line bg-paper text-ink"}`}
      >
        {form.is_available ? "In stock \u00b7 tap to mark sold out" : "Sold out \u00b7 tap to put back on the board"}
      </button>
      <div className="flex flex-wrap gap-2">
        {ALL_TAGS.map((tag) => (
          <button key={tag} type="button" onClick={() => toggleTag(tag)} className={`tap rounded-full px-3 text-sm ${form.tags.includes(tag) ? "bg-ink text-invert" : "border border-line bg-paper text-ink"}`}>
            {tag}
          </button>
        ))}
      </div>
      {error ? <p className="text-sm text-red-700">{error}</p> : null}
      <div className="flex gap-2">
        <button type="submit" className="tap rounded-full bg-pine px-5 text-sm font-semibold text-invert">Save dish</button>
        <button type="button" onClick={onCancel} className="tap rounded-full border border-line px-5 text-sm text-ink">Cancel</button>
      </div>
    </form>
  );
}
