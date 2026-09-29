import { useMemo, useState } from "react";
import { Search } from "lucide-react";
import { ALL_TAGS } from "../lib/seed";
import { money } from "../lib/store";
import EmptyCard from "../components/EmptyCard";

export default function LiveMenu({ settings, categories, items, onAddDish }) {
  const [query, setQuery] = useState("");
  const [categoryId, setCategoryId] = useState("all");
  const [tag, setTag] = useState("all");

  const visible = useMemo(() => {
    return items.filter((item) => {
      const matchesQuery = `${item.name} ${item.description}`.toLowerCase().includes(query.toLowerCase());
      const matchesCategory = categoryId === "all" || Number(item.category_id) === Number(categoryId);
      const matchesTag = tag === "all" || item.tags.includes(tag);
      return matchesQuery && matchesCategory && matchesTag;
    });
  }, [items, query, categoryId, tag]);

  return (
    <section className="space-y-4">
      <div className="relative">
        <Search size={16} className="absolute left-3 top-1/2 -translate-y-1/2 text-stone-400" />
        <input
          value={query}
          onChange={(event) => setQuery(event.target.value)}
          placeholder="Search dishes"
          className="tap w-full rounded-2xl border border-line bg-card pl-9 pr-3 shadow-soft"
        />
      </div>

      <div className="flex gap-2 overflow-x-auto pb-1">
        <button
          className={`tap shrink-0 rounded-full px-3 text-sm ${categoryId === "all" ? "bg-ink text-white" : "border border-line bg-card"}`}
          onClick={() => setCategoryId("all")}
        >
          All
        </button>
        {categories.map((category) => (
          <button
            key={category.id}
            className={`tap shrink-0 rounded-full px-3 text-sm ${
              Number(categoryId) === Number(category.id) ? "bg-ink text-white" : "border border-line bg-card"
            }`}
            onClick={() => setCategoryId(category.id)}
          >
            {category.name}
          </button>
        ))}
      </div>

      <div className="flex flex-wrap gap-2">
        <button
          className={`tap rounded-full px-3 text-sm ${tag === "all" ? "bg-clay text-white" : "border border-line bg-card"}`}
          onClick={() => setTag("all")}
        >
          Any diet
        </button>
        {ALL_TAGS.map((item) => (
          <button
            key={item}
            className={`tap rounded-full px-3 text-sm ${tag === item ? "bg-clay text-white" : "border border-line bg-card"}`}
            onClick={() => setTag(item)}
          >
            {item}
          </button>
        ))}
      </div>

      {categories.map((category) => {
        const dishes = visible.filter((item) => Number(item.category_id) === Number(category.id));
        if (categoryId !== "all" && Number(categoryId) !== Number(category.id)) return null;
        return (
          <div key={category.id} className="space-y-2">
            <h2 className="text-base font-semibold">{category.name}</h2>
            {dishes.length === 0 ? (
              <EmptyCard title="No dishes in this category yet." buttonLabel="Add First Dish" onClick={onAddDish} />
            ) : (
              <div className="grid gap-3 sm:grid-cols-2">
                {dishes.map((item) => (
                  <article key={item.id} className="rounded-2xl border border-line bg-card p-4 shadow-soft">
                    <div className="flex items-start justify-between gap-3">
                      <div>
                        <h3 className="font-semibold">{item.name}</h3>
                        <p className="mt-1 text-sm text-stone-600">{item.description}</p>
                      </div>
                      <p className="shrink-0 font-semibold">{money(settings.currency_symbol, item.price)}</p>
                    </div>
                    <div className="mt-3 flex flex-wrap items-center gap-2">
                      <span
                        className={`rounded-full px-2 py-1 text-xs font-semibold ${
                          item.is_available ? "bg-emerald-100 text-pine" : "bg-stone-200 text-stone-500"
                        }`}
                      >
                        {item.is_available ? "In Stock" : "Sold Out"}
                      </span>
                      {item.tags.map((itemTag) => (
                        <span key={itemTag} className="rounded-full bg-paper px-2 py-1 text-xs text-stone-600">
                          {itemTag}
                        </span>
                      ))}
                    </div>
                  </article>
                ))}
              </div>
            )}
          </div>
        );
      })}
    </section>
  );
}
