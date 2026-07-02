var url = `${window.location.origin}`
// var url = 'http://four.nttrip.cn'

// create an axios instance
const service = axios.create({
  baseURL: url,
  timeout: 10000 // request timeout
})


// request interceptor
// service.interceptors.request.use((config)=>{
//   config.headers['X-Requested-With'] = 'XMLHttpRequest';
//   return config
// }

// )

// response interceptor
service.interceptors.response.use(
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
