
<script setup lang="ts">
import SafeIcon from '@/components/common/SafeIcon.vue'

interface Step {
  number: number
  label: string
  description: string
}

interface Props {
  currentStep: number
  steps: Step[]
}

defineProps<Props>()

const isStepCompleted = (stepNumber: number, currentStep: number) => {
  return stepNumber < currentStep
}

const isStepActive = (stepNumber: number, currentStep: number) => {
  return stepNumber === currentStep
}
</script>

<template>
  <div class="surface-base card-padding">
    <div class="flex items-center justify-between gap-2 md:gap-4">
      <template v-for="(step, index) in steps" :key="step.number">
        <!-- Step Item -->
        <div class="flex flex-col items-center flex-1">
          <div
            :class="[
              'step-item',
              isStepActive(step.number, currentStep) && 'active',
              isStepCompleted(step.number, currentStep) && 'completed',
            ]"
          >
            <div class="step-number">
              <span v-if="!isStepCompleted(step.number, currentStep)">
                {{ step.number }}
              </span>
              <SafeIcon v-else name="Check" :size="16" />
            </div>
            <div class="hidden md:flex flex-col gap-0.5">
              <span class="text-sm font-medium">{{ step.label }}</span>
              <span class="text-xs text-muted-foreground">{{ step.description }}</span>
            </div>
          </div>
        </div>

        <!-- Connector Line -->
        <div
          v-if="index < steps.length - 1"
          :class="[
            'h-1 flex-1 rounded-full transition-colors',
            isStepCompleted(step.number, currentStep)
              ? 'bg-[hsl(var(--success))]'
              : 'bg-border',
          ]"
        />
      </template>
    </div>

    <!-- Mobile Step Info -->
    <div class="md:hidden mt-4 text-center">
      <p class="text-sm font-medium">
        {{ steps[currentStep - 1]?.label }}
      </p>
      <p class="text-xs text-muted-foreground">
        {{ currentStep }} / {{ steps.length }}
      </p>
    </div>
  </div>
</template>

