
<script setup lang="ts">
import { ref } from 'vue';
import { 
  Sidebar, 
  SidebarContent, 
  SidebarGroup, 
  SidebarGroupContent, 
  SidebarGroupLabel, 
  SidebarMenu, 
  SidebarMenuItem, 
  SidebarMenuButton 
} from '@/components/ui/sidebar';
import {
  Dialog,
  DialogScrollContent,
  DialogHeader,
  DialogTitle,
  DialogDescription,
} from '@/components/ui/dialog';
import SafeIcon from '@/components/common/SafeIcon.vue';
import FavoritesList from '@/components/favorites/FavoritesList.vue';
import BrowsingHistoryContent from '@/components/browsing_history/BrowsingHistoryContent.vue';
import { cn } from '@/lib/utils';

interface Props {
  currentPath?: string;
}

const props = defineProps<Props>();

interface MenuItem {
  title: string;
  icon: string;
  url: string;
  panel?: 'favorites' | 'history';
}

interface MenuGroup {
  label: string;
  items: MenuItem[];
}

const menuGroups: MenuGroup[] = [
  {
    label: '核心业务',
    items: [
      { title: '工作台概览', icon: 'BarChart4', url: './management-workbench' },
      { title: '产品管理', icon: 'Package', url: './product-management' },
      { title: '分类管理', icon: 'FolderTree', url: './category-management' },
      { title: '编辑主页', icon: 'Store', url: './home-settings' },
      { title: '客户选款', icon: 'ClipboardList', url: './customer-selections' },
    ],
  },
  {
    label: '个人中心',
    items: [
      { title: '我的收藏', icon: 'Heart', url: './favorites', panel: 'favorites' },
      { title: '我的选款', icon: 'ListChecks', url: './my-selections' },
      { title: '浏览足迹', icon: 'History', url: './browsing-history', panel: 'history' },
    ],
  },
  {
    label: '系统设置',
    items: [
      { title: '水印设置', icon: 'Stamp', url: './watermark-settings' },
      { title: '容量套餐', icon: 'Database', url: './billing-usage' },
      { title: '回收站', icon: 'Trash2', url: './recycling-bin' },
    ],
  },
];

const quickPanel = ref<'favorites' | 'history' | ''>('');

const isActive = (url: string) => {
  if (!props.currentPath) return false;
  const normalizedNav = url.replace(/^\.\//, '');
  const normalizedPath = props.currentPath.replace(/^\//, '');
  return normalizedPath === normalizedNav || (normalizedNav !== '' && normalizedPath.startsWith(normalizedNav));
};

const handleNavigate = (item: MenuItem) => {
  if (item.panel) {
    quickPanel.value = item.panel;
    return;
  }
  window.location.href = item.url;
};
</script>

<template>
  <Sidebar collapsible="none" variant="sidebar" class="h-full w-72 border-r border-border bg-card">
    <SidebarContent class="h-full overflow-y-auto px-4 py-6">
      <SidebarGroup v-for="group in menuGroups" :key="group.label">
        <SidebarGroupLabel class="px-4 text-[11px] font-bold uppercase tracking-wider text-muted-foreground/70 mb-2">
          {{ group.label }}
        </SidebarGroupLabel>
        <SidebarGroupContent>
          <SidebarMenu>
            <SidebarMenuItem v-for="item in group.items" :key="item.title">
              <SidebarMenuButton 
                :class="cn(
                  'w-full flex items-center gap-3 px-4 py-2.5 text-sm font-medium transition-colors rounded-md',
                  (isActive(item.url) || quickPanel === item.panel)
                    ? 'bg-secondary text-primary'
                    : 'text-muted-foreground hover:bg-muted hover:text-foreground'
                )"
                @click="handleNavigate(item)"
              >
                <SafeIcon 
                  :name="item.icon" 
                  :size="18" 
                  :class="(isActive(item.url) || quickPanel === item.panel) ? 'text-primary' : 'text-muted-foreground'"
                />
                <span>{{ item.title }}</span>
              </SidebarMenuButton>
            </SidebarMenuItem>
          </SidebarMenu>
        </SidebarGroupContent>
      </SidebarGroup>
    </SidebarContent>
  </Sidebar>

  <Dialog :open="quickPanel === 'favorites'" @update:open="(val) => quickPanel = val ? 'favorites' : ''">
    <DialogScrollContent class="max-h-[88vh] max-w-[1120px] overflow-hidden p-0">
      <div class="flex max-h-[88vh] min-h-[620px] flex-col">
        <DialogHeader class="border-b border-border px-6 py-5">
          <DialogTitle>我的收藏</DialogTitle>
          <DialogDescription>查看你收藏的主页、分类和产品</DialogDescription>
        </DialogHeader>
        <div class="min-h-0 flex-1 overflow-y-auto px-6 py-5">
          <FavoritesList v-if="quickPanel === 'favorites'" embedded />
        </div>
      </div>
    </DialogScrollContent>
  </Dialog>

  <Dialog :open="quickPanel === 'history'" @update:open="(val) => quickPanel = val ? 'history' : ''">
    <DialogScrollContent class="max-h-[88vh] max-w-[1120px] overflow-hidden p-0">
      <div class="flex max-h-[88vh] min-h-[620px] flex-col">
        <DialogHeader class="border-b border-border px-6 py-5">
          <DialogTitle>浏览足迹</DialogTitle>
          <DialogDescription>查看你浏览过的主页、分类和产品</DialogDescription>
        </DialogHeader>
        <div class="min-h-0 flex-1 overflow-y-auto px-6 py-5">
          <BrowsingHistoryContent v-if="quickPanel === 'history'" embedded />
        </div>
      </div>
    </DialogScrollContent>
  </Dialog>
</template>
