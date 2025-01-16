import { Typography } from '@mui/material';
import React, { useEffect } from 'react';
import { useForm } from 'react-hook-form';
import { useNavigate, useParams } from 'react-router-dom';

import { setFormWithObject } from '../../../util/object.ts';
import ManagerForm from '../shared/hooks/Form/ManagerForm.tsx';
import { FieldConfig } from '../shared/types.ts';
import { {variableName}Labels } from './components/labels.ts';
import { {variableName}Tooltips } from './components/tooltips.ts';
import {
  useCreate{modelName},
  useShow{modelName},
  useUpdate{modelName},
} from './hooks/use{modelName}Query.ts';
import { {modelName} } from './types.ts';

type FormDataType = Omit<{modelName}, 'id'>;

const {modelName}Form: React.FC = () => {
  const { id } = useParams<{ id: string }>();
  const navigate = useNavigate();
  const isEditMode = !!id;

  const { data: {variableName}, isLoading } = useShow{modelName}(Number(id), {
    enabled: isEditMode,
  });
  const { mutateAsync: create{modelName} } = useCreate{modelName}();
  const { mutateAsync: update{modelName} } = useUpdate{modelName}();

  const initForm = useForm<FormDataType>({
    defaultValues: {
{defaultValues}
    },
  });

  const { setValue } = initForm;

  useEffect(() => {
    if ({variableName}) {
      setFormWithObject<{modelName}, FormDataType>({variableName}, setValue);
    }
  }, [{variableName}, setValue]);

  const fields: FieldConfig<FormDataType>[] = [
{fields}
  ];

  const form = ManagerForm<{modelName}, FormDataType>({
    id,
    type: '{routeName}',
    formName: '{modelName}',
    fields,
    initForm: initForm,
    createMutation: create{modelName},
    updateMutation: update{modelName},
    isEditMode,
    navigate,
  });

  if (isLoading && isEditMode) {
    return <Typography>Loading...</Typography>;
  }

  return form;
};

export default {modelName}Form;
