<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { fetchLogFiles, fetchLogContent, type LogLine } from '../services/adminLogs'

const files = ref<string[]>([])
const activeFile = ref<string | null>(null)
const lines = ref<LogLine[]>([])
const loadingFiles = ref(false)
const loadingContent = ref(false)
const error = ref('')

const levelStyle: Record<string, string> = {
  ERROR:     'bg-rose-100 text-rose-700',
  CRITICAL:  'bg-red-200 text-red-800',
  ALERT:     'bg-orange-100 text-orange-700',
  EMERGENCY: 'bg-red-300 text-red-900',
  WARNING:   'bg-amber-100 text-amber-700',
  NOTICE:    'bg-blue-100 text-blue-700',
  INFO:      'bg-sky-100 text-sky-700',
  DEBUG:     'bg-slate-100 text-slate-500',
}

function labelFromFilename(name: string): string {
  // laravel_20_07_2026.log → 20/07/2026
  const m = name.match(/laravel_(\d{2})_(\d{2})_(\d{4})\.log/)
  return m ? `${m[1]}/${m[2]}/${m[3]}` : name
}

async function loadFiles() {
  loadingFiles.value = true
  error.value = ''
  try {
    files.value = await fetchLogFiles()
    if (files.value.length) {
      await selectFile(files.value[0])
    }
  } catch {
    error.value = 'Failed to load log files.'
  } finally {
    loadingFiles.value = false
  }
}

async function selectFile(name: string) {
  activeFile.value = name
  lines.value = []
  loadingContent.value = true
  error.value = ''
  try {
    lines.value = await fetchLogContent(name)
  } catch {
    error.value = `Failed to load ${name}.`
  } finally {
    loadingContent.value = false
  }
}

onMounted(loadFiles)
</script>

<template>
  <div class="flex h-[calc(100vh-5rem)] pt-6 gap-4">

    <!-- Left panel: file list -->
    <aside class="w-56 shrink-0 flex flex-col rounded-2xl border border-slate-200 bg-white overflow-hidden">
      <div class="border-b border-slate-100 px-4 py-3">
        <h2 class="text-sm font-semibold text-slate-700">Log Files</h2>
      </div>

      <div v-if="loadingFiles" class="flex flex-1 items-center justify-center text-sm text-slate-400">
        Loading…
      </div>

      <nav v-else class="flex-1 overflow-y-auto p-2 space-y-1">
        <div v-if="files.length === 0" class="px-3 py-2 text-sm text-slate-400">No log files.</div>
        <button
          v-for="file in files"
          :key="file"
          @click="selectFile(file)"
          :class="[
            'w-full text-left rounded-xl px-3 py-2 text-sm transition',
            activeFile === file
              ? 'bg-slate-900 text-white font-medium'
              : 'text-slate-600 hover:bg-slate-100'
          ]"
        >
          {{ labelFromFilename(file) }}
        </button>
      </nav>
    </aside>

    <!-- Right panel: log content -->
    <div class="flex flex-1 flex-col rounded-2xl border border-slate-200 bg-white overflow-hidden">
      <div class="flex items-center justify-between border-b border-slate-100 px-5 py-3">
        <h2 class="text-sm font-semibold text-slate-700">
          {{ activeFile ? labelFromFilename(activeFile) : 'Select a file' }}
        </h2>
        <span v-if="lines.length" class="text-xs text-slate-400">{{ lines.length }} entries (newest first)</span>
      </div>

      <div v-if="error" class="px-5 py-4 text-sm text-rose-600">{{ error }}</div>

      <div v-else-if="loadingContent" class="flex flex-1 items-center justify-center text-sm text-slate-400">
        Loading…
      </div>

      <div v-else-if="!activeFile" class="flex flex-1 items-center justify-center text-sm text-slate-400">
        Select a log file from the left panel.
      </div>

      <div v-else-if="lines.length === 0" class="flex flex-1 items-center justify-center text-sm text-slate-400">
        File is empty.
      </div>

      <div v-else class="flex-1 overflow-y-auto font-mono text-xs">
        <div
          v-for="(line, i) in lines"
          :key="i"
          :class="[
            'border-b border-slate-50 px-5 py-2 flex gap-3 items-start',
            line.level === 'ERROR' || line.level === 'CRITICAL' || line.level === 'EMERGENCY'
              ? 'bg-rose-50/40'
              : line.level === 'WARNING' || line.level === 'ALERT'
              ? 'bg-amber-50/40'
              : ''
          ]"
        >
          <!-- Timestamp -->
          <span class="shrink-0 text-slate-400 w-36">{{ line.timestamp ?? '' }}</span>

          <!-- Level badge -->
          <span
            v-if="line.level"
            :class="['shrink-0 rounded px-1.5 py-0.5 text-[10px] font-bold uppercase', levelStyle[line.level] ?? 'bg-slate-100 text-slate-500']"
          >{{ line.level }}</span>

          <!-- Message -->
          <span class="break-all text-slate-700 leading-relaxed">{{ line.message }}</span>
        </div>
      </div>
    </div>

  </div>
</template>
