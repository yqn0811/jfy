import { computed, defineAsyncComponent, markRaw, watch, type Component } from 'vue'
import { currentRouteState, normalizePath, syncRouteFromLocation } from '@/navigation'

interface AppRoute {
  path: string
  title: string
  component: Component
}

const lazyView = (loader: () => Promise<{ default: Component }>) => markRaw(defineAsyncComponent(loader))

const routes: AppRoute[] = [
  { path: '/workbench', title: '工作台 - 织序传输', component: lazyView(() => import('@/views/WorkbenchView.vue')) },
  { path: '/quick-send', title: '快速发文件 - 织序传输', component: lazyView(() => import('@/views/QuickSendView.vue')) },
  {
    path: '/create-collection-task',
    title: '创建收集任务 - 织序传输',
    component: lazyView(() => import('@/views/CreateCollectionTaskView.vue')),
  },
  {
    path: '/delivery-records',
    title: '交付记录 - 织序传输',
    component: lazyView(() => import('@/views/DeliveryRecordsView.vue')),
  },
  { path: '/space-archive', title: '空间与归档 - 织序传输', component: lazyView(() => import('@/views/SpaceArchiveView.vue')) },
  { path: '/task-details', title: '收集任务详情 - 织序传输', component: lazyView(() => import('@/views/TaskDetailsView.vue')) },
  { path: '/share-result', title: '分享结果 - 织序传输', component: lazyView(() => import('@/views/ShareResultView.vue')) },
  {
    path: '/submission-upload',
    title: '提交材料 - 织序传输',
    component: lazyView(() => import('@/views/SubmissionUploadView.vue')),
  },
  {
    path: '/submission-success',
    title: '提交成功 - 织序传输',
    component: lazyView(() => import('@/views/SubmissionSuccessView.vue')),
  },
  { path: '/placeholder', title: '页面建设中 - 织序传输', component: lazyView(() => import('@/views/PlaceholderView.vue')) },
]

const placeholderRoute = routes.find((route) => route.path === '/placeholder')!

const findRoute = (path: string) => {
  const normalized = normalizePath(path)
  return routes.find((route) => route.path === normalized) || placeholderRoute
}

export const currentRoute = computed(() => ({
  ...findRoute(currentRouteState.value.path),
  path: currentRouteState.value.path,
  query: currentRouteState.value.query,
}))

export const installAppRouter = () => {
  syncRouteFromLocation()
  if (window.location.pathname !== currentRouteState.value.path) {
    window.history.replaceState({}, '', `${currentRouteState.value.path}${window.location.search}${window.location.hash}`)
  }
  window.addEventListener('popstate', syncRouteFromLocation)
  watch(
    currentRouteState,
    () => {
      document.title = findRoute(currentRouteState.value.path).title || '织序传输'
    },
    { immediate: true, deep: true }
  )
}
