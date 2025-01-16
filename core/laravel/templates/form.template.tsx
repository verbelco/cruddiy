import { Autocomplete, TextField } from '@mui/material';
import React, { useEffect, useState } from 'react';
import { Controller, useForm } from 'react-hook-form';
import { useNavigate, useParams } from 'react-router-dom';

import { setFormWithObject } from '../../../util/object.ts';
import { useSensor } from '../sensor/hooks/useSensorQuery.ts';
import { Sensor } from '../sensor/types.ts';
import ManagerForm from '../shared/hooks/Form/ManagerForm.tsx';
import { FieldConfig } from '../shared/types.ts';
import { batterijniveauLabels } from './components/labels.ts';
import { batterijniveauTooltips } from './components/tooltips.ts';
import {
  useCreateBatterijniveau,
  useShowBatterijniveau,
  useUpdateBatterijniveau,
} from './hooks/useBatterijniveauQuery.ts';
import { Batterijniveau } from './types.template.ts';

type FormDataType = Omit<Batterijniveau, 'id'>;

const BatterijniveauForm: React.FC = () => {
  const { id } = useParams<{ id: string }>();
  const navigate = useNavigate();
  const [globalSensorFilter, setSensorFilter] = useState('');
  const isEditMode = !!id;

  const { data: batterijniveau, isLoading } = useShowBatterijniveau(
    Number(id),
    {
      enabled: isEditMode,
    },
  );
  const { mutateAsync: createBatterijniveau } = useCreateBatterijniveau();
  const { mutateAsync: updateBatterijniveau } = useUpdateBatterijniveau();
  const { data: sensors } = useSensor({ globalFilter: globalSensorFilter });

  const initForm = useForm<FormDataType>({
    defaultValues: {
      sensor: batterijniveau?.sensor ?? undefined,
      Spanning: batterijniveau?.Spanning ?? 0,
      MeetMoment: batterijniveau?.MeetMoment ?? undefined,
    },
  });

  const { setValue, control } = initForm;

  useEffect(() => {
    if (batterijniveau) {
      setFormWithObject<Batterijniveau, FormDataType>(
        batterijniveau,
        setValue,
        ['sensor'],
      );
    }
  }, [batterijniveau, setValue]);

  const fields: FieldConfig<FormDataType>[] = [
    {
      id: 'sensor',
      label: batterijniveauLabels.sensor,
      name: 'sensor',
      required: true,
      tooltip: batterijniveauLabels.sensor,
      component: (
        <Controller
          name="sensor"
          control={control}
          defaultValue={batterijniveau?.sensor}
          rules={{
            required: {
              value: true,
              message: 'Dit veld is verplicht',
            },
          }}
          render={({ field: { onChange, onBlur, value, ref } }) => (
            <Autocomplete
              options={sensors?.data || []}
              getOptionLabel={(option: Sensor) =>
                `${option.sensor_type?.naam ?? ''} ${option.serienummer ?? ''}`
              }
              onChange={(_, newValue) => {
                onChange(newValue);
              }}
              onBlur={onBlur}
              value={value}
              renderInput={(params) => (
                <TextField
                  {...params}
                  id="sensor"
                  name="sensor"
                  inputRef={ref}
                />
              )}
              onInputChange={(_, value) => setSensorFilter(value)}
            />
          )}
        />
      ),
    },
    {
      id: 'Spanning',
      label: batterijniveauLabels.Spanning,
      name: 'Spanning',
      type: 'number',
      tooltip: batterijniveauTooltips.Spanning,
    },
    {
      id: 'MeetMoment',
      label: batterijniveauLabels.MeetMoment,
      name: 'MeetMoment',
      type: 'datetime',
      tooltip: batterijniveauTooltips.MeetMoment,
    },
  ];

  const form = ManagerForm<Batterijniveau, FormDataType>({
    id,
    type: 'batterijniveau',
    formName: 'Batterijniveau',
    fields,
    initForm: initForm,
    createMutation: createBatterijniveau,
    updateMutation: updateBatterijniveau,
    isEditMode,
    navigate,
  });

  if (isLoading && isEditMode) {
    return <div>Loading...</div>;
  }

  return (
    <div className="p-5 flex justify-center">
      <div
        className="bg-white p-8 rounded-lg w-full overflow-y-auto"
        style={{ maxHeight: '90vh' }}
      >
        {form}
      </div>
    </div>
  );
};

export default BatterijniveauForm;
