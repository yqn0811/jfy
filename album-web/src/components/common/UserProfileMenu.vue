<script setup lang="ts">
import { computed, ref } from 'vue'
import { Button } from '@/components/ui/button'
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import { Avatar, AvatarImage, AvatarFallback } from '@/components/ui/avatar'
import HomeSettingsDialog from '@/components/home_settings/HomeSettingsDialog.vue'
import SafeIcon from '@/components/common/SafeIcon.vue'
import { authStore } from '@/lib/api'

interface Props {
  userInfo?: Record<string, any>
  fallbackName?: string
  fallbackAvatar?: string
  triggerClass?: string
  avatarClass?: string
  logoutRedirect?: string
}

const props = withDefaults(defineProps<Props>(), {
  userInfo: () => ({}),
  fallbackName: '家纺云用户',
  fallbackAvatar: '',
  triggerClass: 'relative h-10 w-10 rounded-full p-0',
  avatarClass: 'h-10 w-10 border border-border',
  logoutRedirect: './share-home',
})

const emit = defineEmits<{
  (e: 'profile-updated', profile: any): void
}>()

const homeSettingsOpen = ref(false)

const nickname = computed(() => {
  return props.userInfo?.nickname || props.fallbackName || '家纺云用户'
})

const companyName = computed(() => {
  return props.userInfo?.company_name || props.userInfo?.companyName || '未设置公司名称'
})

const avatarUrl = computed(() => {
  return props.userInfo?.avatar || props.userInfo?.company_logo || props.fallbackAvatar || ''
})

const fallbackInitial = computed(() => {
  return (nickname.value || companyName.value || '家').substring(0, 1)
})

const toNumber = (value: any) => {
  const num = Number(value)
  return Number.isFinite(num) ? num : 0
}

const formatBytes = (bytes: number) => {
  if (!bytes || bytes <= 0) return '0 KB'
  const units = ['B', 'KB', 'MB', 'GB', 'TB']
  let value = bytes
  let index = 0
  while (value >= 1024 && index < units.length - 1) {
    value /= 1024
    index += 1
  }
  const decimals = value >= 100 || index === 0 ? 0 : value >= 10 ? 1 : 2
  return `${value.toFixed(decimals)} ${units[index]}`
}

const parseStorageTextToBytes = (value: any) => {
  if (typeof value === 'number') return value
  const text = String(value || '').trim()
  if (!text) return 0
  const match = text.match(/^([\d.]+)\s*(B|KB|K|MB|M|GB|G|TB|T)?$/i)
  if (!match) return 0
  const amount = Number(match[1])
  if (!Number.isFinite(amount)) return 0
  const unit = (match[2] || 'MB').toUpperCase()
  const multiplier =
    unit === 'TB' || unit === 'T' ? 1024 ** 4 :
    unit === 'GB' || unit === 'G' ? 1024 ** 3 :
    unit === 'KB' || unit === 'K' ? 1024 :
    unit === 'B' ? 1 :
    1024 ** 2
  return amount * multiplier
}

const usedBytes = computed(() => toNumber(props.userInfo?.use_space))
const normalBytes = computed(() => toNumber(props.userInfo?.normal_space_bytes) || Math.max(usedBytes.value - trashBytes.value, 0))
const trashBytes = computed(() => toNumber(props.userInfo?.trash_space_bytes))
const totalBytes = computed(() => parseStorageTextToBytes(props.userInfo?.all_space || props.userInfo?.space_size))
const usedPercent = computed(() => {
  const backendPercent = toNumber(props.userInfo?.space_used)
  if (backendPercent > 0) return Math.min(Math.max(backendPercent, 0), 100)
  if (!totalBytes.value) return 0
  return Math.min(Math.max((usedBytes.value / totalBytes.value) * 100, 0), 100)
})
const normalPercent = computed(() => {
  if (!totalBytes.value) return usedPercent.value
  return Math.min(Math.max((normalBytes.value / totalBytes.value) * 100, 0), 100)
})
const trashPercent = computed(() => {
  if (!totalBytes.value) return 0
  return Math.min(Math.max((trashBytes.value / totalBytes.value) * 100, 0), Math.max(100 - normalPercent.value, 0))
})
const remainingBytes = computed(() => Math.max(totalBytes.value - usedBytes.value, 0))
const remainingPercent = computed(() => Math.max(100 - normalPercent.value - trashPercent.value, 0))
const totalStorageText = computed(() => props.userInfo?.all_space || (totalBytes.value ? formatBytes(totalBytes.value) : '-'))
const usedStorageText = computed(() => formatBytes(usedBytes.value))

const goToWorkbench = () => {
  window.location.href = './management-workbench'
}

const openHomeSettings = () => {
  homeSettingsOpen.value = true
}

const handleBilling = () => {
  window.location.href = './billing-usage'
}

const handleProfileSaved = (profile: any) => {
  emit('profile-updated', profile)
}

const handleLogout = () => {
  authStore.clearToken()
  window.location.href = props.logoutRedirect
}
</script>

<template>
  <DropdownMenu>
    <DropdownMenuTrigger as-child>
      <Button variant="ghost" :class="triggerClass">
        <Avatar :class="avatarClass">
          <AvatarImage :src="avatarUrl" :alt="nickname" />
          <AvatarFallback class="bg-primary/10 text-primary">{{ fallbackInitial }}</AvatarFallback>
        </Avatar>
      </Button>
    </DropdownMenuTrigger>
    <DropdownMenuContent class="w-[calc(100vw-2rem)] p-0 sm:w-[360px]" align="end">
      <DropdownMenuLabel class="font-normal p-0">
        <div class="space-y-4 px-5 py-4">
          <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
              <p class="truncate text-base font-semibold leading-tight text-foreground">{{ nickname }}</p>
              <p class="mt-1 text-sm text-muted-foreground">家纺云相册</p>
            </div>
            <div class="inline-flex max-w-[160px] items-center gap-1.5 rounded-md bg-muted px-2 py-1 text-xs font-medium text-muted-foreground">
              <SafeIcon name="Building2" :size="13" />
              <span class="truncate">{{ companyName }}</span>
            </div>
          </div>

          <div class="rounded-lg border border-border bg-card p-3">
            <div class="mb-2 flex items-center justify-between gap-3">
              <p class="text-sm font-semibold text-foreground">存储空间</p>
              <p class="shrink-0 text-sm text-muted-foreground">{{ usedStorageText }} / {{ totalStorageText }}</p>
            </div>
            <div class="flex h-2.5 overflow-hidden rounded-full bg-muted">
              <div class="bg-primary" :style="{ width: `${normalPercent}%` }" />
              <div class="bg-amber-500" :style="{ width: `${trashPercent}%` }" />
              <div class="bg-muted" :style="{ width: `${remainingPercent}%` }" />
            </div>
            <div class="mt-3 grid gap-1.5 text-xs text-muted-foreground">
              <span class="flex items-center gap-2">
                <i class="h-2 w-2 rounded-full bg-primary" />正常资源 {{ formatBytes(normalBytes) }}
              </span>
              <span class="flex items-center gap-2">
                <i class="h-2 w-2 rounded-full bg-amber-500" />回收站 {{ formatBytes(trashBytes) }}
              </span>
              <span class="flex items-center gap-2">
                <i class="h-2 w-2 rounded-full bg-muted-foreground/30" />剩余额度 {{ formatBytes(remainingBytes) }}
              </span>
            </div>
            <Button variant="outline" size="sm" class="mt-3 h-8 w-full gap-2 text-xs" @click.stop="handleBilling">
              <SafeIcon name="TrendingUp" :size="14" />
              升级容量
            </Button>
          </div>
        </div>
      </DropdownMenuLabel>
      <DropdownMenuSeparator class="m-0" />
      <div class="p-2">
        <DropdownMenuItem class="cursor-pointer gap-3 px-3 py-2.5 text-sm" @click="openHomeSettings">
          <SafeIcon name="Store" :size="18" />
          编辑主页
        </DropdownMenuItem>
        <DropdownMenuItem class="cursor-pointer gap-3 px-3 py-2.5 text-sm" @click="goToWorkbench">
          <SafeIcon name="LayoutDashboard" :size="18" />
          管理工作台
        </DropdownMenuItem>
        <DropdownMenuItem class="cursor-pointer gap-3 px-3 py-2.5 text-sm" @click="handleBilling">
          <SafeIcon name="CreditCard" :size="18" />
          版本与账单
        </DropdownMenuItem>
      </div>
      <DropdownMenuSeparator class="m-0" />
      <div class="p-2">
        <DropdownMenuItem class="cursor-pointer gap-3 px-3 py-2.5 text-sm text-destructive focus:text-destructive" @click="handleLogout">
          <SafeIcon name="LogOut" :size="18" />
          退出登录
        </DropdownMenuItem>
      </div>
    </DropdownMenuContent>
  </DropdownMenu>

  <HomeSettingsDialog
    v-model:open="homeSettingsOpen"
    @saved="handleProfileSaved"
  />
</template>
