export default function Toast({ toast }) {
  if (!toast) return null;
  const tone = toast.tone === "error" ? "bg-red-700" : "bg-pine";
  return (
    <div className="fixed inset-x-0 top-3 z-[60] flex justify-center px-3">
      <div className={`${tone} flex max-w-md items-center gap-3 rounded-full px-4 py-2 text-sm font-medium text-invert shadow-soft`}>
        <span>{toast.text}</span>
        {toast.actionLabel && toast.onAction && (
          <button type="button" onClick={toast.onAction} className="rounded-full bg-white/20 px-3 py-1 text-xs font-semibold">
            {toast.actionLabel}
          </button>
        )}
      </div>
    </div>
  );
}
