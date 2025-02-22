export type {modelName} = {
{Types}
};

{Enums}

export type {modelName}Tooltips = {
  [K in keyof {modelName}]: string;
};

export type {modelName}Labels = {
  [K in keyof {modelName}]: string;
};

export type FormDataType = Omit<{modelName}, 'id'>;
export type PreGivenAttributes = Partial<FormDataType>;
