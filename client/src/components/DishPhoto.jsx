import { useState } from "react";

export default function DishPhoto({ src, name, className = "h-24 w-24" }) {
  const [failed, setFailed] = useState(false);
  if (src && !failed) {
    return (
      <img
        src={src}
        alt={name ? name : "Dish photo"}
        className={`${className} rounded-2xl object-cover`}
        onError={() => setFailed(true)}
      />
    );
  }
  const mark = name ? name.trim().slice(0, 1).toUpperCase() : "?";
  return (
    <div className={`${className} flex items-center justify-center rounded-2xl bg-paper text-lg font-semibold text-muted`} aria-hidden={!name} role={name ? "img" : undefined} aria-label={name ? `${name}, no photo` : "No photo"}>
      {mark}
    </div>
  );
}
