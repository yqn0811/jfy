
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
  <div class="collection-step-indicator surface-base">
    <ol class="collection-step-track" aria-label="创建收集任务步骤">
      <li
        v-for="(step, index) in steps"
        :key="step.number"
        class="collection-step-node"
      >
        <div
          :class="[
            'collection-step',
            isStepActive(step.number, currentStep) && 'active',
            isStepCompleted(step.number, currentStep) && 'completed',
          ]"
        >
          <div class="collection-step-number">
              <span v-if="!isStepCompleted(step.number, currentStep)">
                {{ step.number }}
              </span>
              <SafeIcon v-else name="Check" :size="16" />
            </div>
          <div class="collection-step-copy">
            <span class="collection-step-label">{{ step.label }}</span>
            <span class="collection-step-description">{{ step.description }}</span>
          </div>
        </div>

        <div
          v-if="index < steps.length - 1"
          :class="[
            'collection-step-connector',
            isStepCompleted(step.number, currentStep)
              ? 'completed'
              : '',
          ]"
        />
      </li>
    </ol>

    <div class="collection-step-mobile md:hidden">
      <p class="text-sm font-medium">
        {{ steps[currentStep - 1]?.label }}
      </p>
      <p class="text-xs text-muted-foreground">
        {{ currentStep }} / {{ steps.length }}
      </p>
    </div>
  </div>
</template>

<style scoped>
.collection-step-indicator {
  padding: 20px 24px;
  overflow: hidden;
}

.collection-step-track {
  display: grid;
  grid-template-columns: repeat(5, minmax(0, 1fr));
  align-items: center;
}

.collection-step-node {
  display: grid;
  grid-template-columns: minmax(112px, auto) minmax(24px, 1fr);
  min-width: 0;
  align-items: center;
}

.collection-step-node:last-child {
  grid-template-columns: minmax(112px, auto);
}

.collection-step {
  display: flex;
  min-width: 0;
  align-items: center;
  gap: 10px;
}

.collection-step-number {
  display: flex;
  width: 32px;
  height: 32px;
  flex: 0 0 32px;
  align-items: center;
  justify-content: center;
  border: 2px solid hsl(var(--border));
  border-radius: 999px;
  background: hsl(var(--background));
  color: hsl(var(--muted-foreground));
  font-weight: 700;
  line-height: 1;
}

.collection-step.active .collection-step-number {
  border-color: hsl(var(--primary));
  background: hsl(var(--primary));
  color: hsl(var(--primary-foreground));
}

.collection-step.completed .collection-step-number {
  border-color: hsl(var(--success));
  background: hsl(var(--success) / 0.1);
  color: hsl(var(--success));
}

.collection-step-copy {
  display: flex;
  min-width: 0;
  flex-direction: column;
  gap: 3px;
}

.collection-step-label {
  color: hsl(var(--foreground));
  font-size: 15px;
  font-weight: 700;
  line-height: 1.25;
  white-space: nowrap;
}

.collection-step-description {
  max-width: 112px;
  overflow: hidden;
  color: hsl(var(--muted-foreground));
  font-size: 13px;
  line-height: 1.35;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.collection-step-connector {
  height: 4px;
  margin: 0 10px;
  border-radius: 999px;
  background: hsl(var(--border));
  transition: background-color 0.18s;
}

.collection-step-connector.completed {
  background: hsl(var(--success));
}

.collection-step-mobile {
  margin-top: 14px;
  text-align: center;
}

@media (max-width: 767px) {
  .collection-step-indicator {
    padding: 16px;
  }

  .collection-step-track {
    min-width: 0;
    grid-template-columns: repeat(5, 1fr);
  }

  .collection-step-node {
    display: flex;
    justify-content: center;
  }

  .collection-step-copy,
  .collection-step-connector {
    display: none;
  }
}
</style>
