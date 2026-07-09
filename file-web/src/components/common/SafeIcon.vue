<script setup lang="ts">
import { computed, type Component } from 'vue';
import {
  AlertCircle,
  AlertTriangle,
  Archive,
  ArrowLeft,
  ArrowRight,
  BadgeHelp,
  Bell,
  BellRing,
  CalendarCheck,
  Check,
  CheckCircle,
  CheckCircle2,
  ChevronDown,
  ChevronLeft,
  ChevronRight,
  ChevronUp,
  Circle,
  Clock,
  Clock3,
  Copy,
  Download,
  Eye,
  File,
  FileCheck,
  FileDown,
  FilePlus2,
  FileQuestion,
  FileText,
  FileUp,
  Files,
  Folder,
  FolderOpen,
  HardDrive,
  Headphones,
  History,
  Image,
  ImagePlus,
  Images,
  Info,
  KeyRound,
  LayoutDashboard,
  Link2,
  Loader2,
  Lock,
  LockKeyhole,
  MoreHorizontal,
  Move,
  Plus,
  QrCode,
  RefreshCw,
  RotateCcw,
  RotateCw,
  Search,
  SearchX,
  Send,
  SendHorizontal,
  Settings,
  ShieldCheck,
  ShipWheel,
  Trash2,
  UploadCloud,
  Users,
  Video,
  X,
  XCircle,
} from 'lucide-vue-next';

interface Props {
  name: string;
  size?: number | string;
  color?: string;
  strokeWidth?: number | string;
  class?: string;
}

const props = withDefaults(defineProps<Props>(), {
  size: 24,
  strokeWidth: 2,
});

const iconMap: Record<string, Component> = {
  AlertCircle,
  AlertTriangle,
  Archive,
  ArrowLeft,
  ArrowRight,
  BadgeHelp,
  Bell,
  BellRing,
  CalendarCheck,
  Check,
  CheckCircle,
  CheckCircle2,
  ChevronDown,
  ChevronLeft,
  ChevronRight,
  ChevronUp,
  Circle,
  Clock,
  Clock3,
  Copy,
  Download,
  Eye,
  File,
  FileCheck,
  FileDown,
  FilePlus2,
  FileQuestion,
  FileText,
  FileUp,
  Files,
  Folder,
  FolderOpen,
  HardDrive,
  Headphones,
  History,
  Image,
  ImagePlus,
  Images,
  Info,
  KeyRound,
  LayoutDashboard,
  Link2,
  Loader2,
  Lock,
  LockKeyhole,
  MoreHorizontal,
  Move,
  Plus,
  QrCode,
  RefreshCw,
  RotateCcw,
  RotateCw,
  Search,
  SearchX,
  Send,
  SendHorizontal,
  Settings,
  ShieldCheck,
  ShipWheel,
  Trash2,
  UploadCloud,
  Users,
  Video,
  X,
  XCircle,
};

const IconComponent = computed(() => {
  const icon = iconMap[props.name];
  if (!icon) {
    console.warn(`SafeIcon: icon "${props.name}" not found in lucide-vue-next, using fallback`);
    return Circle;
  }
  return icon;
});
</script>

<template>
  <component
    :is="IconComponent"
    :size="size"
    :color="color"
    :stroke-width="strokeWidth"
    :class="props.class"
  />
</template>
