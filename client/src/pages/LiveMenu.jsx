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
    <section className="page-enter space-y-6">
      <div className="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
          <p className="text-xs font-semibold uppercase tracking-[0.2em] text-clay">{settings.restaurant_name}</p>
          <h1 className="mt-1 text-3xl font-semibold tracking-tight text-ink">Today’s board</h1>
          <p className="mt-2 max-w-xl text-sm leading-relaxed text-muted">Names and prices first. Add what you want, then send the plate to the kitchen.</p>
        </div>
        <p className="text-sm font-medium text-ink">{visible.length} {visible.length === 1 ? "dish" : "dishes"}</p>
      </div>

      <div className="grid gap-6 lg:grid-cols-[220px_1fr]">
        <aside className="space-y-4 lg:sticky lg:top-24 lg:self-start">
          <div className="rounded-[1.6rem] border border-line bg-card p-4 shadow-soft">
            <label className="relative block">
              <span className="sr-only">Search the board</span>
              <Search size={16} className="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-muted" />
              <input value={query} onChange={(event) => setQuery(event.target.value)} placeholder="Search the board" aria-label="Search the board" className="tap h-12 w-full rounded-2xl border border-line bg-paper pl-9 pr-3 text-ink" />
            </label>
            <p className="mt-4 text-xs font-semibold uppercase tracking-[0.16em] text-muted">Lists</p>
            <div className="nav-scroll mt-2 flex gap-2 overflow-x-auto pb-1 lg:flex-col lg:overflow-visible">
              <button type="button" className={`tap h-11 shrink-0 rounded-2xl px-4 text-left text-sm ${categoryId === "all" ? "bg-ink text-invert" : "border border-line bg-paper text-ink"}`} onClick={() => setCategoryId("all")}>Whole board</button>
              {categories.map((category) => (
                <button type="button" key={category.id} className={`tap h-11 shrink-0 rounded-2xl px-4 text-left text-sm ${Number(categoryId) === Number(category.id) ? "bg-ink text-invert" : "border border-line bg-paper text-ink"}`} onClick={() => setCategoryId(category.id)}>{category.name}</button>
              ))}
            </div>
          </div>
          <div className="rounded-[1.6rem] border border-line bg-card p-4 shadow-soft">
            <p className="text-xs font-semibold uppercase tracking-[0.16em] text-muted">Diet</p>
            <div className="mt-2 flex flex-wrap gap-2">
              <button type="button" className={`tap h-11 rounded-full px-4 text-sm ${tag === "all" ? "bg-clay text-invert" : "border border-line bg-paper text-ink"}`} onClick={() => setTag("all")}>Any</button>
              {ALL_TAGS.map((item) => (
                <button type="button" key={item} className={`tap h-11 rounded-full px-4 text-sm ${tag === item ? "bg-clay text-invert" : "border border-line bg-paper text-ink"}`} onClick={() => setTag(item)}>{item}</button>
              ))}
            </div>
            <button type="button" className={`tap mt-3 h-11 w-full rounded-full px-4 text-sm ${showSoldOut ? "bg-ink text-invert" : "border border-line bg-paper text-ink"}`} onClick={() => setShowSoldOut((value) => !value)}>{showSoldOut ? "Hide sold out" : "Show sold out"}</button>
          </div>
        </aside>

        <div className="space-y-8">
          {visible.length === 0 ? (
            <div className="rounded-[2rem] border border-dashed border-line bg-card px-5 py-14 text-center">
              <p className="text-lg font-semibold text-ink">Nothing matches that search</p>
              <p className="mt-2 text-sm text-muted">Clear the search or pick another list.</p>
              <button type="button" className="tap mt-5 rounded-full bg-ink px-5 text-sm font-semibold text-invert" onClick={() => { setQuery(""); setCategoryId("all"); setTag("all"); setShowSoldOut(false); }}>Show the whole board</button>
            </div>
          ) : (
            sections.map((category) => {
              const dishes = visible.filter((item) => Number(item.category_id) === Number(category.id));
              if (!dishes.length) return null;
              return (
                <section key={category.id} className="overflow-hidden rounded-[2rem] border border-line bg-card shadow-soft">
                  <div className="flex items-end justify-between gap-3 border-b border-line px-5 py-4">
                    <h2 className="text-xl font-semibold text-ink">{category.name}</h2>
                    <p className="text-xs text-muted">{dishes.length} on this list</p>
                  </div>
                  <div>
                    {dishes.map((item) => {
                      const tags = item.tags || [];
                      return (
                        <article key={item.id} className={`grid gap-3 border-b border-line px-5 py-4 last:border-b-0 sm:grid-cols-[1fr_auto] sm:items-center ${item.is_available ? "" : "opacity-60"}`}>
                          <div className="min-w-0">
                            <div className="flex items-baseline gap-3">
                              <h3 className="text-base font-semibold text-ink">{item.name}</h3>
                              <span className="hidden min-w-8 flex-1 border-b border-dotted border-line sm:block" />
                              <p className="shrink-0 text-base font-semibold text-ink sm:hidden">{money(settings.currency_symbol, item.price)}</p>
                            </div>
                            <p className="mt-1 text-sm leading-relaxed text-muted">{item.description}</p>
                            <div className="mt-2 flex flex-wrap gap-1.5">
                              <span className="rounded-full bg-paper px-2.5 py-1 text-xs font-semibold text-ink">{item.is_available ? "Ready" : "Sold out"}</span>
                              {tags.map((itemTag) => (
                                <span key={itemTag} className="rounded-full bg-paper px-2.5 py-1 text-xs text-muted">{itemTag}</span>
                              ))}
                            </div>
                          </div>
                          <div className="flex items-center justify-between gap-3 sm:flex-col sm:items-end">
                            <p className="hidden text-lg font-semibold text-ink sm:block">{money(settings.currency_symbol, item.price)}</p>
                            {item.is_available && onAddToPlate ? (
                              <button type="button" onClick={() => onAddToPlate(item)} className="tap h-11 rounded-full bg-ink px-5 text-sm font-semibold text-invert">Add to plate</button>
                            ) : (
                              <p className="text-sm text-muted">Not on the fire</p>
                            )}
                          </div>
                        </article>
                      );
                    })}
                  </div>
                </section>
              );
            })
          )}
        </div>
      </div>
    </section>
  );
}
