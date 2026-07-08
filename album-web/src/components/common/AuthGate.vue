<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { toast } from 'vue-sonner'
import SafeIcon from '@/components/common/SafeIcon.vue'
import LoginDialog from '@/components/common/LoginDialog.vue'
import { authStore, pcApi } from '@/lib/api'
import { isLocalMockEnabled } from '@/lib/mock-api'

const isReady = ref(false)
const isAuthenticated = ref(false)
const showLoginDialog = ref(false)

const enterMockSession = async () => {
  const data = await pcApi.checkLoginStatus('mock_scene')
  const token = data?.token || data?.access_token || data?.authorization
  if (token) authStore.setToken(token)
  if (data?.user || data?.user_info) authStore.setUser(data.user || data.user_info)
}

const verifyLogin = async () => {
  authStore.consumeCallbackToken()

  if (!authStore.isLoggedIn() && isLocalMockEnabled()) {
    await enterMockSession()
  }

  isAuthenticated.value = authStore.isLoggedIn()
  if (!isAuthenticated.value) {
    showLoginDialog.value = true
    isReady.value = true
    return
  }

  try {
    const profile = await pcApi.getCurrentUser()
    authStore.setUser(profile)
    isAuthenticated.value = true
  } catch (error: any) {
    authStore.clearToken()
    isAuthenticated.value = false
    showLoginDialog.value = true
    toast.error(error?.message || '登录已失效，请重新扫码')
  } finally {
    isReady.value = true
  }
}

const handleLoginSuccess = async () => {
  isReady.value = false
  await verifyLogin()
  showLoginDialog.value = false
}

onMounted(verifyLogin)
</script>

<template>
  <div v-if="!isReady" class="h-full flex items-center justify-center text-muted-foreground">
    <div class="flex items-center gap-2 text-sm">
      <SafeIcon name="Loader2" :size="18" class="animate-spin" />
      正在校验登录状态...
    </div>
  </div>

  <slot v-else-if="isAuthenticated" />

  <div v-else class="h-full flex items-center justify-center bg-background">
    <div class="surface-raised card-padding w-full max-w-sm text-center space-y-4">
      <div class="w-12 h-12 mx-auto rounded-lg bg-primary/10 flex items-center justify-center">
        <SafeIcon name="ShieldCheck" :size="24" class="text-primary" />
      </div>
      <div>
        <h2 class="text-section-title">请先登录</h2>
        <p class="text-caption mt-1">扫码后即可进入产品工作台</p>
      </div>
    </div>
  </div>

  <LoginDialog
    :open="showLoginDialog"
    @update:open="showLoginDialog = $event"
    @login-success="handleLoginSuccess"
  />
</template>
