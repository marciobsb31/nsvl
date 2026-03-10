import { onMounted, ref } from 'vue';

type ThemeMode = 'dark' | 'light';

const STORAGE_KEY = 'app-theme';

export function useTheme() {
  const mode = ref<ThemeMode>('light');


  const applyTheme = (theme: any) => {
    const root = document.documentElement;

    // você pode usar atributo...
    root.setAttribute('data-theme', theme);

    // ...e também classes utilitárias se quiser
    root.classList.remove('theme-light', 'theme-dark');
    root.classList.add(`theme-${theme}`);
  };

  const setMode = (newMode: ThemeMode) => {
    mode.value = newMode;
    localStorage.setItem(STORAGE_KEY, newMode || '');
    applyTheme(mode.value);
  };

  onMounted(() => {
    const saved = localStorage.getItem(STORAGE_KEY) as ThemeMode | null;
    mode.value = saved || 'light';
    applyTheme(mode.value);


  });

  return {
    mode,
    setMode,
  };
}