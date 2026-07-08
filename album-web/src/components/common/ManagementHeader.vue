
<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { Button } from '@/components/ui/button';
import { Progress } from '@/components/ui/progress';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Avatar, AvatarImage, AvatarFallback } from '@/components/ui/avatar';
import SafeIcon from '@/components/common/SafeIcon.vue';
import { authStore, pcApi } from '@/lib/api';
import { isLocalMockEnabled } from '@/lib/mock-api';

interface Props {
  usedStorage?: string;
  totalStorage?: string;
  remainingStorage?: string;
}

const props = withDefaults(defineProps<Props>(), {
  usedStorage: '1.2 GB',
  totalStorage: '5.0 GB',
  remainingStorage: '3.8 GB',
});

const userInfo = ref<any>({});

const storagePercent = computed(() => {
  const value = Number(userInfo.value?.space_used ?? 0);
  return Number.isFinite(value) ? Math.min(Math.max(value, 0), 100) : 0;
});

const displayName = computed(() => userInfo.value?.company_name || userInfo.value?.nickname || '家纺云用户');
const avatarUrl = computed(() => userInfo.value?.avatar || userInfo.value?.company_logo || '');
const totalStorageText = computed(() => userInfo.value?.all_space || props.totalStorage);
const usedStorageText = computed(() => {
  const bytes = Number(userInfo.value?.use_space || 0);
  if (!bytes) return props.usedStorage;
  const mb = bytes / 1024 / 1024;
  if (mb >= 1024) return `${(mb / 1024).toFixed(2)} GB`;
  return `${mb.toFixed(2)} MB`;
});

onMounted(async () => {
  if (!authStore.isLoggedIn() && !isLocalMockEnabled()) return;
  try {
    const profile = await pcApi.getCurrentUser();
    userInfo.value = profile || {};
    authStore.setUser(profile);
  } catch {
    // 头部信息失败不影响页面主体
  }
});

const handlePreview = () => {
  const uid = userInfo.value?.id || userInfo.value?.uid || '';
  window.location.href = `./share-home.html${uid ? `?uid=${uid}` : ''}`;
};

const handleUpgrade = () => {
  window.location.href = './billing-usage.html';
};

const handleLogout = () => {
  authStore.clearToken();
  userInfo.value = {};
  window.location.href = './share-home.html';
};

const goToWorkbench = () => {
  window.location.href = './management-workbench.html';
};
</script>

<template>
  <header class="h-[var(--header-height)] border-b border-border bg-card px-6 flex items-center justify-between shrink-0 z-10">
    <div class="flex items-center gap-8">
      <div 
        class="flex items-center gap-2 cursor-pointer hover:opacity-80 transition-opacity"
        @click="goToWorkbench"
      >
        <div class="w-8 h-8 bg-primary rounded-md flex items-center justify-center">
          <SafeIcon name="LayoutDashboard" :size="20" class="text-primary-foreground" />
        </div>
        <h1 class="text-lg font-bold tracking-tight">产品工作台</h1>
      </div>

      <div class="hidden lg:flex items-center gap-4 border-l border-border pl-8">
        <div class="flex flex-col gap-1 w-48">
          <div class="flex justify-between text-[10px] text-muted-foreground uppercase font-bold">
            <span>存储空间</span>
            <span>{{ usedStorageText }} / {{ totalStorageText }}</span>
          </div>
          <Progress :model-value="storagePercent" class="h-1.5" />
        </div>
        <Button variant="outline" size="sm" class="h-8 text-xs" @click="handleUpgrade">
          <SafeIcon name="TrendingUp" :size="14" class="mr-1" />
          升级容量
        </Button>
      </div>
    </div>

    <div class="flex items-center gap-3">
      <Button variant="ghost" size="sm" @click="handlePreview">
        <SafeIcon name="Eye" :size="18" class="mr-2" />
        预览主页
      </Button>

      <DropdownMenu>
        <DropdownMenuTrigger as-child>
          <Button variant="ghost" class="relative h-9 w-9 rounded-full">
            <Avatar class="h-9 w-9">
              <AvatarImage :src="avatarUrl" alt="User" />
              <AvatarFallback>{{ displayName.substring(0, 1) }}</AvatarFallback>
            </Avatar>
          </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent class="w-56" align="end">
          <DropdownMenuLabel class="font-normal">
            <div class="flex flex-col space-y-1">
              <p class="text-sm font-medium leading-none">{{ displayName }}</p>
              <p class="text-xs leading-none text-muted-foreground">家纺云产品工作台</p>
            </div>
          </DropdownMenuLabel>
          <DropdownMenuSeparator />
          <DropdownMenuItem @click="goToWorkbench">
            <SafeIcon name="LayoutDashboard" :size="16" class="mr-2" />
            管理工作台
          </DropdownMenuItem>
          <DropdownMenuItem @click="handleUpgrade">
            <SafeIcon name="CreditCard" :size="16" class="mr-2" />
            版本与账单
          </DropdownMenuItem>
          <DropdownMenuSeparator />
          <DropdownMenuItem class="text-destructive focus:text-destructive" @click="handleLogout">
            <SafeIcon name="LogOut" :size="16" class="mr-2" />
            退出登录
          </DropdownMenuItem>
        </DropdownMenuContent>
      </DropdownMenu>
    </div>
  </header>
</template>
