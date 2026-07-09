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

const getRuntimeApiBase = () => {
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

export const authStore = {
  getToken() {
    if (typeof localStorage === 'undefined') return ''
    return normalizeToken(
      localStorage.getItem('jfyuntu_pc_token') ||
        localStorage.getItem('file_web_token') ||
        localStorage.getItem('token') ||
        ''
    )
  },
  setToken(token: string) {
    if (typeof localStorage === 'undefined') return
    const normalized = normalizeToken(token)
    if (!normalized) return
    localStorage.setItem('file_web_token', normalized)
    localStorage.setItem('jfyuntu_pc_token', normalized)
    localStorage.setItem('token', normalized)
  },
  hasToken() {
    return !!this.getToken()
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

export async function apiUpload<T = any>(
  path: string,
  formData: FormData,
  token = authStore.getToken()
): Promise<T> {
  const response = await fetch(joinUrl(getRuntimeApiBase(), path), {
    method: 'POST',
    headers: makeHeaders(token),
    body: formData,
  })
  const payload = (await response.json().catch(() => null)) as ApiEnvelope<T> | null
  if (!response.ok && !payload) {
    throw new ApiError('上传失败，请重试', response.status)
  }
  return unwrapEnvelope<T>(payload as ApiEnvelope<T>, response.status)
}

export const getApiErrorMessage = (error: unknown, fallback = '请求失败，请重试') => {
  if (error instanceof ApiError) return error.message || fallback
  if (error instanceof Error) return error.message || fallback
  return fallback
}
