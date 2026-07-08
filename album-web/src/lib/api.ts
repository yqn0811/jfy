const DEFAULT_API_BASE = 'https://api.jfyuntu.com/api'

export interface ApiResponse<T = any> {
  code: number
  msg?: string
  message?: string
  data: T
}

export class ApiError extends Error {
  code: number
  data: any

  constructor(message: string, code = -1, data: any = null) {
    super(message)
    this.name = 'ApiError'
    this.code = code
    this.data = data
  }
}

const TOKEN_KEY = 'jfyuntu_pc_token'
const USER_KEY = 'jfyuntu_pc_user'
const UPLOAD_TOKEN_KEY = 'jfyuntu_web_upload_token'
const UPLOAD_TOKEN_CODE_KEY = 'jfyuntu_web_upload_code'
const SHOULD_BUNDLE_MOCK =
  import.meta.env.DEV ||
  import.meta.env.PUBLIC_ENABLE_MOCK === '1' ||
  import.meta.env.PUBLIC_ENABLE_MOCK === 'true' ||
  import.meta.env.PUBLIC_JFYUNTU_MOCK === '1' ||
  import.meta.env.PUBLIC_JFYUNTU_MOCK === 'true'

const getRuntimeApiBase = () => {
  if (typeof window === 'undefined') return DEFAULT_API_BASE
  const injected = (window as any).__JFYUNTU_API_BASE__
  return injected || import.meta.env.PUBLIC_API_BASE || DEFAULT_API_BASE
}

const joinUrl = (base: string, path: string) => {
  if (/^https?:\/\//i.test(path)) return path
  return `${base.replace(/\/$/, '')}/${path.replace(/^\//, '')}`
}

const normalizeToken = (token = '') => token.replace(/^Bearer\s+/i, '').trim()

export const normalizeCurrentUser = (raw: any = {}) => {
  const user = raw?.user_info || raw?.user || raw?.info || raw?.profile || raw || {}
  const id = String(user.id || user.uid || user.user_id || user.userid || user.userId || user.userID || '')
  return {
    ...user,
    id,
    uid: String(user.uid || id || ''),
  }
}

export const getCurrentUserId = (raw: any = {}) => {
  const user = normalizeCurrentUser(raw)
  return String(user.id || user.uid || '')
}

export const isMockEnabled = () => {
  if (!SHOULD_BUNDLE_MOCK) return false
  const envValue = import.meta.env.PUBLIC_ENABLE_MOCK || import.meta.env.PUBLIC_JFYUNTU_MOCK
  if (envValue === '1' || envValue === 'true') return true
  if (typeof localStorage === 'undefined') return false
  return localStorage.getItem('jfyuntu_enable_mock') === '1'
}

const requestMockApi = async <T = any>(
  path: string,
  options: {
    method?: 'GET' | 'POST' | 'PUT' | 'DELETE'
    params?: Record<string, any>
    body?: Record<string, any>
    token?: string
    auth?: boolean
  } = {}
) => {
  if (!SHOULD_BUNDLE_MOCK) {
    throw new ApiError('Mock 未启用')
  }
  const { mockApiRequest } = await import('./mock-api')
  return mockApiRequest<T>(path, options)
}

const uploadWithMockApi = async <T = any>(path: string, formData: FormData) => {
  if (!SHOULD_BUNDLE_MOCK) {
    throw new ApiError('Mock 未启用')
  }
  const { mockApiUpload } = await import('./mock-api')
  return mockApiUpload<T>(path, formData)
}

const removeAuthCallbackParams = () => {
  if (typeof window === 'undefined') return
  const url = new URL(window.location.href)
  let changed = false
  ;['token', 'access_token', 'authorization', 'login', 'error'].forEach((key) => {
    if (url.searchParams.has(key)) {
      url.searchParams.delete(key)
      changed = true
    }
  })
  if (changed) {
    const next = `${url.pathname}${url.search}${url.hash}`
    window.history.replaceState({}, '', next || window.location.pathname)
  }
}

export const authStore = {
  getToken() {
    if (typeof localStorage === 'undefined') return ''
    return normalizeToken(localStorage.getItem(TOKEN_KEY) || localStorage.getItem('token') || '')
  },
  setToken(token: string) {
    if (typeof localStorage === 'undefined') return
    const normalized = normalizeToken(token)
    if (normalized) {
      localStorage.setItem(TOKEN_KEY, normalized)
      localStorage.setItem('token', normalized)
    }
  },
  clearToken() {
    if (typeof localStorage === 'undefined') return
    localStorage.removeItem(TOKEN_KEY)
    localStorage.removeItem('token')
    localStorage.removeItem(USER_KEY)
    localStorage.removeItem('userInfo')
  },
  getUser<T = any>(): T | null {
    if (typeof localStorage === 'undefined') return null
    const raw = localStorage.getItem(USER_KEY) || localStorage.getItem('userInfo')
    if (!raw) return null
    try {
      return normalizeCurrentUser(JSON.parse(raw)) as T
    } catch {
      return null
    }
  },
  setUser(user: any) {
    if (typeof localStorage === 'undefined') return
    const normalized = normalizeCurrentUser(user)
    localStorage.setItem(USER_KEY, JSON.stringify(normalized))
    localStorage.setItem('userInfo', JSON.stringify(normalized))
  },
  isLoggedIn() {
    return !!this.getToken()
  },
  consumeCallbackToken() {
    if (typeof window === 'undefined') return ''
    const params = new URLSearchParams(window.location.search)
    const token = normalizeToken(
      params.get('token') || params.get('access_token') || params.get('authorization') || ''
    )
    if (token) {
      this.setToken(token)
      removeAuthCallbackParams()
      return token
    }
    const error = params.get('error')
    if (error) removeAuthCallbackParams()
    return ''
  },
}

export const uploadTokenStore = {
  get(code = '') {
    if (typeof sessionStorage === 'undefined') return ''
    const savedCode = sessionStorage.getItem(UPLOAD_TOKEN_CODE_KEY) || ''
    if (code && savedCode && savedCode !== code) return ''
    return normalizeToken(sessionStorage.getItem(UPLOAD_TOKEN_KEY) || '')
  },
  set(token: string, code = '') {
    if (typeof sessionStorage === 'undefined') return
    sessionStorage.setItem(UPLOAD_TOKEN_KEY, normalizeToken(token))
    if (code) sessionStorage.setItem(UPLOAD_TOKEN_CODE_KEY, code)
  },
  clear() {
    if (typeof sessionStorage === 'undefined') return
    sessionStorage.removeItem(UPLOAD_TOKEN_KEY)
    sessionStorage.removeItem(UPLOAD_TOKEN_CODE_KEY)
  },
}

const buildQuery = (params?: Record<string, any>) => {
  const search = new URLSearchParams()
  Object.entries(params || {}).forEach(([key, value]) => {
    if (value === undefined || value === null || value === '') return
    search.set(key, String(value))
  })
  return search.toString()
}

export async function apiRequest<T = any>(
  path: string,
  options: {
    method?: 'GET' | 'POST' | 'PUT' | 'DELETE'
    params?: Record<string, any>
    body?: Record<string, any>
    token?: string
    auth?: boolean
  } = {}
): Promise<T> {
  if (isMockEnabled()) {
    return requestMockApi<T>(path, options)
  }

  const method = options.method || 'GET'
  const query = buildQuery(options.params)
  const url = `${joinUrl(getRuntimeApiBase(), path)}${query ? `?${query}` : ''}`
  const token = normalizeToken(options.token || (options.auth === false ? '' : authStore.getToken()))
  const headers: Record<string, string> = {
    'X-Requested-With': 'XMLHttpRequest',
  }
  if (token) headers.Authorization = `Bearer ${token}`

  const init: RequestInit = { method, headers }
  if (method !== 'GET') {
    headers['Content-Type'] = 'application/json'
    init.body = JSON.stringify(options.body || {})
  }

  const response = await fetch(url, init)
  const payload = (await response.json().catch(() => null)) as ApiResponse<T> | null
  if (!payload) {
    throw new ApiError('接口响应异常', response.status)
  }
  if (Number(payload.code) !== 0) {
    throw new ApiError(payload.msg || payload.message || '请求失败', Number(payload.code), payload.data)
  }
  return payload.data
}

export async function apiUpload<T = any>(
  path: string,
  formData: FormData,
  token = authStore.getToken()
): Promise<T> {
  if (isMockEnabled()) {
    return uploadWithMockApi<T>(path, formData)
  }

  const headers: Record<string, string> = {
    'X-Requested-With': 'XMLHttpRequest',
  }
  const normalized = normalizeToken(token)
  if (normalized) headers.Authorization = `Bearer ${normalized}`

  const response = await fetch(joinUrl(getRuntimeApiBase(), path), {
    method: 'POST',
    headers,
    body: formData,
  })
  const payload = (await response.json().catch(() => null)) as ApiResponse<T> | null
  if (!payload) throw new ApiError('上传响应异常', response.status)
  if (Number(payload.code) !== 0) {
    throw new ApiError(payload.msg || payload.message || '上传失败', Number(payload.code), payload.data)
  }
  return payload.data
}

export const pcApi = {
  getLoginOauthConfig: (redirect = '') =>
    apiRequest<any>('user/login/oauth_config', { params: { redirect, timestamp: Date.now() }, auth: false }),
  getLoginQrcode: () => apiRequest<any>('user/login/qrcode', { auth: false }),
  checkLoginStatus: (scene: string) =>
    apiRequest<any>('user/login/status', { params: { scene, timestamp: Date.now() }, auth: false }),
  getCurrentUser: async () => normalizeCurrentUser(await apiRequest<any>('user/show_info')),
  updatePcSettings: (body: Record<string, any>) =>
    apiRequest<any>('user/update_pc_settings', { method: 'POST', body: { timestamp: Date.now(), ...body } }),

  getHomeInfo: (targetUserId = '') =>
    apiRequest<any>('user/home/info', { params: { target_user_id: targetUserId, timestamp: Date.now() } }),
  getHomeCategories: (targetUserId = '', fid = '', includeCurrent = 0) =>
    apiRequest<any>('user/home/categories', {
      params: { target_user_id: targetUserId, fid, include_current: includeCurrent, timestamp: Date.now() },
    }),
  getHomeProducts: (targetUserId = '', cateId = '') =>
    apiRequest<any>('user/home/products', {
      params: { target_user_id: targetUserId, cate_id: cateId, timestamp: Date.now() },
    }),
  getHomeProductDetail: (targetUserId = '', productId: string) =>
    apiRequest<any>('user/home/products/detail', {
      params: { target_user_id: targetUserId, product_id: productId, timestamp: Date.now() },
    }),
  getHomeShareLink: (targetUserId: string, path = '') =>
    apiRequest<any>('user/home/share_link', {
      params: { target_user_id: targetUserId, path, timestamp: Date.now() },
      auth: false,
    }),
  getHomeMiniCode: (targetUserId: string, type: 'home' | 'category' | 'product' = 'home', id = '', path = '') =>
    apiRequest<any>('user/home/minicode', {
      params: { target_user_id: targetUserId, type, id, path, timestamp: Date.now() },
      auth: false,
    }),

  getManagementCategories: (params: Record<string, any>) =>
    apiRequest<any>('album/lists/folder', { method: 'POST', body: { folder_type: 1, limit: 100, timestamp: Date.now(), ...params } }),
  getManagementProducts: (params: Record<string, any>) =>
    apiRequest<any>('album/lists/folder', { method: 'POST', body: { folder_type: 2, limit: 50, timestamp: Date.now(), ...params } }),
  getProductEditDetail: (fid: string) =>
    apiRequest<any>('album/products/detail', { method: 'POST', body: { fid, timestamp: Date.now() } }),
  createProductOrCategory: (body: Record<string, any>) =>
    apiRequest<any>('album/create/folder', { method: 'POST', body: { timestamp: Date.now(), ...body } }),
  editProductOrCategory: (body: Record<string, any>) =>
    apiRequest<any>('album/edit/folder', { method: 'POST', body: { timestamp: Date.now(), ...body } }),
  updateProductStatus: (body: Record<string, any>) =>
    apiRequest<any>('album/product/update_status', { method: 'POST', body: { timestamp: Date.now(), ...body } }),
  deleteProductOrFolder: (fid: string, delType = 1) =>
    apiRequest<any>('album/delete/folder', { method: 'POST', body: { fid, del_type: delType, timestamp: Date.now() } }),
  uploadProductImage: (fid: string, file: File, type: 'colorChart' | 'detailChart') => {
    const form = new FormData()
    form.append('pid', fid)
    form.append('files', file, file.name)
    form.append('filename', file.name)
    form.append('file_name', file.name)
    form.append('original_name', file.name)
    form.append('name', file.name)
    form.append('file_type', type === 'detailChart' ? '2' : '1')
    return apiUpload<any>('album/upload/folder', form)
  },

  getBatchUploadLink: (fid: string) =>
    apiRequest<any>('album/batch_link', { params: { fid, timestamp: Date.now() } }),
  resetBatchUploadLink: (fid: string) =>
    apiRequest<any>('album/reset_batch_link', { method: 'POST', body: { fid, timestamp: Date.now() } }),
  saveBatchUploadPassword: (body: Record<string, any>) =>
    apiRequest<any>('album/batch_upload_password', { method: 'POST', body: { timestamp: Date.now(), ...body } }),

  getWebUploadInfo: (code: string) =>
    apiRequest<any>('web/upload', { params: { code, timestamp: Date.now() }, auth: false }),
  getWebUploadToken: (code: string, password = '') =>
    apiRequest<any>('web/token/upload', { method: 'POST', body: { code, password }, auth: false }),
  uploadWebProductImage: (fid: string, file: File, type: 'colorChart' | 'detailChart', token: string) => {
    const form = new FormData()
    form.append('fid', fid)
    form.append('files', file, file.name)
    form.append('filename', file.name)
    form.append('file_name', file.name)
    form.append('original_name', file.name)
    form.append('name', file.name)
    form.append('file_type', type === 'detailChart' ? '2' : '1')
    return apiUpload<any>('web/folder/pic/upload', form, token)
  },

  toggleFavorite: (type: 'homepage' | 'product' | 'category', id: string, add: boolean) =>
    apiRequest<any>(add ? 'user/add/collect' : 'user/cancel/collect', {
      method: 'POST',
      body: { type, id, timestamp: Date.now() },
    }),
  addVisit: (type: 'homepage' | 'product' | 'category', id: string) =>
    apiRequest<any>('user/add/visit', { method: 'POST', body: { type, id, timestamp: Date.now() } }),
  getFavorites: (type = 'all', key = '', page = 1) =>
    apiRequest<any>('user/collect/records', { params: { type, key, page, timestamp: Date.now() } }),
  getVisits: (type = 'all', key = '', page = 1) =>
    apiRequest<any>('user/visit/records', { params: { type, key, page, timestamp: Date.now() } }),
  deleteVisit: (visitIds: string) =>
    apiRequest<any>('user/del/visit', { method: 'POST', body: { visit_ids: visitIds, timestamp: Date.now() } }),

  getSubscriptionPlans: () => apiRequest<any>('web_payment/subscription/plans', { auth: false }),
  createMembershipOrder: (body: Record<string, any>) =>
    apiRequest<any>('web_payment/membership/order/create', { method: 'POST', body: { timestamp: Date.now(), ...body } }),
  getPaymentOrderStatus: (orderNo: string) =>
    apiRequest<any>('web_payment/order/status', { params: { order_no: orderNo, timestamp: Date.now() } }),
  getPaymentOrders: (params: Record<string, any> = {}) =>
    apiRequest<any>('web_payment/orders', { params: { page: 1, page_size: 20, timestamp: Date.now(), ...params } }),

  getAiResources: (params: Record<string, any> = {}) =>
    apiRequest<any>('album/ai/resources', { params: { page: 1, page_size: 30, timestamp: Date.now(), ...params } }),
  importAiResource: (resourceId: string, role: 'cover' | 'detail' = 'cover', productId = '') =>
    apiRequest<any>('album/ai/import_resource', {
      method: 'POST',
      body: { resource_id: resourceId, role, product_id: productId, timestamp: Date.now() },
    }),

  getRecycleList: (params: Record<string, any> = {}) =>
    apiRequest<any>('user/recycle/list', { params: { page: 1, limit: 20, timestamp: Date.now(), ...params } }),
  restoreRecycleItem: (id: string) =>
    apiRequest<any>('user/restore/product', { method: 'POST', body: { product_ids: id, timestamp: Date.now() } }),
  deleteRecycleItem: (id: string) =>
    apiRequest<any>('user/destroy/product', { method: 'POST', body: { product_ids: id, timestamp: Date.now() } }),
}
