import { useMemo, useState } from "react";
import { Search } from "lucide-react";
import { ALL_TAGS } from "../lib/seed";
import { money } from "../lib/store";

export default function LiveMenu({ settings, categories, items, onAddToPlate }) {
  const [query, setQuery] = useState("");
  const [categoryId, setCategoryId] = useState("all");
  const [tag, setTag] = useState("all");
  const [showSoldOut, setShowSoldOut] = useState(false);

  const visible = useMemo(() => {
    return items.filter((item) => {
      const tags = item.tags || [];
      const matchesQuery = `${item.name} ${item.description}`.toLowerCase().includes(query.toLowerCase());
      const matchesCategory = categoryId === "all" || Number(item.category_id) === Number(categoryId);
      const matchesTag = tag === "all" || tags.includes(tag);
      const matchesStock = showSoldOut || item.is_available;
      return matchesQuery && matchesCategory && matchesTag && matchesStock;
    });
  }, [items, query, categoryId, tag, showSoldOut]);

  const selectedCategory = categories.find((category) => Number(category.id) === Number(categoryId));
  const sections = categoryId === "all" ? categories : selectedCategory ? [selectedCategory] : [];

  return (
    <section className="page-enter space-y-3">
      <div className="flex items-end justify-between gap-3">
        <div>
          <p className="text-[11px] font-semibold uppercase tracking-[0.16em] text-clay">{settings.restaurant_name}</p>
          <h1 className="text-xl font-semibold tracking-tight text-ink">Menu</h1>
        </div>
        <p className="text-xs font-medium text-muted">{visible.length} dishes</p>
      </div>

      <div className="sticky top-16 z-20 space-y-2 rounded-2xl border border-line bg-card/95 p-2 shadow-soft backdrop-blur">
        <label className="relative block">
          <span className="sr-only">Search the menu</span>
          <Search size={14} className="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-muted" />
          <input value={query} onChange={(event) => setQuery(event.target.value)} placeholder="Search dishes" aria-label="Search dishes" className="h-9 w-full rounded-xl border border-line bg-paper pl-8 pr-3 text-sm text-ink" />
        </label>
        <div className="nav-scroll flex gap-1.5 overflow-x-auto">
          <button type="button" className={`h-8 shrink-0 rounded-full px-3 text-xs font-medium ${categoryId === "all" ? "bg-ink text-invert" : "border border-line bg-paper text-ink"}`} onClick={() => setCategoryId("all")}>All</button>
          {categories.map((category) => (
            <button type="button" key={category.id} className={`h-8 shrink-0 rounded-full px-3 text-xs font-medium ${Number(categoryId) === Number(category.id) ? "bg-ink text-invert" : "border border-line bg-paper text-ink"}`} onClick={() => setCategoryId(category.id)}>{category.name}</button>
          ))}
        </div>
        <div className="nav-scroll flex gap-1.5 overflow-x-auto">
          <button type="button" className={`h-8 shrink-0 rounded-full px-3 text-xs ${tag === "all" ? "bg-clay text-invert" : "border border-line bg-paper text-ink"}`} onClick={() => setTag("all")}>Any diet</button>
          {ALL_TAGS.map((item) => (
            <button type="button" key={item} className={`h-8 shrink-0 rounded-full px-3 text-xs ${tag === item ? "bg-clay text-invert" : "border border-line bg-paper text-ink"}`} onClick={() => setTag(item)}>{item}</button>
          ))}
          <button type="button" className={`h-8 shrink-0 rounded-full px-3 text-xs ${showSoldOut ? "bg-ink text-invert" : "border border-line bg-paper text-ink"}`} onClick={() => setShowSoldOut((value) => !value)}>{showSoldOut ? "Hide sold out" : "Sold out"}</button>
        </div>
      </div>

      {visible.length === 0 ? (
        <div className="rounded-2xl border border-dashed border-line bg-card px-4 py-8 text-center">
          <p className="text-sm font-semibold text-ink">Nothing matches that search</p>
          <button type="button" className="tap mt-3 h-9 rounded-full bg-ink px-4 text-xs font-semibold text-invert" onClick={() => { setQuery(""); setCategoryId("all"); setTag("all"); setShowSoldOut(false); }}>Show everything</button>
        </div>
      ) : (
        <div className="space-y-3">
          {sections.map((category) => {
            const dishes = visible.filter((item) => Number(item.category_id) === Number(category.id));
            if (!dishes.length) return null;
            return (
              <section key={category.id} className="overflow-hidden rounded-2xl border border-line bg-card">
                <div className="flex items-center justify-between border-b border-line px-3 py-2">
                  <h2 className="text-sm font-semibold text-ink">{category.name}</h2>
                  <p className="text-[11px] text-muted">{dishes.length}</p>
                </div>
                {dishes.map((item) => {
                  const tags = item.tags || [];
                  return (
                    <article key={item.id} className={`flex items-center gap-3 border-b border-line px-3 py-2 last:border-b-0 ${item.is_available ? "" : "opacity-60"}`}>
                      <div className="min-w-0 flex-1">
                        <div className="flex items-baseline justify-between gap-2">
                          <h3 className="truncate text-sm font-semibold text-ink">{item.name}</h3>
                          <p className="shrink-0 text-sm font-semibold text-ink">{money(settings.currency_symbol, item.price)}</p>
                        </div>
                        {item.description ? <p className="truncate text-xs text-muted">{item.description}</p> : null}
                        <p className="mt-0.5 truncate text-[11px] text-muted">{item.is_available ? "Ready" : "Sold out"}{tags.length ? ` · ${tags.join(", ")}` : ""}</p>
                      </div>
                      {item.is_available && onAddToPlate ? (
                        <button type="button" onClick={() => onAddToPlate(item)} className="h-8 shrink-0 rounded-full bg-ink px-3 text-xs font-semibold text-invert">Add</button>
                      ) : (
                        <span className="shrink-0 text-[11px] text-muted">Out</span>
                      )}
                    </article>
                  );
                })}
              </section>
            );
          })}
        </div>
      )}
    </section>
  );
}
