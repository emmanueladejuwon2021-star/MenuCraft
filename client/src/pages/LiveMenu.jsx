import { useMemo, useState } from "react";
import { Search } from "lucide-react";
import { ALL_TAGS } from "../lib/seed";
import { money } from "../lib/store";
import PageHeader from "../components/PageHeader.jsx";

export default function LiveMenu({ settings, categories, items, onAddToPlate }) {
  const [query, setQuery] = useState("");
  const [categoryId, setCategoryId] = useState("all");
  const [tag, setTag] = useState("all");
  const [showSoldOut, setShowSoldOut] = useState(false);

  const visible = useMemo(() => {
    return items.filter((item) => {
      const matchesQuery = `${item.name} ${item.description}`.toLowerCase().includes(query.toLowerCase());
      const matchesCategory = categoryId === "all" || Number(item.category_id) === Number(categoryId);
      const matchesTag = tag === "all" || item.tags.includes(tag);
      const matchesStock = showSoldOut || item.is_available;
      return matchesQuery && matchesCategory && matchesTag && matchesStock;
    });
  }, [items, query, categoryId, tag, showSoldOut]);

  return (
    <section className="page-enter space-y-5">
      <PageHeader
        kicker={settings.restaurant_name}
        title="Today's menu"
        hint="Choose a category, add in-stock food to your plate, then pay."
      />

      <div className="rounded-3xl border border-line bg-card p-4 shadow-soft">
        <div className="relative">
          <Search size={16} className="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-muted" />
          <input value={query} onChange={(event) => setQuery(event.target.value)} placeholder="Search jollof, soup, drinks…" className="tap h-11 w-full rounded-2xl border border-line bg-paper pl-9 pr-3 text-ink" />
        </div>
        <div className="mt-3 flex gap-2 overflow-x-auto pb-1">
          <button className={`tap h-10 shrink-0 rounded-full px-4 text-sm ${categoryId === "all" ? "bg-ink text-invert" : "border border-line bg-paper text-ink"}`} onClick={() => setCategoryId("all")}>All dishes</button>
          {categories.map((category) => (
            <button key={category.id} className={`tap h-10 shrink-0 rounded-full px-4 text-sm ${Number(categoryId) === Number(category.id) ? "bg-ink text-invert" : "border border-line bg-paper text-ink"}`} onClick={() => setCategoryId(category.id)}>{category.name}</button>
          ))}
        </div>
        <div className="mt-2 flex gap-2 overflow-x-auto pb-1">
          <button className={`tap h-10 shrink-0 rounded-full px-4 text-sm ${tag === "all" ? "bg-clay text-invert" : "border border-line bg-paper text-ink"}`} onClick={() => setTag("all")}>Any diet</button>
          {ALL_TAGS.map((item) => (
            <button key={item} className={`tap h-10 shrink-0 rounded-full px-4 text-sm ${tag === item ? "bg-clay text-invert" : "border border-line bg-paper text-ink"}`} onClick={() => setTag(item)}>{item}</button>
          ))}
          <button className={`tap h-10 shrink-0 rounded-full px-4 text-sm ${showSoldOut ? "bg-ink text-invert" : "border border-line bg-paper text-ink"}`} onClick={() => setShowSoldOut((value) => !value)}>{showSoldOut ? "Hide sold out" : "Show sold out"}</button>
        </div>
      </div>

      {categories.map((category) => {
        const dishes = visible.filter((item) => Number(item.category_id) === Number(category.id));
        if (categoryId !== "all" && Number(categoryId) !== Number(category.id)) return null;
        return (
          <section key={category.id} className="space-y-3">
            <div className="flex items-end justify-between gap-3 px-1">
              <h2 className="text-lg font-semibold text-ink">{category.name}</h2>
              <p className="text-xs text-muted">{dishes.length} on the list</p>
            </div>
            {dishes.length === 0 ? (
              <div className="rounded-3xl border border-dashed border-line bg-card px-4 py-10 text-center text-sm text-muted">Nothing in this list right now.</div>
            ) : (
              <div className="space-y-3">
                {dishes.map((item) => (
                  <article key={item.id} className={`overflow-hidden rounded-3xl border border-line bg-card shadow-soft ${item.is_available ? "" : "opacity-60"}`}>
                    <div className="flex gap-3 p-3 sm:gap-4 sm:p-4">
                      {item.image_url ? (
                        <img src={item.image_url} alt="" className="h-24 w-24 shrink-0 rounded-2xl object-cover sm:h-28 sm:w-28" />
                      ) : (
                        <div className="flex h-24 w-24 shrink-0 items-center justify-center rounded-2xl bg-paper text-xs text-muted sm:h-28 sm:w-28">No photo</div>
                      )}
                      <div className="min-w-0 flex-1">
                        <div className="flex items-start justify-between gap-3">
                          <h3 className="text-base font-semibold leading-snug text-ink">{item.name}</h3>
                          <p className="shrink-0 text-base font-semibold text-ink">{money(settings.currency_symbol, item.price)}</p>
                        </div>
                        <p className="mt-1 line-clamp-2 text-sm leading-relaxed text-muted">{item.description}</p>
                        <div className="mt-2 flex flex-wrap gap-1.5">
                          <span className={`rounded-full px-2.5 py-1 text-xs font-semibold ${item.is_available ? "bg-paper text-pine" : "bg-paper text-muted"}`}>{item.is_available ? "In Stock" : "Sold Out"}</span>
                          {item.tags.map((itemTag) => (
                            <span key={itemTag} className="rounded-full bg-paper px-2.5 py-1 text-xs text-muted">{itemTag}</span>
                          ))}
                        </div>
                      </div>
                    </div>
                    {item.is_available && onAddToPlate && (
                      <div className="border-t border-line px-3 py-3 sm:px-4">
                        <button type="button" onClick={() => onAddToPlate(item)} className="tap h-11 w-full rounded-full bg-ink text-sm font-semibold text-invert">Add to plate</button>
                      </div>
                    )}
                  </article>
                ))}
              </div>
            )}
          </section>
        );
      })}
    </section>
  );
}
