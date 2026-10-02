export default function PageHeader({ kicker, title, hint }) {
  return (
    <header className="overflow-hidden rounded-[2rem] border border-line bg-card shadow-soft">
      <div className="bg-gradient-to-br from-blue-200/70 to-transparent px-5 py-6 dark:from-blue-500/15">
        {kicker ? <p className="text-xs font-semibold uppercase tracking-[0.18em] text-clay">{kicker}</p> : null}
        <h1 className="mt-1 text-2xl font-semibold leading-tight text-ink md:text-3xl">{title}</h1>
        {hint ? <p className="mt-2 max-w-2xl text-sm leading-relaxed text-muted">{hint}</p> : null}
      </div>
    </header>
  );
}
