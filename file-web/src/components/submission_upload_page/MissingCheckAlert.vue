
<script setup lang="ts">
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert'
import SafeIcon from '@/components/common/SafeIcon.vue'

interface Props {
  result: {
    missingNames: string[]
    summary: string
    state: 'passing' | 'missing' | 'warning'
  }
}

const props = defineProps<Props>()

const getAlertConfig = () => {
  switch (props.result.state) {
    case 'passing':
      return {
        icon: 'CheckCircle2',
        iconClass: 'text-[hsl(var(--success))]',
        borderClass: 'border-[hsl(var(--success))]/30',
        bgClass: 'bg-[hsl(var(--success))]/5',
        title: '材料检查通过'
      }
    case 'missing':
      return {
        icon: 'AlertCircle',
        iconClass: 'text-destructive',
        borderClass: 'border-destructive/30',
        bgClass: 'bg-destructive/5',
        title: '缺少必填材料'
      }
    case 'warning':
      return {
        icon: 'AlertTriangle',
        iconClass: 'text-warning',
        borderClass: 'border-warning/30',
        bgClass: 'bg-warning/5',
        title: '材料检查提醒'
      }
    default:
      return {
        icon: 'Info',
        iconClass: 'text-primary',
        borderClass: 'border-primary/30',
        bgClass: 'bg-primary/5',
        title: '材料检查结果'
      }
  }
}

const config = getAlertConfig()
</script>

<template>
  <Alert :class="`${config.borderClass} ${config.bgClass}`">
    <SafeIcon :name="config.icon" :size="18" :class="config.iconClass" />
    <AlertTitle>{{ config.title }}</AlertTitle>
    <AlertDescription class="mt-2 space-y-2">
      <p>{{ result.summary }}</p>
      <ul v-if="result.missingNames.length > 0" class="list-disc list-inside text-xs text-muted-foreground space-y-1">
        <li v-for="name in result.missingNames" :key="name">
          {{ name }}
        </li>
      </ul>
    </AlertDescription>
  </Alert>
</template>
