
<script setup lang="ts">
import { 
  Dialog, 
  DialogContent, 
  DialogHeader, 
  DialogTitle, 
  DialogDescription,
  DialogFooter
} from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import SafeIcon from '@/components/common/SafeIcon.vue';
import { toast } from 'vue-sonner';
import { authStore, pcApi } from '@/lib/api';

interface Props {
  open: boolean;
}

const props = defineProps<Props>();
const emit = defineEmits(['update:open', 'login-success']);

const isClient = ref(true);
const loginStatus = ref<'loading' | 'scanning' | 'expired' | 'success'>('loading');
const qrCodeUrl = ref('');
const scene = ref('');
let pollTimer: ReturnType<typeof window.setInterval> | null = null;

const stopPolling = () => {
  if (pollTimer) {
    window.clearInterval(pollTimer);
    pollTimer = null;
  }
};

const resolveQrPayload = (data: any) => {
  qrCodeUrl.value = data?.qrcode || data?.qrcode_url || data?.data_url || data?.url || data?.image || data?.image_url || '';
  scene.value = data?.scene || data?.ticket || data?.key || data?.uuid || '';
};

const completeLogin = async (token: string, user: any = null) => {
  authStore.setToken(token);
  if (user) authStore.setUser(user);
  try {
    const profile = await pcApi.getCurrentUser();
    authStore.setUser(profile);
  } catch {
    // token 已保存，资料接口失败不阻断登录完成
  }
  loginStatus.value = 'success';
  toast.success('登录成功');
  stopPolling();
  setTimeout(() => {
    emit('update:open', false);
    emit('login-success');
    loginStatus.value = 'scanning';
  }, 800);
};

const startPolling = () => {
  stopPolling();
  if (!scene.value) return;
  pollTimer = window.setInterval(async () => {
    try {
      const data = await pcApi.checkLoginStatus(scene.value);
      const status = String(data?.status || data?.login_status || '').toLowerCase();
      const token = data?.token || data?.access_token || data?.authorization;
      if (token) {
        await completeLogin(token, data?.user || data?.user_info || null);
        return;
      }
      if (status === 'expired' || Number(data?.expired || 0) === 1) {
        loginStatus.value = 'expired';
        stopPolling();
      }
    } catch (error: any) {
      const message = error?.message || '';
      if (message.includes('过期') || message.includes('失效')) {
        loginStatus.value = 'expired';
        stopPolling();
      }
    }
  }, 1800);
};

const loadQrcode = async () => {
  loginStatus.value = 'loading';
  stopPolling();
  try {
    const data = await pcApi.getLoginQrcode();
    resolveQrPayload(data);
    if (!qrCodeUrl.value) {
      throw new Error('二维码生成失败');
    }
    loginStatus.value = 'scanning';
    startPolling();
  } catch (error: any) {
    loginStatus.value = 'expired';
    toast.error(error?.message || '二维码生成失败');
  }
};

onMounted(() => {
  isClient.value = false;
  setTimeout(() => {
    isClient.value = true;
  }, 0);
});

watch(() => props.open, (open) => {
  if (open) {
    loadQrcode();
  } else {
    stopPolling();
  }
}, { immediate: true });

const handleRefresh = () => {
  loadQrcode();
};

const handleUpdateOpen = (value: boolean) => {
  emit('update:open', value);
};

</script>

<template>
  <Dialog :open="open" @update:open="handleUpdateOpen">
    <DialogContent class="sm:max-w-[400px] gap-6">
      <DialogHeader class="items-center text-center">
        <DialogTitle class="text-xl">微信扫码安全登录</DialogTitle>
        <DialogDescription class="text-sm">
          扫码关注公众号，实时接收订单及产品更新通知
        </DialogDescription>
      </DialogHeader>

      <div class="flex flex-col items-center justify-center space-y-4 py-4">
        <div class="relative w-48 h-48 border border-border rounded-lg bg-white p-2 flex items-center justify-center overflow-hidden">
          <!-- 二维码图像 -->
          <img 
            v-if="loginStatus !== 'success' && qrCodeUrl"
            :src="qrCodeUrl" 
            alt="WeChat Login QR Code"
            class="w-full h-full object-contain"
            :class="loginStatus === 'expired' && 'blur-[2px] opacity-20'"
          />
          <div v-else-if="loginStatus === 'loading'" class="flex flex-col items-center text-muted-foreground">
            <SafeIcon name="Loader2" :size="30" class="animate-spin mb-2" />
            <p class="text-sm">二维码生成中</p>
          </div>
          
          <!-- 成功状态覆盖 -->
          <div v-if="loginStatus === 'success'" class="flex flex-col items-center animate-in fade-in zoom-in duration-300">
            <div class="w-16 h-16 bg-primary/10 rounded-full flex items-center justify-center mb-3">
              <SafeIcon name="Check" :size="32" class="text-primary" />
            </div>
            <p class="text-primary font-medium">登录成功</p>
          </div>

          <!-- 过期遮罩 -->
          <div 
            v-if="loginStatus === 'expired'" 
            class="absolute inset-0 flex flex-col items-center justify-center bg-background/60 backdrop-blur-[1px]"
          >
            <p class="text-sm font-medium mb-3">二维码已失效</p>
            <Button size="sm" variant="primary" @click="handleRefresh">
              <SafeIcon name="RefreshCw" :size="14" class="mr-2" />
              点击刷新
            </Button>
          </div>
        </div>

        <div class="flex flex-col items-center space-y-2">
          <p v-if="loginStatus === 'scanning'" class="text-sm flex items-center text-muted-foreground">
            <SafeIcon name="Loader2" :size="14" class="mr-2 animate-spin" />
            正在等待扫码...
          </p>
        </div>
      </div>

      <DialogFooter class="sm:justify-center flex-col items-center gap-2 border-t pt-4">
        <div class="flex items-center text-xs text-muted-foreground">
          <SafeIcon name="ShieldCheck" :size="12" class="mr-1 text-primary" />
          <span>家纺云技术提供安全加密保护</span>
        </div>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>
