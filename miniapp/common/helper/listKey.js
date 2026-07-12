const DEFAULT_KEY_FIELDS = [
  "id",
  "pic_id",
  "picture_id",
  "folder_id",
  "fid",
  "uid",
  "user_id",
  "resource_id",
  "case_id",
  "record_id",
  "category_id",
  "name",
  "title",
  "folder_name",
  "picture_url",
  "imgurl",
  "new_thumb",
  "url",
];

const normalizeKeyPart = (value) => {
  if (value === null || value === undefined) return "";
  const text = String(value).trim();
  if (!text || text === "null" || text === "undefined") return "";
  return text;
};

export const buildListItemKey = (item, index = 0, prefix = "item", fields = DEFAULT_KEY_FIELDS) => {
  if (!item || typeof item !== "object") {
    return `${prefix}-${index}`;
  }

  for (let i = 0; i < fields.length; i++) {
    const field = fields[i];
    const keyPart = normalizeKeyPart(item[field]);
    if (keyPart) return `${prefix}-${keyPart}`;
  }

  return `${prefix}-${index}`;
};
