const DEFAULT_REFRESH_EVENT = "refreshClassDetailData";

const nowMarker = () => `${Date.now()}`;

const markerKey = (scope) => `${scope || "global"}NeedsRefresh`;

export const notifyRefresh = (scopes = [], eventName = DEFAULT_REFRESH_EVENT) => {
  const list = Array.isArray(scopes) ? scopes : [scopes];
  const marker = nowMarker();
  list
    .filter(Boolean)
    .forEach((scope) => {
      try {
        uni.setStorageSync(scope, marker);
        uni.setStorageSync(markerKey(scope), marker);
      } catch (e) {}
    });
  try {
    uni.$emit(eventName, marker);
  } catch (e) {}
  return marker;
};

export const consumeRefreshMarker = (key, consumedKey, lastConsumed = "") => {
  let marker = "";
  let consumed = "";
  try {
    marker = uni.getStorageSync(key) || "";
    consumed = uni.getStorageSync(consumedKey) || "";
  } catch (e) {}
  if (!marker || marker === consumed || marker === lastConsumed) {
    return "";
  }
  return marker;
};

export const markRefreshMarkerConsumed = (key, marker) => {
  if (!key || !marker) return;
  try {
    uni.setStorageSync(key, marker);
  } catch (e) {}
};
