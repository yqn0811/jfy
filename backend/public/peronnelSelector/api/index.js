function userselectiondata(data){
  return post(`/index/WxuserController/userselectiondata.html?page=${data.page}`, data)
}