import { computed, reactive } from 'vue'

export interface AppRouteState {
  path: string
  query: Record<string, string>
  hash: string
}

export const normalizePath = (path: string) => {
  if (!path || path === '/') return '/share-home'
  if (!path.startsWith('/')) return `/${path}`
  return path
}

const getLocationState = (): AppRouteState => ({
  path: normalizePath(window.location.pathname),
  query: Object.fromEntries(new URLSearchParams(window.location.search).entries()),
  hash: window.location.hash,
})

const state = reactive(getLocationState())

export const currentRouteState = computed(() => ({
  path: state.path,
  query: state.query,
  hash: state.hash,
}))

export const syncRouteFromLocation = () => {
  const next = getLocationState()
  state.path = next.path
  state.query = next.query
  state.hash = next.hash
}

const resolveTarget = (target: string) => {
  const current = typeof window === 'undefined' ? 'http://localhost/' : window.location.href
  const url = new URL(target.replace(/^\.\//, '/'), current)
  return `${normalizePath(url.pathname)}${url.search}${url.hash}`
}

export const navigateTo = (target: string) => {
  window.history.pushState({}, '', resolveTarget(target))
  syncRouteFromLocation()
  window.scrollTo({ top: 0 })
}

export const navigateToInternal = (target: string) => {
  if (/^(mailto:|tel:|weixin:)/i.test(target)) {
    window.location.href = target
    return
  }

  if (/^https?:/i.test(target)) {
    const current = typeof window === 'undefined' ? 'http://localhost/' : window.location.href
    const url = new URL(target, current)
    const currentUrl = new URL(current)
    if (url.origin !== currentUrl.origin) {
      window.location.href = target
      return
    }
  }

  navigateTo(target)
}

export const replaceTo = (target: string) => {
  window.history.replaceState({}, '', resolveTarget(target))
  syncRouteFromLocation()
}
