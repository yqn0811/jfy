<script setup lang="ts">
import { computed, nextTick, ref, watch } from 'vue'
import { toast } from 'vue-sonner'
import { Button } from '@/components/ui/button'
import { Card, CardContent } from '@/components/ui/card'
import SafeIcon from '@/components/common/SafeIcon.vue'
import EmptyState from '@/components/common/EmptyState.vue'
import ConfirmDialog from '@/components/common/ConfirmDialog.vue'
import TargetShareDialog from '@/components/common/TargetShareDialog.vue'
import SelectionDetailDialog from '@/components/selection/SelectionDetailDialog.vue'
import SelectionPickerDialog from '@/components/selection/SelectionPickerDialog.vue'
import { getUrlHomeTarget, pcApi } from '@/lib/api'
import { mapProduct, mapProductImagesFromDetail, pickImage, unwrapList } from '@/lib/jfyuntu-mappers'
import { buildSelectionProductImageMap, pickSelectionImageList } from '@/lib/selection-images'
import { navigateToInternal } from '@/navigation'
import type { ProductData } from '@/data/ProductData'
import type { ProductImageData } from '@/data/ProductImageData'

type SelectionMode = 'my' | 'customer'

const props = withDefaults(defineProps<{ mode?: SelectionMode }>(), {
  mode: 'my',
})

const isLoading = ref(false)
const selections = ref<any[]>([])
const confirmOpen = ref(false)
const selectionToDelete = ref<any>(null)
const detailDialogOpen = ref(false)
const activeSelection = ref<any>(null)
const shareDialogOpen = ref(false)
const sharingSelection = ref<any>(null)
const brokenPreviewImages = ref<Set<string>>(new Set())
const selectionPickerOpen = ref(false)
const editingSelection = ref<any>(null)
const editingProduct = ref<ProductData | null>(null)
const editingProductImages = ref<ProductImageData[]>([])
const editLoadingSelectionId = ref('')
const pendingSelectionId = ref('')
let loadSerial = 0

const pageTitle = computed(() => props.mode === 'customer' ? '客户选款' : '我的选款')
const pageDesc = computed(() => props.mode === 'customer' ? '客户发送给你的选款单会展示在这里' : '你发送给商家的选款单会展示在这里')
const emptyTitle = computed(() => props.mode === 'customer' ? '暂无客户选款' : '暂无选款单')
const emptyDesc = computed(() => props.mode === 'customer' ? '客户从你的主页选款并发送后，会在这里看到记录' : '从商家分享主页选择花色并发送后，会在这里看到记录')

const normalizeText = (value: any) => {
  if (value === undefined || value === null) return ''
  const text = String(value).trim()
  return text && text !== 'null' && text !== 'undefined' ? text : ''
}

const normalizeRows = (raw: any) => {
  const rows = unwrapList(raw)
  if (rows.length) return rows
  return unwrapList(raw?.data || raw?.list || raw?.lists || raw?.records || raw?.items || raw?.result)
}

const normalizeUserSummary = (user: any = {}) => {
  const avatar = pickImage(
    user.avatar,
    user.avatar_url,
    user.avatarUrl,
    user.headimgurl,
    user.head_img,
    user.headImg,
    user.company_logo,
    user.logo,
    user.picture
  )
  const nickname =
    normalizeText(user.company_name) ||
    normalizeText(user.nickname) ||
    normalizeText(user.display_name) ||
    normalizeText(user.name) ||
    normalizeText(user.mobile) ||
    '微信用户'
  return {
    ...user,
    avatar,
    avatar_url: avatar,
    nickname,
  }
}

const mergeSelectionDetail = (item: any, detail: any = null) => {
  const info = detail?.info || {}
  const product = detail?.product_summary || detail?.product || item.product_summary || item.product || {}
  const productImageMap = buildSelectionProductImageMap(detail?.product, item.product, item.product_summary, detail?.product_summary)
  const detailImages = pickSelectionImageList(
    productImageMap,
    detail?.list,
    detail?.selected_preview,
    detail?.grouped_pictures?.variant_pictures,
    detail?.grouped_pictures?.color_pictures,
    detail?.grouped_pictures?.pictures,
    detail?.cover_img
  )
  const fallbackImages = pickSelectionImageList(productImageMap, item.selected_preview, item.list, item.cover_img)
  const selectedImages = detailImages.length ? detailImages : fallbackImages
  const totalSelected = Number(detail?.total_selected || item.total_selected || item.product_count || selectedImages.length || 0)

  return {
    ...info,
    ...item,
    title: item.title || item.name || info.title || info.name || `选款单 #${item.id || info.id}`,
    name: item.name || item.title || info.name || info.title || `选款单 #${item.id || info.id}`,
    customer: normalizeUserSummary(detail?.customer || item.customer || {}),
    factory: normalizeUserSummary(detail?.factory || item.factory || {}),
    product,
    product_summary: detail?.product_summary || item.product_summary || product,
    share_code: detail?.share_code || detail?.code || item.share_code || item.code || item.factory?.share_code || '',
    code: detail?.code || detail?.share_code || item.code || item.share_code || item.factory?.share_code || '',
    detail: detail ? { ...detail, list: selectedImages, selected_preview: selectedImages } : detail,
    list: selectedImages,
    selected_preview: selectedImages,
    cover_img: detail?.cover_img || item.cover_img || [],
    share_img: detail?.share_img || item.share_img || product.share_img || product.cover_img || '',
    product_count: totalSelected,
    total_selected: totalSelected,
  }
}

const loadSelections = async () => {
  const serial = ++loadSerial
  isLoading.value = true
  try {
    const raw = props.mode === 'customer'
      ? await pcApi.getCustomerSelections({ limit: 50 })
      : await pcApi.getMySelections({ limit: 50 })
    const rows = normalizeRows(raw)
    const enrichedRows = await Promise.all(rows.map(async (item: any) => {
      if (!item?.id) return mergeSelectionDetail(item)
      try {
        const detail = await pcApi.getSelectionDetail(String(item.id))
        return mergeSelectionDetail(item, detail)
      } catch {
        return mergeSelectionDetail(item)
      }
    }))
    if (serial === loadSerial) {
      selections.value = enrichedRows
      openSelectionFromQuery()
    }
  } catch (error: any) {
    if (serial === loadSerial) {
      toast.error(error?.message || '选款单加载失败')
      selections.value = []
    }
  } finally {
    if (serial === loadSerial) {
      isLoading.value = false
    }
  }
}

const getTitle = (item: any) => item.title || item.name || item.detail?.info?.title || item.detail?.info?.name || `选款单 #${item.id}`
const getProductName = (item: any) => {
  const product = item.product || item.product_summary || item.detail?.product_summary || item.detail?.product || {}
  return product.name || product.folder_name || item.product_name || item.detail?.info?.product_name || '关联产品'
}
const getPeerName = (item: any) => {
  const peer = props.mode === 'customer'
    ? (item.customer || item.detail?.customer)
    : (item.factory || item.detail?.factory)
  return peer?.company_name || peer?.nickname || peer?.mobile || '未知用户'
}
const getPreviewImages = (item: any) => {
  const productImageMap = buildSelectionProductImageMap(item.detail?.product, item.product, item.product_summary)
  return pickSelectionImageList(
    productImageMap,
    item.selected_preview,
    item.list,
    item.detail?.list,
    item.detail?.selected_preview,
    item.detail?.grouped_pictures?.variant_pictures,
    item.cover_img,
    item.detail?.cover_img
  )
}
const getPreviewImageSrc = (image: any) => pickImage(image)
const getPreviewImageName = (image: any, index: number) =>
  image?.pic_name || image?.name || image?.title || `花色${index + 1}`
const getPreviewImageKey = (item: any, image: any, index: number) =>
  `${item.id || 'selection'}:${image?.id || image?.pic_id || index}:${getPreviewImageSrc(image)}`
const isPreviewImageBroken = (item: any, image: any, index: number) =>
  brokenPreviewImages.value.has(getPreviewImageKey(item, image, index))
const markPreviewImageBroken = (item: any, image: any, index: number) => {
  brokenPreviewImages.value = new Set([...brokenPreviewImages.value, getPreviewImageKey(item, image, index)])
}

const getSelectionProductId = (rawItem: any = {}) => {
  const item = rawItem || {}
  return String(
    item.product?.id ||
      item.product_summary?.id ||
      item.detail?.product_summary?.id ||
      item.detail?.product?.id ||
      item.product_id ||
      item.detail?.info?.product_id ||
      ''
  )
}

const getSelectionFactoryUid = (rawItem: any = {}) => {
  const item = rawItem || {}
  return String(
    item.factory?.id ||
      item.detail?.factory?.id ||
      item.factory_uid ||
      item.detail?.info?.factory_uid ||
      ''
  )
}

const getSelectionShareCode = (rawItem: any = {}) => {
  const item = rawItem || {}
  return String(
    item.share_code ||
      item.code ||
      item.detail?.share_code ||
      item.detail?.code ||
      item.factory?.share_code ||
      item.detail?.factory?.share_code ||
      ''
  )
}

const getItemSelectionId = (rawItem: any = {}) => {
  const item = rawItem || {}
  return String(item.id || item.info?.id || item.detail?.info?.id || '')
}

const actionGridClass = computed(() =>
  props.mode === 'my'
    ? 'grid-cols-2 2xl:grid-cols-5'
    : 'grid-cols-2 xl:grid-cols-3'
)

const normalizeProductDetailSource = (raw: any) => {
  const detailSource = raw?.folder_info || raw?.product || raw?.data?.folder_info || raw?.data?.product || raw?.data || raw || {}
  return {
    ...detailSource,
    pictures: detailSource?.pictures ?? raw?.pictures,
    pic_list: detailSource?.pic_list ?? raw?.pic_list,
    pic_ids_arr: detailSource?.pic_ids_arr ?? raw?.pic_ids_arr,
    detail_pic_list: detailSource?.detail_pic_list ?? raw?.detail_pic_list,
    detail_pic_ids_arr: detailSource?.detail_pic_ids_arr ?? raw?.detail_pic_ids_arr,
  }
}

const formatTime = (value: any) => {
  if (!value) return ''
  if (typeof value === 'string' && /[年/-]/.test(value)) return value
  const numberValue = Number(value)
  const date = Number.isFinite(numberValue)
    ? new Date(numberValue > 10_000_000_000 ? numberValue : numberValue * 1000)
    : new Date(String(value).replace(/-/g, '/'))
  if (Number.isNaN(date.getTime())) return ''
  return date.toLocaleDateString('zh-CN', { year: 'numeric', month: '2-digit', day: '2-digit' })
}

const handleViewProduct = (item: any) => {
  const productId = getSelectionProductId(item)
  const targetUserId = getSelectionFactoryUid(item)
  if (!productId) {
    toast.error('关联产品不存在')
    return
  }
  const params = new URLSearchParams({ productId: String(productId) })
  if (targetUserId) params.set('uid', String(targetUserId))
  navigateToInternal(`./share-home?${params.toString()}`)
}

const openDetailDialog = (item: any) => {
  activeSelection.value = item
  detailDialogOpen.value = true
}

const handleShareSelection = async (item: any) => {
  const selectionId = getItemSelectionId(item)
  if (!selectionId) {
    toast.error('选款单不存在')
    return
  }

  let targetItem = item
  if (!getSelectionShareCode(targetItem)) {
    try {
      const detail = await pcApi.getSelectionDetail(selectionId)
      targetItem = mergeSelectionDetail(item, detail)
      selections.value = selections.value.map(row =>
        getItemSelectionId(row) === selectionId ? targetItem : row
      )
    } catch (error: any) {
      toast.error(error?.message || '选款单分享信息加载失败')
      return
    }
  }
  if (!getSelectionShareCode(targetItem)) {
    toast.error('选款单分享码缺失，请刷新后重试')
    return
  }
  sharingSelection.value = targetItem
  shareDialogOpen.value = true
}

const openSelectionFromQuery = () => {
  if (!pendingSelectionId.value) return
  const selectionId = pendingSelectionId.value
  const matched = selections.value.find(item => getItemSelectionId(item) === selectionId)
  const params = new URLSearchParams(window.location.search)
  const shareTarget = readSelectionShareTargetFromLocation()
  const fallback = {
    id: selectionId,
    title: `选款单 #${selectionId}`,
    name: `选款单 #${selectionId}`,
    product_id: params.get('productId') || params.get('product_id') || '',
    share_code: shareTarget.shareCode,
    code: shareTarget.shareCode,
    factory_uid: shareTarget.targetUserId,
  }
  openDetailDialog(matched || fallback)
  pendingSelectionId.value = ''
}

const readSelectionIdFromLocation = () => {
  if (typeof window === 'undefined') return ''
  const params = new URLSearchParams(window.location.search)
  return params.get('selectionId') || params.get('selection_id') || ''
}

const readSelectionShareTargetFromLocation = () => {
  const target = getUrlHomeTarget()
  return {
    targetUserId: target.targetUserId,
    shareCode: target.shareCode,
  }
}

const openEditSelectionDialog = async (item: any) => {
  const productId = getSelectionProductId(item)
  const factoryUid = getSelectionFactoryUid(item)
  const selectionId = String(item.id || item.info?.id || '')
  if (!selectionId) {
    toast.error('选款单不存在')
    return
  }
  if (!productId) {
    toast.error('关联产品不存在')
    return
  }
  if (!factoryUid) {
    toast.error('缺少商家信息')
    return
  }

  editLoadingSelectionId.value = selectionId
  try {
    const [selectionDetail, productRaw] = await Promise.all([
      pcApi.getSelectionDetail(selectionId).catch(() => item.detail || null),
      pcApi.getHomeProductDetail({ targetUserId: factoryUid }, productId),
    ])
    const productDetail = normalizeProductDetailSource(productRaw)
    const product = {
      ...mapProduct({ ...productDetail, id: productDetail.id || productDetail.fid || productId, uid: productDetail.uid || factoryUid }, factoryUid),
      id: String(productDetail.id || productDetail.fid || productId),
      ownerUserId: String(productDetail.uid || productDetail.owner_uid || factoryUid),
    }
    const images = mapProductImagesFromDetail(productDetail, product.id)
    editingSelection.value = mergeSelectionDetail(item, selectionDetail)
    editingProduct.value = product
    editingProductImages.value = images
    selectionPickerOpen.value = true
  } catch (error: any) {
    toast.error(error?.message || '选款产品加载失败')
  } finally {
    editLoadingSelectionId.value = ''
  }
}

const handleSelectionSaved = async (selection: any) => {
  if (selection && editingSelection.value?.id) {
    editingSelection.value = mergeSelectionDetail(editingSelection.value, selection)
  }
  await loadSelections()
}

const openDeleteConfirm = (item: any) => {
  selectionToDelete.value = item
  confirmOpen.value = true
}

const handleDelete = async () => {
  if (!selectionToDelete.value?.id) return
  try {
    await pcApi.deleteSelection(String(selectionToDelete.value.id))
    toast.success('选款单已删除')
    confirmOpen.value = false
    selectionToDelete.value = null
    await loadSelections()
  } catch (error: any) {
    toast.error(error?.message || '删除失败')
  }
}

watch(
  () => props.mode,
  () => {
    selections.value = []
    brokenPreviewImages.value = new Set()
    pendingSelectionId.value = readSelectionIdFromLocation()
    loadSelections()
  },
  { immediate: true }
)

watch(
  () => selections.value,
  () => nextTick(openSelectionFromQuery)
)
</script>

<template>
  <div class="page-body space-y-6">
    <div class="flex items-center justify-between gap-4">
      <div>
        <h1 class="text-page-title">{{ pageTitle }}</h1>
        <p class="text-caption mt-2">{{ pageDesc }}</p>
      </div>
      <Button variant="outline" class="gap-2" @click="loadSelections">
        <SafeIcon name="RefreshCw" :size="16" />
        刷新
      </Button>
    </div>

    <div v-if="isLoading" class="py-16 text-center text-muted-foreground">
      <SafeIcon name="Loader2" :size="24" class="mx-auto mb-2 animate-spin" />
      加载中...
    </div>

    <div v-else-if="selections.length === 0" class="py-16">
      <EmptyState icon="ClipboardList" :title="emptyTitle" :description="emptyDesc" />
    </div>

    <div v-else class="grid gap-4 xl:grid-cols-2">
      <Card v-for="item in selections" :key="item.id" class="overflow-hidden">
        <CardContent class="p-5">
          <div class="space-y-4">
            <div class="flex items-start justify-between gap-3">
              <div class="min-w-0">
                <h2 class="truncate text-base font-semibold">{{ getTitle(item) }}</h2>
                <p class="mt-1 truncate text-sm text-muted-foreground">{{ getProductName(item) }}</p>
              </div>
              <span class="rounded bg-primary/10 px-2 py-1 text-xs font-medium text-primary">
                {{ item.product_count || item.total_selected || getPreviewImages(item).length || 0 }} 款
              </span>
            </div>

            <div class="flex flex-wrap gap-2">
              <div
                v-for="(image, index) in getPreviewImages(item).slice(0, 4)"
                :key="index"
                class="h-24 w-24 overflow-hidden rounded-md border border-border bg-muted"
              >
                <div class="h-full w-full">
                  <img
                    v-if="getPreviewImageSrc(image) && !isPreviewImageBroken(item, image, index)"
                    :src="getPreviewImageSrc(image)"
                    :alt="getPreviewImageName(image, index)"
                    class="h-full w-full object-cover"
                    @error="markPreviewImageBroken(item, image, index)"
                  />
                  <div v-else class="flex h-full w-full items-center justify-center bg-muted">
                    <SafeIcon name="Image" :size="18" class="text-muted-foreground" />
                  </div>
                </div>
              </div>
              <div v-if="getPreviewImages(item).length === 0" class="flex h-24 w-24 items-center justify-center rounded-md bg-muted">
                <SafeIcon name="Image" :size="24" class="text-muted-foreground" />
              </div>
            </div>

            <div>
              <div class="grid gap-1 text-xs text-muted-foreground">
                <span>{{ props.mode === 'customer' ? '客户' : '商家' }}：{{ getPeerName(item) }}</span>
                <span>创建时间：{{ formatTime(item.display_time || item.create_time) || '-' }}</span>
              </div>
              <div class="mt-4 grid gap-2" :class="actionGridClass">
                <Button
                  v-if="props.mode === 'my'"
                  variant="outline"
                  size="sm"
                  class="w-full justify-center gap-2"
                  :disabled="editLoadingSelectionId === String(item.id)"
                  @click="openEditSelectionDialog(item)"
                >
                  <SafeIcon
                    :name="editLoadingSelectionId === String(item.id) ? 'Loader2' : 'CheckSquare'"
                    :size="14"
                    :class="editLoadingSelectionId === String(item.id) ? 'animate-spin' : ''"
                  />
                  编辑选款单
                </Button>
                <Button
                  v-if="props.mode === 'my'"
                  variant="outline"
                  size="sm"
                  class="w-full justify-center gap-2"
                  @click="handleShareSelection(item)"
                >
                  <SafeIcon name="Share2" :size="14" />
                  分享选款单
                </Button>
                <Button variant="outline" size="sm" class="w-full justify-center gap-2" @click="openDetailDialog(item)">
                  <SafeIcon name="ClipboardList" :size="14" />
                  查看选款单
                </Button>
                <Button variant="outline" size="sm" class="w-full justify-center gap-2" @click="handleViewProduct(item)">
                  <SafeIcon name="Eye" :size="14" />
                  查看产品
                </Button>
                <Button variant="outline" size="sm" class="w-full justify-center gap-2 border-destructive/30 text-destructive hover:bg-destructive/5 hover:text-destructive" @click="openDeleteConfirm(item)">
                  <SafeIcon name="Trash2" :size="14" />
                  删除
                </Button>
              </div>
            </div>
          </div>
        </CardContent>
      </Card>
    </div>

    <ConfirmDialog
      :open="confirmOpen"
      title="删除选款单"
      description="确定要删除该选款单吗？"
      confirm-text="删除"
      cancel-text="取消"
      variant="destructive"
      @update:open="confirmOpen = $event"
      @confirm="handleDelete"
    />

    <SelectionDetailDialog
      :open="detailDialogOpen"
      :selection-id="String(activeSelection?.id || '')"
      :fallback="activeSelection"
      :share-target="readSelectionShareTargetFromLocation()"
      @update:open="detailDialogOpen = $event"
    />

    <TargetShareDialog
      :open="shareDialogOpen"
      type="selection"
      title="分享选款单"
      description="选择分享方式，让客户查看这张选款单"
      :target-id="getItemSelectionId(sharingSelection)"
      :target-user-id="getSelectionFactoryUid(sharingSelection)"
      :share-code="getSelectionShareCode(sharingSelection)"
      :product-id="getSelectionProductId(sharingSelection)"
      web-path="./my-selections"
      web-param-name="selectionId"
      @update:open="shareDialogOpen = $event"
    />

    <SelectionPickerDialog
      v-if="editingProduct"
      :open="selectionPickerOpen"
      :product="editingProduct"
      :images="editingProductImages"
      :factory-uid="getSelectionFactoryUid(editingSelection)"
      :existing-selection="editingSelection"
      @update:open="selectionPickerOpen = $event"
      @saved="handleSelectionSaved"
    />
  </div>
</template>
