import { useMemo, useState } from "react";
import { Search } from "lucide-react";
import { ALL_TAGS } from "../lib/seed";
import { money } from "../lib/store";
import DishPhoto from "../components/DishPhoto.jsx";

function countLabel(count, one, many) {
  return `${count} ${count === 1 ? one : many}`;
}

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
  const chip = (on) => `tap h-11 shrink-0 rounded-full px-4 text-sm font-medium ${on ? "bg-pine text-invert" : "border border-line bg-paper text-ink"}`;

  const hasActiveFilters = query || categoryId !== "all" || tag !== "all" || showSoldOut;

  return (
    <section className="page-enter space-y-4 pb-8">
      <div className="flex items-end justify-between gap-3">
        <div>
          <p className="text-xs font-semibold uppercase tracking-[0.16em] text-clay">{settings.restaurant_name}</p>
          <h1 className="mt-1 text-3xl font-semibold tracking-tight text-ink">Today's board</h1>
        </div>
        <p className="text-sm font-medium text-ink">{countLabel(visible.length, "dish", "dishes")}</p>
      </div>

      <div className="sticky top-[4.25rem] z-20 space-y-3 rounded-[1.4rem] border border-line bg-card/95 p-3 shadow-soft backdrop-blur">
        <label className="relative block">
          <span className="sr-only">Search the board</span>
          <Search size={16} className="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-muted" />
          <input value={query} onChange={(event) => setQuery(event.target.value)} placeholder="Search the board" aria-label="Search the board" className="tap h-12 w-full rounded-2xl border border-line bg-paper pl-9 pr-16 text-ink" />
          {query ? (
            <button type="button" className="absolute right-2 top-1/2 -translate-y-1/2 rounded-full px-2 text-sm font-medium text-pine" onClick={() => setQuery("")}>Clear</button>
          ) : null}
        </label>
        <div className="nav-scroll flex gap-2 overflow-x-auto pb-1">
          <button type="button" className={chip(categoryId === "all")} onClick={() => setCategoryId("all")}>Whole board</button>
          {categories.map((category) => (
            <button type="button" key={category.id} className={chip(Number(categoryId) === Number(category.id))} onClick={() => setCategoryId(category.id)}>{category.name}</button>
          ))}
        </div>
        <div className="flex flex-wrap gap-2">
          <button type="button" className={chip(tag === "all")} onClick={() => setTag("all")}>Any diet</button>
          {ALL_TAGS.map((item) => (
            <button type="button" key={item} className={chip(tag === item)} onClick={() => setTag(item)}>{item}</button>
          ))}
          <button type="button" className={chip(showSoldOut)} onClick={() => setShowSoldOut((value) => !value)}>{showSoldOut ? "Hide sold out" : "Show sold out"}</button>
          {hasActiveFilters ? (
            <button type="button" className="tap h-11 shrink-0 rounded-full border border-line bg-paper px-4 text-sm font-medium text-pine" onClick={() => { setQuery(""); setCategoryId("all"); setTag("all"); setShowSoldOut(false); }}>Reset filters</button>
          ) : null}
        </div>
      </div>
      {visible.length === 0 ? (
        <div className="rounded-[2rem] border border-dashed border-line bg-card px-5 py-12 text-center">
          <p className="text-lg font-semibold text-ink">Nothing matches that search</p>
          <p className="mt-2 text-sm text-muted">Clear the search or pick another list.</p>
          <button type="button" className="tap mt-5 rounded-full bg-pine px-5 text-sm font-semibold text-invert" onClick={() => { setQuery(""); setCategoryId("all"); setTag("all"); setShowSoldOut(false); }}>Show the whole board</button>
        </div>
      ) : (
        <div className="space-y-5">
          {sections.map((category) => {
            const dishes = visible.filter((item) => Number(item.category_id) === Number(category.id));
            if (!dishes.length) return null;
            return (
              <section key={category.id} className="overflow-hidden rounded-[1.6rem] border border-line bg-card shadow-soft">
                <div className="flex items-center justify-between border-b border-line px-4 py-3">
                  <h2 className="text-lg font-semibold text-ink">{category.name}</h2>
                  <p className="text-xs text-muted">{countLabel(dishes.length, "dish", "dishes")}</p>
                </div>
                {dishes.map((item) => {
                  const tags = item.tags || [];
                  return (
                    <article key={item.id} className={`flex flex-col gap-3 border-b border-line px-4 py-3 last:border-b-0 sm:flex-row sm:items-center ${item.is_available ? "" : "opacity-70"}`}>
                      <div className="flex min-w-0 flex-1 items-center gap-3">
                        <DishPhoto src={item.image_url} name={item.name} className="h-20 w-20 shrink-0" />
                        <div className="min-w-0 flex-1">
                          <div className="flex items-baseline justify-between gap-3">
                            <h3 className="min-w-0 break-words text-base font-semibold text-ink">{item.name}</h3>
                            <p className="shrink-0 text-base font-semibold text-ink">{money(settings.currency_symbol, item.price)}</p>
                          </div>
                          {item.description ? <p className="mt-1 text-sm leading-relaxed text-muted">{item.description}</p> : null}
                          <p className="mt-2 text-xs font-semibold text-ink">{item.is_available ? "Ready" : "Sold out"}{tags.length ? <span className="font-normal text-muted">{` · ${tags.join(", ")}`}</span> : null}</p>
                        </div>
                      </div>
                      {item.is_available && onAddToPlate ? (
                        <button type="button" onClick={() => onAddToPlate(item)} className="tap h-11 w-full shrink-0 rounded-full bg-pine px-5 text-sm font-semibold text-invert sm:w-auto">Add to plate</button>
                      ) : (
                        <span className="inline-flex h-11 shrink-0 items-center justify-center rounded-full bg-paper px-3 text-xs font-semibold text-ink">Sold out</span>
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
