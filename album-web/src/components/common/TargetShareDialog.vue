<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { toast } from 'vue-sonner'
import {
  Dialog,
  DialogFooter,
  DialogHeader,
  DialogScrollContent,
  DialogTitle,
  DialogDescription,
} from '@/components/ui/dialog'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import SafeIcon from '@/components/common/SafeIcon.vue'
import { pcApi } from '@/lib/api'

type ShareTargetType = 'category' | 'product' | 'selection'

interface Props {
  open: boolean
  type: ShareTargetType
  title?: string
  description?: string
  targetId: string
  targetUserId?: string
  shareCode?: string
  productId?: string
  webPath?: string
  webParamName?: string
}

const props = withDefaults(defineProps<Props>(), {
  title: '',
  description: '',
  targetUserId: '',
  shareCode: '',
  productId: '',
  webPath: './share-home',
  webParamName: '',
})

const emit = defineEmits<{
  (e: 'update:open', value: boolean): void
}>()

const shareUrl = ref('')
const miniCodeUrl = ref('')
const miniPath = ref('')
const isLoadingShare = ref(false)

const typeLabel = computed(() => {
  if (props.type === 'category') return '分类'
  if (props.type === 'selection') return '选款单'
  return '产品'
})

const dialogTitle = computed(() => props.title || `分享${typeLabel.value}`)
const dialogDescription = computed(() => props.description || `选择分享方式，让更多人查看这个${typeLabel.value}`)
const targetRef = computed(() => ({
  targetUserId: props.targetUserId || '',
  shareCode: props.shareCode || '',
}))

const getWebParamName = () => {
  if (props.webParamName) return props.webParamName
  if (props.type === 'category') return 'categoryId'
  if (props.type === 'selection') return 'selectionId'
  return 'productId'
}

const buildShareUrl = () => {
  const url = new URL(props.webPath || './share-home', window.location.href)
  if (props.targetId) url.searchParams.set(getWebParamName(), props.targetId)
  if (props.productId) url.searchParams.set('productId', props.productId)
  if (props.shareCode) url.searchParams.set('code', props.shareCode)
  else if (props.targetUserId && props.type !== 'selection') url.searchParams.set('uid', props.targetUserId)
  return url.toString()
}

const loadShareData = async () => {
  shareUrl.value = buildShareUrl()
  miniCodeUrl.value = ''
  miniPath.value = ''
  if ((!props.targetUserId && !props.shareCode) || !props.targetId) return
  isLoadingShare.value = true
  try {
    const codeData = await pcApi.getHomeMiniCode(
      targetRef.value,
      props.type,
      props.targetId,
    ).catch(() => null)
    miniCodeUrl.value = codeData?.qrcode || codeData?.qrcode_url || ''
    miniPath.value = codeData?.mini_path || ''
  } finally {
    isLoadingShare.value = false
  }
}

watch(
  () => [
    props.open,
    props.type,
    props.targetId,
    props.targetUserId,
    props.shareCode,
    props.productId,
    props.webPath,
  ],
  () => {
    if (props.open) loadShareData()
  },
  { immediate: true }
)

const copyText = async (text: string) => {
  if (!text) return false
  if (navigator.clipboard?.writeText) {
    await navigator.clipboard.writeText(text)
    return true
  }
  const textarea = document.createElement('textarea')
  textarea.value = text
  textarea.setAttribute('readonly', 'true')
  textarea.style.position = 'fixed'
  textarea.style.left = '-9999px'
  document.body.appendChild(textarea)
  textarea.select()
  const ok = document.execCommand('copy')
  document.body.removeChild(textarea)
  return ok
}

const handleCopyLink = async () => {
  const ok = await copyText(shareUrl.value)
  if (ok) toast.success(`${typeLabel.value}链接已复制`)
}

const handleCopyWithTitle = async () => {
  const rows = [dialogTitle.value, shareUrl.value].filter(Boolean)
  const ok = await copyText(rows.join('\n'))
  if (ok) toast.success('已复制')
}

const handleShare = (platform: string) => {
  const encodedUrl = encodeURIComponent(shareUrl.value)
  const encodedTitle = encodeURIComponent(dialogTitle.value)
  if (platform === 'wechat') {
    toast.success('可复制链接或使用小程序码分享')
    return
  }
  if (platform === 'qq') {
    window.open(`https://connect.qq.com/widget/shareqq/index.html?url=${encodedUrl}&title=${encodedTitle}`, '_blank')
    return
  }
  if (platform === 'weibo') {
    window.open(`https://service.weibo.com/share/share.php?url=${encodedUrl}&title=${encodedTitle}`, '_blank')
  }
}
</script>

<template>
  <Dialog :open="open" @update:open="emit('update:open', $event)">
    <DialogScrollContent class="max-h-[min(88vh,760px)] max-w-[560px] overflow-hidden p-0">
      <div class="flex max-h-[min(88vh,760px)] flex-col">
        <DialogHeader class="border-b border-border px-6 py-5">
          <DialogTitle>{{ dialogTitle }}</DialogTitle>
          <DialogDescription>{{ dialogDescription }}</DialogDescription>
        </DialogHeader>

        <div class="min-h-0 flex-1 space-y-5 overflow-y-auto px-6 py-5">
          <div class="space-y-2">
            <div class="flex items-center justify-between gap-3">
              <label class="text-sm font-medium">分享链接</label>
              <span class="text-xs text-muted-foreground">请在PC电脑端打开，手机端只能支持小程序</span>
            </div>
            <div class="flex gap-2">
              <Input
                :model-value="shareUrl"
                readonly
                class="min-w-0 flex-1 bg-muted/50 text-xs"
              />
              <Button
                variant="outline"
                size="sm"
                class="shrink-0 gap-2"
                @click="handleCopyLink"
                :disabled="!shareUrl"
              >
                <SafeIcon name="Copy" :size="16" />
                复制
              </Button>
            </div>
          </div>

          <div class="space-y-2">
            <label class="text-sm font-medium">小程序码</label>
            <div class="flex items-center gap-4 rounded-lg bg-muted/30 p-4">
              <div class="flex h-32 w-32 shrink-0 items-center justify-center overflow-hidden rounded border border-border bg-white">
                <SafeIcon v-if="isLoadingShare" name="Loader2" :size="24" class="animate-spin text-muted-foreground" />
                <img
                  v-else-if="miniCodeUrl"
                  :src="miniCodeUrl"
                  :alt="`${typeLabel}小程序码`"
                  class="h-full w-full object-contain"
                />
                <SafeIcon v-else name="QrCode" :size="32" class="text-muted-foreground" />
              </div>
              <div class="min-w-0 flex-1 space-y-2">
                <p class="text-sm font-medium">微信扫一扫打开小程序</p>
                <p class="break-all text-xs text-muted-foreground">
                  {{ miniPath || '小程序码生成中，网页链接可直接复制分享' }}
                </p>
              </div>
            </div>
          </div>

          <div class="space-y-2">
            <label class="text-sm font-medium">分享文案</label>
            <Button
              variant="outline"
              class="w-full gap-2"
              @click="handleCopyWithTitle"
              :disabled="!shareUrl"
            >
              <SafeIcon name="Copy" :size="16" />
              复制文案和链接
            </Button>
          </div>

          <div class="space-y-2">
            <label class="text-sm font-medium">分享到社交平台</label>
            <div class="grid grid-cols-3 gap-2">
              <Button variant="outline" class="h-12 gap-2" @click="handleShare('wechat')">
                <SafeIcon name="MessageCircle" :size="18" />
                微信
              </Button>
              <Button variant="outline" class="h-12 gap-2" @click="handleShare('qq')">
                <SafeIcon name="Share2" :size="18" />
                QQ
              </Button>
              <Button variant="outline" class="h-12 gap-2" @click="handleShare('weibo')">
                <SafeIcon name="Share2" :size="18" />
                微博
              </Button>
            </div>
          </div>
        </div>

        <DialogFooter class="border-t border-border px-6 py-4">
          <Button variant="outline" @click="emit('update:open', false)">
            关闭
          </Button>
        </DialogFooter>
      </div>
    </DialogScrollContent>
  </Dialog>
</template>
