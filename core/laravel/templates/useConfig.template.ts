import { Autocomplete, TextField, Tooltip } from '@mui/material';
import type { MRT_ColumnDef } from 'material-react-table';
import { useMemo, useState } from 'react';
import { Control, Controller } from 'react-hook-form';

import { showDate, showDateTime } from '../../../../util/dateTime.ts';
import { showNumber } from '../../../../util/number.ts';
import { parseReferentieUrl } from '../../../../util/url.tsx';
import { Paginated } from '../../../shared/components/types.ts';
import { FieldConfig, booleanFilterParams } from '../../shared/types.ts';
import ControlledAutocomplete from '../../shared/components/Form/ControlledAutocomplete.tsx';
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
    ],
    [],
  );
};
