<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { Button } from '@/components/ui/button'
import HomeSettingsDialog from '@/components/home_settings/HomeSettingsDialog.vue'
import SafeIcon from '@/components/common/SafeIcon.vue'

const dialogOpen = ref(true)

onMounted(() => {
  dialogOpen.value = true
})
</script>

<template>
  <div class="page-body flex min-h-[70vh] flex-col items-center justify-center gap-4 text-center">
    <div class="flex h-14 w-14 items-center justify-center rounded-full bg-primary/10 text-primary">
      <SafeIcon name="Store" :size="26" />
    </div>
    <div>
      <h1 class="text-page-title">编辑主页</h1>
      <p class="mt-2 text-sm text-muted-foreground">主页资料已移到个人中心弹窗中编辑。</p>
    </div>
    <Button class="gap-2" @click="dialogOpen = true">
      <SafeIcon name="PenLine" :size="16" />
      打开编辑主页
    </Button>
    <HomeSettingsDialog v-model:open="dialogOpen" />
  </div>
</template>
