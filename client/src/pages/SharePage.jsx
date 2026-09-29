import { useEffect, useState } from "react";
import QRCode from "qrcode";
import { guestMenuLink } from "../lib/links";

export default function SharePage({ settings, onSaveSettings, user }) {
  const [name, setName] = useState(settings.restaurant_name);
  const [symbol, setSymbol] = useState(settings.currency_symbol);
  const [qr, setQr] = useState("");
  const [note, setNote] = useState("");
  const link = guestMenuLink();

  useEffect(() => {
    setName(settings.restaurant_name);
    setSymbol(settings.currency_symbol);
  }, [settings.restaurant_name, settings.currency_symbol]);

  useEffect(() => {
    let cancelled = false;
    QRCode.toDataURL(link, {
      width: 480,
      margin: 2,
      errorCorrectionLevel: "H",
      color: { dark: "#1c1917", light: "#ffffff" },
    })
      .then((data) => {
        if (!cancelled) setQr(data);
      })
      .catch(() => {
        if (!cancelled) setNote("Could not draw the QR code. Copy the web link instead.");
      });
    return () => {
      cancelled = true;
    };
  }, [link]);

  async function copyLink() {
    try {
      if (navigator.clipboard?.writeText) {
        await navigator.clipboard.writeText(link);
      } else {
        const box = document.createElement("textarea");
        box.value = link;
        document.body.appendChild(box);
        box.select();
        document.execCommand("copy");
        box.remove();
      }
      setNote("Link copied. Guests can scan or paste it to open the live menu.");
    } catch {
      setNote("Copy did not work. Select the link and copy it yourself.");
    }
  }

  function downloadQr() {
    if (!qr) return;
    const anchor = document.createElement("a");
    anchor.href = qr;
    anchor.download = "menu-qr.png";
    document.body.appendChild(anchor);
    anchor.click();
    anchor.remove();
  }

  return (
    <section className="grid gap-4 md:grid-cols-[1fr_280px]">
      <form
        className="space-y-3 rounded-2xl border border-line bg-card p-4 shadow-soft"
        onSubmit={(event) => {
          event.preventDefault();
          if (!user) {
            setNote("Sign in first to save restaurant details.");
            return;
          }
          onSaveSettings({ restaurant_name: name, currency_symbol: symbol });
        }}
      >
        <h1 className="text-lg font-semibold text-ink">Share the live menu</h1>
        <p className="text-sm text-muted">Guests scan the code or open the link. They only see the menu, not kitchen tools.</p>
        {user && (
          <>
            <label className="block text-sm text-ink">
              Restaurant name
              <input
                value={name}
                onChange={(event) => setName(event.target.value)}
                className="tap mt-1 w-full rounded-xl border border-line bg-paper px-3 text-ink"
              />
            </label>
            <label className="block text-sm text-ink">
              Money symbol
              <input
                value={symbol}
                onChange={(event) => setSymbol(event.target.value)}
                className="tap mt-1 w-full rounded-xl border border-line bg-paper px-3 text-ink"
              />
            </label>
            <button className="tap rounded-full bg-ink px-5 text-sm font-semibold text-invert">Save details</button>
          </>
        )}
        <p className="text-sm text-muted">Guest link</p>
        <p className="break-all rounded-xl bg-paper px-3 py-2 text-sm text-ink">{link}</p>
        <button type="button" onClick={copyLink} className="tap rounded-full border border-line px-4 text-sm text-ink">
          Copy web link
        </button>
        {note && <p className="text-sm text-pine">{note}</p>}
      </form>
      <div className="rounded-2xl border border-line bg-card p-4 text-center shadow-soft">
        {qr ? (
          <img src={qr} alt="Scan to open the live menu" className="mx-auto w-56 rounded-xl bg-white p-2" />
        ) : (
          <p className="text-sm text-muted">Drawing the QR code…</p>
        )}
        <p className="mt-3 text-sm text-muted">Point a phone camera at this code to open the live menu.</p>
        <button type="button" onClick={downloadQr} disabled={!qr} className="tap mt-3 rounded-full bg-pine px-4 text-sm font-semibold text-invert disabled:opacity-60">
          Download QR image
        </button>
      </div>
    </section>
  );
}
