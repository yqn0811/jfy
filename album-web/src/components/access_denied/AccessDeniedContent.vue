
<script setup lang="ts">
import { Button } from '@/components/ui/button'
import SafeIcon from '@/components/common/SafeIcon.vue'

const reasonMap: Record<string, { title: string; description: string }> = {
  home_closed: {
    title: '暂时无法访问',
    description: '该主页已关闭公开访问，请联系分享者确认。',
  },
  category_private: {
    title: '暂时无法访问',
    description: '该分类不存在或暂未公开，请联系分享者确认。',
  },
  product_private: {
    title: '暂时无法访问',
    description: '该产品不存在或暂未公开，请联系分享者确认。',
  },
  default: {
    title: '暂时无法访问',
    description: '该内容不存在或暂未公开，请联系分享者确认。',
  },
}

const reason = ref<string | null>(null)

onMounted(() => {
  const params = new URLSearchParams(window.location.search)
  reason.value = params.get('reason')
})

const currentReason = computed(() => {
  if (!reason.value || !reasonMap[reason.value]) {
    return reasonMap.default
  }
  return reasonMap[reason.value]
})

const handleReturn = () => {
  window.location.href = './share-home.html'
}
</script>

<template>
  <div class="flex flex-col items-center justify-center space-y-6 text-center">
    <!-- 错误图标 -->
    <div class="w-24 h-24 bg-destructive/10 rounded-full flex items-center justify-center">
      <SafeIcon 
        name="Lock" 
        :size="48" 
        class="text-destructive"
      />
    </div>

    <!-- 错误标题和描述 -->
    <div class="space-y-3">
      <h1 class="text-page-title text-foreground">
        {{ currentReason.title }}
      </h1>
      <p class="text-body text-muted-foreground leading-relaxed max-w-sm">
        {{ currentReason.description }}
      </p>
    </div>

    <!-- 操作按钮 -->
    <div class="pt-4">
      <Button 
        size="lg" 
        class="px-8 h-11 rounded-lg font-medium"
        @click="handleReturn"
      >
        <SafeIcon name="ArrowLeft" :size="18" class="mr-2" />
        返回主页
      </Button>
    </div>

    <!-- 帮助提示 -->
    <div class="pt-4 border-t border-border/50 w-full">
      <p class="text-xs text-muted-foreground/70">
        如有问题，请联系分享者或
        <a href="#" class="text-primary hover:underline">联系客服</a>
      </p>
    </div>
  </div>
</template>

<style scoped>
/* 确保内容在 CenteredLayout 的居中容器内正确显示 */
:deep(.centered-content) {
  @apply w-full;
}
</style>
