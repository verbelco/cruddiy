import { useForm } from 'react-hook-form';

import { {modelName}, FormDataType, PreGivenAttributes } from '../types.ts';

export const use{modelName}Form = (
  {variableName}: {modelName} | undefined = undefined,
  preGivenAttributes: PreGivenAttributes | null = null,
) => {
  return useForm<FormDataType>({
    defaultValues: {
{defaultValues},
      ...preGivenAttributes,
    },
  });
};