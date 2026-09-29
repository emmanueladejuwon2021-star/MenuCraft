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
    is_available: initial?.is_available !== false,
  });

  function toggleTag(tag) {
    setForm((prev) => ({
      ...prev,
      tags: prev.tags.includes(tag) ? prev.tags.filter((item) => item !== tag) : [...prev.tags, tag],
    }));
  }

  function handleSubmit(event) {
    event.preventDefault();
    onSubmit({
      ...form,
      price: Number(form.price),
      category_id: Number(form.category_id),
      prep_time: Number(form.prep_time || 10),
    });
  }

  return (
    <form onSubmit={handleSubmit} className="space-y-3 rounded-2xl border border-line bg-card p-4 shadow-soft">
      <div className="grid gap-3 md:grid-cols-2">
        <label className="block text-sm">
          Dish name
          <input
            required
            value={form.name}
            onChange={(event) => setForm({ ...form, name: event.target.value })}
            className="tap mt-1 w-full rounded-xl border border-line bg-paper px-3"
          />
        </label>
        <label className="block text-sm">
          Price
          <input
            required
            type="number"
            min="0"
            step="0.01"
            value={form.price}
            onChange={(event) => setForm({ ...form, price: event.target.value })}
            className="tap mt-1 w-full rounded-xl border border-line bg-paper px-3"
          />
        </label>
        <label className="block text-sm">
          Category
          <select
            value={form.category_id}
            onChange={(event) => setForm({ ...form, category_id: event.target.value })}
            className="tap mt-1 w-full rounded-xl border border-line bg-paper px-3"
          >
            {categories.map((category) => (
              <option key={category.id} value={category.id}>
                {category.name}
              </option>
            ))}
          </select>
        </label>
        <label className="block text-sm">
          Prep minutes
          <input
            type="number"
            min="1"
            value={form.prep_time}
            onChange={(event) => setForm({ ...form, prep_time: event.target.value })}
            className="tap mt-1 w-full rounded-xl border border-line bg-paper px-3"
          />
        </label>
      </div>
      <label className="block text-sm">
        Description
        <textarea
          rows={2}
          value={form.description}
          onChange={(event) => setForm({ ...form, description: event.target.value })}
          className="mt-1 w-full rounded-xl border border-line bg-paper px-3 py-2"
        />
      </label>
      <div className="flex flex-wrap gap-2">
        {ALL_TAGS.map((tag) => (
          <button
            key={tag}
            type="button"
            onClick={() => toggleTag(tag)}
            className={`tap rounded-full px-3 text-sm ${
              form.tags.includes(tag) ? "bg-ink text-white" : "border border-line bg-paper"
            }`}
          >
            {tag}
          </button>
        ))}
      </div>
      <div className="flex gap-2">
        <button type="submit" className="tap rounded-full bg-pine px-5 text-sm font-semibold text-white">
          Save dish
        </button>
        <button type="button" onClick={onCancel} className="tap rounded-full border border-line px-5 text-sm">
          Cancel
        </button>
      </div>
    </form>
  );
}
