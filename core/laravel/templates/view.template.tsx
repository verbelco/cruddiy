import { AxiosError } from 'axios';
import { MRT_Row, MaterialReactTable } from 'material-react-table';
import { useEffect, useState } from 'react';
import { useLocation, useNavigate } from 'react-router-dom';

import { parseURLParams } from '../../../util/parse.ts';
import ShowDialog from '../shared/components/showDialog.tsx';
import ViewBox from '../shared/components/viewBox.tsx';
import { useExtendedTable } from '../shared/hooks/Table/ManagerExtendedTable.tsx';
import { BulkProps } from '../shared/types.ts';
import {
  use{modelName}FormConfig,
  use{modelName}TableConfig,
} from './hooks/use{modelName}Config.tsx';
import { use{modelName}Filters } from './hooks/use{modelName}Filters.ts';
import { use{modelName}, useDelete{modelName}, useDeleteBulk{modelName}, useUpdateBulk{modelName} } from './hooks/use{modelName}Query.ts';
import { {modelName}, FormDataType } from './types.ts';

const {modelName}View = () => {
  const navigate = useNavigate();
  const { search } = useLocation();
  const [open, setOpen] = useState(false);
  const [showSnackbar, setShowSnackbar] = useState(false);
  const [snackbarMessage, setSnackbarMessage] = useState('');
  const [selected{modelName}, setSelected{modelName}] = useState<{modelName} | null>(null);

  const {variableName}Filters = use{modelName}Filters();
  const {
    columnFilters,
    globalFilter,
    sorting,
    pagination,
    nullFilters,
    setColumnFilters,
  } = {variableName}Filters;

  const {
    data: {variableName},
    isError,
    isRefetching,
    isLoading,
  } = use{modelName}({
    columnFilters,
    globalFilter,
    pagination,
    sorting,
    nullFilters,
  });

  useEffect(() => {
    const parsedParams = parseURLParams(new URLSearchParams(search));
    if (parsedParams.length > 0) {
      setColumnFilters(parsedParams);
    }
  }, [search, setColumnFilters]);

  const columns = use{modelName}TableConfig();

  const bulkForm = use{modelName}Form();
  const { control } = bulkForm;
  const bulkFields = use{modelName}FormConfig(control);
  const { mutateAsync: bulkDelete } = useDeleteBulk{modelName}();
  const { mutateAsync: bulkUpdate } = useUpdateBulk{modelName}();
  const bulkProps: BulkProps<FormDataType> = {
    form: bulkForm,
    fields: bulkFields,
    updateMutation: bulkUpdate,
    deleteMutation: bulkDelete,
  };

  const { mutateAsync: delete{modelName} } = useDelete{modelName}();

  const handleDelete{modelName} = async () => {
    if (selected{modelName}) {
      try {
        await delete{modelName}(selected{modelName}?.id);
        setOpen(false);
      } catch (error) {
        if (error instanceof AxiosError) {
          setSnackbarMessage(error.response?.data.message);
        }

        setOpen(false);
        setShowSnackbar(true);
      }
    }
  };

  const openDeleteConfirmModal = (row: MRT_Row<{modelName}>) => {
    setSelected{modelName}(row.original);
    setOpen(true);
  };

  const table = useExtendedTable(
    {
      navigate,
      openDeleteConfirmModal,
      filters: {variableName}Filters,
      isError,
      isLoading,
      isRefetching,
      newButtonText: '{modelName} aanmaken',
      bulkEnabled: false,
      bulkProps,
    },
    {
      columns,
      data: {variableName}?.data || [],
      rowCount: {variableName}?.meta.total || 0,
    },
  );

  const handleSnackbarClose = () => {
    setShowSnackbar(false);
  };

  return (
    <ViewBox>
      <MaterialReactTable table={table} />
      <ShowDialog
        className={'{modelName}'}
        instanceName={selected{modelName}?.id.toString()}
        open={open}
        setOpen={setOpen}
        handleDelete={handleDelete{modelName}}
        showSnackbar={showSnackbar}
        handleSnackbarClose={handleSnackbarClose}
        snackbarMessage={snackbarMessage}
      />
    </ViewBox>
  );
};

export default {modelName}View;
