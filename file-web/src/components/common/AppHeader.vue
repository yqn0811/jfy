
<script setup lang="ts">
import { computed } from 'vue';
import { 
  Search, 
  Bell, 
  HelpCircle, 
  ChevronDown, 
  Plus,
  Menu
} from 'lucide-vue-next';
import { 
  Input 
} from '@/components/ui/input';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
  Avatar,
  AvatarFallback,
  AvatarImage,
} from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import {
  Sheet,
  SheetContent,
  SheetHeader,
  SheetTitle,
  SheetTrigger,
} from '@/components/ui/sheet';
import SafeIcon from '@/components/common/SafeIcon.vue';
import { navigateTo } from '@/navigation';
import { cn } from '@/lib/utils';

const props = defineProps<{
  currentPath?: string;
}>();

// 模拟当前用户信息
const user = {
  name: '张三',
  avatar: 'https://spark-builder.s3.cn-north-1.amazonaws.com.cn/image/2026/7/8/a8771541-47d9-4f11-af06-52ae2ea02336.png',
  team: '设计工程部'
};

const navigationItems = [
  { name: '发文件', href: '/quick-send', icon: 'Send' },
  { name: '收文件', href: '/create-collection-task', icon: 'Download' },
  { name: '交付记录', href: '/delivery-records', icon: 'History' },
  { name: '空间', href: '/space-archive', icon: 'FolderOpen' },
];

const isActive = (href: string) => {
  if (!props.currentPath) return false;
  const current = props.currentPath.replace(/\/$/, '');
  const target = href.replace(/\/$/, '');
  return current === target || (target !== '/workbench' && current.startsWith(target));
};

const handleNavClick = (href: string) => {
  navigateTo(href);
};
</script>

<template>
  <header class="sticky top-0 z-50 w-full h-[60px] bg-card border-b border-border shadow-sm">
    <div class="h-full w-full max-w-[1440px] px-4 sm:px-6 lg:px-8 flex items-center justify-between mx-auto gap-3">
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
              'h-[60px] flex items-center px-4 text-sm font-medium transition-colors cursor-pointer border-b-2 border-transparent hover:text-primary',
              isActive(item.href) ? 'text-primary border-primary' : 'text-muted-foreground'
            )"
            @click="handleNavClick(item.href)"
          >
            {{ item.name }}
          </div>
        </nav>
      </div>

      <!-- Right: Search and Tools -->
      <div class="flex items-center gap-1 sm:gap-3 lg:gap-4 min-w-0">
        <!-- Global Search -->
        <div class="hidden md:flex items-center relative w-48 xl:w-64">
          <Search class="absolute left-3 w-4 h-4 text-muted-foreground" />
          <Input 
            placeholder="搜索任务、文件或记录..." 
            class="pl-9 h-9 bg-muted/50 border-transparent focus:bg-background transition-all"
          />
        </div>

        <div class="hidden sm:flex items-center gap-1 border-x border-border/60 px-2 mx-1">
          <Button variant="ghost" size="icon" class="text-muted-foreground hover:text-foreground relative">
            <Bell class="w-5 h-5" />
            <span class="absolute top-2.5 right-2.5 w-2 h-2 bg-destructive rounded-full border-2 border-card"></span>
          </Button>
          <Button variant="ghost" size="icon" class="text-muted-foreground hover:text-foreground">
            <HelpCircle class="w-5 h-5" />
          </Button>
        </div>

        <!-- Team & User Profile -->
        <div class="flex items-center gap-2 sm:gap-3">
          <DropdownMenu>
            <DropdownMenuTrigger as-child>
              <Button variant="ghost" class="hidden sm:flex items-center gap-2 px-2 hover:bg-muted">
                <span class="text-sm font-medium">{{ user?.team }}</span>
                <ChevronDown class="w-4 h-4 text-muted-foreground" />
              </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" class="w-48">
              <DropdownMenuLabel>切换团队</DropdownMenuLabel>
              <DropdownMenuSeparator />
              <DropdownMenuItem>设计工程部</DropdownMenuItem>
              <DropdownMenuItem>市场运营组</DropdownMenuItem>
              <DropdownMenuItem>技术研发中心</DropdownMenuItem>
              <DropdownMenuSeparator />
              <DropdownMenuItem class="text-primary">
                <Plus class="w-4 h-4 mr-2" /> 创建新团队
              </DropdownMenuItem>
            </DropdownMenuContent>
          </DropdownMenu>

          <DropdownMenu>
            <DropdownMenuTrigger as-child>
              <div class="cursor-pointer hover:opacity-80 transition-opacity">
                <Avatar class="h-9 w-9 border border-border">
                  <AvatarImage :src="user?.avatar" :alt="user?.name" />
                  <AvatarFallback>{{ user?.name?.substring(0, 1) || 'U' }}</AvatarFallback>
                </Avatar>
              </div>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" class="w-56">
              <DropdownMenuLabel class="font-normal">
                <div class="flex flex-col space-y-1">
                  <p class="text-sm font-medium leading-none">{{ user?.name }}</p>
                  <p class="text-xs leading-none text-muted-foreground">zhangsan@weave-order.com</p>
                </div>
              </DropdownMenuLabel>
              <DropdownMenuSeparator />
              <DropdownMenuItem @click="handleNavClick('/workbench')">
                <SafeIcon name="LayoutDashboard" :size="16" class="mr-2" />
                工作台
              </DropdownMenuItem>
              <DropdownMenuItem>账号设置</DropdownMenuItem>
              <DropdownMenuItem>安全钥匙</DropdownMenuItem>
              <DropdownMenuSeparator />
              <DropdownMenuItem class="text-destructive">退出登录</DropdownMenuItem>
            </DropdownMenuContent>
          </DropdownMenu>
        </div>
      </div>
    </div>
  </header>
</template>

<style scoped>
/* 确保搜索框在焦点时有一定的阴影提示 */
input:focus {
  @apply ring-1 ring-primary/20 border-primary/30;
}
</style>
