export const KITCHEN_SLUG = "iyabisi";
export const KITCHEN_LOCK = "kitchenlock";
export const kitchenLockPath = `/${KITCHEN_SLUG}/${KITCHEN_LOCK}`;

export function isKitchenLockPath(pathname) {
  const clean = String(pathname || "")
    .replace(/\/+$/, "")
    .toLowerCase();
  return clean === kitchenLockPath;
}
