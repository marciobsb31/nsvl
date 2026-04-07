import { ref } from 'vue'

type FontSizeMode = 'small' | 'normal' | 'large' | 'xlarge'

const STORAGE_KEY = 'app-font-size'
const sizes: FontSizeMode[] = ['small', 'normal', 'large', 'xlarge']

const fontSizeMap: Record<FontSizeMode, string> = {
  small: '14px',
  normal: '16px',
  large: '18px',
  xlarge: '20px',
}

const currentFontSize = ref<FontSizeMode>('normal')

function applyFontSize(size: FontSizeMode) {
  currentFontSize.value = size
  document.documentElement.style.fontSize = fontSizeMap[size]
  localStorage.setItem(STORAGE_KEY, size)
}

function initFontSize() {
  const saved = localStorage.getItem(STORAGE_KEY) as FontSizeMode | null
  applyFontSize(saved && sizes.includes(saved) ? saved : 'normal')
}

function increaseFontSize() {
  const index = sizes.indexOf(currentFontSize.value)
  applyFontSize(sizes[Math.min(index + 1, sizes.length - 1)] as FontSizeMode)
}

function decreaseFontSize() {
  const index = sizes.indexOf(currentFontSize.value)
  applyFontSize(sizes[Math.max(index - 1, 0)] as FontSizeMode)
}

function resetFontSize() {
  applyFontSize('normal')
}

export function useAccessibilityFont() {
  return {
    currentFontSize,
    initFontSize,
    increaseFontSize,
    decreaseFontSize,
    resetFontSize,
  }
}