
<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { toast } from 'vue-sonner'
import { pcApi, uploadTokenStore } from '@/lib/api'
import { mapProduct } from '@/lib/jfyuntu-mappers'
import SafeIcon from '@/components/common/SafeIcon.vue'
import { Button } from '@/components/ui/button'
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog'
import { Input } from '@/components/ui/input'
import UploadZone from '@/components/batch_upload/UploadZone.vue'

const isClient = ref(false)
const initialLoading = ref(true)
const productId = ref('')
const uploadCode = ref('')
const uploadToken = ref('')
const product = ref<any>(null)
const folderInfo = ref<any>(null)
const ownerId = ref('')
const ownerName = ref('')
const ownerAvatar = ref('')
const remainingSize = ref('')
const uploadLimit = ref('')
const capacityTotal = ref('')
const capacityUsed = ref('')
const capacityPercent = ref(0)
const trafficRemaining = ref('')
const trafficUsed = ref('')
const trafficTotal = ref('')
const trafficPercent = ref(0)
const concurrencyLimit = ref(1)

const passwordVerified = ref(false)
const passwordDialogOpen = ref(false)
const passwordValue = ref('')
const passwordLoading = ref(false)
const accessClosed = ref(false)
const accessError = ref('')
const uploadedFiles = ref<{ colorChart: string[]; detailChart: string[] }>({
  colorChart: [],
  detailChart: []
})
const uploadingState = ref<{ colorChart: boolean; detailChart: boolean }>({
  colorChart: false,
  detailChart: false,
})

const hasUploadedAny = computed(() => {
  return uploadedFiles.value.colorChart.length > 0 || uploadedFiles.value.detailChart.length > 0
})

const maxUploadCountText = computed(() => {
  const count = Math.max(1, Number(concurrencyLimit.value || 1))
  return `${count} 并发`
})

const normalizePercent = (value: any) => {
  const percent = Number(value || 0)
  if (!Number.isFinite(percent)) return 0
  return Math.max(0, Math.min(Math.round(percent), 100))
}

const pickText = (...values: any[]) => {
  for (const value of values) {
    if (value === undefined || value === null) continue
    const text = String(value).trim()
    if (text && text !== 'null' && text !== 'undefined') return text
  }
  return ''
}

const initUpload = async () => {
  initialLoading.value = true
  accessClosed.value = false
  accessError.value = ''
  try {
    const info = await pcApi.getWebUploadInfo(uploadCode.value)
    const ownerInfo = info?.owner_info || {}
    const storageInfo = info?.owner_storage || info?.storage || {}
    const uploadPolicy = info?.upload_policy || {}
    const productInfo = info?.product_info || {}
    folderInfo.value = info
    accessClosed.value = Number(info?.upload_enabled ?? 1) !== 1
    productId.value = String(productInfo?.id || info?.id || '')
    ownerId.value = String(ownerInfo?.id || info?.owner_id || info?.uid || '')
    ownerName.value = pickText(ownerInfo?.display_name, ownerInfo?.company_name, ownerInfo?.nickname, info?.owner_name, info?.company_name, info?.nickname)
    ownerAvatar.value = pickText(ownerInfo?.company_logo, ownerInfo?.avatar, info?.owner_avatar, info?.company_logo, info?.avatar)
    remainingSize.value = pickText(storageInfo?.remaining_text, info?.remaining_text, info?.remaining_size_text, info?.remaining_size !== undefined ? `${info.remaining_size} MB` : '')
    uploadLimit.value = pickText(uploadPolicy?.single_file_limit_text, info?.single_file_limit_text, info?.upload_limit_text, info?.upload_limit !== undefined ? `${info.upload_limit} MB/张` : '')
    capacityTotal.value = pickText(storageInfo?.capacity_text, info?.capacity_text, info?.storage_capacity_text, info?.total_size_text)
    capacityUsed.value = pickText(storageInfo?.used_text, info?.used_text, info?.storage_used_text, info?.used_size_text)
    capacityPercent.value = normalizePercent(storageInfo?.used_percent || info?.used_percent || info?.storage_used_percent)
    trafficRemaining.value = pickText(uploadPolicy?.traffic_remaining_text, info?.traffic_remaining_text)
    trafficUsed.value = pickText(uploadPolicy?.traffic_used_text, info?.traffic_used_text)
    trafficTotal.value = pickText(uploadPolicy?.traffic_limit_text, info?.traffic_limit_text)
    trafficPercent.value = normalizePercent(uploadPolicy?.traffic_used_percent || info?.traffic_used_percent)
    concurrencyLimit.value = Math.max(1, Math.min(Number(uploadPolicy?.concurrency_limit || uploadPolicy?.upload_concurrency || info?.concurrency_limit || 1), 10))
    product.value = mapProduct({
      id: productInfo?.id || info?.id,
      uid: ownerId.value || info?.owner_id || info?.uid,
      folder_name: productInfo?.folder_name || info?.folder_name,
      folder_desc: productInfo?.folder_desc || info?.folder_desc,
      new_thumb: productInfo?.new_thumb || info?.new_thumb,
      folder_type: 2,
    })
    if (accessClosed.value) {
      accessError.value = '此产品协同编辑入口已关闭，请联系分享者开启'
      return
    }
    const cachedToken = uploadTokenStore.get(uploadCode.value)
    if (cachedToken) {
      uploadToken.value = cachedToken
      passwordVerified.value = true
      passwordDialogOpen.value = false
      return
    }
    if (Number(info?.has_password || 0) === 1) {
      passwordDialogOpen.value = true
    } else {
      await requestUploadToken('')
    }
  } catch (error: any) {
    accessClosed.value = true
    accessError.value = error?.message || '链接无效或已失效'
  } finally {
    initialLoading.value = false
    isClient.value = true
  }
}

onMounted(() => {
  requestAnimationFrame(() => {
    const params = new URLSearchParams(window.location.search)
    uploadCode.value = params.get('uploadd_code') || params.get('code') || ''
    if (!uploadCode.value) {
      accessClosed.value = true
      accessError.value = '缺少上传码，请检查链接是否完整'
      initialLoading.value = false
      isClient.value = true
    } else {
      initUpload()
    }
  })
})

const handlePasswordVerified = async () => {
  passwordVerified.value = true
  toast.success('密码验证成功')
}

const requestUploadToken = async (password: string) => {
  passwordLoading.value = true
  try {
    const data = await pcApi.getWebUploadToken(uploadCode.value, password)
    uploadToken.value = data?.token || ''
    uploadTokenStore.set(uploadToken.value, uploadCode.value)
    passwordVerified.value = true
    passwordDialogOpen.value = false
    if (password) toast.success('密码验证成功')
  } catch (error: any) {
    passwordVerified.value = false
    toast.error(error?.message || '密码验证失败')
  } finally {
    passwordLoading.value = false
  }
}

const submitPassword = () => {
  if (!passwordValue.value.trim()) {
    toast.error('请输入访问密码')
    return
  }
  requestUploadToken(passwordValue.value.trim())
}

const handleUploadComplete = (type: 'colorChart' | 'detailChart', files: string[]) => {
  const merged = new Set([...uploadedFiles.value[type], ...files].filter(Boolean))
  uploadedFiles.value[type] = Array.from(merged)
}

const uploadFile = async (file: File, type: 'colorChart' | 'detailChart') => {
  const token = uploadToken.value || uploadTokenStore.get(uploadCode.value)
  if (!token || !productId.value) {
    throw new Error('上传凭证未就绪')
  }
  const result = await pcApi.uploadWebProductImage(productId.value, file, type, token)
  const rows = Array.isArray(result?.data) ? result.data : Array.isArray(result) ? result : []
  const item = rows?.[0] || {}
  return item.url || item.picture_url || item.imgurl || item.file_url || URL.createObjectURL(file)
}

const handleViewProduct = () => {
  if (productId.value) {
    const params = new URLSearchParams({ productId: productId.value })
    if (ownerId.value) params.set('uid', ownerId.value)
    window.location.href = `./share-home?${params.toString()}`
  } else {
    window.location.href = './share-home'
  }
}

const handleBackHome = () => {
  window.location.href = './share-home'
}

const setUploading = (type: 'colorChart' | 'detailChart', value: boolean) => {
  uploadingState.value[type] = value
}
</script>

<template>
  <div class="batch-upload-page">
    <div v-if="initialLoading || !isClient" class="batch-upload-loading">
      <SafeIcon name="Loader2" :size="28" class="animate-spin text-primary" />
      <span>正在打开上传页面...</span>
    </div>

    <template v-else>
    <!-- 标题区 -->
    <div class="batch-upload-hero">
      <div>
        <div class="inline-flex items-center gap-2 rounded-full bg-primary/10 px-4 py-1 text-sm font-semibold text-primary">
          <SafeIcon name="CloudUpload" :size="16" />
          产品专属上传
        </div>
        <h1 class="mt-5 text-3xl font-bold text-foreground md:text-4xl">
          {{ product?.name || folderInfo?.folder_name || '未命名产品' }} · 大批量上传
        </h1>
        <p class="mt-3 text-base text-muted-foreground">
          分别上传花色图和详情图，文件会自动加入当前产品。
        </p>
      </div>
      <div class="batch-upload-stats">
        <div class="batch-upload-stat">
          <strong>200</strong>
          <span>单次最多</span>
        </div>
        <div class="batch-upload-stat">
          <strong>{{ uploadLimit || '-' }}</strong>
          <span>单图限制</span>
        </div>
        <div class="batch-upload-stat">
          <strong>{{ maxUploadCountText }}</strong>
          <span>上传并发</span>
        </div>
      </div>
    </div>

    <div v-if="accessClosed" class="mx-auto mt-8 max-w-2xl surface-base card-padding text-center space-y-3">
      <SafeIcon name="Lock" :size="36" class="mx-auto text-muted-foreground" />
      <h3 class="text-section-title">无法访问协同编辑入口</h3>
      <p class="text-sm text-muted-foreground">{{ accessError || '此产品协同编辑入口已关闭' }}</p>
    </div>

    <!-- 上传区域 -->
    <div v-if="!accessClosed && passwordVerified" class="batch-upload-shell animate-in fade-in duration-300">
      <main class="batch-upload-main">
        <div class="batch-upload-grid">
          <!-- 花色图上传 -->
          <UploadZone
            title="上传花色图"
            description="产品主图、花型、颜色展示图上传到这里"
            action-label="上传花色图"
            waiting-label="等待选择花色图"
            type="colorChart"
            :disabled="uploadingState.colorChart"
            :max-concurrent="concurrencyLimit"
            :upload-handler="uploadFile"
            @upload-complete="(files) => handleUploadComplete('colorChart', files)"
            @uploading="(val) => setUploading('colorChart', val)"
          />

          <!-- 详情图上传 -->
          <UploadZone
            title="上传详情图"
            description="产品参数、细节、场景介绍图上传到这里"
            action-label="上传详情图"
            waiting-label="等待选择详情图"
            type="detailChart"
            :disabled="uploadingState.detailChart"
            :max-concurrent="concurrencyLimit"
            :upload-handler="uploadFile"
            @upload-complete="(files) => handleUploadComplete('detailChart', files)"
            @uploading="(val) => setUploading('detailChart', val)"
          />
        </div>

        <!-- 操作按钮 -->
        <div v-if="hasUploadedAny" class="flex gap-3 justify-center pt-4">
          <Button
            variant="default"
            size="lg"
            class="px-8"
            @click="handleViewProduct"
          >
            <SafeIcon name="Eye" :size="18" class="mr-2" />
            查看产品
          </Button>
        </div>
      </main>

      <aside class="batch-upload-sidebar">
        <div class="rounded-full border border-border bg-card px-4 py-2 text-sm font-semibold text-foreground">
          <SafeIcon name="ClipboardCheck" :size="16" class="mr-2 inline-block text-primary" />
          上传对象
        </div>

        <section class="batch-side-card">
          <div class="flex items-start gap-3">
            <img
              v-if="ownerAvatar"
              :src="ownerAvatar"
              :alt="ownerName"
              class="h-14 w-14 rounded-md border border-border bg-muted object-cover"
            />
            <div class="min-w-0">
              <p class="text-xs text-muted-foreground">分享方</p>
              <h3 class="truncate text-base font-semibold text-foreground">{{ ownerName || '分享者' }}</h3>
              <p class="mt-1 text-sm text-muted-foreground">产品：{{ product?.name || folderInfo?.folder_name || '未命名产品' }}</p>
            </div>
          </div>
        </section>

        <section class="batch-side-card">
          <p class="text-sm font-semibold text-muted-foreground">相册剩余资源</p>
          <strong class="mt-3 block text-3xl font-bold text-foreground">{{ remainingSize || '-' }}</strong>
          <div class="mt-4 h-2 rounded-full bg-muted">
            <div class="h-full rounded-full bg-primary" :style="{ width: `${capacityPercent}%` }" />
          </div>
          <div class="mt-3 flex justify-between text-xs text-muted-foreground">
            <span>已用 {{ capacityUsed || '0MB' }}</span>
            <span>总量 {{ capacityTotal || '-' }}</span>
          </div>
        </section>

        <section class="batch-side-card">
          <p class="text-sm font-semibold text-muted-foreground">本月剩余流量</p>
          <strong class="mt-3 block text-3xl font-bold text-foreground">{{ trafficRemaining || '不限量' }}</strong>
          <div class="mt-4 h-2 rounded-full bg-muted">
            <div class="h-full rounded-full bg-primary" :style="{ width: `${trafficPercent}%` }" />
          </div>
          <div class="mt-3 flex justify-between text-xs text-muted-foreground">
            <span>已用 {{ trafficUsed || '0MB' }}</span>
            <span>总量 {{ trafficTotal || '不限量' }}</span>
          </div>
        </section>
      </aside>
    </div>

    <Dialog v-model:open="passwordDialogOpen">
      <DialogContent class="sm:max-w-[420px]">
        <DialogHeader>
          <DialogTitle>请输入访问密码</DialogTitle>
        </DialogHeader>
        <div class="space-y-4">
          <Input
            v-model="passwordValue"
            type="password"
            maxlength="12"
            placeholder="请输入分享者提供的密码"
            @keyup.enter="submitPassword"
          />
          <Button class="w-full" :disabled="passwordLoading || !passwordValue.trim()" @click="submitPassword">
            <SafeIcon v-if="passwordLoading" name="Loader2" :size="16" class="mr-2 animate-spin" />
            确认进入
          </Button>
        </div>
      </DialogContent>
    </Dialog>

    <!-- 错误状态 -->
    <div v-if="!accessClosed && !passwordDialogOpen && !product" class="mx-auto max-w-md text-center space-y-4 py-12">
      <div class="w-16 h-16 bg-destructive/10 rounded-full flex items-center justify-center mx-auto">
        <SafeIcon name="AlertCircle" :size="32" class="text-destructive" />
      </div>
      <div>
        <h3 class="text-lg font-semibold text-foreground mb-1">产品不存在</h3>
        <p class="text-sm text-muted-foreground">请检查链接是否正确</p>
      </div>
      <Button
        variant="outline"
        @click="handleBackHome"
      >
        返回主页
      </Button>
    </div>
    </template>
  </div>
</template>

<style scoped>
.batch-upload-page {
  min-height: 100vh;
  background: linear-gradient(180deg, hsl(var(--background)) 0%, hsl(220 27% 98%) 100%);
  padding: 2rem clamp(1rem, 4vw, 5rem);
}

.batch-upload-loading {
  display: flex;
  min-height: 60vh;
  align-items: center;
  justify-content: center;
  gap: 0.75rem;
  color: hsl(var(--muted-foreground));
  font-weight: 600;
}

.batch-upload-hero {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 2rem;
  max-width: 1820px;
  margin: 0 auto;
  padding: 2.75rem 2rem;
  border: 1px solid hsl(var(--border));
  border-radius: var(--radius);
  background: hsl(var(--card));
  box-shadow: var(--shadow-soft);
}

.batch-upload-stats {
  display: grid;
  grid-template-columns: repeat(3, minmax(92px, 1fr));
  gap: 0.75rem;
}

.batch-upload-stat {
  min-width: 92px;
  border-radius: var(--radius);
  background: hsl(var(--muted) / 0.28);
  padding: 1rem;
  text-align: center;
}

.batch-upload-stat strong {
  display: block;
  color: hsl(var(--foreground));
  font-size: 1.35rem;
  line-height: 1.25;
}

.batch-upload-stat span {
  display: block;
  margin-top: 0.35rem;
  color: hsl(var(--muted-foreground));
  font-size: 0.8rem;
}

.batch-upload-shell {
  display: grid;
  grid-template-columns: minmax(0, 1fr) minmax(260px, 300px);
  gap: 1.75rem;
  max-width: 1820px;
  margin: 0 auto;
  border-left: 1px solid hsl(var(--border));
  border-right: 1px solid hsl(var(--border));
  background: hsl(var(--card));
}

.batch-upload-main {
  min-width: 0;
  padding: 2.75rem 2.5rem;
}

.batch-upload-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 2rem;
}

.batch-upload-sidebar {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
  background: hsl(var(--muted) / 0.25);
  border-left: 1px solid hsl(var(--border));
  padding: 2rem 1.5rem;
}

.batch-side-card {
  border: 1px solid hsl(var(--border));
  border-radius: var(--radius);
  background: hsl(var(--card));
  padding: 1.25rem;
}

@media (max-width: 1180px) {
  .batch-upload-shell {
    grid-template-columns: 1fr;
  }

  .batch-upload-sidebar {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    border-left: 0;
    border-top: 1px solid hsl(var(--border));
  }

  .batch-upload-sidebar > :first-child {
    grid-column: 1 / -1;
    justify-self: start;
  }
}

@media (max-width: 900px) {
  .batch-upload-page {
    padding: 1rem;
  }

  .batch-upload-hero,
  .batch-upload-shell {
    border-radius: var(--radius);
  }

  .batch-upload-hero {
    flex-direction: column;
    align-items: stretch;
    padding: 1.5rem;
  }

  .batch-upload-stats,
  .batch-upload-grid,
  .batch-upload-sidebar {
    grid-template-columns: 1fr;
  }

  .batch-upload-main {
    padding: 1.25rem;
  }
}
</style>
