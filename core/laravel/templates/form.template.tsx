import { Typography } from '@mui/material';
import React, { useEffect } from 'react';
import { useLocation, useNavigate, useParams } from 'react-router-dom';

import { setFormWithObject } from '../../../util/object.ts';
import ManagerForm from '../shared/hooks/Form/ManagerForm.tsx';
import { use{modelName}FormConfig } from './hooks/use{modelName}Config.tsx';
import { use{modelName}Form } from './hooks/use{modelName}Form.ts';
import {
  useCreate{modelName},
  useShow{modelName},
  useUpdate{modelName},
} from './hooks/use{modelName}Query.ts';
import { {modelName}, FormDataType, PreGivenAttributes } from './types.ts';

const {modelName}Form: React.FC = () => {
  const { id } = useParams<{ id: string }>();
  const { state } = useLocation();
  const navigate = useNavigate();
  const isEditMode = !!id;

  const { data: {variableName}, isLoading } = useShow{modelName}(Number(id), {
    enabled: isEditMode,
  });
  const { mutateAsync: create{modelName} } = useCreate{modelName}();
  const { mutateAsync: update{modelName} } = useUpdate{modelName}();
  const preGivenAttributes: PreGivenAttributes | null = state ?? null;

  const initForm = use{modelName}Form({variableName}, preGivenAttributes);

  const { setValue, control } = initForm;

  useEffect(() => {
    if ({variableName}) {
      setFormWithObject<{modelName}, FormDataType>({variableName}, setValue);
    }
  }, [{variableName}, setValue]);

  const fields = use{modelName}FormConfig(control, {variableName});

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
