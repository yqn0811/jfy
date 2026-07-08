
<script setup lang="ts">
import { ref } from 'vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import SafeIcon from '@/components/common/SafeIcon.vue'
import { toast } from 'vue-sonner'

interface Props {
  required?: boolean
}

withDefaults(defineProps<Props>(), {
  required: true
})

const emit = defineEmits<{
  (e: 'verified'): void
}>()

const password = ref('')
const showPassword = ref(false)
const isVerifying = ref(false)

const handleVerify = async () => {
  if (!password.value.trim()) {
    toast.error('请输入访问密码')
    return
  }

  isVerifying.value = true
  
  setTimeout(() => {
    if (password.value === 'A7K3') {
      emit('verified')
      toast.success('密码验证成功')
    } else {
      toast.error('密码错误，请重试')
      password.value = ''
    }
    isVerifying.value = false
  }, 500)
}

const handleKeyDown = (e: KeyboardEvent) => {
  if (e.key === 'Enter' && !isVerifying.value) {
    handleVerify()
  }
}
</script>

<template>
  <div class="surface-base card-padding space-y-4">
    <div class="flex items-center gap-2 mb-4">
      <SafeIcon name="Lock" :size="20" class="text-primary" />
      <h3 class="text-section-title">访问密码验证</h3>
    </div>

    <div class="space-y-3">
      <div class="relative">
        <Input
          :type="showPassword ? 'text' : 'password'"
          v-model="password"
          placeholder="请输入访问密码"
          class="pr-10"
          :disabled="isVerifying"
          @keydown="handleKeyDown"
        />
        <button
          type="button"
          class="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground transition-colors"
          @click="showPassword = !showPassword"
        >
          <SafeIcon :name="showPassword ? 'EyeOff' : 'Eye'" :size="18" />
        </button>
      </div>

      <Button
        variant="default"
        class="w-full"
        :disabled="isVerifying || !password.trim()"
        @click="handleVerify"
      >
        <SafeIcon v-if="isVerifying" name="Loader2" :size="18" class="mr-2 animate-spin" />
        {{ isVerifying ? '验证中...' : '验证密码' }}
      </Button>
    </div>

    <p class="text-xs text-muted-foreground text-center">
      输入分享者提供的密码以继续上传
    </p>
  </div>
</template>
