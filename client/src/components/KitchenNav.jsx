export default function KitchenNav({ title, hint }) {
  if (!title && !hint) return null;
  return (
    <div>
      {title ? <h1 className="text-lg font-semibold text-ink">{title}</h1> : null}
      {hint ? <p className="mt-0.5 text-sm text-muted">{hint}</p> : null}
    </div>
  );
}
