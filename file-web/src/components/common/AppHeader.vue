
<script setup lang="ts">
import { ref } from 'vue';
import { Menu } from 'lucide-vue-next';
import { toast } from 'vue-sonner';
import { Button } from '@/components/ui/button';
import {
  Sheet,
  SheetContent,
  SheetHeader,
  SheetTitle,
  SheetTrigger,
} from '@/components/ui/sheet';
import SafeIcon from '@/components/common/SafeIcon.vue';
import WechatLoginDialog from '@/components/common/WechatLoginDialog.vue';
import { authStore } from '@/lib/apiClient';
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

const startWechatLogin = () => {
  if (authStore.hasToken()) return;
  isLoginDialogOpen.value = true;
};

const handleLogout = () => {
  authStore.clearToken();
  toast.success('已退出登录');
};
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
        <Button v-if="!authStore.hasToken()" variant="outline" size="sm" @click="startWechatLogin">
          <SafeIcon name="LogIn" :size="16" class="mr-2" />
          登录
        </Button>
        <Button v-else variant="outline" size="sm" @click="handleLogout">
          <SafeIcon name="LogOut" :size="16" class="mr-2" />
          退出
        </Button>
      </div>
    </div>
    <WechatLoginDialog v-model:open="isLoginDialogOpen" />
  </header>
</template>
