<script setup lang="ts">
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogDescription,
} from '@/components/ui/dialog'
import { Button } from '@/components/ui/button'
import SafeIcon from '@/components/common/SafeIcon.vue'
import { toast } from 'vue-sonner'
import { isMockEnabled, pcApi } from '@/lib/api'

interface Props {
  open: boolean
}

declare global {
  interface Window {
    WxLogin?: new (config: Record<string, any>) => any
  }
}

const props = defineProps<Props>()
const emit = defineEmits(['update:open', 'login-success'])

const status = ref<'loading' | 'ready' | 'error' | 'local'>('loading')
const loginError = ref('')
const authUrl = ref('')
const containerId = `wx-login-${Math.random().toString(36).slice(2)}`
let scriptPollTimer: ReturnType<typeof window.setInterval> | null = null

const stopWaitingScript = () => {
  if (scriptPollTimer) {
    window.clearInterval(scriptPollTimer)
    scriptPollTimer = null
  }
}

const getRedirectUrl = () => {
  if (typeof window === 'undefined') return ''
  return window.location.href
}

const renderWxLogin = (config: any) => {
  const container = document.getElementById(containerId)
  if (!container) return false
  container.innerHTML = ''

  if (!window.WxLogin) return false

  new window.WxLogin({
    self_redirect: false,
    id: containerId,
    appid: config.appid,
    scope: config.scope || 'snsapi_login',
    redirect_uri: encodeURIComponent(config.redirect_uri),
    state: config.state,
    style: '',
    href: '',
  })
  status.value = 'ready'
  return true
}

const openAuthUrl = () => {
  if (!authUrl.value) return
  window.location.href = authUrl.value
}

const enterLocalMock = async () => {
  try {
    const data = await pcApi.checkLoginStatus('mock_scene')
    const token = data?.token || data?.access_token || data?.authorization
    if (!token) throw new Error('本地登录失败')
    localStorage.setItem('jfyuntu_pc_token', token)
    localStorage.setItem('token', token)
    if (data?.user || data?.user_info) {
      const user = JSON.stringify(data.user || data.user_info)
      localStorage.setItem('jfyuntu_pc_user', user)
      localStorage.setItem('userInfo', user)
    }
    emit('update:open', false)
    emit('login-success')
  } catch (error: any) {
    toast.error(error?.message || '本地登录失败')
  }
}

const loadOauthLogin = async () => {
  stopWaitingScript()
  loginError.value = ''
  authUrl.value = ''
  status.value = 'loading'

  if (isMockEnabled()) {
    status.value = 'local'
    return
  }

  try {
    const data = await pcApi.getLoginOauthConfig(getRedirectUrl())
    authUrl.value = data?.auth_url || ''
    if (!data?.appid || !data?.redirect_uri || !data?.state) {
      throw new Error('微信登录配置缺失')
    }

    await nextTick()
    if (renderWxLogin(data)) return

    let retryTimes = 0
    scriptPollTimer = window.setInterval(() => {
      retryTimes += 1
      if (renderWxLogin(data) || retryTimes >= 20) {
        stopWaitingScript()
        if (retryTimes >= 20 && !window.WxLogin) {
          status.value = authUrl.value ? 'ready' : 'error'
          if (!authUrl.value) loginError.value = '微信登录组件加载失败，请刷新页面重试'
        }
      }
    }, 250)
  } catch (error: any) {
    status.value = 'error'
    loginError.value = error?.message || '微信登录初始化失败，请稍后重试'
    toast.error(loginError.value)
  }
}

watch(
  () => props.open,
  (open) => {
    if (open) {
      loadOauthLogin()
    } else {
      stopWaitingScript()
    }
  },
  { immediate: true }
)

onBeforeUnmount(stopWaitingScript)
</script>

<template>
  <Dialog :open="open" @update:open="emit('update:open', $event)">
    <DialogContent class="sm:max-w-[430px] gap-6">
      <DialogHeader class="items-center text-center">
        <DialogTitle class="text-xl">微信扫码登录</DialogTitle>
        <DialogDescription class="text-sm">
          使用微信扫码后自动进入
        </DialogDescription>
      </DialogHeader>

      <div class="flex flex-col items-center justify-center gap-4 py-2">
        <div class="relative h-[310px] w-[310px] overflow-hidden rounded-lg border border-border bg-white">
          <div :id="containerId" class="h-full w-full" />

          <div
            v-if="status === 'loading'"
            class="absolute inset-0 flex flex-col items-center justify-center bg-white text-muted-foreground"
          >
            <SafeIcon name="Loader2" :size="30" class="mb-2 animate-spin" />
            <p class="text-sm">正在加载微信登录</p>
          </div>

          <div
            v-else-if="status === 'local'"
            class="absolute inset-0 flex flex-col items-center justify-center bg-white p-6 text-center"
          >
            <SafeIcon name="MonitorCog" :size="34" class="mb-3 text-primary" />
            <p class="text-sm font-medium text-foreground">本地开发环境</p>
            <p class="mt-1 text-xs text-muted-foreground">微信回调不支持 localhost，可使用本地会话预览页面</p>
            <Button class="mt-4" size="sm" @click="enterLocalMock">
              进入本地预览
            </Button>
          </div>

          <div
            v-else-if="status === 'error'"
            class="absolute inset-0 flex flex-col items-center justify-center bg-white p-6 text-center"
          >
            <SafeIcon name="CircleAlert" :size="34" class="mb-3 text-destructive" />
            <p class="text-sm font-medium text-foreground">{{ loginError || '微信登录加载失败' }}</p>
            <Button class="mt-4" size="sm" variant="outline" @click="loadOauthLogin">
              <SafeIcon name="RefreshCw" :size="14" class="mr-2" />
              重新加载
            </Button>
          </div>
        </div>

        <Button v-if="authUrl && status !== 'local'" variant="outline" class="w-[310px]" @click="openAuthUrl">
          <SafeIcon name="ExternalLink" :size="14" class="mr-2" />
          打开微信登录页面
        </Button>
      </div>
    </DialogContent>
  </Dialog>
</template>
