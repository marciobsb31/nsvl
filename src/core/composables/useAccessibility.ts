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

function initAccessibility() {
  applyGrayscale()
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

function toggleGrayscale() {
  const body = document.body
  const isGrayscale = body.classList.contains('grayscale')

  if (isGrayscale) {
    body.classList.remove('grayscale')
    localStorage.removeItem('accessibility-grayscale')
  } else {
    body.classList.add('grayscale')
    localStorage.setItem('accessibility-grayscale', 'true')
  }
}

function applyGrayscale() {
  if (localStorage.getItem('accessibility-grayscale') === 'true') {
    document.body.classList.add('grayscale')
  }
}

export function useAccessibility() {
  return {
    currentFontSize,
    initAccessibility,
    increaseFontSize,
    decreaseFontSize,
    resetFontSize,
    toggleGrayscale,
  }
}
