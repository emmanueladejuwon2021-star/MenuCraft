import KitchenNav from "../components/KitchenNav";

export default function StockToggle({ items, categories, onToggle }) {
  return (
    <section className="page-enter space-y-3">
      <KitchenNav title="Stock" hint="Tap a switch to mark a dish in stock or sold out." />
      {categories.map((category) => {
        const dishes = items.filter((item) => Number(item.category_id) === Number(category.id));
        return (
          <div key={category.id} className="rounded-2xl border border-line bg-card p-3 shadow-soft">
            <h2 className="mb-2 font-semibold">{category.name}</h2>
            <div className="space-y-2">
              {dishes.map((item) => (
                <div key={item.id} className="flex items-center justify-between gap-3 rounded-xl bg-paper px-3 py-2">
                  <div>
                    <p className="font-medium text-ink">{item.name}</p>
                    <p className={`text-xs ${item.is_available ? "text-pine" : "text-muted"}`}>{item.is_available ? "In Stock" : "Sold Out"}</p>
                  </div>
                  <button type="button" aria-pressed={item.is_available} onClick={() => onToggle(item.id, !item.is_available)} className={`tap relative w-16 rounded-full px-1 transition ${item.is_available ? "bg-pine" : "bg-line"}`}>
                    <span className={`block h-7 w-7 rounded-full bg-white shadow transition ${item.is_available ? "ml-auto" : "ml-0"}`} />
                  </button>
                </div>
              ))}
            </div>
          </div>
        );
      })}
    </section>
  );
}
