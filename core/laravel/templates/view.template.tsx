import { Tooltip } from '@mui/material';
import { AxiosError } from 'axios';
import {
  type MRT_ColumnDef,
  MRT_Row,
  MaterialReactTable,
} from 'material-react-table';
import { useEffect, useMemo, useState } from 'react';
import { useLocation, useNavigate } from 'react-router-dom';

import { showDateTime } from '../../../util/dateTime.ts';
import { showNumber } from '../../../util/number.ts';
import { parseURLParams } from '../../../util/parse.ts';
import { parseReferentieUrl } from '../../../util/url.tsx';
import { useSensor } from '../sensor/hooks/useSensorQuery.ts';
import { Sensor } from '../sensor/types.ts';
import ShowDialog from '../shared/components/showDialog.tsx';
import { useExtendedTable } from '../shared/hooks/Table/ManagerExtendedTable.tsx';
import { batterijniveauLabels } from './components/labels.ts';
import { batterijniveauTooltips } from './components/tooltips.ts';
import { useBatterijniveauFilters } from './hooks/useBatterijniveauFilters.ts';
import {
  useBatterijniveau,
  useDeleteBatterijniveau,
} from './hooks/useBatterijniveauQuery.ts';
import { Batterijniveau } from './types.template.ts';

const BatterijniveauView = () => {
  const navigate = useNavigate();
  const { search } = useLocation();
  const [open, setOpen] = useState(false);
  const [showSnackbar, setShowSnackbar] = useState(false);
  const [snackbarMessage, setSnackbarMessage] = useState('');
  const [selectedBatterijniveau, setSelectedBatterijniveau] =
    useState<Batterijniveau | null>(null);
  const [globalSensorFilter, setSensorFilter] = useState('');

  const batterijFilters = useBatterijniveauFilters();
  const {
    columnFilters,
    globalFilter,
    sorting,
    pagination,
    nullFilters,
    setColumnFilters,
  } = batterijFilters;

  const {
    data: batterijniveau,
    isError,
    isRefetching,
    isLoading,
  } = useBatterijniveau({
    columnFilters,
    globalFilter,
    pagination,
    sorting,
    nullFilters,
  });

  const { data: sensor } = useSensor({
    globalFilter: globalSensorFilter,
  });

  useEffect(() => {
    const parsedParams = parseURLParams(new URLSearchParams(search));
    if (parsedParams.length > 0) {
      setColumnFilters(parsedParams);
    }
  }, [search, setColumnFilters]);

  const columns = useMemo<MRT_ColumnDef<Batterijniveau>[]>(
    () => [
      {
        accessorKey: 'id',
        header: batterijniveauLabels.id,
        enableEditing: false,
        enableColumnFilter: false,
        size: 50,
        Header: () => (
          <Tooltip title={batterijniveauTooltips.id}>
            <p>{batterijniveauLabels.id}</p>
          </Tooltip>
        ),
      },
      {
        accessorKey: 'sensor.serienummer',
        header: batterijniveauLabels.sensor,
        enableSorting: false,
        size: 20,
        filterFn: 'equals',
        filterVariant: 'autocomplete',
        Cell: ({ cell }) =>
          parseReferentieUrl(
            'sensor',
            cell.row.original.sensor?.id,
            `${cell.row.original.sensor?.sensor_type?.naam ?? ''} ${cell.row.original.sensor?.serienummer ?? ''}`,
          ),
        muiFilterAutocompleteProps: ({ column, table }) => ({
          options: sensor?.data || [],
          getOptionLabel: (option: string | Sensor) => {
            return typeof option === 'string'
              ? option
              : `${option.sensor_type?.naam ?? ''} ${option.serienummer ?? ''}`;
          },
          isOptionEqualToValue: (option, value) => option?.id === value?.id,
          onChange: (_, value) => {
            table.setColumnFilters((prev) => {
              const excludeId = 'Sensor';

              const updatedFilters = prev.filter(
                (filter) => filter.id !== column.id && filter.id !== excludeId,
              );

              if (value) {
                updatedFilters.push({ id: excludeId, value: value.id });
              }

              return updatedFilters;
            });
          },
          onInputChange: (_, value) => {
            setSensorFilter(value);
          },
          renderInput: (params) => <div {...params} />,
        }),
        Header: () => (
          <Tooltip title={batterijniveauTooltips.sensor}>
            <p>{batterijniveauLabels.sensor}</p>
          </Tooltip>
        ),
      },
      {
        accessorKey: 'Spanning',
        size: 20,
        filterFn: 'between',
        header: batterijniveauLabels.Spanning,
        Cell: ({ cell }) => {
          return cell.getValue() ? showNumber(cell.getValue() as number) : '';
        },
        Header: () => (
          <Tooltip title={batterijniveauTooltips.Spanning}>
            <p>{batterijniveauLabels.Spanning}</p>
          </Tooltip>
        ),
      },
      {
        accessorKey: 'MeetMoment',
        size: 20,
        header: batterijniveauLabels.MeetMoment,
        filterVariant: 'date-range',
        Cell: ({ cell }) => {
          return cell.getValue() ? showDateTime(cell.getValue() as Date) : '';
        },
        Header: () => (
          <Tooltip title={batterijniveauTooltips.MeetMoment}>
            <p>{batterijniveauLabels.MeetMoment}</p>
          </Tooltip>
        ),
      },
    ],
    [sensor?.data, setSensorFilter],
  );

  const { mutateAsync: deleteBatterijniveau } = useDeleteBatterijniveau();

  const handleDeleteBatterijniveau = async () => {
    if (selectedBatterijniveau) {
      try {
        await deleteBatterijniveau(selectedBatterijniveau?.id);
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

  const openDeleteConfirmModal = (row: MRT_Row<Batterijniveau>) => {
    setSelectedBatterijniveau(row.original);
    setOpen(true);
  };

  const table = useExtendedTable(
    {
      navigate,
      openDeleteConfirmModal,
      filters: batterijFilters,
      isError,
      isLoading,
      isRefetching,
      newButtonText: 'Batterijniveau aanmaken',
    },
    {
      columns,
      data: batterijniveau?.data || [],
      rowCount: batterijniveau?.meta.total || 0,
    },
  );

  const handleSnackbarClose = () => {
    setShowSnackbar(false);
  };

  return (
    <div className="p-4 h-[calc(100vh-56px-2em)]">
      <MaterialReactTable table={table} />
      <ShowDialog
        className={'BatterijNiveau'}
        instanceName={
          selectedBatterijniveau?.sensor?.sensor_type?.naam +
          ' ' +
          selectedBatterijniveau?.sensor?.serienummer +
          ' ' +
          showDateTime(selectedBatterijniveau?.MeetMoment)
        }
        open={open}
        setOpen={setOpen}
        handleDelete={handleDeleteBatterijniveau}
        showSnackbar={showSnackbar}
        handleSnackbarClose={handleSnackbarClose}
        snackbarMessage={snackbarMessage}
      />
    </div>
  );
};

export default BatterijniveauView;
