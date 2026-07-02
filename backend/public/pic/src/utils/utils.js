/**
 * 过滤数组样式
 * @param {*} arr 
 * @returns 
 */
function filterNoChildData(arr) {
  var filterObj = function (item) {
    item.label = item.name;
    item.value = item.id;
    if (item.hasOwnProperty("children")) {
      if (item.children.length == 0) {
        delete item.children;
        return item;
      } else {
        item.children = item.children.filter(function (child) {
          child.label = child.name;
          child.value = child.id;
          if (child.hasOwnProperty("children")) {
            if (child.children.length == 0) {
              delete child.children;
              child.value = child.id;
            } else {
              child.children = child.children.filter(function (ch) {
                ch.label = ch.name;
                ch.value = ch.id;
                if (ch.hasOwnProperty("children")) {
                  if (ch.children.length == 0) {
                    delete ch.children;
                    ch.value = ch.id;
                  }
                }
                return ch;
              });
            }
          }
          return child;
        });
      }
    }
    return item;
  };
  var filter = arr.filter(function (item) {
    return filterObj(item);
  });
  return filter;
}