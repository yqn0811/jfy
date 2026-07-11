
<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Switch } from '@/components/ui/switch'
import { Label } from '@/components/ui/label'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog'
import SafeIcon from '@/components/common/SafeIcon.vue'
import ConfirmDialog from '@/components/common/ConfirmDialog.vue'
import { pcApi } from '@/lib/api'
import { mapProduct } from '@/lib/jfyuntu-mappers'
import { navigateToInternal } from '@/navigation'
import type { ProductVO } from '@/data/ProductService'
import { toast } from 'vue-sonner'

interface Props {
  productId?: string
  embedded?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  productId: '',
  embedded: false,
})
const emit = defineEmits<{
  (e: 'cancel'): void
  (e: 'saved'): void
  (e: 'view-product', productId: string): void
}>()
const currentProductId = ref(props.productId || '')

const productData = ref<Partial<ProductVO> | null>(null)

// 本地编辑状态
const isClient = ref(true)
const uploadEnabled = ref(false)
const passwordEnabled = ref(false)
const password = ref('')
const expire = ref('permanent')
const uploadUrl = ref('')
const qrCodeUrl = ref('')

// UI 状态
const showQrPreview = ref(false)
const showConfirmClose = ref(false)
const isSaving = ref(false)
const isLoading = ref(false)

// 生成随机密码
const generatePassword = () => {
  const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'
  let result = ''
  for (let i = 0; i < 4; i++) {
    result += chars.charAt(Math.floor(Math.random() * chars.length))
  }
  return result
}

const normalizeQrImage = (value = '') => {
  if (!value) return ''
  if (value.startsWith('data:image/')) return value
  if (/^https?:\/\//i.test(value)) return value
  if (value.startsWith('/')) return value
  return `data:image/png;base64,${value}`
}

const syncExpire = (timestamp: number) => {
  if (!timestamp) {
    expire.value = 'permanent'
    return
  }
  const diffDays = Math.round((timestamp - Math.floor(Date.now() / 1000)) / 86400)
  if (diffDays <= 1) expire.value = '1d'
  else if (diffDays <= 7) expire.value = '7d'
  else if (diffDays <= 30) expire.value = '30d'
  else expire.value = 'permanent'
}

const getExpireTimestamp = () => {
  const days: Record<string, number> = { '1d': 1, '7d': 7, '30d': 30 }
  const day = days[expire.value] || 0
  return day ? Math.floor(Date.now() / 1000) + day * 86400 : 0
}

const applyBatchData = (data: any) => {
  uploadUrl.value = data?.upload_url || data?.url || uploadUrl.value
  qrCodeUrl.value = normalizeQrImage(data?.qrcode || data?.qrcode_url || data?.qr_image || data?.image_base64 || '') || qrCodeUrl.value

  const incomingPassword = data?.password ?? data?.pwd
  if (incomingPassword !== undefined) {
    password.value = incomingPassword || ''
  }

  const incomingEnabled = data?.upload_enabled ?? data?.access_enabled
  if (incomingEnabled !== undefined) {
    uploadEnabled.value = Number(incomingEnabled) === 1
  }

  const incomingHasPassword = data?.has_password
  if (incomingHasPassword !== undefined) {
    passwordEnabled.value = Number(incomingHasPassword) === 1
  } else {
    passwordEnabled.value = !!incomingPassword
  }
  syncExpire(Number(data?.password_expire_time || data?.upload_pwd_expire_time || 0))
}

const loadData = async () => {
  if (!currentProductId.value) {
    toast.error('缺少产品信息，请从产品列表进入')
    return
  }
  isLoading.value = true
  try {
    const [batch, detail] = await Promise.all([
      pcApi.getBatchUploadLink(currentProductId.value),
      pcApi.getProductEditDetail(currentProductId.value).catch(() => null),
    ])
    applyBatchData(batch)
    if (detail) {
      const rawProduct = detail?.folder_info || detail?.product || detail
      productData.value = mapProduct(rawProduct) as ProductVO
    }
  } catch (error: any) {
    toast.error(error?.message || '加载协同编辑设置失败')
  } finally {
    isLoading.value = false
  }
}

// 生成链接
const handleGenerateLink = async () => {
  try {
    const data = await pcApi.resetBatchUploadLink(currentProductId.value)
    applyBatchData(data)
    toast.success('链接已生成')
  } catch (error: any) {
    toast.error(error?.message || '生成失败')
  }
}

// 复制链接
const handleCopyLink = async () => {
  try {
    await navigator.clipboard.writeText(uploadUrl.value)
    toast.success('已复制')
  } catch (err) {
    toast.error('复制失败')
  }
}

// 复制二维码
const handleCopyQrCode = async () => {
  if (!qrCodeUrl.value) {
    toast.error('暂无二维码')
    return
  }
  try {
    await navigator.clipboard.writeText(qrCodeUrl.value)
    toast.success('已复制')
  } catch (err) {
    toast.error('复制失败')
  }
}

const openQrPreview = () => {
  if (!qrCodeUrl.value) return
  showQrPreview.value = true
}

// 复制组合信息
const handleCopyCombined = async () => {
  const combined = uploadEnabled.value
    ? `协同编辑链接: ${uploadUrl.value}${passwordEnabled.value ? `\n访问密码: ${password.value}\n密码有效期: ${expireLabel.value}` : '\n访问密码: 未启用'}${qrCodeUrl.value ? `\n二维码: ${qrCodeUrl.value}` : ''}`
    : `协同编辑链接: ${uploadUrl.value}\n状态: 已关闭，此产品无法访问`
  try {
    await navigator.clipboard.writeText(combined)
    toast.success('已复制')
  } catch (err) {
    toast.error('复制失败')
  }
}

const handleToggleUpload = () => {
  if (uploadEnabled.value && passwordEnabled.value && !password.value) {
    password.value = generatePassword()
  }
}

const toggleUploadEnabled = () => {
  uploadEnabled.value = !uploadEnabled.value
  handleToggleUpload()
}

// 切换密码启用
const handleTogglePassword = () => {
  if (passwordEnabled.value) {
    uploadEnabled.value = true
    if (!password.value) {
      password.value = generatePassword()
    }
  }
}

const togglePasswordEnabled = () => {
  passwordEnabled.value = !passwordEnabled.value
  handleTogglePassword()
}

// 刷新密码
const handleRefreshPassword = () => {
  passwordEnabled.value = true
  uploadEnabled.value = true
  password.value = generatePassword()
  toast.success('密码已刷新')
}

const handleCopyPassword = async () => {
  if (!passwordEnabled.value || !password.value) {
    toast.error('请先启用访问密码')
    return
  }
  try {
    await navigator.clipboard.writeText(password.value)
    toast.success('密码已复制')
  } catch (err) {
    toast.error('复制失败')
  }
}

// 关闭链接
const handleCloseLink = () => {
  showConfirmClose.value = true
}

const handleConfirmClose = () => {
  uploadEnabled.value = false
  toast.success('链接已关闭')
  showConfirmClose.value = false
}

// 保存设置
const handleSave = async () => {
  isSaving.value = true
  try {
    const isEnabled = uploadEnabled.value
    if (isEnabled && passwordEnabled.value && !password.value) {
      password.value = generatePassword()
    }
    const updatedData = {
      fid: currentProductId.value,
      upload_enabled: isEnabled ? 1 : 0,
      upload_pwd: passwordEnabled.value ? password.value : '',
      upload_pwd_expire_time: passwordEnabled.value ? getExpireTimestamp() : 0,
    }
    const data = await pcApi.saveBatchUploadPassword(updatedData)
    applyBatchData(data)
    toast.success('设置已保存')
    emit('saved')
  } catch (err) {
    toast.error((err as any)?.message || '保存失败，请稍后重试')
  } finally {
    isSaving.value = false
  }
}

// 取消
const handleCancel = () => {
  if (props.embedded) {
    emit('cancel')
    return
  }
  navigateToInternal('./product-management')
}

// 查看产品
const handleViewProduct = () => {
  if (props.embedded) {
    emit('view-product', currentProductId.value)
    return
  }
  navigateToInternal(`./share-home?productId=${currentProductId.value}`)
}

onMounted(() => {
  isClient.value = false
  requestAnimationFrame(() => {
    const params = new URLSearchParams(window.location.search)
    currentProductId.value = params.get('productId') || params.get('fid') || props.productId || ''
    productData.value = { id: currentProductId.value }
    isClient.value = true
    loadData()
  })
})

// 计算有效期显示文本
const expireLabel = computed(() => {
  const labels: Record<string, string> = {
    permanent: '永久有效',
    '1d': '1 天',
    '7d': '7 天',
    '30d': '30 天',
  }
  return labels[expire.value] || '永久有效'
})
</script>

<template>
  <div :class="props.embedded ? 'flex h-full min-h-0 flex-col' : 'page-body space-y-6'">
    <!-- 页面标题 -->
    <div v-if="!props.embedded" class="flex items-center justify-between">
      <div>
        <h1 class="text-page-title">协同编辑设置</h1>
        <p class="text-caption mt-1">为产品生成协同编辑链接，邀请团队成员上传花色图和详情图</p>
      </div>
      <Button variant="outline" size="sm" @click="handleViewProduct">
        <SafeIcon name="Eye" :size="16" class="mr-2" />
        查看产品
      </Button>
    </div>

    <div :class="props.embedded ? 'min-h-0 flex-1 overflow-y-auto pr-1' : ''">
      <div class="grid gap-4 lg:grid-cols-[1fr_340px]">
        <div class="space-y-4">
          <Card class="surface-raised">
            <CardContent class="flex items-center gap-4 p-4">
              <img
                v-if="productData?.coverUrl"
                :src="productData.coverUrl"
                :alt="productData?.name || '产品封面'"
                class="h-16 w-16 rounded-lg object-cover bg-muted"
              />
              <div
                v-else
                class="h-16 w-16 rounded-lg bg-muted flex items-center justify-center shrink-0"
              >
                <SafeIcon name="Image" :size="22" class="text-muted-foreground" />
              </div>
              <div class="min-w-0 flex-1">
                <h3 class="text-item-title font-medium truncate">{{ productData?.name || '未命名产品' }}</h3>
                <p class="text-caption mt-1 line-clamp-1">{{ productData?.intro || '暂无产品简介' }}</p>
              </div>
            </CardContent>
          </Card>

          <Card class="surface-raised">
            <CardHeader class="pb-3">
              <CardTitle class="text-base">协同编辑链接</CardTitle>
              <CardDescription>分享给团队成员上传花色图和详情图</CardDescription>
            </CardHeader>
            <CardContent class="space-y-3">
              <div class="flex items-center gap-2">
                <Input
                  :model-value="uploadUrl"
                  readonly
                  class="flex-1 bg-muted/50 text-muted-foreground cursor-not-allowed"
                />
                <Button
                  variant="outline"
                  size="sm"
                  @click="handleCopyLink"
                  :disabled="!uploadUrl"
                  class="shrink-0"
                >
                  <SafeIcon name="Copy" :size="16" class="mr-1" />
                  复制
                </Button>
              </div>
              <div class="grid grid-cols-2 gap-2">
                <Button
                  variant="secondary"
                  @click="handleCopyCombined"
                  :disabled="!uploadUrl"
                >
                  <SafeIcon name="Copy" :size="16" class="mr-2" />
                  复制链接和密码
                </Button>
                <Button
                  variant="outline"
                  @click="handleGenerateLink"
                  :disabled="isSaving"
                >
                  <SafeIcon name="RefreshCw" :size="16" class="mr-2" />
                  生成新链接
                </Button>
              </div>
            </CardContent>
          </Card>

          <Card class="surface-raised">
            <CardHeader class="pb-3">
              <CardTitle class="text-base">访问控制</CardTitle>
              <CardDescription>关闭后，此产品协同编辑入口无法访问</CardDescription>
            </CardHeader>
            <CardContent class="space-y-4">
              <div
                role="button"
                tabindex="0"
                class="flex w-full items-center justify-between gap-4 rounded-lg border border-border bg-muted/20 p-3 text-left transition-colors hover:border-primary/40 hover:bg-muted/30"
                @click="toggleUploadEnabled"
                @keydown.enter.prevent="toggleUploadEnabled"
                @keydown.space.prevent="toggleUploadEnabled"
              >
                <div class="flex items-center gap-2">
                  <Label class="text-label">开启协同编辑入口</Label>
                  <SafeIcon name="UploadCloud" :size="14" class="text-muted-foreground" />
                </div>
                <Switch
                  v-model="uploadEnabled"
                  @update:model-value="handleToggleUpload"
                  @click.stop
                />
              </div>

              <div
                role="button"
                tabindex="0"
                class="flex w-full items-center justify-between gap-4 rounded-lg p-3 text-left transition-colors hover:bg-muted/30"
                @click="togglePasswordEnabled"
                @keydown.enter.prevent="togglePasswordEnabled"
                @keydown.space.prevent="togglePasswordEnabled"
              >
                <div class="flex items-center gap-2">
                  <Label class="text-label">启用访问密码</Label>
                  <SafeIcon name="Lock" :size="14" class="text-muted-foreground" />
                </div>
                <Switch
                  v-model="passwordEnabled"
                  @update:model-value="handleTogglePassword"
                  @click.stop
                />
              </div>

              <div v-if="uploadEnabled && passwordEnabled" class="rounded-lg border border-primary/20 bg-primary/5 p-3">
                <div class="mb-2 flex items-center justify-between">
                  <span class="text-sm font-medium text-foreground">访问密码</span>
                  <span class="text-xs text-muted-foreground">有效期：{{ expireLabel }}</span>
                </div>
                <div class="grid gap-3 md:grid-cols-[1fr_180px]">
                  <div class="flex items-center gap-2">
                    <Input
                      v-model="password"
                      readonly
                      class="flex-1 bg-card font-mono text-center text-lg font-semibold tracking-widest"
                    />
                    <Button
                      variant="outline"
                      size="sm"
                      @click="handleCopyPassword"
                      class="shrink-0"
                    >
                      <SafeIcon name="Copy" :size="16" />
                    </Button>
                    <Button
                      variant="outline"
                      size="sm"
                      @click="handleRefreshPassword"
                      class="shrink-0"
                    >
                      <SafeIcon name="RefreshCw" :size="16" />
                    </Button>
                  </div>
                  <Select v-model="expire">
                    <SelectTrigger class="w-full bg-card">
                      <SelectValue :placeholder="expireLabel" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="permanent">永久有效</SelectItem>
                      <SelectItem value="1d">1 天</SelectItem>
                      <SelectItem value="7d">7 天</SelectItem>
                      <SelectItem value="30d">30 天</SelectItem>
                    </SelectContent>
                  </Select>
                </div>
              </div>

              <div
                :class="[
                  'flex items-center gap-3 rounded-lg border p-3 text-sm',
                  !uploadEnabled ? 'border-destructive/30 bg-destructive/5 text-destructive' : 'border-emerald-200 bg-emerald-50 text-emerald-700'
                ]"
              >
                <SafeIcon :name="!uploadEnabled ? 'AlertTriangle' : 'ShieldCheck'" :size="18" class="shrink-0" />
                <span>{{ !uploadEnabled ? '链接已关闭，外部用户无法访问协同编辑页面' : passwordEnabled ? `链接已开启，访客进入时需要输入访问密码，有效期${expireLabel}` : '链接已开启，访客无需密码即可上传' }}</span>
              </div>
            </CardContent>
          </Card>
        </div>

        <Card class="surface-raised">
          <CardHeader class="pb-3">
            <CardTitle class="text-base">二维码</CardTitle>
            <CardDescription>可扫码打开协同编辑页面</CardDescription>
          </CardHeader>
          <CardContent class="space-y-3">
            <div
              class="mx-auto flex h-56 w-56 cursor-pointer items-center justify-center rounded-lg border border-border bg-white p-3 transition-shadow hover:shadow-card"
              @click="openQrPreview"
              :class="!uploadUrl && 'opacity-50 cursor-not-allowed'"
            >
              <img
                v-if="qrCodeUrl"
                :src="qrCodeUrl"
                alt="QR Code"
                class="h-full w-full object-contain"
              />
              <SafeIcon v-else name="QrCode" :size="40" class="text-muted-foreground" />
            </div>
            <div class="grid grid-cols-2 gap-2">
              <Button
                variant="outline"
                size="sm"
                @click="handleCopyQrCode"
                :disabled="!qrCodeUrl"
              >
                <SafeIcon name="Copy" :size="16" class="mr-1" />
                复制二维码
              </Button>
              <Button
                variant="outline"
                size="sm"
                @click="openQrPreview"
                :disabled="!qrCodeUrl"
              >
                <SafeIcon name="Maximize2" :size="16" class="mr-1" />
                预览
              </Button>
            </div>
          </CardContent>
        </Card>
      </div>
    </div>

    <!-- 操作按钮 -->
    <div :class="props.embedded ? 'mt-4 flex items-center justify-between gap-3 border-t border-border pt-4' : 'flex items-center justify-between gap-3 pt-4 border-t border-border'">
      <Button
        variant="outline"
        @click="handleCancel"
      >
        取消
      </Button>

      <div class="flex items-center gap-3">
        <Button
          v-if="uploadEnabled"
          variant="outline"
          class="text-destructive hover:text-destructive hover:bg-destructive/10"
          @click="handleCloseLink"
        >
          <SafeIcon name="X" :size="16" class="mr-2" />
          关闭链接
        </Button>

        <Button
          variant="default"
          @click="handleSave"
          :disabled="isSaving"
        >
          <SafeIcon v-if="isSaving" name="Loader2" :size="16" class="mr-2 animate-spin" />
          <span v-else>{{ uploadEnabled ? '保存设置' : '保存关闭状态' }}</span>
        </Button>
      </div>
    </div>

    <!-- 二维码预览对话框 -->
    <Dialog v-model:open="showQrPreview">
      <DialogContent class="sm:max-w-[400px]">
        <DialogHeader>
          <DialogTitle>二维码预览</DialogTitle>
        </DialogHeader>
        <div class="flex flex-col items-center justify-center py-8">
          <img
            v-if="qrCodeUrl"
            :src="qrCodeUrl"
            alt="QR Code Preview"
            class="w-64 h-64 object-contain"
          />
          <p class="text-xs text-muted-foreground mt-4 text-center">
            使用微信或其他二维码扫描工具扫描此二维码即可打开协同编辑页面
          </p>
        </div>
      </DialogContent>
    </Dialog>

    <!-- 关闭链接确认对话框 -->
    <ConfirmDialog
      :open="showConfirmClose"
      @update:open="showConfirmClose = $event"
      title="关闭链接"
      description="关闭后，此链接将无法访问。已上传的图片不会删除，但外部用户无法再上传新图片。确定要关闭吗？"
      confirm-text="确认关闭"
      cancel-text="取消"
      variant="destructive"
      @confirm="handleConfirmClose"
    />
  </div>
</template>
