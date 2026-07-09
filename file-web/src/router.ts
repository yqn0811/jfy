import { computed, markRaw, watch, type Component } from 'vue'
import { currentRouteState, normalizePath, syncRouteFromLocation } from '@/navigation'

import WorkbenchView from '@/views/WorkbenchView.vue'
import QuickSendView from '@/views/QuickSendView.vue'
import CreateCollectionTaskView from '@/views/CreateCollectionTaskView.vue'
import DeliveryRecordsView from '@/views/DeliveryRecordsView.vue'
import SpaceArchiveView from '@/views/SpaceArchiveView.vue'
import TaskDetailsView from '@/views/TaskDetailsView.vue'
import ShareResultView from '@/views/ShareResultView.vue'
import SubmissionUploadView from '@/views/SubmissionUploadView.vue'
import SubmissionSuccessView from '@/views/SubmissionSuccessView.vue'
import PlaceholderView from '@/views/PlaceholderView.vue'

interface AppRoute {
  path: string
  title: string
  component: Component
}

const routes: AppRoute[] = [
  { path: '/workbench', title: '工作台 - 织序传输', component: markRaw(WorkbenchView) },
  { path: '/quick-send', title: '快速发文件 - 织序传输', component: markRaw(QuickSendView) },
  { path: '/create-collection-task', title: '创建收集任务 - 织序传输', component: markRaw(CreateCollectionTaskView) },
  { path: '/delivery-records', title: '交付记录 - 织序传输', component: markRaw(DeliveryRecordsView) },
  { path: '/space-archive', title: '空间与归档 - 织序传输', component: markRaw(SpaceArchiveView) },
  { path: '/task-details', title: '收集任务详情 - 织序传输', component: markRaw(TaskDetailsView) },
  { path: '/share-result', title: '分享结果 - 织序传输', component: markRaw(ShareResultView) },
  { path: '/submission-upload', title: '提交材料 - 织序传输', component: markRaw(SubmissionUploadView) },
  { path: '/submission-success', title: '提交成功 - 织序传输', component: markRaw(SubmissionSuccessView) },
  { path: '/placeholder', title: '页面建设中 - 织序传输', component: markRaw(PlaceholderView) },
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
