export const normalizeAuthToken = (value) => {
  if (value === null || value === undefined) return "";
  const token = String(value).replace(/^Bearer\s+/i, "").trim();
  if (!token || token === "null" || token === "undefined") return "";
  return token;
};

export const getAuthToken = () => {
  const token = normalizeAuthToken(uni.getStorageSync("token"));
  if (token) return token;

  const legacyToken = normalizeAuthToken(uni.getStorageSync("TOKEN"));
  if (legacyToken) {
    uni.setStorageSync("token", legacyToken);
    uni.removeStorageSync("TOKEN");
  }
  return legacyToken;
};

export const buildAuthHeader = (token = "") => {
  const authToken = normalizeAuthToken(token) || getAuthToken();
  return authToken ? { "authorization-token": `Bearer ${authToken}` } : {};
};
