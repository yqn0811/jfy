import { checkLoginStatus, requireShareLogin } from "@/common/request/api.js";
import { getAuthToken } from "@/common/helper/auth.js";

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

export function ensureSharedPageLogin(route, options = {}, uid = "", config = {}) {
  const ownerUid = normalizeShareLoginValue(uid || options.uid || options.target_user_id);
  const source = normalizeShareLoginValue(options.source);
  const strict = config === true || !!(config && config.strict);
  if (!ownerUid && source !== "share") {
    return true;
  }
  if (strict ? checkLoginStatus() : checkLoginStatus() || getAuthToken()) {
    return true;
  }
  const redirectUrl = buildShareLoginPath(route, options);
  return requireShareLogin(ownerUid, redirectUrl);
}
