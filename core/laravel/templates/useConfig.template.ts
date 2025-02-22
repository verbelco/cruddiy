import { Autocomplete, TextField, Tooltip } from '@mui/material';
import type { MRT_ColumnDef } from 'material-react-table';
import { useMemo, useState } from 'react';
import { Control, Controller } from 'react-hook-form';

import { FieldConfig } from '../../shared/types.ts';
import { {variableName}Labels } from '../components/labels.ts';
import { {variableName}Tooltips } from '../components/tooltips.ts';
import { {modelName}, FormDataType } from '../types.ts';

export const use{modelName}FormConfig = (
  control: Control<FormDataType>,
  {variableName}: {modelName} | undefined = undefined,
) => {
  const fields: FieldConfig<FormDataType>[] = [
{fields}
  ];
  return fields;
};

export const use{modelName}TableConfig = (
) => {
  return useMemo<MRT_ColumnDef<{modelName}>[]>(
    () => [
{tableColumns}
    ]
    [],
  );
};
