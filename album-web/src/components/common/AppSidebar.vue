
<script setup lang="ts">
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
import SafeIcon from '@/components/common/SafeIcon.vue';
import { cn } from '@/lib/utils';

interface Props {
  currentPath?: string;
}

const props = defineProps<Props>();

interface MenuItem {
  title: string;
  icon: string;
  url: string;
}

interface MenuGroup {
  label: string;
  items: MenuItem[];
}

const menuGroups: MenuGroup[] = [
  {
    label: '核心业务',
    items: [
      { title: '工作台概览', icon: 'BarChart4', url: './management-workbench.html' },
      { title: '产品管理', icon: 'Package', url: './product-management.html' },
      { title: '分类管理', icon: 'FolderTree', url: './category-management.html' },
      { title: '资源库', icon: 'Image', url: './resource-library-picker.html?targetType=colorChart' },
    ],
  },
  {
    label: '个人中心',
    items: [
      { title: '我的收藏', icon: 'Heart', url: './favorites.html' },
      { title: '浏览足迹', icon: 'History', url: './browsing-history.html' },
    ],
  },
  {
    label: '系统设置',
    items: [
      { title: '水印设置', icon: 'Stamp', url: './watermark-settings.html' },
      { title: '容量套餐', icon: 'Database', url: './billing-usage.html' },
      { title: '回收站', icon: 'Trash2', url: './recycling-bin.html' },
    ],
  },
];

const isActive = (url: string) => {
  if (!props.currentPath) return false;
  const normalizedNav = url.replace(/^\.\//, '').replace(/\.html$/, '');
  const normalizedPath = props.currentPath.replace(/^\//, '').replace(/\.html$/, '');
  return normalizedPath === normalizedNav || (normalizedNav !== '' && normalizedPath.startsWith(normalizedNav));
};

const handleNavigate = (url: string) => {
  window.location.href = url;
};
</script>

<template>
  <Sidebar variant="inset" class="h-full border-r border-border bg-card">
    <SidebarContent class="py-4">
      <SidebarGroup v-for="group in menuGroups" :key="group.label">
        <SidebarGroupLabel class="px-4 text-[11px] font-bold uppercase tracking-wider text-muted-foreground/70 mb-2">
          {{ group.label }}
        </SidebarGroupLabel>
        <SidebarGroupContent>
          <SidebarMenu>
            <SidebarMenuItem v-for="item in group.items" :key="item.title">
              <SidebarMenuButton 
                :class="cn(
                  'w-full flex items-center gap-3 px-4 py-2 text-sm font-medium transition-colors rounded-none border-l-4 border-transparent',
                  isActive(item.url) 
                    ? 'bg-secondary text-primary border-primary' 
                    : 'text-muted-foreground hover:bg-muted hover:text-foreground'
                )"
                @click="handleNavigate(item.url)"
              >
                <SafeIcon 
                  :name="item.icon" 
                  :size="18" 
                  :class="isActive(item.url) ? 'text-primary' : 'text-muted-foreground'" 
                />
                <span>{{ item.title }}</span>
              </SidebarMenuButton>
            </SidebarMenuItem>
          </SidebarMenu>
        </SidebarGroupContent>
      </SidebarGroup>
    </SidebarContent>
  </Sidebar>
</template>
