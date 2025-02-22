import {
  UseQueryOptions,
  useMutation,
  useQuery,
  useQueryClient,
} from '@tanstack/react-query';

import { client } from '../../../api/Client.ts';
import {
  CrudFilters,
  Paginated,
  ShowResponse,
} from '../../../shared/components/types.ts';
import { BulkRequest, BulkResponse } from '../../shared/types.ts';
import { {modelName}, FormDataType } from '../types.ts';

export const use{modelName} = ({
  columnFilters = [],
  globalFilter = '',
  pagination = { pageIndex: 0, pageSize: 20 },
  sorting = [],
  nullFilters = [],
}: CrudFilters) =>
  useQuery({
    queryKey: [
      '{routeName}',
      columnFilters,
      globalFilter,
      pagination.pageIndex,
      pagination.pageSize,
      sorting,
      nullFilters,
    ],
    queryFn: async () => {
      const response = await client.get<Paginated<{modelName}>>(
        'manager/crud/{routeName}',
        {
          params: {
            search: globalFilter || undefined,
            page: pagination.pageIndex + 1,
            per_page: pagination.pageSize,
            sorting: sorting.length
              ? sorting.map((s) => ({
                  id: s.id,
                  desc: Number(s.desc),
                }))
              : undefined,
            filters: columnFilters.length ? columnFilters : undefined,
            null_filters: nullFilters.length
              ? nullFilters.map((item) => ({
                  id: item.id,
                  value: Number(item.value),
                }))
              : undefined,
          },
        },
      );
      return response.data;
    },
  });

export const useUpdate{modelName} = () => {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async (data: {modelName}) => {
      const response = await client.put<{modelName}>(
        `/manager/crud/{routeName}/${data.id}`,
        data,
      );
      return response.data;
    },
    onSuccess: ({variableName}) => {
      queryClient.invalidateQueries({
        queryKey: ['{routeName}', {variableName}.id],
      });
    },
  });
};

export const useShow{modelName} = (
  id: number,
  options?: Omit<UseQueryOptions<{modelName}>, 'queryKey'>,
) => {
  return useQuery({
    queryKey: ['{routeName}', id],
    queryFn: async () => {
      const response = await client.get<ShowResponse<{modelName}>>(
        `/manager/crud/{routeName}/${id}`,
      );
      return response.data.data;
    },
    ...options,
  });
};

export const useCreate{modelName} = () => {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: async (data: Omit<{modelName}, 'id'>) => {
      const response = await client.post<{modelName}>(
        '/manager/crud/{routeName}',
        data,
      );
      return response.data;
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['{routeName}'] });
    },
  });
};

export const useDelete{modelName} = () => {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: async (id: number) => {
      await client.delete(`/manager/crud/{routeName}/${id}`);
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['{routeName}'] });
    },
  });
};

export const useUpdateBulk{modelName} = () => {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async (data: BulkRequest<FormDataType>) => {
      const response = await client.post<BulkResponse>(
        `/manager/crud/{routeName}/bulk-update`,
        data,
      );
      return response.data;
    },
    onSuccess: () => {
      queryClient.invalidateQueries({
        queryKey: ['{routeName}'],
      });
    },
  });
};

export const useDeleteBulk{modelName} = () => {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async (data: BulkRequest<FormDataType>) => {
      const response = await client.post<BulkResponse>(
        `/manager/crud/{routeName}/bulk-destroy`,
        data,
      );
      return response.data;
    },
    onSuccess: () => {
      queryClient.invalidateQueries({
        queryKey: ['{routeName}'],
      });
    },
  });
};
