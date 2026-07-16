<script setup lang="ts">
import { nextTick, onBeforeUnmount, ref, watch } from 'vue'
import { toast } from 'vue-sonner'
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog'
import { Button } from '@/components/ui/button'
import SafeIcon from '@/components/common/SafeIcon.vue'
import { AuthApi, type LoginOauthConfigVO } from '@/data/AuthApi'
import { authStore, getApiErrorMessage } from '@/lib/apiClient'

interface Props {
  open: boolean
}

declare global {
  interface Window {
    WxLogin?: new (config: Record<string, any>) => any
  }
}

const props = defineProps<Props>()
const emit = defineEmits<{
  (e: 'update:open', value: boolean): void
}>()

const LOGIN_MESSAGE_TYPE = 'file-web-wechat-login'
const LOGIN_STORAGE_KEY = 'file_web_wechat_login_result'
const WX_LOGIN_SCRIPT_ID = 'wx-login-script'
const WX_LOGIN_SCRIPT_SRC = 'https://res.wx.qq.com/connect/zh_CN/htmledition/js/wxLogin.js'

let wxLoginScriptPromise: Promise<void> | null = null
let popupWindow: Window | null = null
let popupCheckTimer: number | null = null

const status = ref<'idle' | 'loading' | 'ready' | 'popup' | 'error'>('idle')
const loginError = ref('')
const authUrl = ref('')
const containerId = `file-wx-login-${Math.random().toString(36).slice(2)}`

const closeDialog = () => emit('update:open', false)

const stopPopupWatcher = () => {
  if (popupCheckTimer) {
    window.clearInterval(popupCheckTimer)
    popupCheckTimer = null
  }
}

const loadWxLoginScript = () => {
  if (typeof window === 'undefined') return Promise.reject(new Error('browser unavailable'))
  if (window.WxLogin) return Promise.resolve()
  if (wxLoginScriptPromise) return wxLoginScriptPromise

  wxLoginScriptPromise = new Promise((resolve, reject) => {
    const existing = document.getElementById(WX_LOGIN_SCRIPT_ID) as HTMLScriptElement | null
    if (existing) {
      existing.addEventListener('load', () => resolve(), { once: true })
      existing.addEventListener('error', () => reject(new Error('wx login script failed')), { once: true })
      return
    }

    const script = document.createElement('script')
    script.id = WX_LOGIN_SCRIPT_ID
    script.src = WX_LOGIN_SCRIPT_SRC
    script.async = true
    script.onload = () => resolve()
    script.onerror = () => reject(new Error('wx login script failed'))
    document.head.appendChild(script)
  })

  return wxLoginScriptPromise
}

const renderWxLogin = (config: LoginOauthConfigVO) => {
  const container = document.getElementById(containerId)
  if (!container || !window.WxLogin) return false

  container.innerHTML = ''
  new window.WxLogin({
    self_redirect: true,
    id: containerId,
    appid: config.appid,
    scope: config.scope || 'snsapi_login',
    redirect_uri: encodeURIComponent(config.redirectUri),
    state: config.state,
    style: 'black',
    href: '',
  })
  status.value = 'ready'
  return true
}

const startPopupWatcher = () => {
  stopPopupWatcher()
  popupCheckTimer = window.setInterval(() => {
    if (popupWindow && popupWindow.closed) {
      stopPopupWatcher()
      if (props.open && !authStore.hasToken()) {
        status.value = 'ready'
      }
    }
  }, 600)
}

const openWechatWindow = () => {
  if (!authUrl.value) return
  const width = 520
  const height = 680
  const left = Math.max(0, window.screenX + (window.outerWidth - width) / 2)
  const top = Math.max(0, window.screenY + (window.outerHeight - height) / 2)
  popupWindow = window.open(
    authUrl.value,
    'fileWechatLogin',
    `width=${width},height=${height},left=${Math.round(left)},top=${Math.round(top)},resizable=yes,scrollbars=yes`
  )
  if (!popupWindow) {
    status.value = 'error'
    loginError.value = '登录窗口被浏览器拦截，请允许弹窗后重试'
    return
  }
  popupWindow.focus()
  status.value = 'popup'
  startPopupWatcher()
}

const finishLogin = (token: string) => {
  if (!token) {
    status.value = 'error'
    loginError.value = '登录失败，请重试'
    return
  }
  authStore.setToken(token)
  toast.success('登录成功')
  stopPopupWatcher()
  try {
    popupWindow?.close()
  } catch {
  }
  closeDialog()
}

const handleLoginMessage = (event: MessageEvent) => {
  if (event.origin !== window.location.origin) return
  const payload = event.data || {}
  if (payload.type !== LOGIN_MESSAGE_TYPE) return
  if (payload.token) {
    finishLogin(String(payload.token))
    return
  }
  status.value = 'error'
  loginError.value = '登录失败，请重试'
}

const consumeStoragePayload = (raw: string | null) => {
  if (!raw) return
  try {
    const payload = JSON.parse(raw)
    if (payload?.type === LOGIN_MESSAGE_TYPE && payload?.token) {
      finishLogin(String(payload.token))
    }
  } catch {
  } finally {
    localStorage.removeItem(LOGIN_STORAGE_KEY)
  }
}

const handleStorage = (event: StorageEvent) => {
  if (event.key !== LOGIN_STORAGE_KEY) return
  consumeStoragePayload(event.newValue)
}

const loadOauthLogin = async () => {
  loginError.value = ''
  authUrl.value = ''
  status.value = 'loading'
  stopPopupWatcher()

  try {
    const data = await AuthApi.getLoginOauthConfig(window.location.href)
    authUrl.value = data.authUrl
    if (!data.appid || !data.redirectUri || !data.state || !data.authUrl) {
      throw new Error('login config missing')
    }

    await nextTick()
    await loadWxLoginScript()
    await nextTick()
    if (renderWxLogin(data)) return

    status.value = 'ready'
  } catch (error) {
    if (authUrl.value) {
      status.value = 'ready'
      return
    }
    status.value = 'error'
    loginError.value = getApiErrorMessage(error, '登录服务暂不可用，请稍后再试')
  }
}

watch(
  () => props.open,
  (open) => {
    if (open) {
      window.addEventListener('message', handleLoginMessage)
      window.addEventListener('storage', handleStorage)
      consumeStoragePayload(localStorage.getItem(LOGIN_STORAGE_KEY))
      loadOauthLogin()
      return
    }

    window.removeEventListener('message', handleLoginMessage)
    window.removeEventListener('storage', handleStorage)
    stopPopupWatcher()
    status.value = 'idle'
  },
  { immediate: true }
)

onBeforeUnmount(() => {
  window.removeEventListener('message', handleLoginMessage)
  window.removeEventListener('storage', handleStorage)
  stopPopupWatcher()
})
</script>

<template>
  <Dialog :open="open" @update:open="emit('update:open', $event)">
    <DialogContent class="w-[calc(100vw-32px)] max-w-[430px] gap-5">
      <DialogHeader class="items-center text-center">
        <DialogTitle class="text-xl">微信登录</DialogTitle>
        <DialogDescription class="text-sm">
          {{ status === 'popup' ? '请在弹出的微信窗口完成登录' : '使用微信扫码后自动进入' }}
        </DialogDescription>
      </DialogHeader>

      <div class="flex flex-col items-center gap-4">
        <div class="relative h-[390px] w-[310px] overflow-hidden rounded-lg border border-border bg-white">
          <div :id="containerId" class="h-full w-full" />

          <div
            v-if="status === 'loading'"
            class="absolute inset-0 flex flex-col items-center justify-center bg-white text-muted-foreground"
          >
            <SafeIcon name="Loader2" :size="30" class="mb-2 animate-spin" />
            <p class="text-sm">正在加载微信登录</p>
          </div>

          <div
            v-else-if="status === 'popup'"
            class="absolute inset-0 flex flex-col items-center justify-center bg-white p-6 text-center"
          >
            <SafeIcon name="ExternalLink" :size="34" class="mb-3 text-primary" />
            <p class="text-sm font-medium text-foreground">登录窗口已打开</p>
            <p class="mt-1 text-xs text-muted-foreground">完成后会自动回到当前页面</p>
          </div>

          <div
            v-else-if="status === 'error'"
            class="absolute inset-0 flex flex-col items-center justify-center bg-white p-6 text-center"
          >
            <SafeIcon name="CircleAlert" :size="34" class="mb-3 text-destructive" />
            <p class="text-sm font-medium text-foreground">{{ loginError || '登录失败，请重试' }}</p>
            <Button class="mt-4" size="sm" variant="outline" @click="loadOauthLogin">
              <SafeIcon name="RefreshCw" :size="14" class="mr-2" />
              重新加载
            </Button>
          </div>
        </div>
      </div>

      <DialogFooter class="gap-2 sm:justify-between">
        <Button variant="ghost" @click="closeDialog">取消</Button>
        <Button v-if="authUrl" variant="outline" @click="openWechatWindow">
          <SafeIcon name="ExternalLink" :size="14" class="mr-2" />
          独立窗口登录
        </Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>
