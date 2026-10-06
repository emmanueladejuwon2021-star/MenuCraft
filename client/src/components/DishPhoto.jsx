import { useState } from "react";

export default function DishPhoto({ src, name, className = "h-24 w-24" }) {
  const [broken, setBroken] = useState(false);
  if (src && !broken) {
    return <img src={src} alt={name ? name : "Dish photo"} className={`${className} rounded-2xl object-cover`} onError={() => setBroken(true)} />;
  }
  const mark = name ? name.trim().slice(0, 1).toUpperCase() : "?";
  return (
    <div className={`${className} flex items-center justify-center rounded-2xl bg-paper text-lg font-semibold text-muted`} aria-hidden={!name} role={name ? "img" : undefined} aria-label={name ? `${name}, no photo` : "No photo"}>
      {mark}
    </div>
  );
}
