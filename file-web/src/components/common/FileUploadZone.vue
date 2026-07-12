
<script setup lang="ts">
import { ref, onUnmounted } from 'vue';
import { cn } from '@/lib/utils';
import SafeIcon from '@/components/common/SafeIcon.vue';
import { toast } from 'vue-sonner';

interface Props {
  accept?: string;
  maxSize?: number; // In megabytes
  multiple?: boolean;
  disabled?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
  accept: '*',
  maxSize: 100,
  multiple: true,
  disabled: false
});

const emit = defineEmits<{
  (e: 'files-selected', files: FileList): void;
}>();

const isDragging = ref(false);
const fileInput = ref<HTMLInputElement | null>(null);

const handleDragOver = (e: DragEvent) => {
  if (props.disabled) return;
  e.preventDefault();
  isDragging.value = true;
};

const handleDragLeave = (e: DragEvent) => {
  if (props.disabled) return;
  e.preventDefault();
  isDragging.value = false;
};

const validateFiles = (files: FileList): boolean => {
  for (let i = 0; i < files.length; i++) {
    const file = files[i];
    if (props.maxSize && file.size > props.maxSize * 1024 * 1024) {
      toast.error(`文件 "${file.name}" 超过最大限制 ${props.maxSize}MB`);
      return false;
    }
  }
  return true;
};

const handleDrop = (e: DragEvent) => {
  if (props.disabled) return;
  e.preventDefault();
  isDragging.value = false;

  const files = e.dataTransfer?.files;
  if (files && files.length > 0) {
    if (validateFiles(files)) {
      emit('files-selected', files);
    }
  }
};

const handleClick = () => {
  if (props.disabled) return;
  fileInput.value?.click();
};

const handleInputChange = (e: Event) => {
  const target = e.target as HTMLInputElement;
  const files = target.files;
  if (files && files.length > 0) {
    if (validateFiles(files)) {
      emit('files-selected', files);
    }
    // Reset input to allow selecting the same file again if needed
    target.value = '';
  }
};
</script>

<template>
  <div
    :class="cn(
      'upload-zone group',
      isDragging && 'dragging',
      disabled && 'opacity-60 cursor-not-allowed grayscale'
    )"
    @dragover="handleDragOver"
    @dragleave="handleDragLeave"
    @drop="handleDrop"
    @click="handleClick"
  >
    <input
      ref="fileInput"
      type="file"
      class="hidden"
      :accept="accept"
      :multiple="multiple"
      :disabled="disabled"
      @change="handleInputChange"
    />

    <div class="action-card-icon-wrapper group-hover:scale-110 transition-transform duration-200">
      <SafeIcon name="UploadCloud" :size="28" :stroke-width="2" />
    </div>

    <div class="space-y-2">
      <slot>
        <div class="text-item-title text-foreground">
          点击或拖拽文件到此处上传
        </div>
        <p class="text-caption">
          支持多文件上传，单个文件最大不超过 {{ maxSize }}MB
        </p>
      </slot>
    </div>

    <div v-if="accept !== '*'" class="mt-4 px-3 py-1 bg-muted rounded-full text-xs text-muted-foreground">
      接收格式: {{ accept }}
    </div>
  </div>
</template>

<style scoped>
/* Scoped styles kept minimal as we use global.css classes */
.dragging {
  animation: pulse 1.5s infinite;
}

@keyframes pulse {
  0% {
    box-shadow: 0 0 0 0 hsla(var(--primary) / 0.1);
  }
  70% {
    box-shadow: 0 0 0 10px hsla(var(--primary) / 0);
  }
  100% {
    box-shadow: 0 0 0 0 hsla(var(--primary) / 0);
  }
}
</style>
    