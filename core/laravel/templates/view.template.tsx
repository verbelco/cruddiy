import { Tooltip } from '@mui/material';
import { AxiosError } from 'axios';
import {
  type MRT_ColumnDef,
  MRT_Row,
  MaterialReactTable,
} from 'material-react-table';
import { useEffect, useMemo, useState } from 'react';
import { useLocation, useNavigate } from 'react-router-dom';

import { parseURLParams } from '../../../util/parse.ts';
import ShowDialog from '../shared/components/showDialog.tsx';
import ViewBox from '../shared/components/viewBox.tsx';
import { useExtendedTable } from '../shared/hooks/Table/ManagerExtendedTable.tsx';
import { {variableName}Labels } from './components/labels.ts';
import { {variableName}Tooltips } from './components/tooltips.ts';
import { use{modelName}Filters } from './hooks/use{modelName}Filters.ts';
import { use{modelName}, useDelete{modelName} } from './hooks/use{modelName}Query.ts';
import { {modelName} } from './types.ts';

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

  const columns = useMemo<MRT_ColumnDef<{modelName}>[]>(
    () => [
{tableColumns}
    ],
    [],
  );

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
