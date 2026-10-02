function basePath() {
  const path = window.location.pathname.replace(/index\.html$/i, "");
  return path.endsWith("/") ? path : `${path}/`;
}

export function guestMenuLink() {
  return `${window.location.origin}${basePath()}#/menu`;
}

export function kitchenDoorLink() {
  return `${window.location.origin}${basePath()}#/iyabisi/kitchenlock`;
}
