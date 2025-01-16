import { create } from 'zustand';

import { FiltersState } from '../../shared/types.ts';

export const use{modelName}Filters = create<FiltersState>()((set) => ({
  columnFilters: [],
  globalFilter: '',
  sorting: [],
  columnVisibility: {
{Columns}
  },
  pagination: { pageIndex: 0, pageSize: 20 },
  nullFilters: [],
  setColumnVisibility: (visibility) =>
    set((state) => ({
      columnVisibility:
        typeof visibility === 'function'
          ? visibility(state.columnVisibility)
          : visibility,
    })),
  setColumnFilters: (filters) =>
    set((state) => ({
      columnFilters:
        typeof filters === 'function' ? filters(state.columnFilters) : filters,
    })),
  setGlobalFilter: (filter) =>
    set((state) => ({
      globalFilter:
        typeof filter === 'function' ? filter(state.globalFilter) : filter,
    })),
  setSorting: (sorting) =>
    set((state) => ({
      sorting: typeof sorting === 'function' ? sorting(state.sorting) : sorting,
    })),
  setPagination: (pagination) =>
    set((state) => ({
      pagination:
        typeof pagination === 'function'
          ? pagination(state.pagination)
          : pagination,
    })),
  setNullFilters: (nullFilters) =>
    set((state) => ({
      nullFilters:
        typeof nullFilters === 'function'
          ? nullFilters(state.nullFilters)
          : nullFilters,
    })),
}));
