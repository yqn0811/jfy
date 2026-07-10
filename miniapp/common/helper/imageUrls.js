const normalizeText = (value) => {
  if (value === null || value === undefined) return "";
  const text = String(value).trim();
  if (!text || text === "null" || text === "undefined") return "";
  return text;
};

const pickFirst = (...values) => {
  for (const value of values) {
    const text = normalizeText(value);
    if (text) return text;
  }
  return "";
};

const isControlledDownloadUrl = (value) => normalizeText(value).indexOf("/api/user/download/original") !== -1;

const pickDisplayUrl = (...values) => {
  for (const value of values) {
    const text = normalizeText(value);
    if (text && !isControlledDownloadUrl(text)) return text;
  }
  return "";
};

export const getImageUrls = (item = {}) => {
  const urls = item.image_urls || item.imageUrls || item.urls || {};
  const origin = pickDisplayUrl(
    urls.origin,
    item.picture_url_original,
    item.original_url,
    item.originalUrl,
    item.file_url,
  );
  const preview = pickDisplayUrl(
    urls.preview,
    item.preview_url,
    item.picture_url,
    item.imgurl,
    item.imageField,
    item.src,
    item.url,
    origin,
  );
  const edit = pickDisplayUrl(urls.edit, item.edit_url, item.editUrl, preview, origin);
  const thumb = pickDisplayUrl(
    urls.thumb,
    item.thumbnail_url,
    item.thumbnailUrl,
    item.thumb_url,
    item.thumbUrl,
    preview,
    edit,
    origin,
  );
  const download = pickFirst(urls.download, item.download_url, item.downloadUrl, origin, edit, preview, thumb);
  return {
    thumb,
    preview,
    edit,
    origin,
    download,
  };
};

export const imageUrlFor = (item = {}, usage = "preview") => {
  const urls = getImageUrls(item);
  if (usage === "thumb") return pickDisplayUrl(urls.thumb, urls.preview, urls.edit, urls.origin, urls.download);
  if (usage === "edit") return pickDisplayUrl(urls.edit, urls.preview, urls.origin, urls.download, urls.thumb);
  if (usage === "origin") return pickDisplayUrl(urls.origin, urls.download, urls.edit, urls.preview, urls.thumb);
  if (usage === "download") return pickFirst(urls.download, urls.origin, urls.edit, urls.preview, urls.thumb);
  return pickDisplayUrl(urls.preview, urls.edit, urls.origin, urls.download, urls.thumb);
};

export const resolveImageDownloadUrl = (request, item = {}, extra = {}) => {
  const picId = item.pic_id || item.id || extra.pic_id || "";
  if (!request || !picId) {
    return Promise.resolve("");
  }
  const entry = imageUrlFor(item, "download");
  return request(
    "user/download/original",
    {
      pic_id: picId,
      target_user_id: extra.target_user_id || extra.uid || item.uid || "",
      product_id: extra.product_id || item.product_id || item.folder_id || "",
      file_url: entry,
      file_size: Number(extra.file_size || item.file_size || item.size || item.size_bytes || 0),
      timestamp: new Date().getTime(),
    },
    "post",
    { show_err: true, loading: false },
  ).then((res) => {
    const data = res && res.data ? res.data : {};
    return normalizeText(data.download_url || data.downloadUrl || data.url);
  });
};

export default {
  getImageUrls,
  imageUrlFor,
  resolveImageDownloadUrl,
};
