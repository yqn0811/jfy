
<script setup lang="ts">
import { ref } from 'vue'
import { Button } from '@/components/ui/button'
import { Card, CardContent } from '@/components/ui/card'
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/components/ui/dialog'
import { Input } from '@/components/ui/input'
import SafeIcon from '@/components/common/SafeIcon.vue'
import UserAvatar from '@/components/common/UserAvatar.vue'
import StatusBadge from '@/components/common/StatusBadge.vue'
import LoginDialog from '@/components/common/LoginDialog.vue'
import ConfirmDialog from '@/components/common/ConfirmDialog.vue'
import { toast } from 'vue-sonner'
import { authStore, pcApi } from '@/lib/api'
import type { CategoryVO } from '@/data/CategoryService'
import type { HomeProfileData } from '@/data/HomeProfileData'

interface Props {
  category: CategoryVO
  homeProfile: HomeProfileData | null
  targetUserId?: string
  shareCode?: string
}

const props = defineProps<Props>()

const isAuthenticated = ref(authStore.isLoggedIn())
const isFavorited = ref(Number((props.category as any).is_collect || (props.category as any).isCollect || 0) === 1)
const showLoginDialog = ref(false)
const showContactDialog = ref(false)
const showShareDialog = ref(false)
const shareUrl = ref('')
const miniCodeUrl = ref('')
const miniPath = ref('')
const isLoadingShare = ref(false)

const handleFavorite = async () => {
  if (!isAuthenticated.value) {
    showLoginDialog.value = true
    return
  }

  try {
    await pcApi.toggleFavorite('category', props.category.id, !isFavorited.value)
    isFavorited.value = !isFavorited.value
    toast.success(isFavorited.value ? '收藏成功' : '已取消收藏')
  } catch (error: any) {
    toast.error(error?.message || '操作失败')
  }
}

const buildShareUrl = () => {
  const params = new URLSearchParams({ categoryId: props.category.id })
  if (props.shareCode) params.set('code', props.shareCode)
  else if (props.targetUserId) params.set('uid', props.targetUserId)
  const basePath = window.location.pathname.replace(/[^/]*$/, '')
  return `${window.location.origin}${basePath}category.html?${params.toString()}`
}

const handleShare = async () => {
  shareUrl.value = buildShareUrl()
  showShareDialog.value = true
  miniCodeUrl.value = ''
  miniPath.value = ''
  if (!props.targetUserId && !props.shareCode) return
  isLoadingShare.value = true
  try {
    const data = await pcApi.getHomeMiniCode({ targetUserId: props.targetUserId || '', shareCode: props.shareCode || '' }, 'category', props.category.id).catch(() => null)
    miniCodeUrl.value = data?.qrcode || data?.qrcode_url || ''
    miniPath.value = data?.mini_path || ''
  } finally {
    isLoadingShare.value = false
  }
}

const copyShareUrl = async () => {
  await navigator.clipboard.writeText(shareUrl.value || buildShareUrl())
  toast.success('分类链接已复制')
}

const handleContact = () => {
  if (!isAuthenticated.value) {
    showLoginDialog.value = true
    return
  }
  showContactDialog.value = true
}

const handleLoginSuccess = () => {
  isAuthenticated.value = true
  toast.success('登录成功')
}
</script>

<template>
  <Card class="surface-raised overflow-hidden">
    <CardContent class="p-0">
      <div class="flex gap-6 p-6">
        <div class="shrink-0">
          <img 
            :src="category.coverUrl" 
            :alt="category.name"
            class="w-32 h-32 rounded-lg object-cover border border-border"
          />
        </div>

        <div class="flex-1 min-w-0 flex flex-col justify-between">
          <div class="space-y-3">
            <div class="flex items-start justify-between gap-4">
              <div class="min-w-0">
                <h1 class="text-page-title truncate">{{ category.name }}</h1>
                <p class="text-caption mt-1">
                  {{ category.productCount }} 个产品
                </p>
              </div>
              <StatusBadge :status="category.visibility" />
            </div>

            <p class="text-body text-muted-foreground line-clamp-2">
              {{ category.intro || '暂无分类简介' }}
            </p>
          </div>

          <div class="flex items-center gap-3 mt-4 pt-4 border-t border-border">
            <Button 
              variant="outline" 
              size="sm"
              :class="isFavorited && 'bg-primary/10 text-primary border-primary'"
              @click="handleFavorite"
            >
              <SafeIcon 
                :name="isFavorited ? 'Heart' : 'Heart'" 
                :size="16" 
                :class="isFavorited ? 'fill-current' : ''"
                class="mr-2"
              />
              {{ isFavorited ? '已收藏' : '收藏分类' }}
            </Button>

            <Button 
              variant="outline" 
              size="sm"
              @click="handleShare"
            >
              <SafeIcon name="Share2" :size="16" class="mr-2" />
              分享分类
            </Button>

            <Button 
              variant="outline" 
              size="sm"
              @click="handleContact"
            >
              <SafeIcon name="MessageCircle" :size="16" class="mr-2" />
              {{ homeProfile?.contactServiceName || '联系商户' }}
            </Button>
          </div>
        </div>
      </div>
    </CardContent>
  </Card>

  <LoginDialog 
    :open="showLoginDialog"
    @update:open="showLoginDialog = $event"
    @login-success="handleLoginSuccess"
  />

  <Dialog :open="showShareDialog" @update:open="showShareDialog = $event">
    <DialogContent class="max-w-[480px]">
      <DialogHeader>
        <DialogTitle>分享分类</DialogTitle>
      </DialogHeader>
      <div class="space-y-4 py-2">
        <div class="space-y-2">
          <label class="text-sm font-medium">分享链接</label>
          <div class="flex gap-2">
            <Input :value="shareUrl" readonly class="flex-1 bg-muted/50 text-xs" />
            <Button variant="outline" size="sm" @click="copyShareUrl">
              <SafeIcon name="Copy" :size="16" />
            </Button>
          </div>
        </div>
        <div class="space-y-2">
          <label class="text-sm font-medium">小程序码</label>
          <div class="flex justify-center p-4 bg-muted/30 rounded-lg">
            <img
              v-if="miniCodeUrl"
              :src="miniCodeUrl"
              alt="分类小程序码"
              class="w-40 h-40 border border-border rounded object-contain bg-white"
            />
            <SafeIcon v-else-if="isLoadingShare" name="Loader2" :size="28" class="animate-spin text-muted-foreground" />
            <SafeIcon v-else name="QrCode" :size="36" class="text-muted-foreground" />
          </div>
          <p class="text-xs text-muted-foreground text-center break-all">
            {{ miniPath || '网页链接可直接复制分享' }}
          </p>
        </div>
      </div>
      <DialogFooter>
        <Button variant="outline" @click="showShareDialog = false">关闭</Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>
