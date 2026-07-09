
<script setup lang="ts">
import { computed } from 'vue';
import { 
  Search, 
  Bell, 
  HelpCircle, 
  ChevronDown, 
  Plus,
  LayoutDashboard,
  Send,
  Download,
  History,
  LayoutTemplate,
  FolderOpen
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
import SafeIcon from '@/components/common/SafeIcon.vue';
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
  { name: '工作台', href: './workbench.html', icon: 'LayoutDashboard' },
  { name: '发文件', href: './quick-send.html', icon: 'Send' },
  { name: '收文件', href: './create-collection-task.html', icon: 'Download' },
  { name: '交付记录', href: './delivery-records.html', icon: 'History' },
  { name: '空间', href: './space-archive.html', icon: 'FolderOpen' },
];

const isActive = (href: string) => {
  if (!props.currentPath) return false;
  const current = props.currentPath.replace(/^\//, '').replace(/\.html$/, '');
  const target = href.replace(/^\.\//, '').replace(/\.html$/, '');
  return current === target || (target !== 'workbench' && current.startsWith(target));
};

const handleNavClick = (href: string) => {
  window.location.href = href;
};
</script>

<template>
  <header class="sticky top-0 z-50 w-full h-[60px] bg-card border-b border-border shadow-sm">
    <div class="h-full px-6 flex items-center justify-between mx-auto">
      <!-- Left: Logo and Main Nav -->
      <div class="flex items-center gap-8 h-full">
        <!-- Logo -->
        <div 
          class="flex items-center gap-2 cursor-pointer shrink-0" 
          @click="handleNavClick('./workbench.html')"
        >
          <div class="w-8 h-8 bg-primary rounded-lg flex items-center justify-center">
            <SafeIcon name="ShipWheel" :size="20" color="white" :stroke-width="2.5" />
          </div>
          <span class="text-xl font-bold tracking-tight text-foreground whitespace-nowrap">织序传输</span>
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
      <div class="flex items-center gap-4">
        <!-- Global Search -->
        <div class="hidden md:flex items-center relative w-64">
          <Search class="absolute left-3 w-4 h-4 text-muted-foreground" />
          <Input 
            placeholder="搜索任务、文件或记录..." 
            class="pl-9 h-9 bg-muted/50 border-transparent focus:bg-background transition-all"
          />
        </div>

        <div class="flex items-center gap-1 border-x border-border/60 px-2 mx-1">
          <Button variant="ghost" size="icon" class="text-muted-foreground hover:text-foreground relative">
            <Bell class="w-5 h-5" />
            <span class="absolute top-2.5 right-2.5 w-2 h-2 bg-destructive rounded-full border-2 border-card"></span>
          </Button>
          <Button variant="ghost" size="icon" class="text-muted-foreground hover:text-foreground">
            <HelpCircle class="w-5 h-5" />
          </Button>
        </div>

        <!-- Team & User Profile -->
        <div class="flex items-center gap-3">
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
              <DropdownMenuItem @click="handleNavClick('./workbench.html')">个人中心</DropdownMenuItem>
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
    