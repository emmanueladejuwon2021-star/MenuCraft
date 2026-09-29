import { useMemo, useState } from "react";
import DishForm from "../components/DishForm";
import EmptyCard from "../components/EmptyCard";
import KitchenNav from "../components/KitchenNav";
import { money } from "../lib/store";

export default function DishManager({
  settings,
  categories,
  items,
  onCreate,
  onUpdate,
  onDelete,
  onCreateCategory,
  showForm,
  setShowForm,
}) {
  const [query, setQuery] = useState("");
  const [editing, setEditing] = useState(null);
  const [newCategory, setNewCategory] = useState("");

  const filtered = useMemo(() => {
    return items.filter((item) => item.name.toLowerCase().includes(query.toLowerCase()));
  }, [items, query]);

  return (
    <section className="space-y-4">
      <KitchenNav />
      <div className="flex flex-col gap-2 sm:flex-row">
        <input
          value={query}
          onChange={(event) => setQuery(event.target.value)}
          placeholder="Find a dish fast"
          className="tap flex-1 rounded-2xl border border-line bg-card px-3 shadow-soft"
        />
        <button
          type="button"
          onClick={() => {
            setEditing(null);
            setShowForm(true);
          }}
          className="tap rounded-2xl bg-ink px-4 text-sm font-semibold text-white"
        >
          Add dish
        </button>
      </div>

      <form
        className="flex gap-2"
        onSubmit={(event) => {
          event.preventDefault();
          if (!newCategory.trim()) return;
          onCreateCategory(newCategory.trim());
          setNewCategory("");
        }}
      >
        <input
          value={newCategory}
          onChange={(event) => setNewCategory(event.target.value)}
          placeholder="New category name"
          className="tap flex-1 rounded-2xl border border-line bg-card px-3"
        />
        <button className="tap rounded-2xl border border-line bg-card px-4 text-sm font-medium">Add category</button>
      </form>

      {(showForm || editing) && (
        <DishForm
          categories={categories}
          initial={editing}
          onCancel={() => {
            setShowForm(false);
            setEditing(null);
          }}
          onSubmit={(payload) => {
            if (editing) onUpdate(editing.id, payload);
            else onCreate(payload);
            setShowForm(false);
            setEditing(null);
          }}
        />
      )}

      {filtered.length === 0 ? (
        <EmptyCard title="No dishes match that search." buttonLabel="Add First Dish" onClick={() => setShowForm(true)} />
      ) : (
        <div className="overflow-hidden rounded-2xl border border-line bg-card shadow-soft">
          <div className="grid grid-cols-[1.4fr_0.8fr_0.6fr_0.8fr] gap-2 border-b border-line bg-paper px-3 py-2 text-xs font-semibold uppercase tracking-wide text-stone-500">
            <span>Dish</span>
            <span>Category</span>
            <span>Price</span>
            <span>Actions</span>
          </div>
          {filtered.map((item) => {
            const category = categories.find((row) => Number(row.id) === Number(item.category_id));
            return (
              <div key={item.id} className="grid grid-cols-[1.4fr_0.8fr_0.6fr_0.8fr] items-center gap-2 border-t border-line px-3 py-3 text-sm">
                <div>
                  <p className="font-medium">{item.name}</p>
                  <p className="text-xs text-stone-500">{item.is_available ? "In Stock" : "Sold Out"}</p>
                </div>
                <span>{category?.name || "—"}</span>
                <span>{money(settings.currency_symbol, item.price)}</span>
                <div className="flex flex-wrap gap-2">
                  <button type="button" onClick={() => { setShowForm(false); setEditing(item); }} className="tap rounded-full border border-line px-3 text-xs">
                    Edit
                  </button>
                  <button
                    type="button"
                    onClick={() => {
                      if (window.confirm(`Remove ${item.name} from the menu?`)) onDelete(item.id);
                    }}
                    className="tap rounded-full border border-red-200 px-3 text-xs text-red-700"
                  >
                    Remove
                  </button>
                </div>
              </div>
            );
          })}
        </div>
      )}
    </section>
  );
}
