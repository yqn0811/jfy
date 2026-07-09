
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
import UploadProgress from '@/components/batch_upload/UploadProgress.vue'

const isClient = ref(true)
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
const concurrencyLimit = ref(1)

const passwordVerified = ref(false)
const passwordDialogOpen = ref(false)
const passwordValue = ref('')
const passwordLoading = ref(false)
const accessClosed = ref(false)
const accessError = ref('')
const uploadProgress = ref<{ colorChart: number; detailChart: number }>({
  colorChart: 0,
  detailChart: 0
})
const uploadedFiles = ref<{ colorChart: string[]; detailChart: string[] }>({
  colorChart: [],
  detailChart: []
})
const uploadingState = ref<{ colorChart: boolean; detailChart: boolean }>({
  colorChart: false,
  detailChart: false,
})

const totalProgress = computed(() => {
  const total = uploadProgress.value.colorChart + uploadProgress.value.detailChart
  return Math.round(total / 2)
})

const isUploadComplete = computed(() => {
  return uploadProgress.value.colorChart === 100 && uploadProgress.value.detailChart === 100
})

const initUpload = async () => {
  try {
    const info = await pcApi.getWebUploadInfo(uploadCode.value)
    folderInfo.value = info
    accessClosed.value = Number(info?.upload_enabled ?? 1) !== 1
    productId.value = String(info?.id || '')
    ownerId.value = String(info?.owner_id || info?.uid || '')
    ownerName.value = info?.owner_name || info?.company_name || info?.nickname || ''
    ownerAvatar.value = info?.owner_avatar || info?.company_logo || info?.avatar || ''
    remainingSize.value = info?.remaining_size !== undefined ? `${info.remaining_size} MB` : ''
    uploadLimit.value = info?.upload_limit !== undefined ? `${info.upload_limit} MB/张` : ''
    concurrencyLimit.value = Math.max(1, Math.min(Number(info?.concurrency_limit || 1), 8))
    product.value = mapProduct({
      id: info?.id,
      uid: info?.owner_id || info?.uid,
      folder_name: info?.folder_name,
      folder_desc: info?.folder_desc,
      new_thumb: info?.new_thumb,
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
  }
}

onMounted(() => {
  isClient.value = false
  requestAnimationFrame(() => {
    const params = new URLSearchParams(window.location.search)
    uploadCode.value = params.get('uploadd_code') || params.get('code') || ''
    if (!uploadCode.value) {
      accessClosed.value = true
      accessError.value = '缺少上传码，请检查链接是否完整'
    } else {
      initUpload()
    }
    isClient.value = true
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
  uploadedFiles.value[type] = files
  uploadProgress.value[type] = 100
  toast.success(`${type === 'colorChart' ? '花色图' : '详情图'}上传成功`)

  if (isUploadComplete.value) toast.success('所有图片上传完成')
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
  <div class="w-full space-y-6">
    <!-- 标题区 -->
    <div class="text-center space-y-2">
      <h1 class="text-3xl font-bold text-foreground">协同编辑图片</h1>
      <p class="text-muted-foreground">
        {{ product?.name || folderInfo?.folder_name || '未命名产品' }}
      </p>
    </div>

    <div v-if="accessClosed" class="surface-base card-padding text-center space-y-3">
      <SafeIcon name="Lock" :size="36" class="mx-auto text-muted-foreground" />
      <h3 class="text-section-title">无法访问协同编辑入口</h3>
      <p class="text-sm text-muted-foreground">{{ accessError || '此产品协同编辑入口已关闭' }}</p>
    </div>

    <!-- 上传区域 -->
    <div v-if="!accessClosed && passwordVerified" class="space-y-6 animate-in fade-in duration-300">
      <div class="surface-base card-padding flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
        <div class="flex items-center gap-3 min-w-0">
          <img
            v-if="ownerAvatar"
            :src="ownerAvatar"
            :alt="ownerName"
            class="w-12 h-12 rounded-lg object-cover border border-border bg-muted"
          />
          <div class="min-w-0">
            <p class="text-sm font-medium truncate">{{ ownerName || '分享者' }}</p>
            <p class="text-xs text-muted-foreground truncate">产品：{{ product?.name || folderInfo?.folder_name || '未命名产品' }}</p>
          </div>
        </div>
        <div class="grid grid-cols-2 gap-3 text-sm md:min-w-64">
          <div class="rounded-md border border-border bg-muted/30 px-3 py-2">
            <p class="text-[11px] text-muted-foreground">剩余容量</p>
            <p class="font-medium">{{ remainingSize || '-' }}</p>
          </div>
          <div class="rounded-md border border-border bg-muted/30 px-3 py-2">
            <p class="text-[11px] text-muted-foreground">单图限制</p>
            <p class="font-medium">{{ uploadLimit || '-' }}</p>
          </div>
        </div>
      </div>

      <!-- 花色图上传 -->
      <UploadZone
        title="花色图"
        description="拖拽或点击选择花色图片"
        type="colorChart"
        :progress="uploadProgress.colorChart"
        :disabled="uploadingState.colorChart"
        :max-concurrent="concurrencyLimit"
        :upload-handler="uploadFile"
        @upload-complete="(files) => handleUploadComplete('colorChart', files)"
        @uploading="(val) => setUploading('colorChart', val)"
      />

      <!-- 详情图上传 -->
      <UploadZone
        title="详情图"
        description="拖拽或点击选择详情图片"
        type="detailChart"
        :progress="uploadProgress.detailChart"
        :disabled="uploadingState.detailChart"
        :max-concurrent="concurrencyLimit"
        :upload-handler="uploadFile"
        @upload-complete="(files) => handleUploadComplete('detailChart', files)"
        @uploading="(val) => setUploading('detailChart', val)"
      />

      <!-- 总体进度 -->
      <UploadProgress :progress="totalProgress" :is-complete="isUploadComplete" />

      <!-- 操作按钮 -->
      <div v-if="isUploadComplete" class="flex gap-3 justify-center pt-4">
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
    <div v-if="!accessClosed && !product" class="text-center space-y-4 py-8">
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
  </div>
</template>
