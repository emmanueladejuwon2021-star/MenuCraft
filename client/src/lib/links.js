export function guestMenuLink() {
  const url = new URL(window.location.href);
  url.search = "";
  url.hash = "/menu";
  url.pathname = url.pathname.replace(/index\.html$/i, "");
  if (!url.pathname.endsWith("/")) url.pathname += "/";
  return url.toString();
}
