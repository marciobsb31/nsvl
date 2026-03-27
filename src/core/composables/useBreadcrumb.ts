import { computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';

interface BreadcrumbItem {
  label: string;
  url?: string;
  active: boolean;
}

export function useBreadcrumb() {
  const route = useRoute();
  const router = useRouter();

  const items = computed<BreadcrumbItem[]>(() => {
    const matchedRoutes = route.matched.filter(
      (record) => record.meta.title,
    );

    return matchedRoutes.map((record, index) => {
        const isLast = index === matchedRoutes.length - 1;

        const resolved = record.name
          ? router.resolve({
              name: record.name as string,
              params: route.params,
              query: route.query,
            })
          : null;

        return {
          label: String(record.meta.title),
          url: isLast ? undefined : resolved?.path,
          active: isLast,
        };
      });
  });

  return {
    items,
  };
}