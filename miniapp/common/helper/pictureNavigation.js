import { imageUrlFor } from "./imageUrls.js";

const normalizeText = (value) => {
  if (value === null || value === undefined) return "";
  const text = String(value).trim();
  if (!text || text === "null" || text === "undefined") return "";
  return text;
};

const normalizePictureId = (item = {}) =>
  normalizeText(item.pic_id || item.id || item.relation_id || "");

export const normalizePictureForNavigation = (item = {}, extra = {}) => {
  const previewUrl = imageUrlFor(item, "preview");
  const thumbUrl = imageUrlFor(item, "thumb");
  const originUrl = imageUrlFor(item, "origin");
  const pictureUrl = previewUrl || thumbUrl || originUrl || normalizeText(item.picture_url || item.imgurl || item.imageField || item.src);
  const id = normalizePictureId(item);
  const productId = normalizeText(extra.product_id || extra.folder_id || item.product_id || item.folder_id || "");
  const picName = normalizeText(item.pic_name || item.pic_beizhu || item.name || item.nameField || "");

  return {
    ...item,
    id,
    pic_id: id,
    picture_url: pictureUrl,
    imgurl: pictureUrl,
    imageField: thumbUrl || pictureUrl,
    src: pictureUrl,
    picture_url_original: originUrl || normalizeText(item.picture_url_original) || pictureUrl,
    image_urls: item.image_urls || item.imageUrls || item.urls || {},
    imageUrls: item.imageUrls || item.image_urls || item.urls || {},
    pic_name: picName,
    pic_beizhu: normalizeText(item.pic_beizhu) || picName,
    nameField: picName,
    file_type: Number(item.file_type || item.fileType || 1),
    is_video: Number(item.is_video || item.isVideo || 0),
    poster: item.poster || thumbUrl || pictureUrl,
    product_id: productId,
    folder_id: productId,
    file_size: Number(item.file_size || item.size || item.size_bytes || 0),
    size: Number(item.size || item.file_size || item.size_bytes || 0),
  };
};

export const flattenPictureGroups = (list = []) => {
  if (!Array.isArray(list)) return [];
  const result = [];
  list.forEach((item) => {
    if (Array.isArray(item && item.pictures)) {
      item.pictures.forEach((picture) => result.push(picture));
      return;
    }
    if (item) {
      result.push(item);
    }
  });
  return result;
};

export const buildPictureListForNavigation = (list = [], extra = {}) =>
  flattenPictureGroups(list)
    .map((item) => normalizePictureForNavigation(item, extra))
    .filter((item) => item.pic_id && item.picture_url);

export const setPictureNavigationContext = (current, list = [], extra = {}) => {
  const currentPicture = normalizePictureForNavigation(current, extra);
  let pictures = buildPictureListForNavigation(list, extra);

  if (currentPicture.pic_id && !pictures.some((item) => String(item.pic_id) === String(currentPicture.pic_id))) {
    pictures = [currentPicture, ...pictures];
  }

  if (!pictures.length && currentPicture.pic_id) {
    pictures = [currentPicture];
  }

  if (pictures.length) {
    uni.setStorageSync("picList", pictures);
  }
  if (currentPicture.pic_id) {
    uni.setStorageSync("picInfo", currentPicture);
  }

  return {
    current: currentPicture,
    list: pictures,
  };
};

export default {
  normalizePictureForNavigation,
  flattenPictureGroups,
  buildPictureListForNavigation,
  setPictureNavigationContext,
};
