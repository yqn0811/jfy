
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
import type { ProductVO } from '@/data/ProductService'
import { toast } from 'vue-sonner'
import { cn } from '@/lib/utils'

interface Props {
  productId?: string
}

const props = defineProps<Props>()
const currentProductId = ref(props.productId || '')

const productData = ref<ProductVO | null>({
  id: props.productId || '',
  homeId: 'home_001',
  categoryId: 'cat_001',
  ownerUserId: 'user_001',
  name: '未命名产品',
  intro: '',
  coverUrl: '',
  visibility: 'public',
  hideDetailImage: false,
  isHot: false,
  sortOrder: 0,
  colorChartCount: 0,
  detailChartCount: 0,
  updatedAt: '',
  createdAt: '',
})

// 本地编辑状态
const isClient = ref(true)
const passwordEnabled = ref(false)
const password = ref('')
const expire = ref('permanent')
const isClosed = ref(true)
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

const updateQrCode = () => {
  qrCodeUrl.value = uploadUrl.value
    ? `https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=${encodeURIComponent(uploadUrl.value)}`
    : ''
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
  password.value = data?.password || data?.pwd || ''
  passwordEnabled.value = Number(data?.upload_enabled ?? data?.access_enabled ?? (password.value ? 1 : 0)) === 1
  isClosed.value = !passwordEnabled.value
  syncExpire(Number(data?.password_expire_time || data?.upload_pwd_expire_time || 0))
  updateQrCode()
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
    toast.error(error?.message || '加载批量上传设置失败')
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
  const combined = passwordEnabled.value
    ? `上传链接: ${uploadUrl.value}\n密码: ${password.value}\n有效期: ${expireLabel.value}\n二维码: ${qrCodeUrl.value}`
    : `上传链接: ${uploadUrl.value}\n状态: 已关闭，此产品无法访问`
  try {
    await navigator.clipboard.writeText(combined)
    toast.success('已复制')
  } catch (err) {
    toast.error('复制失败')
  }
}

// 切换密码启用
const handleTogglePassword = (checked: boolean) => {
  passwordEnabled.value = checked
  isClosed.value = !checked
  if (checked && !password.value) {
    password.value = generatePassword()
  } else if (!checked) {
    isClosed.value = true
  }
}

// 刷新密码
const handleRefreshPassword = () => {
  password.value = generatePassword()
  toast.success('密码已刷新')
}

// 关闭链接
const handleCloseLink = () => {
  showConfirmClose.value = true
}

const handleConfirmClose = () => {
  passwordEnabled.value = false
  isClosed.value = true
  toast.success('链接已关闭')
  showConfirmClose.value = false
}

// 保存设置
const handleSave = async () => {
  isSaving.value = true
  try {
    const updatedData = {
      fid: currentProductId.value,
      upload_enabled: passwordEnabled.value && !isClosed.value ? 1 : 0,
      upload_pwd: passwordEnabled.value && !isClosed.value ? password.value : '',
      upload_pwd_expire_time: passwordEnabled.value && !isClosed.value ? getExpireTimestamp() : 0,
    }
    const data = await pcApi.saveBatchUploadPassword(updatedData)
    applyBatchData(data)
    toast.success('设置已保存')
  } catch (err) {
    toast.error((err as any)?.message || '保存失败，请稍后重试')
  } finally {
    isSaving.value = false
  }
}

// 取消
const handleCancel = () => {
  window.location.href = './product-management.html'
}

// 查看产品
const handleViewProduct = () => {
  window.location.href = `./product-detail.html?productId=${currentProductId.value}`
}

onMounted(() => {
  isClient.value = false
  requestAnimationFrame(() => {
    const params = new URLSearchParams(window.location.search)
    currentProductId.value = params.get('productId') || params.get('fid') || props.productId || ''
    productData.value = {
      ...(productData.value as ProductVO),
      id: currentProductId.value,
    }
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
  <div class="page-body space-y-6">
    <!-- 页面标题 -->
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-page-title">批量上传设置</h1>
        <p class="text-caption mt-1">为产品生成协作上传链接，邀请团队成员上传花色图和详情图</p>
      </div>
      <Button variant="outline" size="sm" @click="handleViewProduct">
        <SafeIcon name="Eye" :size="16" class="mr-2" />
        查看产品
      </Button>
    </div>

    <!-- 产品信息卡片 -->
    <Card class="surface-raised">
      <CardHeader class="pb-3">
        <CardTitle class="text-base">关联产品</CardTitle>
      </CardHeader>
      <CardContent class="flex items-center gap-4">
        <img
          :src="productData?.coverUrl || 'https://spark-builder.s3.cn-north-1.amazonaws.com.cn/image/2026/7/8/c6dbdae1-bc53-4e99-b099-b9f589fd9d02.png'"
          :alt="productData?.name"
          class="w-20 h-20 rounded-lg object-cover bg-muted"
        />
        <div class="flex-1 min-w-0">
          <h3 class="text-item-title font-medium truncate">{{ productData?.name || '未命名产品' }}</h3>
          <p class="text-caption mt-1 line-clamp-2">{{ productData?.intro || '暂无产品简介' }}</p>
        </div>
      </CardContent>
    </Card>

    <!-- 上传链接配置 -->
    <Card class="surface-raised">
      <CardHeader>
        <CardTitle class="text-base">上传链接</CardTitle>
        <CardDescription>分享此链接给团队成员，他们可以直接上传图片</CardDescription>
      </CardHeader>
      <CardContent class="space-y-4">
        <!-- 链接显示区 -->
        <div class="space-y-2">
          <Label class="text-label">上传链接</Label>
          <div class="flex items-center gap-2">
            <Input
              :value="uploadUrl"
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
        </div>

        <!-- 二维码区 -->
        <div class="space-y-2">
          <Label class="text-label">二维码</Label>
          <div class="flex items-center gap-4">
            <div
              class="w-32 h-32 border border-border rounded-lg p-2 bg-white flex items-center justify-center cursor-pointer hover:shadow-card transition-shadow"
              @click="openQrPreview"
              :class="!uploadUrl && 'opacity-50 cursor-not-allowed'"
            >
              <img
                v-if="qrCodeUrl"
                :src="qrCodeUrl"
                alt="QR Code"
                class="w-full h-full object-contain"
              />
              <SafeIcon v-else name="QrCode" :size="32" class="text-muted-foreground" />
            </div>
            <div class="flex flex-col gap-2">
              <Button
                variant="outline"
                size="sm"
                @click="handleCopyQrCode"
                :disabled="!uploadUrl"
              >
                <SafeIcon name="Copy" :size="16" class="mr-1" />
                复制二维码
              </Button>
              <Button
                variant="outline"
                size="sm"
                @click="openQrPreview"
                :disabled="!uploadUrl"
              >
                <SafeIcon name="Maximize2" :size="16" class="mr-1" />
                放大预览
              </Button>
            </div>
          </div>
        </div>

        <!-- 组合复制 -->
        <Button
          variant="secondary"
          class="w-full"
          @click="handleCopyCombined"
          :disabled="!uploadUrl"
        >
          <SafeIcon name="Copy" :size="16" class="mr-2" />
          复制组合信息（链接+密码+二维码）
        </Button>

        <!-- 生成新链接 -->
        <Button
          variant="outline"
          class="w-full"
          @click="handleGenerateLink"
          :disabled="isSaving"
        >
          <SafeIcon name="RefreshCw" :size="16" class="mr-2" />
          生成新链接
        </Button>
      </CardContent>
    </Card>

    <!-- 访问控制 -->
    <Card class="surface-raised">
      <CardHeader>
        <CardTitle class="text-base">访问控制</CardTitle>
        <CardDescription>设置链接的访问权限和有效期</CardDescription>
      </CardHeader>
      <CardContent class="space-y-6">
        <!-- 密码保护 -->
        <div class="space-y-3">
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
              <Label class="text-label">启用访问密码</Label>
              <SafeIcon name="Lock" :size="14" class="text-muted-foreground" />
            </div>
            <Switch
              :checked="passwordEnabled"
              @update:checked="handleTogglePassword"
            />
          </div>

          <!-- 密码输入框 -->
          <div v-if="passwordEnabled" class="flex items-center gap-2 pl-4 border-l-2 border-primary/30">
            <Input
              v-model="password"
              readonly
              class="flex-1 bg-muted/50 text-muted-foreground cursor-not-allowed font-mono text-center tracking-widest"
            />
            <Button
              variant="outline"
              size="sm"
              @click="handleRefreshPassword"
              class="shrink-0"
            >
              <SafeIcon name="RefreshCw" :size="16" />
            </Button>
          </div>
        </div>

        <!-- 有效期 -->
        <div class="space-y-2">
          <Label for="expire" class="text-label">链接有效期</Label>
          <Select v-model="expire" :disabled="!passwordEnabled">
            <SelectTrigger id="expire" class="w-full">
              <SelectValue :placeholder="expireLabel" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="permanent">永久有效</SelectItem>
              <SelectItem value="1d">1 天</SelectItem>
              <SelectItem value="7d">7 天</SelectItem>
              <SelectItem value="30d">30 天</SelectItem>
            </SelectContent>
          </Select>
          <p class="text-[10px] text-muted-foreground">
            <SafeIcon name="AlertCircle" :size="12" class="inline mr-1" />
            关闭访问密码后，此产品上传入口无法访问；开启后访客需输入密码
          </p>
        </div>
      </CardContent>
    </Card>

    <!-- 链接状态 -->
    <Card v-if="isClosed" class="surface-raised border-destructive/30 bg-destructive/5">
      <CardContent class="pt-6 flex items-center gap-3">
        <SafeIcon name="AlertTriangle" :size="20" class="text-destructive shrink-0" />
        <div class="flex-1">
          <p class="text-sm font-medium text-destructive">链接已关闭</p>
          <p class="text-xs text-destructive/70 mt-0.5">此链接已被关闭，外部用户无法再访问上传页面</p>
        </div>
      </CardContent>
    </Card>

    <!-- 操作按钮 -->
    <div class="flex items-center justify-between gap-3 pt-4 border-t border-border">
      <Button
        variant="outline"
        @click="handleCancel"
      >
        取消
      </Button>

      <div class="flex items-center gap-3">
        <Button
          v-if="!isClosed"
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
          <span v-else>{{ isClosed ? '保存关闭状态' : '保存设置' }}</span>
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
            使用微信或其他二维码扫描工具扫描此二维码即可打开上传页面
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
