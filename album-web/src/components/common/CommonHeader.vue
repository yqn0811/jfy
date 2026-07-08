
<script setup lang="ts">
import { ref, computed, onMounted } from "vue";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Avatar, AvatarImage, AvatarFallback } from "@/components/ui/avatar";
import {
  Dialog,
  DialogScrollContent,
  DialogHeader,
  DialogTitle,
  DialogDescription,
} from "@/components/ui/dialog";
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu";
import SafeIcon from "@/components/common/SafeIcon.vue";
import BrandMark from "@/components/common/BrandMark.vue";
import LoginDialog from "@/components/common/LoginDialog.vue";
import FavoritesList from "@/components/favorites/FavoritesList.vue";
import BrowsingHistoryContent from "@/components/browsing_history/BrowsingHistoryContent.vue";
import { cn } from "@/lib/utils";
import { authStore, pcApi } from "@/lib/api";

interface Props {
  isAuthenticated?: boolean;
  userName?: string;
  userAvatar?: string;
  currentPath?: string;
}

const props = withDefaults(defineProps<Props>(), {
  isAuthenticated: false,
  userName: "访客用户",
  userAvatar: "",
  currentPath: "./share-home.html",
});

const searchQuery = ref("");
const showLoginDialog = ref(false);
const quickPanel = ref<"favorites" | "history" | "">("");
const userInfo = ref<any>({});
const isHydrated = ref(false);
const loggedIn = computed(() => isHydrated.value && (props.isAuthenticated || authStore.isLoggedIn()));
const displayName = computed(() => userInfo.value?.company_name || userInfo.value?.nickname || props.userName);
const displayAvatar = computed(() => userInfo.value?.avatar || userInfo.value?.company_logo || props.userAvatar);

const navItems = [
  { name: "我的收藏", href: "./favorites.html", icon: "Heart", panel: "favorites" as const },
  { name: "浏览足迹", href: "./browsing-history.html", icon: "History", panel: "history" as const },
];

const handleSearch = () => {
  if (searchQuery.value.trim()) {
    window.location.href = `./share-home.html?keyword=${encodeURIComponent(searchQuery.value.trim())}`;
  }
};

const handleNavigate = (href: string) => {
  window.location.href = href;
};

const handleNavClick = (item: typeof navItems[number]) => {
  quickPanel.value = item.panel;
};

const handleMerchantLogin = () => {
  if (loggedIn.value) {
    handleNavigate('./management-workbench.html');
    return;
  }
  showLoginDialog.value = true;
};

const handleLogout = () => {
  authStore.clearToken();
  userInfo.value = {};
  window.location.reload();
};

const handleLoginSuccess = async () => {
  userInfo.value = authStore.getUser<any>() || {};
  try {
    const profile = await pcApi.getCurrentUser();
    userInfo.value = profile || {};
    authStore.setUser(profile);
  } catch {
    // ignore
  }
  handleNavigate('./management-workbench.html');
};

const isActive = (href: string) => {
  return props.currentPath?.includes(href.replace("./", ""));
};

onMounted(() => {
  authStore.consumeCallbackToken();
  isHydrated.value = true;
  userInfo.value = authStore.getUser<any>() || {};
  if (authStore.isLoggedIn()) {
    pcApi.getCurrentUser()
      .then((profile) => {
        userInfo.value = profile || {};
        authStore.setUser(profile);
      })
      .catch(() => {
        // 头部资料加载失败不影响页面主体登录校验
      });
  }
});
</script>

<template>
  <header class="sticky top-0 z-50 w-full border-b border-border bg-background/95 backdrop-blur supports-[backdrop-filter]:bg-background/60">
    <div class="h-[var(--header-height)] page-container px-8 flex items-center justify-between gap-8">
      <!-- Logo Section -->
      <div 
        class="flex items-center gap-2 cursor-pointer shrink-0" 
        @click="handleNavigate('./share-home.html')"
      >
        <BrandMark :size="32" />
        <span class="text-xl font-bold tracking-tight text-foreground">家纺云相册</span>
      </div>

      <!-- Search Bar -->
      <div class="flex-1 max-w-xl relative">
        <SafeIcon 
          name="Search" 
          class="absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground" 
          :size="18" 
        />
        <Input
          v-model="searchQuery"
          placeholder="搜索产品名称..."
          class="pl-10 h-10 w-full bg-muted/50 border-none focus-visible:ring-1 focus-visible:ring-primary"
          @keyup.enter="handleSearch"
        />
      </div>

      <!-- Navigation & User Actions -->
      <div class="flex items-center gap-2">
        <nav class="flex items-center gap-1 mr-4">
          <Button
            v-for="item in navItems"
            :key="item.href"
            variant="ghost"
            :class="cn(
              'flex items-center gap-2 px-4 h-10 text-muted-foreground hover:text-primary hover:bg-primary/5 transition-all',
              (isActive(item.href) || quickPanel === item.panel) && 'text-primary bg-primary/10'
            )"
            @click="handleNavClick(item)"
          >
            <SafeIcon :name="item.icon" :size="20" />
            <span class="text-sm font-medium">{{ item.name }}</span>
          </Button>
        </nav>

        <slot />

        <div class="h-8 w-px bg-border mx-2" />

        <!-- Profile Section -->
        <div v-if="loggedIn">
          <DropdownMenu>
            <DropdownMenuTrigger as-child>
              <Button variant="ghost" class="relative h-10 w-10 rounded-full p-0">
                <Avatar class="h-10 w-10 border border-border">
                  <AvatarImage :src="displayAvatar" :alt="displayName" />
                  <AvatarFallback class="bg-primary/10 text-primary">{{ displayName.substring(0, 1) }}</AvatarFallback>
                </Avatar>
              </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent class="w-56" align="end">
              <DropdownMenuLabel class="font-normal">
                <div class="flex flex-col space-y-1">
                  <p class="text-sm font-medium leading-none">{{ displayName }}</p>
                  <p class="text-xs leading-none text-muted-foreground">欢迎使用家纺云相册</p>
                </div>
              </DropdownMenuLabel>
              <DropdownMenuSeparator />
              <DropdownMenuItem @click="handleNavigate('./management-workbench.html')" class="cursor-pointer">
                <SafeIcon name="LayoutDashboard" class="mr-2 h-4 w-4" />
                <span>管理工作台</span>
              </DropdownMenuItem>
              <DropdownMenuItem class="cursor-pointer text-destructive focus:text-destructive" @click="handleLogout">
                <SafeIcon name="LogOut" class="mr-2 h-4 w-4" />
                <span>退出登录</span>
              </DropdownMenuItem>
            </DropdownMenuContent>
          </DropdownMenu>
        </div>
        <div v-else>
          <Button 
            variant="default" 
            size="sm" 
            class="h-9 px-6 rounded-full"
            @click="handleMerchantLogin"
          >
            商家登录
          </Button>
        </div>
      </div>
    </div>
    <LoginDialog
      :open="showLoginDialog"
      @update:open="showLoginDialog = $event"
      @login-success="handleLoginSuccess"
    />

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
  </header>
</template>
