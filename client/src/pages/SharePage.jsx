import { useEffect, useState } from "react";
import QRCode from "qrcode";

export default function SharePage({ settings, onSaveSettings }) {
  const [name, setName] = useState(settings.restaurant_name);
  const [symbol, setSymbol] = useState(settings.currency_symbol);
  const [qr, setQr] = useState("");
  const link = `${window.location.origin}${window.location.pathname}#/menu`;

  useEffect(() => {
    QRCode.toDataURL(link, { width: 360, margin: 1 }).then(setQr);
  }, [link]);

  function copyLink() {
    navigator.clipboard.writeText(link);
  }

  function downloadQr() {
    const anchor = document.createElement("a");
    anchor.href = qr;
    anchor.download = "menucraft-qr.png";
    anchor.click();
  }

  return (
    <section className="grid gap-4 md:grid-cols-[1fr_280px]">
      <form
        className="space-y-3 rounded-2xl border border-line bg-card p-4 shadow-soft"
        onSubmit={(event) => {
          event.preventDefault();
          onSaveSettings({ restaurant_name: name, currency_symbol: symbol });
        }}
      >
        <label className="block text-sm">
          Restaurant name
          <input
            value={name}
            onChange={(event) => setName(event.target.value)}
            className="tap mt-1 w-full rounded-xl border border-line bg-paper px-3"
          />
        </label>
        <label className="block text-sm">
          Money symbol
          <input
            value={symbol}
            onChange={(event) => setSymbol(event.target.value)}
            className="tap mt-1 w-full rounded-xl border border-line bg-paper px-3"
          />
        </label>
        <button className="tap rounded-full bg-ink px-5 text-sm font-semibold text-white">Save details</button>
        <p className="text-sm text-stone-600">Guests open this link to see the live menu:</p>
        <p className="break-all rounded-xl bg-paper px-3 py-2 text-sm">{link}</p>
        <button type="button" onClick={copyLink} className="tap rounded-full border border-line px-4 text-sm">
          Copy web link
        </button>
      </form>
      <div className="rounded-2xl border border-line bg-card p-4 text-center shadow-soft">
        {qr && <img src={qr} alt="Menu QR code" className="mx-auto w-56" />}
        <button type="button" onClick={downloadQr} className="tap mt-3 rounded-full bg-pine px-4 text-sm font-semibold text-white">
          Download QR image
        </button>
      </div>
    </section>
  );
}
