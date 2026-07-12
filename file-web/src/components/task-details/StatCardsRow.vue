
<script setup lang="ts">
import StatCard from '@/components/common/StatCard.vue'

interface Stats {
  total: number
  submitted: number
  needResubmit: number
  approved: number
}

interface Props {
  stats: Stats
}

defineProps<Props>()

const submitRate = (submitted: number, total: number) => {
  if (total === 0) return '0%'
  return `${Math.round((submitted / total) * 100)}%`
}
</script>

<template>
  <div class="stat-card-grid">
    <StatCard
      label="应提交人数"
      :value="stats.total"
      icon="Users"
      variant="default"
    />
    <StatCard
      label="已提交"
      :value="`${stats.submitted}/${stats.total}`"
      icon="CheckCircle2"
      :trend="stats.submitted === stats.total ? 'up' : undefined"
      :trend-value="submitRate(stats.submitted, stats.total)"
      variant="success"
    />
    <StatCard
      label="需补交"
      :value="stats.needResubmit"
      icon="AlertCircle"
      :trend="stats.needResubmit > 0 ? 'down' : undefined"
      variant="warning"
    />
    <StatCard
      label="已通过"
      :value="stats.approved"
      icon="CheckCircle"
      variant="success"
    />
  </div>
</template>
