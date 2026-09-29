export default function StockToggle({ items, categories, onToggle }) {
  return (
    <section className="space-y-3">
      <p className="text-sm text-stone-600">Tap a switch to mark a dish in stock or sold out.</p>
      {categories.map((category) => {
        const dishes = items.filter((item) => Number(item.category_id) === Number(category.id));
        return (
          <div key={category.id} className="rounded-2xl border border-line bg-card p-3 shadow-soft">
            <h2 className="mb-2 font-semibold">{category.name}</h2>
            <div className="space-y-2">
              {dishes.map((item) => (
                <div key={item.id} className="flex items-center justify-between gap-3 rounded-xl bg-paper px-3 py-2">
                  <div>
                    <p className="font-medium">{item.name}</p>
                    <p className={`text-xs ${item.is_available ? "text-pine" : "text-stone-500"}`}>
                      {item.is_available ? "In Stock" : "Sold Out"}
                    </p>
                  </div>
                  <button
                    type="button"
                    aria-pressed={item.is_available}
                    onClick={() => onToggle(item.id, !item.is_available)}
                    className={`tap relative w-16 rounded-full px-1 transition ${
                      item.is_available ? "bg-pine" : "bg-stone-300"
                    }`}
                  >
                    <span
                      className={`block h-7 w-7 rounded-full bg-white shadow transition ${
                        item.is_available ? "ml-auto" : "ml-0"
                      }`}
                    />
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
