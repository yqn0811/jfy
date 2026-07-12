import { computed, markRaw, watch, type Component } from 'vue'

import AccessDeniedContent from '@/components/access_denied/AccessDeniedContent.vue'
import BatchUploadSettingsContent from '@/components/batch_upload_settings/BatchUploadSettingsContent.vue'
import BatchUploadVisitorContent from '@/components/batch_upload/BatchUploadVisitorContent.vue'
import BillingUsageContent from '@/components/billing_usage/BillingUsageContent.vue'
import BrowsingHistoryContent from '@/components/browsing_history/BrowsingHistoryContent.vue'
import CategoryBrowserContent from '@/components/category_browser/CategoryBrowserContent.vue'
import CategoryList from '@/components/category_management/CategoryList.vue'
import FavoritesList from '@/components/favorites/FavoritesList.vue'
import HomeSettingsContent from '@/components/home_settings/HomeSettingsContent.vue'
import ImageViewerContainer from '@/components/image-viewer/ImageViewerContainer.vue'
import LinkInvalidContent from '@/components/link_invalid/LinkInvalidContent.vue'
import WorkbenchOverview from '@/components/management_workbench/WorkbenchOverview.vue'
import ProductDetailContent from '@/components/product_detail/ProductDetailContent.vue'
import ProductEditForm from '@/components/product_edit/ProductEditForm.vue'
import ProductManagementContent from '@/components/product_management/ProductManagementContent.vue'
import RecyclingBinContent from '@/components/recycling_bin/RecyclingBinContent.vue'
import ResourceLibraryPicker from '@/components/resource-library-picker/ResourceLibraryPicker.vue'
import SelectionListContent from '@/components/selection/SelectionListContent.vue'
import ShareHomeContent from '@/components/share_home/ShareHomeContent.vue'
import WatermarkSettingsContent from '@/components/watermark_settings/WatermarkSettingsContent.vue'
import CenteredLayout from '@/layouts/CenteredLayout.vue'
import BatchUploadLayout from '@/layouts/BatchUploadLayout.vue'
import ManagementLayout from '@/layouts/ManagementLayout.vue'
import StandardLayout from '@/layouts/StandardLayout.vue'
import PlaceholderView from '@/views/PlaceholderView.vue'
import { currentRouteState, normalizePath, replaceTo, syncRouteFromLocation, type AppRouteState } from '@/navigation'

interface AppRoute {
  path: string
  title: string
  component: Component
  description?: string
  layout?: Component
  props?: (state: AppRouteState) => Record<string, unknown>
  redirect?: (state: AppRouteState) => string
}

const toQueryString = (query: Record<string, string>) => {
  const params = new URLSearchParams()
  Object.entries(query).forEach(([key, value]) => {
    if (value !== undefined && value !== '') params.set(key, value)
  })
  const value = params.toString()
  return value ? `?${value}` : ''
}

const routes: AppRoute[] = [
  {
    path: '/product-detail',
    title: '产品详情 - 家纺云相册',
    description: '查看产品花色图与详情图',
    layout: markRaw(StandardLayout),
    component: markRaw(ProductDetailContent),
  },
  {
    path: '/recycle-bin',
    title: '回收站 - 家纺云',
    component: markRaw(PlaceholderView),
    redirect: ({ query, hash }) => `/recycling-bin${toQueryString(query)}${hash}`,
  },
  {
    path: '/share-home',
    title: '家纺云产品主页',
    description: '展示家纺产品、分类与详情图',
    layout: markRaw(StandardLayout),
    component: markRaw(ShareHomeContent),
  },
  {
    path: '/category',
    title: '分类浏览 - 家纺云相册',
    layout: markRaw(StandardLayout),
    component: markRaw(CategoryBrowserContent),
  },
  {
    path: '/favorites',
    title: '我的收藏 - 家纺云相册',
    layout: markRaw(StandardLayout),
    component: markRaw(FavoritesList),
  },
  {
    path: '/browsing-history',
    title: '浏览足迹 - 家纺云相册',
    layout: markRaw(StandardLayout),
    component: markRaw(BrowsingHistoryContent),
  },
  {
    path: '/my-selections',
    title: '我的选款 - 家纺云',
    layout: markRaw(ManagementLayout),
    component: markRaw(SelectionListContent),
    props: () => ({ mode: 'my' }),
  },
  {
    path: '/customer-selections',
    title: '客户选款 - 家纺云',
    layout: markRaw(ManagementLayout),
    component: markRaw(SelectionListContent),
    props: () => ({ mode: 'customer' }),
  },
  {
    path: '/image-viewer',
    title: '图片查看器 - 家纺云',
    layout: markRaw(CenteredLayout),
    component: markRaw(ImageViewerContainer),
  },
  {
    path: '/access-denied',
    title: '无权限访问 - 家纺云',
    layout: markRaw(CenteredLayout),
    component: markRaw(AccessDeniedContent),
  },
  {
    path: '/link-invalid',
    title: '链接已失效 - 家纺云相册',
    layout: markRaw(CenteredLayout),
    component: markRaw(LinkInvalidContent),
  },
  {
    path: '/batch-upload',
    title: '协同编辑 - 家纺云',
    layout: markRaw(BatchUploadLayout),
    component: markRaw(BatchUploadVisitorContent),
  },
  {
    path: '/management-workbench',
    title: '管理工作台 - 家纺云',
    layout: markRaw(ManagementLayout),
    component: markRaw(WorkbenchOverview),
  },
  {
    path: '/product-management',
    title: '产品管理 - 家纺云',
    layout: markRaw(ManagementLayout),
    component: markRaw(ProductManagementContent),
  },
  {
    path: '/product-edit',
    title: '产品编辑 - 家纺云',
    layout: markRaw(ManagementLayout),
    component: markRaw(ProductEditForm),
  },
  {
    path: '/resource-library-picker',
    title: '资源库选择 - 家纺云',
    layout: markRaw(CenteredLayout),
    component: markRaw(ResourceLibraryPicker),
  },
  {
    path: '/batch-upload-settings',
    title: '协同编辑设置 - 家纺云',
    layout: markRaw(ManagementLayout),
    component: markRaw(BatchUploadSettingsContent),
    props: ({ query }) => ({ productId: String(query.productId || '') }),
  },
  {
    path: '/category-management',
    title: '分类管理 - 家纺云',
    layout: markRaw(ManagementLayout),
    component: markRaw(CategoryList),
  },
  {
    path: '/home-settings',
    title: '编辑主页 - 家纺云',
    layout: markRaw(ManagementLayout),
    component: markRaw(HomeSettingsContent),
  },
  {
    path: '/billing-usage',
    title: '容量与套餐 - 家纺云管理工作台',
    layout: markRaw(ManagementLayout),
    component: markRaw(BillingUsageContent),
  },
  {
    path: '/watermark-settings',
    title: '水印设置 - 家纺云',
    layout: markRaw(ManagementLayout),
    component: markRaw(WatermarkSettingsContent),
  },
  {
    path: '/recycling-bin',
    title: '回收站 - 家纺云',
    layout: markRaw(ManagementLayout),
    component: markRaw(RecyclingBinContent),
  },
  {
    path: '/placeholder',
    title: '页面建设中 - 家纺云',
    component: markRaw(PlaceholderView),
  },
]

const placeholderRoute = routes.find((route) => route.path === '/placeholder')!

const findRoute = (path: string) => {
  const normalized = normalizePath(path)
  return routes.find((route) => route.path === normalized) || placeholderRoute
}

export const currentRoute = computed(() => {
  const route = findRoute(currentRouteState.value.path)
  return {
    ...route,
    path: currentRouteState.value.path,
    query: currentRouteState.value.query,
    props: route.props?.(currentRouteState.value) || {},
  }
})

const updateDocumentMeta = () => {
  const route = findRoute(currentRouteState.value.path)
  document.title = route.title || '家纺云相册'
  const description = route.description || '家纺云产品相册与协作上传'
  document.querySelector('meta[name="description"]')?.setAttribute('content', description)
}

const applyRedirect = () => {
  const route = findRoute(currentRouteState.value.path)
  if (!route.redirect) return false
  replaceTo(route.redirect(currentRouteState.value))
  return true
}

export const installAppRouter = () => {
  syncRouteFromLocation()
  window.addEventListener('popstate', syncRouteFromLocation)
  watch(
    currentRouteState,
    () => {
      if (!applyRedirect()) updateDocumentMeta()
    },
    { immediate: true, deep: true }
  )
}
