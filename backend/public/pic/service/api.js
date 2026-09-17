
/**
 * 删除分类
 * @param {} data
 * @returns
 */
function delGroup(data){
  return post('/index/pic/delGroup',data)
}
/**
 * 获取分类列表
 * @param {} params
 * @returns
 */
function getTagLists(params){
  return get('/index/pic/groups',params)
}
/**
 * 分类排序/置顶
 * @param {} params
 * @returns
 */
 function tagSort(data){
  return post('/index/pic/sortGroup',data)
}
/**
 * 删除图片信息
 * @param {} data
 * @returns
 */
function delImg(data){
  return post('/index/pic/delPics',data)
}
/**
 * 获取图库列表
 * @param {} params
 * @returns
 */
 function getLibararyLists(params){
  return get('/index/pic/lists',params)
}
/**
 * 获取视频/文件列表
 * @param {} params
 * @returns
 */
 function getMediaLists(params){
  return get('/media/list',params)
}
/**
 * 图片重命名
 */
function renameImg(data){
  return post('/index/pic/rename',data)
}
/**
 * 视频重命名
 */
function renameVideo(data){
  return post('/media/rename',data)
}
/**
 * 删除视频文件信息
 * @param {} data
 * @returns
 */
 function delVideo(data){
  return post('/media/delete',data)
}
