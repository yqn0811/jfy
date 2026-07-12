
<script setup lang="ts">
import { ref } from 'vue'
import { Button } from '@/components/ui/button'
import { Card, CardContent } from '@/components/ui/card'
import SafeIcon from '@/components/common/SafeIcon.vue'
import FallbackImage from '@/components/common/FallbackImage.vue'
import UserAvatar from '@/components/common/UserAvatar.vue'
import StatusBadge from '@/components/common/StatusBadge.vue'
import LoginDialog from '@/components/common/LoginDialog.vue'
import ConfirmDialog from '@/components/common/ConfirmDialog.vue'
import TargetShareDialog from '@/components/common/TargetShareDialog.vue'
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

const handleShare = () => {
  showShareDialog.value = true
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
          <FallbackImage
            :src="category.coverUrl"
            :candidates="category.coverUrlCandidates"
            :alt="category.name"
            class="w-32 h-32 rounded-lg object-cover border border-border"
          >
            <div class="flex h-32 w-32 items-center justify-center rounded-lg border border-border bg-muted">
              <SafeIcon name="Image" :size="32" class="text-muted-foreground/60" />
            </div>
          </FallbackImage>
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

  <TargetShareDialog
    :open="showShareDialog"
    type="category"
    title="分享分类"
    description="选择分享方式，让更多人查看这个分类"
    :target-id="category.id"
    :target-user-id="targetUserId || ''"
    :share-code="shareCode || ''"
    web-path="./category"
    web-param-name="categoryId"
    @update:open="showShareDialog = $event"
  />
</template>
