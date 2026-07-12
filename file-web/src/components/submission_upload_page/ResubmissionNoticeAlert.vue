
<script setup lang="ts">
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert'
import SafeIcon from '@/components/common/SafeIcon.vue'

interface Props {
  notice: {
    reason: string
    sentAt: string
    sentBy: string
  }
}

const props = defineProps<Props>()

const formatDate = (dateString: string) => {
  const date = new Date(dateString)
  return date.toLocaleString('zh-CN', { year: 'numeric', month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit' })
}
</script>

<template>
  <Alert class="border-warning/30 bg-warning/5">
    <SafeIcon name="AlertTriangle" :size="18" class="text-warning" />
    <AlertTitle>需要补交材料</AlertTitle>
    <AlertDescription class="mt-2 space-y-2">
      <p class="font-medium">退回原因：{{ notice.reason }}</p>
      <p class="text-xs text-muted-foreground">
        由 {{ notice.sentBy }} 于 {{ formatDate(notice.sentAt) }} 发送
      </p>
      <p class="text-xs text-muted-foreground">
        请根据上述原因重新补交材料，感谢配合。
      </p>
    </AlertDescription>
  </Alert>
</template>
