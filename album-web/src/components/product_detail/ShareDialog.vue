<script setup lang="ts">
import TargetShareDialog from '@/components/common/TargetShareDialog.vue'

interface Props {
  open: boolean
  productId: string
  targetUserId?: string
  shareCode?: string
}

const props = withDefaults(defineProps<Props>(), {
  targetUserId: '',
  shareCode: '',
})

const emit = defineEmits<{
  (e: 'update:open', value: boolean): void
}>()
</script>

<template>
  <TargetShareDialog
    :open="open"
    type="product"
    title="分享产品"
    description="选择分享方式，让更多人查看这个产品"
    :target-id="productId"
    :target-user-id="targetUserId"
    :share-code="shareCode"
    web-path="./share-home"
    web-param-name="productId"
    @update:open="emit('update:open', $event)"
  />
</template>
