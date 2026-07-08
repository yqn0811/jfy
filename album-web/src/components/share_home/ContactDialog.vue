
<script setup lang="ts">
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogDescription,
  DialogFooter,
} from '@/components/ui/dialog'
import { Button } from '@/components/ui/button'
import SafeIcon from '@/components/common/SafeIcon.vue'
import { toast } from 'vue-sonner'
import type { HomeProfileData } from '@/data/HomeProfileData'

interface Props {
  open: boolean
  homeProfile: HomeProfileData
}

const props = defineProps<Props>()
const emit = defineEmits<{
  (e: 'update:open', value: boolean): void
}>()

const handleCopyPhone = () => {
  if (props.homeProfile.contactPhone) {
    navigator.clipboard.writeText(props.homeProfile.contactPhone)
    toast.success('电话已复制')
  }
}

const handleCopyWechat = () => {
  if (props.homeProfile.wechatId) {
    navigator.clipboard.writeText(props.homeProfile.wechatId)
    toast.success('微信已复制')
  }
}

const handleCallPhone = () => {
  if (props.homeProfile.contactPhone) {
    window.location.href = `tel:${props.homeProfile.contactPhone}`
  }
}
</script>

<template>
  <Dialog :open="open" @update:open="(val) => emit('update:open', val)">
    <DialogContent class="sm:max-w-[400px]">
      <DialogHeader>
        <DialogTitle>联系商户</DialogTitle>
        <DialogDescription>
          {{ homeProfile.companyName }}
        </DialogDescription>
      </DialogHeader>

      <div class="space-y-4 py-4">
        <!-- 电话 -->
        <div v-if="homeProfile.contactPhone" class="flex items-center justify-between p-4 border border-border rounded-lg">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-primary/10 rounded-lg flex items-center justify-center">
              <SafeIcon name="Phone" :size="20" class="text-primary" />
            </div>
            <div>
              <p class="text-sm font-medium">电话</p>
              <p class="text-xs text-muted-foreground">{{ homeProfile.contactPhone }}</p>
            </div>
          </div>
          <Button
            size="sm"
            variant="ghost"
            @click="handleCopyPhone"
          >
            <SafeIcon name="Copy" :size="16" />
          </Button>
        </div>

        <!-- 微信 -->
        <div v-if="homeProfile.wechatId" class="flex items-center justify-between p-4 border border-border rounded-lg">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-primary/10 rounded-lg flex items-center justify-center">
              <SafeIcon name="MessageCircle" :size="20" class="text-primary" />
            </div>
            <div>
              <p class="text-sm font-medium">微信</p>
              <p class="text-xs text-muted-foreground">{{ homeProfile.wechatId }}</p>
            </div>
          </div>
          <Button
            size="sm"
            variant="ghost"
            @click="handleCopyWechat"
          >
            <SafeIcon name="Copy" :size="16" />
          </Button>
        </div>

        <!-- 地址 -->
        <div v-if="homeProfile.address" class="flex items-center gap-3 p-4 border border-border rounded-lg">
          <div class="w-10 h-10 bg-primary/10 rounded-lg flex items-center justify-center shrink-0">
            <SafeIcon name="MapPin" :size="20" class="text-primary" />
          </div>
          <div class="min-w-0">
            <p class="text-sm font-medium">地址</p>
            <p class="text-xs text-muted-foreground truncate">{{ homeProfile.address }}</p>
          </div>
        </div>
      </div>

      <DialogFooter class="flex gap-2">
        <Button
          variant="outline"
          @click="() => emit('update:open', false)"
        >
          关闭
        </Button>
        <Button
          v-if="homeProfile.contactPhone"
          @click="handleCallPhone"
        >
          <SafeIcon name="Phone" :size="16" class="mr-2" />
          拨打电话
        </Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>
