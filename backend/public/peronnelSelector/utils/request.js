// const { axios } = require("../assets/axios.min");

// const service = axios.create({
//     baseUrl: window.location.origin,
//     timeout: 5000
// })
var url = `${window.location.origin}/`
// var url = `https://dev-cloud1103-qdy.hdewm.com/`
// var url = `https://four.nttrip.cn/`
// var url = `https://zy.local.com/`
// create an axios instance
const service = axios.create({
  baseURL: url, // url = base url + request url
  // withCredentials: true, // send cookies when cross-domain requests
  timeout: 10000 // request timeout
})


// request interceptor
service.interceptors.request.use((config)=>{
  config.headers['X-Requested-With'] = 'XMLHttpRequest';
  return config
}

)

// response interceptor
service.interceptors.response.use(
  /**
   * If you want to get http information such as headers or status
   * Please return  response => response
  */

  /**
   * Determine the request status by custom code
   * Here is just an example
   * You can also judge the status by HTTP Status Code
   */
  response => {
    const res = response.data
    return res
  },
  error => {
    if(error.code === 'ECONNABORTED' || error.message === "Network Error" || error.message.includes("timeout")){
      ELEMENT.Message({
        message: '请求超时，请重试',
        type: 'error',
        duration: 3 * 1000
      })
    }else{

      ELEMENT.Message({
        message: error.message,
        type: 'error',
        duration: 3 * 1000
      })
    }
    return 'fail';//进入error

    // Message({
    //   message: error.message,
    //   type: 'error',
    //   duration: 5 * 1000
    // })

  }
)

function get(url,data){
  return service.request({
    url:url,
    method:'get',
    params:data
  })
}

function post(url,data){
  return service.request({
    url:url,
    method:'post',
    data:data
  })
}
// console.log(service.request())

// export default service
