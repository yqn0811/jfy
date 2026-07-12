<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { Button } from '@/components/ui/button'
import SafeIcon from '@/components/common/SafeIcon.vue'
import { pcApi } from '@/lib/api'

type MiniProgramTargetType = 'home' | 'category' | 'product' | 'selection'

const props = withDefaults(defineProps<{
  type?: MiniProgramTargetType
  targetId?: string
  targetUserId?: string
  shareCode?: string
}>(), {
  type: 'home',
  targetId: '',
  targetUserId: '',
  shareCode: '',
})

const miniCodeUrl = ref('')
const isLoading = ref(false)
const errorMessage = ref('')
const imageLoadFailed = ref(false)

const targetLabel = computed(() => {
  if (props.type === 'category') return '分类'
  if (props.type === 'product') return '产品'
  if (props.type === 'selection') return '选款单'
  return '主页'
})

const canLoadMiniCode = computed(() => !!props.shareCode || !!props.targetUserId)

const loadMiniCode = async () => {
  const directImageUrl = pcApi.getHomeMiniCodeImageUrl(
    { targetUserId: props.targetUserId, shareCode: props.shareCode },
    props.type,
    props.targetId,
    '',
    { t: Date.now() }
  )
  miniCodeUrl.value = directImageUrl
  errorMessage.value = ''
  imageLoadFailed.value = false

  if (!canLoadMiniCode.value) {
    miniCodeUrl.value = ''
    errorMessage.value = '当前链接缺少分享信息，请联系分享者重新发送。'
    return
  }

  isLoading.value = true
  try {
    const data = await pcApi.getHomeMiniCode(
      { targetUserId: props.targetUserId, shareCode: props.shareCode },
      props.type,
      props.targetId,
    )
    miniCodeUrl.value = data?.qrcode || data?.qrcode_url || directImageUrl
    if (!miniCodeUrl.value) {
      errorMessage.value = '小程序码生成中，请稍后重试。'
    }
  } catch (error: any) {
    const message = error?.message || ''
    errorMessage.value = message && message !== 'Load failed' && message !== 'Failed to fetch'
      ? message
      : '小程序码加载失败，请稍后重试。'
  } finally {
    isLoading.value = false
  }
}

const handleImageError = () => {
  imageLoadFailed.value = true
  if (!errorMessage.value) {
    errorMessage.value = '小程序码图片加载失败，请点击重新加载。'
  }
}

watch(
  () => [props.type, props.targetId, props.targetUserId, props.shareCode],
  loadMiniCode,
  { immediate: true }
)
</script>

<template>
  <main class="min-h-screen bg-background px-5 py-8 text-foreground">
    <div class="mx-auto flex min-h-[calc(100vh-64px)] w-full max-w-sm flex-col justify-center">
      <section class="space-y-6 text-center">
        <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-xl bg-primary/10 text-primary">
          <SafeIcon name="Smartphone" :size="28" />
        </div>

        <div class="space-y-3">
          <h1 class="text-2xl font-semibold tracking-normal">请在PC电脑端打开</h1>
          <p class="text-sm leading-6 text-muted-foreground">
            手机端只能支持小程序，请长按识别下方小程序码进入{{ targetLabel }}。
          </p>
        </div>

        <div class="rounded-lg border border-border bg-card p-5 shadow-sm">
          <div class="mx-auto flex h-56 w-56 items-center justify-center overflow-hidden rounded-md border border-border bg-white">
            <img
              v-if="miniCodeUrl && !imageLoadFailed"
              :src="miniCodeUrl"
              :alt="`${targetLabel}小程序码`"
              class="h-full w-full object-contain"
              @error="handleImageError"
            />
            <SafeIcon
              v-else-if="isLoading"
              name="Loader2"
              :size="32"
              class="animate-spin text-muted-foreground"
            />
            <SafeIcon v-else name="QrCode" :size="44" class="text-muted-foreground" />
          </div>

          <div class="mt-4 space-y-2">
            <p class="text-sm font-medium">长按识别小程序码</p>
            <p v-if="errorMessage" class="text-xs leading-5 text-destructive">{{ errorMessage }}</p>
            <p v-else class="text-xs leading-5 text-muted-foreground">无法识别时，请让分享者重新发送手机版链接。</p>
          </div>

          <Button
            v-if="errorMessage"
            variant="outline"
            size="sm"
            class="mt-4 w-full gap-2"
            :disabled="isLoading"
            @click="loadMiniCode"
          >
            <SafeIcon name="RefreshCw" :size="14" />
            重新加载
          </Button>
        </div>
      </section>
    </div>
  </main>
</template>
