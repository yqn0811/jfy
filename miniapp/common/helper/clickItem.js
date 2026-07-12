export const resolveClickedListItem = (item, index, event, list = []) => {
  const eventArgs =
    item &&
    item.detail &&
    Array.isArray(item.detail.__args__) &&
    item.detail.__args__;
  if (eventArgs && eventArgs.length) {
    return resolveClickedListItem(eventArgs[0], eventArgs[1], item, list);
  }

  const detailItem =
    item &&
    item.detail &&
    (item.detail.item || item.detail.data || item.detail.value);
  if (detailItem && typeof detailItem === "object") {
    return detailItem;
  }

  if (item && typeof item === "object" && !item.currentTarget) {
    return item;
  }
  if (Number.isInteger(index) && list[index]) {
    return list[index];
  }
  const datasetIndex =
    event &&
    event.currentTarget &&
    event.currentTarget.dataset &&
    event.currentTarget.dataset.index;
  const resolvedIndex = Number(datasetIndex);
  if (Number.isInteger(resolvedIndex) && list[resolvedIndex]) {
    return list[resolvedIndex];
  }
  return null;
};

export const getObjectId = (item, fields = ["id"]) => {
  if (!item || typeof item !== "object") {
    return "";
  }
  for (const field of fields) {
    const value = item[field];
    if (value !== undefined && value !== null && value !== "") {
      return value;
    }
  }
  return "";
};

export const showInvalidRecordToast = (title = "数据异常，请刷新后重试") => {
  uni.showToast({
    title,
    icon: "none",
  });
};
