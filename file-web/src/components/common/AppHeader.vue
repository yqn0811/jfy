
<script setup lang="ts">
import { onBeforeUnmount, ref } from 'vue';
import { Menu } from 'lucide-vue-next';
import { toast } from 'vue-sonner';
import { Button } from '@/components/ui/button';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
import {
  Sheet,
  SheetContent,
  SheetHeader,
  SheetTitle,
  SheetTrigger,
} from '@/components/ui/sheet';
import SafeIcon from '@/components/common/SafeIcon.vue';
import { AuthApi } from '@/data/AuthApi';
import { authStore, getApiErrorMessage } from '@/lib/apiClient';
import { navigateTo } from '@/navigation';
import { cn } from '@/lib/utils';

const props = defineProps<{
  currentPath?: string;
}>();

const navigationItems = [
  { name: '发文件', href: '/quick-send', icon: 'Send' },
  { name: '收文件', href: '/create-collection-task', icon: 'Download' },
  { name: '收发记录', href: '/delivery-records', icon: 'History' },
  { name: '文小盘', href: '/space-archive', icon: 'FolderOpen' },
];

const isLoginDialogOpen = ref(false);
const isLoadingLogin = ref(false);
const loginScene = ref('');
const loginQrcode = ref('');
const loginStatusText = ref('');
const loginTick = ref<number | null>(null);
const isActive = (href: string) => {
  if (!props.currentPath) return false;
  const current = props.currentPath.replace(/\/$/, '');
  const target = href.replace(/\/$/, '');
  if (target === '/delivery-records' && current === '/task-details') return true;
  return current === target || (target !== '/workbench' && current.startsWith(target));
};

const handleNavClick = (href: string) => {
  navigateTo(href);
};

const stopLoginPolling = () => {
  if (loginTick.value !== null) {
    window.clearInterval(loginTick.value);
    loginTick.value = null;
  }
};

const pollLoginStatus = async () => {
  if (!loginScene.value) return;
  try {
    const status = await AuthApi.getLoginStatus(loginScene.value);
    if (status.status === 'success' && status.token) {
      authStore.setToken(status.token);
      loginStatusText.value = '登录成功';
      stopLoginPolling();
      isLoginDialogOpen.value = false;
      toast.success('登录成功');
      return;
    }
    if (status.status === 'expired') {
      loginStatusText.value = '二维码已过期，请刷新';
      stopLoginPolling();
      return;
    }
    loginStatusText.value = '等待扫码确认';
  } catch (error) {
    loginStatusText.value = getApiErrorMessage(error, '登录状态检查失败');
    stopLoginPolling();
  }
};

const openLoginDialog = async () => {
  if (authStore.hasToken()) return;
  isLoginDialogOpen.value = true;
  isLoadingLogin.value = true;
  loginStatusText.value = '正在获取登录二维码';
  loginScene.value = '';
  loginQrcode.value = '';
  stopLoginPolling();

  try {
    const data = await AuthApi.getLoginQrcode();
    loginScene.value = data.scene;
    loginQrcode.value = data.qrcode;
    loginStatusText.value = '请使用微信扫码登录';
    loginTick.value = window.setInterval(() => {
      void pollLoginStatus();
    }, 2000);
  } catch (error) {
    loginStatusText.value = getApiErrorMessage(error, '登录服务暂不可用');
  } finally {
    isLoadingLogin.value = false;
  }
};

const handleLogout = () => {
  authStore.clearToken();
  toast.success('已退出登录');
};

onBeforeUnmount(() => {
  stopLoginPolling();
});
</script>

<template>
  <header class="sticky top-0 z-50 w-full h-[60px] bg-card border-b border-border shadow-sm">
    <div class="app-shell-padded h-full flex items-center justify-between gap-3">
      <!-- Left: Logo and Main Nav -->
      <div class="flex items-center gap-3 lg:gap-8 h-full min-w-0">
        <Sheet>
          <SheetTrigger as-child>
            <Button variant="ghost" size="icon" class="lg:hidden shrink-0">
              <Menu class="w-5 h-5" />
            </Button>
          </SheetTrigger>
          <SheetContent side="left" class="w-[280px] p-0">
            <SheetHeader class="border-b border-border px-4 py-4 text-left">
              <SheetTitle class="flex items-center gap-2">
                <div class="w-8 h-8 bg-primary rounded-lg flex items-center justify-center">
                  <SafeIcon name="ShipWheel" :size="20" color="white" :stroke-width="2.5" />
                </div>
                织序传输
              </SheetTitle>
            </SheetHeader>
            <nav class="p-3 space-y-1">
              <button
                v-for="item in navigationItems"
                :key="item.href"
                :class="cn(
                  'w-full flex items-center gap-3 rounded-md px-3 py-2.5 text-sm font-medium text-left transition-colors',
                isActive(item.href) ? 'bg-primary/10 text-primary' : 'text-muted-foreground hover:bg-muted hover:text-foreground'
              )"
                @click="handleNavClick(item.href)"
              >
                <SafeIcon :name="item.icon" :size="18" />
                {{ item.name }}
              </button>
            </nav>
          </SheetContent>
        </Sheet>

        <!-- Logo -->
        <div 
          class="flex items-center gap-2 cursor-pointer shrink-0" 
          @click="handleNavClick('/quick-send')"
        >
          <div class="w-8 h-8 bg-primary rounded-lg flex items-center justify-center">
            <SafeIcon name="ShipWheel" :size="20" color="white" :stroke-width="2.5" />
          </div>
          <span class="hidden min-[420px]:inline text-lg sm:text-xl font-bold tracking-tight text-foreground whitespace-nowrap">织序传输</span>
        </div>

        <!-- Navigation -->
        <nav class="hidden lg:flex items-center h-full">
          <div 
            v-for="item in navigationItems" 
            :key="item.href"
            :class="cn(
              'h-[60px] flex items-center gap-2 px-4 text-sm font-medium transition-colors cursor-pointer border-b-2 border-transparent hover:text-primary',
              isActive(item.href) ? 'text-primary border-primary' : 'text-muted-foreground'
            )"
            @click="handleNavClick(item.href)"
          >
            <SafeIcon :name="item.icon" :size="17" />
            {{ item.name }}
          </div>
        </nav>
      </div>

      <div class="flex shrink-0 items-center gap-2">
        <Button v-if="!authStore.hasToken()" variant="outline" size="sm" @click="openLoginDialog">
          <SafeIcon name="LogIn" :size="16" class="mr-2" />
          登录
        </Button>
        <Button v-else variant="outline" size="sm" @click="handleLogout">
          <SafeIcon name="LogOut" :size="16" class="mr-2" />
          退出
        </Button>
      </div>
    </div>
  </header>

  <Dialog v-model:open="isLoginDialogOpen" @update:open="(open) => !open && stopLoginPolling()">
    <DialogContent class="max-w-sm">
      <DialogHeader>
        <DialogTitle>登录织序传输</DialogTitle>
        <DialogDescription>
          登录后可管理更长有效期的文件、收发记录和空间归档。
        </DialogDescription>
      </DialogHeader>

      <div class="flex min-h-[220px] flex-col items-center justify-center gap-4 rounded-lg border border-border bg-muted/30 p-5">
        <SafeIcon v-if="isLoadingLogin" name="Loader2" :size="28" class="animate-spin text-primary" />
        <img
          v-else-if="loginQrcode"
          :src="loginQrcode"
          alt="登录二维码"
          class="h-44 w-44 rounded-md bg-white p-2"
        />
        <SafeIcon v-else name="CircleAlert" :size="30" class="text-muted-foreground" />
        <p class="text-sm text-muted-foreground">{{ loginStatusText }}</p>
      </div>

      <DialogFooter>
        <Button variant="outline" :disabled="isLoadingLogin" @click="openLoginDialog">
          刷新二维码
        </Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>
