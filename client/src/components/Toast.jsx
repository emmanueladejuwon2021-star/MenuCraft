export default function Toast({ toast }) {
  if (!toast) return null;
  const tone = toast.tone === "error" ? "bg-red-700" : "bg-pine";
  return (
    <div className="pointer-events-none fixed inset-x-0 top-3 z-[60] flex justify-center px-3">
      <div className={`${tone} max-w-md rounded-full px-4 py-2 text-sm font-medium text-white shadow-soft`}>
        {toast.text}
      </div>
    </div>
  );
}
