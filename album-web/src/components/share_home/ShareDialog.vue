
<script setup lang="ts">
import { ref, onMounted, watch } from 'vue'
import { toast } from 'vue-sonner'
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogDescription,
  DialogFooter,
} from '@/components/ui/dialog'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import SafeIcon from '@/components/common/SafeIcon.vue'
import type { HomeProfileData } from '@/data/HomeProfileData'
import { pcApi } from '@/lib/api'

interface Props {
  open: boolean
  homeProfile: HomeProfileData
}

const props = defineProps<Props>()
const emit = defineEmits<{
  (e: 'update:open', value: boolean): void
}>()

const shareUrl = ref('')
const miniCodeUrl = ref('')
const miniPath = ref('')
const isLoadingShare = ref(false)
const shareTitle = ref(props.homeProfile.shareTitle || `${props.homeProfile.companyName}的产品主页`)
const shareDescription = ref(props.homeProfile.shareDescription || props.homeProfile.intro)

const buildPcShareUrl = () => {
  const params = new URLSearchParams({ uid: props.homeProfile.ownerUserId || props.homeProfile.id })
  const url = new URL('./share-home.html', window.location.href)
  url.search = params.toString()
  return url.toString()
}

const pickShareLink = (data: any) => {
  return data?.pc_link || data?.web_link || data?.share_link || data?.link || data?.url_link || ''
}

const loadShareData = async () => {
  shareUrl.value = buildPcShareUrl()
  miniCodeUrl.value = ''
  miniPath.value = ''
  if (!props.homeProfile?.id) return
  isLoadingShare.value = true
  try {
    const [linkData, codeData] = await Promise.all([
      pcApi.getHomeShareLink(props.homeProfile.id).catch(() => null),
      pcApi.getHomeMiniCode(props.homeProfile.id, 'home').catch(() => null),
    ])
    shareUrl.value = pickShareLink(linkData) || shareUrl.value
    miniCodeUrl.value = codeData?.qrcode || codeData?.qrcode_url || ''
    miniPath.value = codeData?.mini_path || linkData?.mini_path || ''
  } finally {
    isLoadingShare.value = false
  }
}

onMounted(loadShareData)
watch(() => props.open, open => {
  if (open) loadShareData()
})

const handleCopyLink = () => {
  navigator.clipboard.writeText(shareUrl.value)
  toast.success('链接已复制')
}

const handleCopyWithTitle = () => {
  const text = `${shareTitle.value}\n${shareUrl.value}`
  navigator.clipboard.writeText(text)
  toast.success('已复制')
}

const handleShare = (platform: string) => {
  const encodedUrl = encodeURIComponent(shareUrl.value)
  const encodedTitle = encodeURIComponent(shareTitle.value)
  let shareLink = ''

  switch (platform) {
    case 'wechat':
      toast.success('请使用微信扫一扫分享')
      break
    case 'qq':
      shareLink = `https://connect.qq.com/widget/shareqq/index.html?url=${encodedUrl}&title=${encodedTitle}`
      window.open(shareLink, '_blank')
      break
    case 'weibo':
      shareLink = `https://service.weibo.com/share/share.php?url=${encodedUrl}&title=${encodedTitle}`
      window.open(shareLink, '_blank')
      break
  }
}
</script>

<template>
  <Dialog :open="open" @update:open="(val) => emit('update:open', val)">
    <DialogContent class="sm:max-w-[500px]">
      <DialogHeader>
        <DialogTitle>分享主页</DialogTitle>
        <DialogDescription>
          分享给朋友，让他们了解你的产品
        </DialogDescription>
      </DialogHeader>

      <div class="space-y-6 py-4">
        <!-- 分享链接 -->
        <div class="space-y-2">
          <label class="text-sm font-medium">分享链接</label>
          <div class="flex gap-2">
            <Input
              :model-value="shareUrl"
              readonly
              class="flex-1 bg-muted/50 border-none text-xs"
            />
            <Button
              size="sm"
              variant="outline"
              @click="handleCopyLink"
              class="shrink-0"
            >
              <SafeIcon name="Copy" :size="16" class="mr-1" />
              复制
            </Button>
          </div>
        </div>

        <div class="space-y-2">
          <label class="text-sm font-medium">小程序码</label>
          <div class="flex items-center gap-4 p-4 bg-muted/30 rounded-lg">
            <div class="w-32 h-32 bg-white rounded border border-border flex items-center justify-center overflow-hidden">
              <SafeIcon v-if="isLoadingShare" name="Loader2" :size="24" class="animate-spin text-muted-foreground" />
              <img
                v-else-if="miniCodeUrl"
                :src="miniCodeUrl"
                alt="主页小程序码"
                class="w-full h-full object-contain"
              />
              <SafeIcon v-else name="QrCode" :size="32" class="text-muted-foreground" />
            </div>
            <div class="flex-1 min-w-0 space-y-2">
              <p class="text-sm font-medium">微信扫一扫打开小程序</p>
              <p class="text-xs text-muted-foreground break-all">
                {{ miniPath || '小程序码生成中，网页链接可直接复制分享' }}
              </p>
            </div>
          </div>
        </div>

        <!-- 分享文案 -->
        <div class="space-y-2">
          <label class="text-sm font-medium">分享文案</label>
          <textarea
            v-model="shareDescription"
            class="w-full h-20 p-3 border border-border rounded-lg text-sm bg-background resize-none focus:outline-none focus:ring-1 focus:ring-primary"
            placeholder="输入分享描述..."
          />
          <Button
            size="sm"
            variant="outline"
            @click="handleCopyWithTitle"
            class="w-full"
          >
            <SafeIcon name="Copy" :size="16" class="mr-2" />
            复制文案和链接
          </Button>
        </div>

        <!-- 社交分享 -->
        <div class="space-y-2">
          <label class="text-sm font-medium">分享到社交平台</label>
          <div class="flex gap-2">
            <Button
              size="sm"
              variant="outline"
              @click="handleShare('wechat')"
              class="flex-1"
            >
              <SafeIcon name="MessageCircle" :size="16" class="mr-2" />
              微信
            </Button>
            <Button
              size="sm"
              variant="outline"
              @click="handleShare('qq')"
              class="flex-1"
            >
              <SafeIcon name="Share2" :size="16" class="mr-2" />
              QQ
            </Button>
            <Button
              size="sm"
              variant="outline"
              @click="handleShare('weibo')"
              class="flex-1"
            >
              <SafeIcon name="Share2" :size="16" class="mr-2" />
              微博
            </Button>
          </div>
        </div>
      </div>

      <DialogFooter>
        <Button
          variant="outline"
          @click="() => emit('update:open', false)"
        >
          关闭
        </Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>
