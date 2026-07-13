import { ref } from 'vue'

const DEFAULT_API_BASE = 'https://api.jfyuntu.com/api'

export interface ApiEnvelope<T = any> {
  code?: number | string
  status?: number | string
  msg?: string
  message?: string
  data?: T
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

type ApiMethod = 'GET' | 'POST' | 'PUT' | 'DELETE'

interface RequestOptions {
  method?: ApiMethod
  params?: Record<string, any>
  body?: Record<string, any>
  token?: string
  auth?: boolean
}

const normalizeToken = (token = '') => token.replace(/^Bearer\s+/i, '').trim()
const FILE_AUTH_TOKEN_KEY = 'file_web_token'
const LEGACY_AUTH_TOKEN_KEYS = ['jfyuntu_pc_token', 'token']
export const authStateVersion = ref(0)

const notifyAuthChanged = () => {
  authStateVersion.value += 1
}

export const getRuntimeApiBase = () => {
  if (typeof window === 'undefined') return DEFAULT_API_BASE
  return (window as any).__JFYUNTU_API_BASE__ || import.meta.env.PUBLIC_API_BASE || DEFAULT_API_BASE
}

const joinUrl = (base: string, path: string) => {
  if (/^https?:\/\//i.test(path)) return path
  return `${base.replace(/\/$/, '')}/${path.replace(/^\//, '')}`
}

const buildQuery = (params?: Record<string, any>) => {
  const search = new URLSearchParams()
  Object.entries(params || {}).forEach(([key, value]) => {
    if (value === undefined || value === null || value === '') return
    if (Array.isArray(value)) {
      value.forEach((item) => search.append(key, String(item)))
      return
    }
    search.set(key, String(value))
  })
  return search.toString()
}

export const buildApiUrl = (path: string, params?: Record<string, any>) => {
  const query = buildQuery(params)
  return `${joinUrl(getRuntimeApiBase(), path)}${query ? `?${query}` : ''}`
}

export const authStore = {
  getToken() {
    if (typeof localStorage === 'undefined') return ''
    return normalizeToken(localStorage.getItem(FILE_AUTH_TOKEN_KEY) || '')
  },
  setToken(token: string) {
    if (typeof localStorage === 'undefined') return
    const normalized = normalizeToken(token)
    if (!normalized) return
    localStorage.setItem(FILE_AUTH_TOKEN_KEY, normalized)
    LEGACY_AUTH_TOKEN_KEYS.forEach((key) => localStorage.removeItem(key))
    notifyAuthChanged()
  },
  hasToken() {
    authStateVersion.value
    return !!this.getToken()
  },
  clearToken() {
    if (typeof localStorage === 'undefined') return
    localStorage.removeItem(FILE_AUTH_TOKEN_KEY)
    LEGACY_AUTH_TOKEN_KEYS.forEach((key) => localStorage.removeItem(key))
    notifyAuthChanged()
  },
}

const makeHeaders = (token = '') => {
  const headers: Record<string, string> = {
    'X-Requested-With': 'XMLHttpRequest',
  }
  const normalized = normalizeToken(token)
  if (normalized) {
    headers.Authorization = `Bearer ${normalized}`
    headers['Authorization-Token'] = `Bearer ${normalized}`
  }
  return headers
}

const unwrapEnvelope = <T>(payload: ApiEnvelope<T> | T, fallbackStatus: number): T => {
  if (!payload || typeof payload !== 'object') {
    throw new ApiError('接口响应异常', fallbackStatus)
  }

  const envelope = payload as ApiEnvelope<T>
  const rawCode = envelope.code ?? envelope.status
  const hasEnvelope = rawCode !== undefined || 'data' in envelope || 'msg' in envelope || 'message' in envelope

  if (!hasEnvelope) return payload as T

  const code = rawCode === undefined || rawCode === '' ? 0 : Number(rawCode)
  if (Number.isFinite(code) && code !== 0 && code !== 200) {
    throw new ApiError(envelope.msg || envelope.message || '请求失败', code, envelope.data)
  }

  return (envelope.data ?? (payload as T)) as T
}

export async function apiRequest<T = any>(path: string, options: RequestOptions = {}): Promise<T> {
  const method = options.method || 'GET'
  const query = buildQuery(options.params)
  const url = `${joinUrl(getRuntimeApiBase(), path)}${query ? `?${query}` : ''}`
  const token = options.auth === false ? '' : normalizeToken(options.token || authStore.getToken())
  const headers = makeHeaders(token)
  const init: RequestInit = { method, headers }

  if (method !== 'GET') {
    headers['Content-Type'] = 'application/json'
    init.body = JSON.stringify(options.body || {})
  }

  const response = await fetch(url, init)
  const payload = (await response.json().catch(() => null)) as ApiEnvelope<T> | null
  if (!response.ok && !payload) {
    throw new ApiError('网络请求失败', response.status)
  }
  return unwrapEnvelope<T>(payload as ApiEnvelope<T>, response.status)
}

const getDownloadFilename = (response: Response, fallback = 'download') => {
  const disposition = response.headers.get('Content-Disposition') || ''
  const encodedMatch = disposition.match(/filename\*=UTF-8''([^;]+)/i)
  if (encodedMatch?.[1]) {
    try {
      return decodeURIComponent(encodedMatch[1])
    } catch {
      return encodedMatch[1]
    }
  }
  const plainMatch = disposition.match(/filename="?([^";]+)"?/i)
  return plainMatch?.[1] || fallback
}

export async function apiDownload(
  path: string,
  options: { params?: Record<string, any>; filename?: string; token?: string; auth?: boolean } = {}
): Promise<void> {
  const url = buildApiUrl(path, options.params)
  const token = options.auth === false ? '' : normalizeToken(options.token || authStore.getToken())
  const response = await fetch(url, {
    method: 'GET',
    headers: makeHeaders(token),
  })

  if (!response.ok) {
    const payload = (await response.json().catch(() => null)) as ApiEnvelope | null
    throw new ApiError(payload?.msg || payload?.message || '下载失败，请重试', response.status, payload?.data)
  }

  const blob = await response.blob()
  const blobUrl = URL.createObjectURL(blob)
  const link = document.createElement('a')
  link.href = blobUrl
  link.download = options.filename || getDownloadFilename(response, 'download')
  document.body.appendChild(link)
  link.click()
  document.body.removeChild(link)
  URL.revokeObjectURL(blobUrl)
}

export async function apiUpload<T = any>(
  path: string,
  formData: FormData,
  token = authStore.getToken(),
  options: { auth?: boolean; optionalAuth?: boolean } = {}
): Promise<T> {
  const uploadToken = options.auth === false ? '' : normalizeToken(token)
  const response = await fetch(joinUrl(getRuntimeApiBase(), path), {
    method: 'POST',
    headers: makeHeaders(uploadToken),
    body: formData,
  })
  const payload = (await response.json().catch(() => null)) as ApiEnvelope<T> | null
  if (!response.ok && !payload) {
    throw new ApiError('上传失败，请重试', response.status)
  }
  return unwrapEnvelope<T>(payload as ApiEnvelope<T>, response.status)
}

export async function rawUpload(
  url: string,
  body: BodyInit,
  options: { method?: ApiMethod; headers?: Record<string, string> } = {}
): Promise<Response> {
  let uploadUrl: URL
  try {
    uploadUrl = new URL(url)
  } catch {
    throw new ApiError('直传上传地址无效，请重试')
  }
  if (!['http:', 'https:'].includes(uploadUrl.protocol)) {
    throw new ApiError('直传上传地址无效，请重试')
  }

  const response = await fetch(url, {
    method: options.method || 'PUT',
    headers: options.headers || {},
    body,
  })
  if (!response.ok) {
    throw new ApiError('直传上传失败，请重试', response.status)
  }
  return response
}

export const getApiErrorMessage = (error: unknown, fallback = '请求失败，请重试') => {
  if (error instanceof ApiError) return error.message || fallback
  if (error instanceof Error) return error.message || fallback
  return fallback
}
