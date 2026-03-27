import { ref } from 'vue';

type ThemeMode = 'dark' | 'light';

const STORAGE_KEY = 'app-theme';

const mode = ref<ThemeMode>('light');
let initialized = false;

const applyTheme = (theme: any) => {
  const root = document.documentElement;

  // você pode usar atributo...
  root.setAttribute('data-theme', theme);

  // ...e também classes utilitárias se quiser
  root.classList.remove('theme-light', 'theme-dark');
  root.classList.add(`theme-${theme}`);
};

const initTheme = () => {
  if (initialized) return;
  initialized = true;

  if (typeof window === 'undefined') return;

  const saved = localStorage.getItem(STORAGE_KEY) as ThemeMode | null;
  mode.value = saved || 'light';
  applyTheme(mode.value);
};

export function useTheme() {
  initTheme();

  const setMode = (newMode: ThemeMode) => {
    mode.value = newMode;
    localStorage.setItem(STORAGE_KEY, newMode || '');
    applyTheme(mode.value);
  };

  return {
    mode,
    setMode,
  };
}