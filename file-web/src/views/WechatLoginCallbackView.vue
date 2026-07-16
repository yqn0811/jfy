<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { Button } from '@/components/ui/button'
import SafeIcon from '@/components/common/SafeIcon.vue'
import { AuthApi } from '@/data/AuthApi'
import { authStore, getApiErrorMessage } from '@/lib/apiClient'
import { navigateTo } from '@/navigation'

const LOGIN_MESSAGE_TYPE = 'file-web-wechat-login'
const LOGIN_STORAGE_KEY = 'file_web_wechat_login_result'

const status = ref<'loading' | 'success' | 'error'>('loading')
const message = ref('正在完成登录')
const redirectUrl = ref('')

const isEmbedded = computed(() => {
  if (typeof window === 'undefined') return false
  return window.parent && window.parent !== window
})

const notifyLoginResult = (payload: Record<string, any>) => {
  const messagePayload = {
    type: LOGIN_MESSAGE_TYPE,
    time: Date.now(),
    ...payload,
  }

  try {
    localStorage.setItem(LOGIN_STORAGE_KEY, JSON.stringify(messagePayload))
  } catch {
  }

  try {
    window.opener?.postMessage(messagePayload, window.location.origin)
  } catch {
  }

  try {
    if (window.parent && window.parent !== window) {
      window.parent.postMessage(messagePayload, window.location.origin)
    }
  } catch {
  }
}

const completeStandaloneLogin = () => {
  if (isEmbedded.value || window.opener) return
  const target = redirectUrl.value || '/quick-send'
  try {
    const url = new URL(target, window.location.origin)
    if (url.origin === window.location.origin) {
      navigateTo(`${url.pathname}${url.search}${url.hash}`)
      return
    }
  } catch {
  }
  navigateTo('/quick-send')
}

const closeWindow = () => {
  try {
    window.close()
  } catch {
  }
}

onMounted(async () => {
  const search = new URLSearchParams(window.location.search)
  const code = search.get('code') || ''
  const state = search.get('state') || ''

  if (!code || !state) {
    status.value = 'error'
    message.value = '登录失败，请重试'
    notifyLoginResult({ error: 'login_failed' })
    return
  }

  try {
    const data = await AuthApi.exchangeWechatLoginCode(code, state)
    if (!data.token) {
      throw new Error('empty token')
    }

    redirectUrl.value = data.redirect || ''
    authStore.setToken(data.token)
    status.value = 'success'
    message.value = '登录成功'
    notifyLoginResult({ token: data.token, redirect: data.redirect })

    window.setTimeout(() => {
      if (window.opener) {
        closeWindow()
        return
      }
      completeStandaloneLogin()
    }, 500)
  } catch (error) {
    status.value = 'error'
    message.value = getApiErrorMessage(error, '登录失败，请重试')
    notifyLoginResult({ error: 'login_failed' })
  }
})
</script>

<template>
  <main class="min-h-screen bg-background flex items-center justify-center p-6">
    <section class="w-full max-w-sm rounded-lg border border-border bg-card p-6 text-center shadow-sm">
      <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-primary/10 text-primary">
        <SafeIcon v-if="status === 'loading'" name="Loader2" :size="26" class="animate-spin" />
        <SafeIcon v-else-if="status === 'success'" name="CheckCircle2" :size="26" />
        <SafeIcon v-else name="CircleAlert" :size="26" class="text-destructive" />
      </div>
      <h1 class="text-lg font-semibold text-foreground">{{ message }}</h1>
      <p class="mt-2 text-sm text-muted-foreground">
        {{ status === 'error' ? '请关闭窗口后重新登录' : '窗口会自动关闭' }}
      </p>
      <Button v-if="status !== 'loading'" class="mt-5 w-full" variant="outline" @click="closeWindow">
        关闭窗口
      </Button>
    </section>
  </main>
</template>
