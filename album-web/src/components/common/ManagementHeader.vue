
<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { Button } from '@/components/ui/button';
import SafeIcon from '@/components/common/SafeIcon.vue';
import BrandMark from '@/components/common/BrandMark.vue';
import UserProfileMenu from '@/components/common/UserProfileMenu.vue';
import { authStore, isMockEnabled, pcApi } from '@/lib/api';

const userInfo = ref<any>({});

onMounted(async () => {
  if (!authStore.isLoggedIn() && !isMockEnabled()) return;
  try {
    const profile = await pcApi.getCurrentUser();
    userInfo.value = profile || {};
    authStore.setUser(profile);
  } catch {
    // 头部信息失败不影响页面主体
  }
});

const handlePreview = () => {
  const code = userInfo.value?.share_code || userInfo.value?.shareCode || userInfo.value?.home_share_code || '';
  if (!code) return;
  window.location.href = `./share-home?code=${encodeURIComponent(code)}`;
};

const goToWorkbench = () => {
  window.location.href = './management-workbench';
};
</script>

<template>
  <header class="h-[var(--header-height)] border-b border-border bg-card px-6 flex items-center justify-between shrink-0 z-10">
    <div class="flex items-center gap-8">
      <div 
        class="flex items-center gap-2 cursor-pointer hover:opacity-80 transition-opacity"
        @click="goToWorkbench"
      >
        <BrandMark :size="32" />
        <h1 class="text-lg font-bold tracking-tight">家纺云相册</h1>
      </div>

    </div>

    <div class="flex items-center gap-3">
      <Button variant="ghost" size="sm" @click="handlePreview">
        <SafeIcon name="Eye" :size="18" class="mr-2" />
        预览主页
      </Button>

      <UserProfileMenu
        :user-info="userInfo"
        trigger-class="relative h-9 w-9 rounded-full p-0"
        avatar-class="h-9 w-9"
        logout-redirect="./share-home"
      />
    </div>
  </header>
</template>
