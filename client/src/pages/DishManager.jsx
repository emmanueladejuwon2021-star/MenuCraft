import { useMemo, useState } from "react";
import DishForm from "../components/DishForm";
import EmptyCard from "../components/EmptyCard";
import KitchenNav from "../components/KitchenNav";
import { money } from "../lib/store";
import DishPhoto from "../components/DishPhoto.jsx";

export default function DishManager({
  settings,
  categories,
  items,
  onCreate,
  onUpdate,
  onDelete,
  onCreateCategory,
  onRemoveCategory,
  showForm,
  setShowForm,
}) {
  const [query, setQuery] = useState("");
  const [editing, setEditing] = useState(null);
  const [newCategory, setNewCategory] = useState("");

  const filtered = useMemo(() => {
    const needle = query.toLowerCase();
    return items.filter((item) => `${item.name} ${item.description}`.toLowerCase().includes(needle));
  }, [items, query]);

  function startAdd() {
    setEditing(null);
    setShowForm(true);
  }

  return (
    <section className="page-enter space-y-5">
      <KitchenNav title="Kitchen dishes" hint="Add, edit, or remove dishes guests will see." />
      <div className="rounded-3xl border border-line bg-card p-4 shadow-soft">
        <input value={query} onChange={(event) => setQuery(event.target.value)} placeholder="Find a dish or description" className="tap h-11 w-full rounded-2xl border border-line bg-paper px-3 text-ink" />
        <button type="button" onClick={startAdd} className="tap mt-3 h-11 w-full rounded-full bg-pine text-sm font-semibold text-invert">Add dish</button>
        <form className="mt-3 flex flex-col gap-2 sm:flex-row" onSubmit={(event) => { event.preventDefault(); if (!newCategory.trim()) return; onCreateCategory(newCategory.trim()); setNewCategory(""); }}>
          <input value={newCategory} onChange={(event) => setNewCategory(event.target.value)} placeholder="New category name" className="tap h-11 w-full rounded-2xl border border-line bg-paper px-3 text-ink sm:flex-1" />
          <button className="tap h-11 rounded-full border border-line bg-paper px-4 text-sm font-medium text-ink sm:shrink-0">Add category</button>
        </form>
        <div className="mt-3 space-y-2">
          {categories.map((category) => (
            <div key={category.id} className="flex items-center justify-between gap-3 rounded-2xl border border-line bg-paper px-3 py-2">
              <p className="text-sm font-medium text-ink">{category.name}</p>
              <button type="button" className="tap h-11 shrink-0 rounded-full border border-line px-3 text-sm font-medium text-danger" onClick={() => onRemoveCategory && onRemoveCategory(category)}>Remove list</button>
            </div>
          ))}
        </div>
      </div>
      {(showForm || editing) && (
        <DishForm
          categories={categories}
          initial={editing}
          onCancel={() => { setShowForm(false); setEditing(null); }}
          onSubmit={(payload) => {
            if (editing) onUpdate(editing.id, payload);
            else onCreate(payload);
            setShowForm(false);
            setEditing(null);
          }}
        />
      )}
      {filtered.length === 0 ? (
        <EmptyCard title="No dishes match that search." buttonLabel="Add First Dish" onClick={startAdd} />
      ) : (
        <div className="space-y-3">
          {filtered.map((item) => {
            const category = categories.find((row) => Number(row.id) === Number(item.category_id));
            return (
              <article key={item.id} className="overflow-hidden rounded-3xl border border-line bg-card shadow-soft">
                <div className="flex gap-3 p-3 sm:p-4">
                  <DishPhoto src={item.image_url} name={item.name} className="h-24 w-24 shrink-0" />
                  <div className="min-w-0 flex-1">
                    <div className="flex items-start justify-between gap-2">
                      <div>
                        <h3 className="font-semibold leading-snug text-ink">{item.name}</h3>
                        <p className="mt-1 text-sm text-muted">{category?.name || "No category"}</p>
                      </div>
                      <p className="shrink-0 font-semibold text-ink">{money(settings.currency_symbol, item.price)}</p>
                    </div>
                    <p className={`mt-2 text-xs font-medium ${item.is_available ? "text-pine" : "text-muted"}`}>{item.is_available ? "In Stock" : "Sold Out"}</p>
                  </div>
                </div>
                <div className="grid grid-cols-2 gap-2 border-t border-line p-3">
                  <button type="button" onClick={() => { setShowForm(false); setEditing(item); }} className="tap h-11 rounded-full border border-line bg-paper text-sm font-medium text-ink">Edit</button>
                  <button type="button" onClick={() => onDelete(item)} className="tap h-11 rounded-full border border-line bg-paper text-sm font-medium text-danger">Remove</button>
                </div>
              </article>
            );
          })}
        </div>
      )}
    </section>
  );
}
