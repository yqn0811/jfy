import { computed, reactive } from 'vue'
import { toast } from 'vue-sonner'
import { authStore } from '@/lib/apiClient'
import { cleanupLegacyDemoData } from '@/data/legacyDemoCleanup'

export interface AppRouteState {
  path: string
  query: Record<string, string>
}

const legacyPathAliases: Record<string, string> = {
  '/submission-upload-page': '/submission-upload',
  '/submission-success-page': '/submission-success',
}

export const normalizePath = (path: string) => {
  if (!path || path === '/' || path === '/index.html') return '/quick-send'
  const withLeadingSlash = path.startsWith('/') ? path : `/${path}`
  const cleanPath = withLeadingSlash.replace(/\.html$/, '').replace(/\/+$/, '') || '/quick-send'
  return legacyPathAliases[cleanPath] || cleanPath
}

const getLocationState = (): AppRouteState => ({
  path: normalizePath(window.location.pathname),
  query: Object.fromEntries(new URLSearchParams(window.location.search).entries()),
})

const consumeLoginTokenFromLocation = () => {
  const search = new URLSearchParams(window.location.search)
  const token = search.get('token')
  const loginResult = search.get('login')
  if (!token && loginResult !== 'failed') return
  if (token) {
    authStore.setToken(token)
    toast.success('登录成功')
  } else {
    toast.error('登录失败，请重试')
  }
  search.delete('token')
  search.delete('login')
  search.delete('error')
  const query = search.toString()
  window.history.replaceState({}, '', `${window.location.pathname}${query ? `?${query}` : ''}${window.location.hash}`)
}

cleanupLegacyDemoData()

const state = reactive(getLocationState())

export const currentRouteState = computed(() => ({
  path: state.path,
  query: state.query,
}))

export const syncRouteFromLocation = () => {
  consumeLoginTokenFromLocation()
  const next = getLocationState()
  state.path = next.path
  state.query = next.query
}

export const navigateTo = (target: string) => {
  const url = new URL(target.replace(/^\.\//, '/'), window.location.origin)
  const path = normalizePath(url.pathname)
  window.history.pushState({}, '', `${path}${url.search}${url.hash}`)
  syncRouteFromLocation()
  window.scrollTo({ top: 0 })
}
