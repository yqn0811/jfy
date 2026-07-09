import { computed, reactive } from 'vue'

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
  const cleanPath = withLeadingSlash.replace(/\.html$/, '')
  return legacyPathAliases[cleanPath] || cleanPath
}

const getLocationState = (): AppRouteState => ({
  path: normalizePath(window.location.pathname),
  query: Object.fromEntries(new URLSearchParams(window.location.search).entries()),
})

const state = reactive(getLocationState())

export const currentRouteState = computed(() => ({
  path: state.path,
  query: state.query,
}))

export const syncRouteFromLocation = () => {
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
