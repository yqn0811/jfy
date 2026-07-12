<script setup lang="ts">
import { computed, ref, onMounted } from 'vue'
import { Toaster } from '@/components/ui/sonner'
import MobileMiniProgramGuide from '@/components/common/MobileMiniProgramGuide.vue'
import { currentRoute } from '@/router'
import { currentRouteState } from '@/navigation'
import { getUrlHomeTarget } from '@/lib/api'

type MiniProgramTargetType = 'home' | 'category' | 'product' | 'selection'

const detectMobileDevice = () => {
  if (typeof window === 'undefined') return false
  const ua = window.navigator.userAgent || ''
  const isTouchIpad = /Macintosh/i.test(ua) && window.navigator.maxTouchPoints > 1
  return /Android|webOS|iPhone|iPad|iPod|Mobile|MicroMessenger/i.test(ua) || isTouchIpad
}

const isMobileDevice = ref(detectMobileDevice())

const mobileShareTarget = computed(() => {
  const route = currentRouteState.value
  const query = route.query || {}
  const target = getUrlHomeTarget()
  let type: MiniProgramTargetType = 'home'
  let targetId = ''

  if (route.path === '/category') {
    type = 'category'
    targetId = query.categoryId || query.cate_id || ''
  } else if (route.path === '/product-detail') {
    type = 'product'
    targetId = query.productId || query.product_id || ''
  } else if (route.path === '/my-selections') {
    type = 'selection'
    targetId = query.selectionId || query.selection_id || ''
  } else if (route.path === '/share-home') {
    const productId = query.productId || query.product_id || ''
    const categoryId = query.categoryId || query.cate_id || ''
    if (productId) {
      type = 'product'
      targetId = productId
    } else if (categoryId && categoryId !== 'all') {
      type = 'category'
      targetId = categoryId
    }
  } else {
    return null
  }

  return {
    type,
    targetId,
    targetUserId: target.targetUserId,
    shareCode: target.shareCode,
  }
})

const shouldShowMobileMiniProgramGuide = computed(() =>
  isMobileDevice.value &&
  !!mobileShareTarget.value &&
  (!!mobileShareTarget.value.targetUserId || !!mobileShareTarget.value.shareCode)
)

onMounted(() => {
  isMobileDevice.value = detectMobileDevice()
})
</script>

<template>
  <MobileMiniProgramGuide
    v-if="shouldShowMobileMiniProgramGuide && mobileShareTarget"
    :type="mobileShareTarget.type"
    :target-id="mobileShareTarget.targetId"
    :target-user-id="mobileShareTarget.targetUserId"
    :share-code="mobileShareTarget.shareCode"
  />
  <component :is="currentRoute.layout" v-else-if="currentRoute.layout">
    <component :is="currentRoute.component" v-bind="currentRoute.props" />
  </component>
  <component :is="currentRoute.component" v-else v-bind="currentRoute.props" />
  <Toaster />
</template>
