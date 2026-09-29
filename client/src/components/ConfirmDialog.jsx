export default function ConfirmDialog({ box, onClose }) {
  if (!box) return null;
  return (
    <div className="fixed inset-0 z-[70] flex items-end justify-center bg-black/40 p-4 sm:items-center">
      <div className="w-full max-w-md rounded-3xl border border-line bg-card p-5 text-ink shadow-soft">
        <h2 className="text-lg font-semibold">{box.title}</h2>
        <p className="mt-2 text-sm leading-relaxed text-muted">{box.body}</p>
        <div className="mt-5 grid grid-cols-2 gap-2">
          <button type="button" onClick={onClose} className="tap rounded-full border border-line bg-paper text-sm font-medium">
            Keep it
          </button>
          <button
            type="button"
            onClick={() => {
              box.onYes();
              onClose();
            }}
            className="tap rounded-full bg-ink text-sm font-semibold text-invert"
          >
            {box.yesLabel || "Yes, continue"}
          </button>
        </div>
      </div>
    </div>
  );
}
