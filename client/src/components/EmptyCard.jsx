export default function EmptyCard({ title, buttonLabel, onClick }) {
  return (
    <div className="flex flex-col items-center justify-center rounded-2xl border border-dashed border-line bg-card px-6 py-10 text-center shadow-soft">
      <div className="mb-3 text-3xl">\ud83c\udf7d\ufe0f</div>
      <p className="mb-4 text-sm text-stone-600">{title}</p>
      <button
        type="button"
        onClick={onClick}
        className="tap rounded-full bg-ink px-5 text-sm font-semibold text-white"
      >
        {buttonLabel}
      </button>
    </div>
  );
}
