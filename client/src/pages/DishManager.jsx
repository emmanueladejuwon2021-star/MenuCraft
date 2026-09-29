import { useMemo, useState } from "react";
import DishForm from "../components/DishForm";
import EmptyCard from "../components/EmptyCard";
import { money } from "../lib/store";
import KitchenNav from "../components/KitchenNav";

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

  function startAdd() {
    setEditing(null);
    setShowForm(true);
  }

  return (
    <section className="space-y-4">
      <KitchenNav />
      <div className="flex flex-col gap-2">
        <input
          value={query}
          onChange={(event) => setQuery(event.target.value)}
          placeholder="Find a dish fast"
          className="tap w-full rounded-2xl border border-line bg-card px-3 text-ink shadow-soft placeholder:text-muted"
        />
        <button type="button" onClick={startAdd} className="tap w-full rounded-2xl bg-ink px-4 text-sm font-semibold text-invert">
          Add dish
        </button>
      </div>

      <form
        className="flex flex-col gap-2 sm:flex-row"
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
          className="tap w-full rounded-2xl border border-line bg-card px-3 text-ink placeholder:text-muted sm:flex-1"
        />
        <button className="tap rounded-2xl border border-line bg-card px-4 text-sm font-medium text-ink sm:shrink-0">
          Add category
        </button>
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
        <EmptyCard title="No dishes match that search." buttonLabel="Add First Dish" onClick={startAdd} />
      ) : (
        <div className="space-y-3">
          {filtered.map((item) => {
            const category = categories.find((row) => Number(row.id) === Number(item.category_id));
            return (
              <article key={item.id} className="rounded-2xl border border-line bg-card p-4 text-ink shadow-soft">
                <div className="flex items-start justify-between gap-3">
                  <div className="min-w-0">
                    <h3 className="font-semibold leading-snug">{item.name}</h3>
                    <p className="mt-1 text-sm text-muted">{category?.name || "No category"}</p>
                  </div>
                  <p className="shrink-0 font-semibold">{money(settings.currency_symbol, item.price)}</p>
                </div>
                <p className={`mt-2 text-xs font-medium ${item.is_available ? "text-pine" : "text-muted"}`}>
                  {item.is_available ? "In Stock" : "Sold Out"}
                </p>
                <div className="mt-3 grid grid-cols-2 gap-2">
                  <button
                    type="button"
                    onClick={() => {
                      setShowForm(false);
                      setEditing(item);
                    }}
                    className="tap rounded-full border border-line bg-paper text-sm font-medium text-ink"
                  >
                    Edit
                  </button>
                  <button
                    type="button"
                    onClick={() => {
                      if (window.confirm(`Remove ${item.name} from the menu?`)) onDelete(item.id);
                    }}
                    className="tap rounded-full border border-red-300 bg-paper text-sm font-medium text-red-600"
                  >
                    Remove
                  </button>
                </div>
              </article>
            );
          })}
        </div>
      )}
    </section>
  );
}
