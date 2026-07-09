
<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { Button } from '@/components/ui/button'
import SafeIcon from '@/components/common/SafeIcon.vue'
import { toast } from 'vue-sonner'
import { PublicSubmissionService } from '@/data/PublicSubmissionService'
import type { SubmissionReceiptVO } from '@/data/PublicSubmissionService'
import { navigateTo } from '@/navigation'

const receipt = ref<SubmissionReceiptVO | null>(null)
const isLoading = ref(false)
const taskId = ref<string>('')

onMounted(() => {
  const params = new URLSearchParams(window.location.search)
  const submissionId = params.get('submissionId') || 'submission-001'
  
  isLoading.value = true
  
  try {
    const result = PublicSubmissionService.submit(submissionId)
    if (result) {
      receipt.value = result
      taskId.value = submissionId.split('-')[1] || 'task-001'
    } else {
      toast.error('无法加载提交信息，请稍后重试')
    }
  } catch (error) {
    toast.error('加载提交信息失败')
  } finally {
    isLoading.value = false
  }
})

const handleCopyContact = () => {
  const contactInfo = '联系方式: 织序传输助手 support@weave-order.com'
  navigator.clipboard.writeText(contactInfo).then(() => {
    toast.success('已复制联系方式到剪贴板')
  }).catch(() => {
    toast.error('复制失败，请手动复制')
  })
}

const handleReturnToSubmission = () => {
  navigateTo(`/submission-upload?taskId=${taskId.value}`)
}

const handleResubmit = () => {
  navigateTo(`/submission-upload?taskId=${taskId.value}`)
}

const formatDate = (dateString: string) => {
  try {
    const date = new Date(dateString)
    return date.toLocaleString('zh-CN', {
      year: 'numeric',
      month: '2-digit',
      day: '2-digit',
      hour: '2-digit',
      minute: '2-digit',
      second: '2-digit'
    })
  } catch {
    return dateString
  }
}
</script>

<template>
  <div class="page-body">
    <div class="page-container">
      <div class="max-w-2xl mx-auto space-y-8">
      <!-- 成功状态卡片 -->
      <div class="surface-raised card-padding text-center space-y-6">
        <!-- 成功图标 -->
        <div class="flex justify-center">
          <div class="w-20 h-20 rounded-full bg-[hsl(var(--success))/0.1] flex items-center justify-center animate-pulse">
            <SafeIcon 
              name="CheckCircle2" 
              :size="48" 
              color="hsl(var(--success))" 
              :stroke-width="1.5" 
            />
          </div>
        </div>

        <!-- 成功标题 -->
        <div class="space-y-2">
          <h1 class="text-page-title text-foreground">
            提交成功！
          </h1>
          <p class="text-body text-muted-foreground">
            您的材料已成功提交，感谢您的配合
          </p>
        </div>

        <!-- 提交编号和时间 -->
        <div v-if="receipt" class="bg-muted/30 rounded-lg p-4 space-y-3 text-left">
          <div class="flex items-center justify-between">
            <span class="text-caption font-medium text-muted-foreground">提交编号</span>
            <span class="text-item-title font-mono text-foreground">{{ receipt.receiptNumber }}</span>
          </div>
          <div class="flex items-center justify-between border-t border-border/50 pt-3">
            <span class="text-caption font-medium text-muted-foreground">提交时间</span>
            <span class="text-item-title text-foreground">{{ formatDate(receipt.submittedAt) }}</span>
          </div>
        </div>
      </div>

      <!-- 已提交材料摘要 -->
      <div v-if="receipt" class="surface-raised card-padding space-y-4">
        <h2 class="text-section-title font-semibold text-foreground flex items-center gap-2">
          <SafeIcon name="FileCheck" :size="20" color="hsl(var(--success))" />
          已提交材料
        </h2>
        <div class="bg-accent/30 rounded-lg p-4 text-sm text-foreground leading-relaxed">
          {{ receipt.materialSummary }}
        </div>
      </div>

      <!-- 补交说明 -->
      <div class="surface-base card-padding space-y-3 border-l-4 border-l-[hsl(var(--warning))]">
        <h3 class="text-item-title font-medium text-foreground flex items-center gap-2">
          <SafeIcon name="AlertCircle" :size="18" color="hsl(var(--warning))" />
          补交说明
        </h3>
        <p class="text-caption text-muted-foreground leading-relaxed">
          如果发起方要求补交材料，您将收到补交通知。请点击通知中的链接或使用原提交链接重新进入，按要求补交即可。
        </p>
      </div>

      <!-- 联系发起方 -->
      <div class="surface-base card-padding space-y-4">
        <h3 class="text-item-title font-medium text-foreground">
          有任何问题？
        </h3>
        <p class="text-caption text-muted-foreground">
          如有疑问，请联系发起方或通过以下方式获取帮助：
        </p>
        <Button 
          variant="outline" 
          class="w-full h-10 justify-center"
          @click="handleCopyContact"
        >
          <SafeIcon name="Copy" :size="16" class="mr-2" />
          复制联系方式
        </Button>
      </div>

      <!-- 操作按钮 -->
      <div class="flex flex-col sm:flex-row gap-3">
        <Button 
          variant="outline" 
          class="flex-1 h-10"
          @click="handleReturnToSubmission"
        >
          <SafeIcon name="ArrowLeft" :size="16" class="mr-2" />
          返回提交页面
        </Button>
        <Button 
          class="flex-1 h-10"
          @click="handleResubmit"
        >
          <SafeIcon name="RotateCcw" :size="16" class="mr-2" />
          重新提交
        </Button>
      </div>

      <!-- 页脚提示 -->
      <div class="text-center text-caption text-muted-foreground/60">
        <p>
          此页面将在 <span class="font-medium">5 分钟</span> 后自动关闭
        </p>
      </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
/* 成功图标脉冲动画 */
@keyframes pulse-success {
  0%, 100% {
    opacity: 1;
  }
  50% {
    opacity: 0.7;
  }
}

.animate-pulse {
  animation: pulse-success 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}
</style>
