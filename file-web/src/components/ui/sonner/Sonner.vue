<script lang="ts" setup>
import { computed, type CSSProperties } from "vue"
import type { ToasterProps } from "vue-sonner"
import { Toaster as Sonner } from "vue-sonner"

const props = defineProps<ToasterProps>()

const defaultTopOffset = "24px"
const toasterPosition = computed(() => props.position ?? "top-center")
const toasterOffset = computed(() => props.offset ?? { top: defaultTopOffset })
const toasterMobileOffset = computed(
  () => props.mobileOffset ?? { top: defaultTopOffset, right: "16px", bottom: "16px", left: "16px" },
)
const toasterStyle = computed<CSSProperties>(() => ({
  ...props.style,
  "--width": "min(520px, calc(100vw - 32px))",
}))
</script>

<template>
  <Sonner
    v-bind="props"
    class="toaster group jfy-toast"
    :position="toasterPosition"
    :offset="toasterOffset"
    :mobile-offset="toasterMobileOffset"
    :style="toasterStyle"
    rich-colors
    :toast-options="{
      ...props.toastOptions,
      classes: {
        ...props.toastOptions?.classes,
        toast: `group toast jfy-toast-item ${props.toastOptions?.classes?.toast ?? ''}`,
        description: 'group-[.toast]:text-muted-foreground',
        actionButton:
          'group-[.toast]:bg-primary group-[.toast]:text-primary-foreground',
        cancelButton:
          'group-[.toast]:bg-muted group-[.toast]:text-muted-foreground',
      },
    }"
  />
</template>
