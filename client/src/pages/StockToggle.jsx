import KitchenNav from "../components/KitchenNav";

export default function StockToggle({ items, categories, onToggle }) {
  return (
    <section className="page-enter space-y-3">
      <KitchenNav title="Stock" hint="Tap a switch to mark a dish in stock or sold out." />
      {categories.length === 0 ? (
        <div className="rounded-3xl border border-dashed border-line bg-card px-5 py-12 text-center">
          <p className="font-semibold text-ink">No lists yet</p>
          <p className="mt-2 text-sm text-muted">Add a category on the dishes page, then the switches show up here.</p>
        </div>
      ) : categories.map((category) => {
        const dishes = items.filter((item) => Number(item.category_id) === Number(category.id));
        return (
          <div key={category.id} className="rounded-2xl border border-line bg-card p-3 shadow-soft">
            <h2 className="mb-2 font-semibold text-ink">{category.name}</h2>
            {dishes.length === 0 ? (
              <p className="rounded-xl bg-paper px-3 py-3 text-sm text-muted">No dishes on this list.</p>
            ) : (
              <div className="space-y-2">
                {dishes.map((item) => (
                  <div key={item.id} className="flex items-center justify-between gap-3 rounded-xl bg-paper px-3 py-2">
                    <div className="min-w-0">
                      <p className="font-medium text-ink">{item.name}</p>
                      <p className={`text-xs font-semibold ${item.is_available ? "text-pine" : "text-danger"}`}>{item.is_available ? "In stock" : "Sold out"}</p>
                    </div>
                    <button
                      type="button"
                      aria-pressed={item.is_available}
                      aria-label={item.is_available ? `Mark ${item.name} sold out` : `Put ${item.name} back in stock`}
                      onClick={() => onToggle(item.id, !item.is_available)}
                      className={`tap relative h-11 w-[4.5rem] shrink-0 rounded-full border px-1 ${item.is_available ? "border-pine bg-pine" : "border-line bg-card"}`}
                    >
                      <span className={`block h-8 w-8 rounded-full shadow ${item.is_available ? "ml-auto bg-invert" : "ml-0 bg-ink"}`} />
                    </button>
                  </div>
                ))}
              </div>
            )}
          </div>
        );
      })}
    </section>
  );
}
