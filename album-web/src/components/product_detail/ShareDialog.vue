
<script setup lang="ts">
import { ref, onMounted, watch } from 'vue'
import { toast } from 'vue-sonner'
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogDescription,
  DialogFooter
} from '@/components/ui/dialog'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import SafeIcon from '@/components/common/SafeIcon.vue'
import { pcApi } from '@/lib/api'

interface Props {
  open: boolean
  productId: string
  targetUserId?: string
  shareCode?: string
}

const props = defineProps<Props>()

const emit = defineEmits<{
  (e: 'update:open', value: boolean): void
}>()

const shareUrl = ref('')
const miniCodeUrl = ref('')
const miniPath = ref('')
const isLoadingShare = ref(false)

const buildShareUrl = () => {
  const params = new URLSearchParams({ productId: props.productId })
  if (props.shareCode) params.set('code', props.shareCode)
  else if (props.targetUserId) params.set('uid', props.targetUserId)
  const url = new URL('./product-detail.html', window.location.href)
  url.search = params.toString()
  return url.toString()
}

const loadShareData = async () => {
  shareUrl.value = buildShareUrl()
  miniCodeUrl.value = ''
  miniPath.value = ''
  if ((!props.targetUserId && !props.shareCode) || !props.productId) return
  isLoadingShare.value = true
  try {
    const codeData = await pcApi.getHomeMiniCode({ targetUserId: props.targetUserId || '', shareCode: props.shareCode || '' }, 'product', props.productId).catch(() => null)
    miniCodeUrl.value = codeData?.qrcode || codeData?.qrcode_url || ''
    miniPath.value = codeData?.mini_path || ''
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
  toast.success('分享链接已复制')
}

const handleShareWeChat = () => {
  toast.info('请使用微信扫描二维码分享')
}

const handleShareQQ = () => {
  toast.info('请使用QQ分享此链接')
}
</script>

<template>
  <Dialog :open="open" @update:open="emit('update:open', $event)">
    <DialogContent class="max-w-[500px]">
      <DialogHeader>
        <DialogTitle>分享产品</DialogTitle>
        <DialogDescription>
          选择分享方式，让更多人了解这款产品
        </DialogDescription>
      </DialogHeader>

      <div class="space-y-4 py-4">
        <!-- Share Link Section -->
        <div class="space-y-2">
          <label class="text-sm font-medium">分享链接</label>
          <div class="flex gap-2">
            <Input
              :model-value="shareUrl"
              readonly
              class="flex-1 bg-muted/50 text-sm"
            />
            <Button
              variant="outline"
              size="sm"
              class="px-3"
              @click="handleCopyLink"
            >
              <SafeIcon name="Copy" :size="16" />
            </Button>
          </div>
        </div>

        <!-- Share Methods -->
        <div class="space-y-2">
          <label class="text-sm font-medium">分享方式</label>
          <div class="grid grid-cols-3 gap-2">
            <Button
              variant="outline"
              class="flex flex-col items-center gap-2 h-auto py-4"
              @click="handleShareWeChat"
            >
              <SafeIcon name="MessageCircle" :size="24" class="text-green-600" />
              <span class="text-xs">微信</span>
            </Button>
            <Button
              variant="outline"
              class="flex flex-col items-center gap-2 h-auto py-4"
              @click="handleShareQQ"
            >
              <SafeIcon name="Send" :size="24" class="text-blue-600" />
              <span class="text-xs">QQ</span>
            </Button>
            <Button
              variant="outline"
              class="flex flex-col items-center gap-2 h-auto py-4"
              @click="handleCopyLink"
            >
              <SafeIcon name="Link" :size="24" class="text-primary" />
              <span class="text-xs">复制链接</span>
            </Button>
          </div>
        </div>

        <!-- QR Code Section -->
        <div class="space-y-2 pt-2 border-t">
          <label class="text-sm font-medium">二维码分享</label>
          <div class="flex justify-center p-4 bg-muted/30 rounded-lg">
            <img
              v-if="miniCodeUrl"
              :src="miniCodeUrl"
              alt="产品小程序码"
              class="w-40 h-40 border border-border rounded object-contain bg-white"
            />
            <SafeIcon v-else-if="isLoadingShare" name="Loader2" :size="28" class="animate-spin text-muted-foreground" />
            <SafeIcon v-else name="QrCode" :size="36" class="text-muted-foreground" />
          </div>
        </div>
      </div>

      <DialogFooter>
        <Button variant="outline" @click="emit('update:open', false)">
          关闭
        </Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>
