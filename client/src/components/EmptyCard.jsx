export default function EmptyCard({ title, buttonLabel, onClick }) {
  return (
    <div className="flex flex-col items-center justify-center rounded-2xl border border-dashed border-line bg-card px-6 py-10 text-center shadow-soft">
      <div className="mb-3 text-3xl" aria-hidden="true">🍽️</div>
      <p className="mb-4 text-sm text-muted">{title}</p>
      <button
        type="button"
        onClick={onClick}
        className="tap rounded-full bg-pine px-5 text-sm font-semibold text-invert"
      >
        {buttonLabel}
      </button>
    </div>
  );
}
