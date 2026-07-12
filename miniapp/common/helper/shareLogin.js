import { requireShareLogin } from "@/common/request/api.js";

export function normalizeShareLoginValue(value = "") {
  if (value === null || value === undefined) return "";
  const text = String(value).trim();
  if (!text || text === "null" || text === "undefined") return "";
  return text;
}

export function buildShareLoginPath(route = "", options = {}) {
  const query = Object.keys(options || {})
    .filter((key) => options[key] !== undefined && options[key] !== null && options[key] !== "")
    .map((key) => `${encodeURIComponent(key)}=${encodeURIComponent(options[key])}`)
    .join("&");
  return `/${String(route || "").replace(/^\//, "")}${query ? `?${query}` : ""}`;
}

export function ensureSharedPageLogin(route, options = {}, uid = "") {
  const ownerUid = normalizeShareLoginValue(uid || options.uid || options.target_user_id);
  const source = normalizeShareLoginValue(options.source);
  if (!ownerUid && source !== "share") {
    return true;
  }
  const redirectUrl = buildShareLoginPath(route, options);
  return requireShareLogin(ownerUid, redirectUrl);
}
