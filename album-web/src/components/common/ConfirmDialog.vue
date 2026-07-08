
<script setup lang="ts">
import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
} from '@/components/ui/alert-dialog';

interface Props {
  open: boolean;
  title: string;
  description: string;
  confirmText?: string;
  cancelText?: string;
  variant?: 'default' | 'destructive';
}

const props = withDefaults(defineProps<Props>(), {
  confirmText: '确定',
  cancelText: '取消',
  variant: 'default',
});

const emit = defineEmits<{
  (e: 'update:open', value: boolean): void;
  (e: 'confirm'): void;
  (e: 'cancel'): void;
}>();

const handleConfirm = () => {
  emit('confirm');
  emit('update:open', false);
};

const handleCancel = () => {
  emit('cancel');
  emit('update:open', false);
};
</script>

<template>
  <AlertDialog :open="open" @update:open="(val) => emit('update:open', val)">
    <AlertDialogContent class="max-w-[400px]">
      <AlertDialogHeader>
        <AlertDialogTitle class="text-xl font-semibold">
          {{ title }}
        </AlertDialogTitle>
        <AlertDialogDescription class="pt-2 text-muted-foreground leading-relaxed">
          {{ description }}
        </AlertDialogDescription>
      </AlertDialogHeader>
      <AlertDialogFooter class="mt-6">
        <AlertDialogCancel 
          @click="handleCancel"
          class="border-border hover:bg-muted text-foreground"
        >
          {{ cancelText }}
        </AlertDialogCancel>
        <AlertDialogAction
          @click="handleConfirm"
          :class="cn(
            'px-6',
            variant === 'destructive' 
              ? 'bg-destructive text-destructive-foreground hover:bg-destructive/90' 
              : 'bg-primary text-primary-foreground hover:bg-primary/90'
          )"
        >
          {{ confirmText }}
        </AlertDialogAction>
      </AlertDialogFooter>
    </AlertDialogContent>
  </AlertDialog>
</template>
