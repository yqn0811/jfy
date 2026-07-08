export const REFRESH_MARKERS = {
  product: "productListNeedsRefresh",
  category: "categoryListNeedsRefresh",
  home: "homeDataNeedsRefresh",
};

const REFRESH_EVENTS = {
  product: [
    "refreshIndexData",
    "refreshProductManageData",
    "refreshProductDetailsSelfData",
    "refreshProductlData",
  ],
  category: [
    "refreshIndexData",
    "refreshClassManageData",
    "refreshClassDetailData",
  ],
  home: ["refreshIndexData"],
};

export function createRefreshMarker() {
  const random = Math.random().toString(36).slice(2, 8);
  return `${Date.now()}_${random}`;
}

export function markRefresh(types = [], marker = createRefreshMarker()) {
  const refreshTypes = Array.isArray(types) ? types : [types];
  Array.from(new Set(refreshTypes)).forEach((type) => {
    const key = REFRESH_MARKERS[type];
    if (key) {
      uni.setStorageSync(key, marker);
    }
  });
  return marker;
}

export function emitRefreshEvents(types = [], marker = createRefreshMarker()) {
  const refreshTypes = Array.isArray(types) ? types : [types];
  const events = new Set();
  refreshTypes.forEach((type) => {
    (REFRESH_EVENTS[type] || []).forEach((eventName) => events.add(eventName));
  });
  events.forEach((eventName) => {
    uni.$emit(eventName, marker);
  });
  return marker;
}

export function notifyRefresh(types = [], marker = createRefreshMarker()) {
  markRefresh(types, marker);
  emitRefreshEvents(types, marker);
  return marker;
}

export function notifyFolderRefresh(folderType, extraTypes = []) {
  const types = Number(folderType) === 2 ? ["product"] : ["category"];
  return notifyRefresh([...types, "home", ...extraTypes]);
}

export function getRefreshMarker(type) {
  const key = REFRESH_MARKERS[type];
  return key ? uni.getStorageSync(key) : "";
}

export function markRefreshMarkerConsumed(consumedKey, marker) {
  if (!consumedKey || !marker) return;
  uni.setStorageSync(consumedKey, marker);
}

export function consumeRefreshMarker(type, consumedKey, lastMarker = "") {
  const marker = getRefreshMarker(type);
  const consumedMarker = consumedKey ? uni.getStorageSync(consumedKey) : "";
  if (!marker || marker === lastMarker || marker === consumedMarker) {
    return "";
  }
  markRefreshMarkerConsumed(consumedKey, marker);
  return marker;
}
