
<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { FileShareService, type FileShareVO } from '@/data/FileShareService'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Separator } from '@/components/ui/separator'
import SafeIcon from '@/components/common/SafeIcon.vue'
import DetailPageHeader from '@/components/common/DetailPageHeader.vue'
import { toast } from 'vue-sonner'
import { cn } from '@/lib/utils'

const allShares = ref(FileShareService.getAll())
const currentShare = ref<FileShareVO | null>(null)
const shareId = ref<string>('')

onMounted(() => {
  const params = new URLSearchParams(window.location.search)
  const paramShareId = params.get('shareId')
  
  if (paramShareId) {
    shareId.value = paramShareId
    const share = FileShareService.getShareVOById(paramShareId)
    if (share) {
      currentShare.value = share
    } else {
      currentShare.value = FileShareService.getShareVOById(allShares.value[0]?.id) || null
    }
  } else {
    const defaultShare = allShares.value[0]
    if (defaultShare) {
      shareId.value = defaultShare.id
      currentShare.value = FileShareService.getShareVOById(defaultShare.id) || null
    }
  }
})

const isExpired = computed(() => {
  if (!currentShare.value) return false
  return new Date(currentShare.value.expiresAt) < new Date()
})

const expiresInDays = computed(() => {
  if (!currentShare.value) return 0
  const now = new Date()
  const expires = new Date(currentShare.value.expiresAt)
  const diffTime = expires.getTime() - now.getTime()
  const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24))
  return Math.max(0, diffDays)
})

const formatFileSize = (mb: number) => {
  if (mb >= 1024) {
    return (mb / 1024).toFixed(2) + ' GB'
  }
  return mb.toFixed(2) + ' MB'
}

const formatDate = (dateString: string) => {
  const date = new Date(dateString)
  return date.toLocaleDateString('zh-CN', {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const handleCopyLink = async () => {
  if (!currentShare.value) return
  try {
    await navigator.clipboard.writeText(currentShare.value.shareUrl)
    toast.success('链接已复制')
  } catch {
    toast.error('复制失败，请重试')
  }
}

const handleCopyPassword = async () => {
  if (!currentShare.value) return
  try {
    await navigator.clipboard.writeText(currentShare.value.password)
    toast.success('密码已复制')
  } catch {
    toast.error('复制失败，请重试')
  }
}

const handleCopyQRCode = async () => {
  if (!currentShare.value) return
  try {
    await navigator.clipboard.writeText(`二维码: ${currentShare.value.shareUrl}`)
    toast.success('二维码已复制')
  } catch {
    toast.error('复制失败，请重试')
  }
}

const handleRegenerateQRCode = () => {
  toast.success('二维码已重新生成')
}

const handleExtendExpiry = () => {
  toast.success('有效期已延长至 30 天')
}

const handleViewDownloadRecords = () => {
  window.location.href = './delivery-records.html'
}

const handleContinueSending = () => {
  window.location.href = './quick-send.html'
}

const handleBack = () => {
  window.location.href = './workbench.html'
}
</script>

<template>
  <div class="page-body">
    <!-- Header with Back Button -->
    <DetailPageHeader
      title="分享链接已生成"
      :breadcrumbs="[
        { label: '工作台', href: './workbench.html' },
        { label: '分享结果' }
      ]"
    />

    <div v-if="currentShare" class="space-y-8">
      <!-- Success Status Card -->
      <Card class="border-l-4 border-l-[hsl(var(--success))] bg-[hsl(var(--success)_/_0.02)]">
        <CardHeader class="pb-3">
          <div class="flex items-start justify-between">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-full bg-[hsl(var(--success))] flex items-center justify-center">
                <SafeIcon name="CheckCircle2" :size="20" color="white" />
              </div>
              <div>
                <CardTitle class="text-lg">分享链接已生成</CardTitle>
                <CardDescription>您可以将链接分享给他人，他们可以通过链接访问您的文件</CardDescription>
              </div>
            </div>
            <Badge variant="outline" class="bg-[hsl(var(--success)_/_0.1)] text-[hsl(var(--success))] border-[hsl(var(--success)_/_0.3)]">
              {{ isExpired ? '已过期' : '有效' }}
            </Badge>
          </div>
        </CardHeader>
      </Card>

      <!-- Main Content Grid -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left Column: Link & Settings -->
        <div class="lg:col-span-2 space-y-6">
          <!-- Share Link Section -->
          <Card>
            <CardHeader>
              <CardTitle class="text-base">分享链接</CardTitle>
            </CardHeader>
            <CardContent class="space-y-4">
              <div class="space-y-2">
                <label class="text-sm font-medium text-foreground">链接地址</label>
                <div class="flex gap-2">
                  <Input
                    :value="currentShare.shareUrl"
                    readonly
                    class="bg-muted/50 text-sm font-mono"
                  />
                  <Button
                    variant="outline"
                    size="icon"
                    class="shrink-0"
                    @click="handleCopyLink"
                  >
                    <SafeIcon name="Copy" :size="18" />
                  </Button>
                </div>
              </div>

              <Separator />

              <div class="space-y-2">
                <label class="text-sm font-medium text-foreground">访问密码</label>
                <div class="flex gap-2">
                  <Input
                    :value="currentShare.password"
                    readonly
                    type="password"
                    class="bg-muted/50 text-sm font-mono"
                  />
                  <Button
                    variant="outline"
                    size="icon"
                    class="shrink-0"
                    @click="handleCopyPassword"
                  >
                    <SafeIcon name="Copy" :size="18" />
                  </Button>
                </div>
              </div>
            </CardContent>
          </Card>

          <!-- Access Settings Summary -->
          <Card>
            <CardHeader>
              <CardTitle class="text-base">访问设置</CardTitle>
            </CardHeader>
            <CardContent>
              <div class="grid grid-cols-2 gap-4">
                <div class="space-y-1">
                  <p class="text-xs text-muted-foreground">有效期</p>
                  <p class="text-sm font-medium">
                    {{ isExpired ? '已过期' : `${expiresInDays} 天` }}
                  </p>
                </div>
                <div class="space-y-1">
                  <p class="text-xs text-muted-foreground">最大下载次数</p>
                  <p class="text-sm font-medium">{{ currentShare.maxDownloads }} 次</p>
                </div>
                <div class="space-y-1">
                  <p class="text-xs text-muted-foreground">已下载次数</p>
                  <p class="text-sm font-medium">{{ currentShare.downloadCount }} 次</p>
                </div>
                <div class="space-y-1">
                  <p class="text-xs text-muted-foreground">在线预览</p>
                  <p class="text-sm font-medium">{{ currentShare.allowPreview ? '允许' : '禁止' }}</p>
                </div>
              </div>
            </CardContent>
          </Card>

          <!-- File Summary -->
          <Card>
            <CardHeader>
              <CardTitle class="text-base">文件信息</CardTitle>
            </CardHeader>
            <CardContent>
              <div class="space-y-3">
                <div class="flex items-center justify-between">
                  <span class="text-sm text-muted-foreground">文件数量</span>
                  <span class="text-sm font-medium">{{ currentShare.fileCount }} 个</span>
                </div>
                <div class="flex items-center justify-between">
                  <span class="text-sm text-muted-foreground">总大小</span>
                  <span class="text-sm font-medium">{{ formatFileSize(currentShare.totalSizeMb) }}</span>
                </div>
                <div class="flex items-center justify-between">
                  <span class="text-sm text-muted-foreground">生成时间</span>
                  <span class="text-sm font-medium">{{ formatDate(currentShare.expiresAt) }}</span>
                </div>
              </div>
            </CardContent>
          </Card>
        </div>

        <!-- Right Column: QR Code & Actions -->
        <div class="space-y-6">
          <!-- QR Code Card -->
          <Card>
            <CardHeader>
              <CardTitle class="text-base">二维码</CardTitle>
              <CardDescription class="text-xs">扫描二维码快速分享</CardDescription>
            </CardHeader>
            <CardContent class="space-y-4">
              <div class="w-full aspect-square bg-muted/30 rounded-lg flex items-center justify-center border border-border">
                <div class="text-center">
                  <SafeIcon name="QrCode" :size="48" class="text-muted-foreground/40 mx-auto mb-2" />
                  <p class="text-xs text-muted-foreground">二维码</p>
                </div>
              </div>
              <div class="flex gap-2">
                <Button
                  variant="outline"
                  size="sm"
                  class="flex-1"
                  @click="handleCopyQRCode"
                >
                  <SafeIcon name="Copy" :size="16" class="mr-1" />
                  复制
                </Button>
                <Button
                  variant="outline"
                  size="sm"
                  class="flex-1"
                  @click="handleRegenerateQRCode"
                >
                  <SafeIcon name="RotateCw" :size="16" class="mr-1" />
                  重新生成
                </Button>
              </div>
            </CardContent>
          </Card>

          <!-- Quick Actions -->
          <Card>
            <CardHeader>
              <CardTitle class="text-base">快速操作</CardTitle>
            </CardHeader>
            <CardContent class="space-y-2">
              <Button
                variant="outline"
                class="w-full justify-start"
                @click="handleViewDownloadRecords"
              >
                <SafeIcon name="Download" :size="16" class="mr-2" />
                查看下载记录
              </Button>
              <Button
                variant="outline"
                class="w-full justify-start"
                @click="handleContinueSending"
              >
                <SafeIcon name="Plus" :size="16" class="mr-2" />
                继续发送文件
              </Button>
              <Button
                v-if="isExpired"
                variant="outline"
                class="w-full justify-start"
                @click="handleExtendExpiry"
              >
                <SafeIcon name="Clock" :size="16" class="mr-2" />
                延长有效期
              </Button>
            </CardContent>
          </Card>

          <!-- Recent Access Log -->
          <Card v-if="currentShare.recentLogs.length > 0">
            <CardHeader>
              <CardTitle class="text-base">最近访问</CardTitle>
            </CardHeader>
            <CardContent>
              <div class="space-y-3">
                <div
                  v-for="log in currentShare.recentLogs"
                  :key="log.id"
                  class="flex items-start justify-between text-sm pb-2 border-b border-border last:border-0 last:pb-0"
                >
                  <div class="min-w-0">
                    <p class="font-medium text-foreground truncate">{{ log.visitorName }}</p>
                    <p class="text-xs text-muted-foreground">{{ log.ipLabel }}</p>
                  </div>
                  <span class="text-xs text-muted-foreground whitespace-nowrap ml-2">
                    {{ formatDate(log.occurredAt) }}
                  </span>
                </div>
              </div>
            </CardContent>
          </Card>
        </div>
      </div>

      <!-- Bottom Action Bar -->
      <div class="flex gap-3 justify-end pt-4 border-t border-border">
        <Button
          variant="outline"
          @click="handleBack"
        >
          返回工作台
        </Button>
        <Button
          @click="handleContinueSending"
        >
          <SafeIcon name="Plus" :size="16" class="mr-2" />
          继续发送文件
        </Button>
      </div>
    </div>

    <!-- Loading State -->
    <div v-else class="flex items-center justify-center py-16">
      <div class="text-center">
        <SafeIcon name="Loader2" :size="48" class="text-muted-foreground/40 mx-auto mb-4 animate-spin" />
        <p class="text-muted-foreground">加载中...</p>
      </div>
    </div>
  </div>
</template>

<style scoped>
/* 确保卡片在悬停时有微妙的交互反馈 */
:deep(.surface-raised) {
  @apply transition-all duration-200;
}

:deep(.surface-raised):hover {
  @apply shadow-md;
}
</style>
