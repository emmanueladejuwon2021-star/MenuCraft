export default function Toast({ toast }) {
  if (!toast) return null;
  const tone = toast.tone === "error" ? "border border-line bg-card text-danger" : "bg-pine text-invert";
  return (
    <div className="fixed inset-x-0 top-[4.6rem] z-[60] flex justify-center px-3" role="status">
      <div className={`${tone} flex max-w-md items-center gap-3 rounded-2xl px-4 py-2 text-sm font-medium shadow-soft`}>
        <span className="min-w-0 flex-1">{toast.text}</span>
        {toast.actionLabel && toast.onAction ? (
          <button type="button" onClick={toast.onAction} className="tap rounded-full bg-paper px-4 text-sm font-semibold text-ink">
            {toast.actionLabel}
          </button>
        ) : null}
      </div>
    </div>
  );
}
