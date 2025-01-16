import { Typography } from '@mui/material';
import { AxiosError } from 'axios';
import { useEffect, useState } from 'react';
import { useNavigate, useParams } from 'react-router-dom';

import { ReadBox, ReadPaper } from '../shared/components/ReadComponents.tsx';
import ShowDialog from '../shared/components/showDialog.tsx';
import ShowHeader from '../shared/components/showHeader.tsx';
import ShowReadColumns from '../shared/components/showReadColumns.tsx';
import { {variableName}Labels } from './components/labels.ts';
import { {variableName}Tooltips } from './components/tooltips.ts';
import { useDelete{ModelName}, useShow{ModelName} } from './hooks/use{ModelName}Query.ts';
import { {ModelName} } from './types.ts';

export default function {ModelName}Show() {
  const { id } = useParams<{ id: string }>();
  const navigate = useNavigate();
  const [open, setOpen] = useState(false);
  const [showSnackbar, setShowSnackbar] = useState(false);
  const [snackbarMessage, setSnackbarMessage] = useState('');
  const [{variableName}Data, set{ModelName}Data] = useState<{ModelName} | null>(null);

  const { data: {variableName}, isLoading } = useShow{ModelName}(Number(id), {
    enabled: !!id,
  });

  const { mutateAsync: delete{ModelName} } = useDelete{ModelName}();

  useEffect(() => {
    if ({variableName}) {
      set{ModelName}Data({variableName});
    }
  }, [{variableName}]);

  if (isLoading || !{variableName}Data) {
    return <Typography>Loading...</Typography>;
  }

  const handleDelete = async () => {
    try {
      if ({variableName}Data) {
        await delete{ModelName}({variableName}Data.id);
        navigate('/manager/{routeName}');
      }
    } catch (error) {
      if (error instanceof AxiosError) {
        setSnackbarMessage(error.response?.data.message);
      }
      setShowSnackbar(true);
      setOpen(false);
    }
  };

  const handleSnackbarClose = () => {
    setShowSnackbar(false);
  };

  const {variableName}Details = [
{details}
  ];

  return (
    <ReadBox>
      <ReadPaper>
        <ShowHeader
          id={id}
          className={'{ModelName}'}
          linkName={'{routeName}'}
          navigate={navigate}
          setOpen={setOpen}
        />
        <ShowReadColumns data={{variableName}Details} />
      </ReadPaper>

      <ShowDialog
        className={'{ModelName}'}
        instanceName={{variableName}Data?.titel}
        open={open}
        setOpen={setOpen}
        handleDelete={handleDelete}
        showSnackbar={showSnackbar}
        handleSnackbarClose={handleSnackbarClose}
        snackbarMessage={snackbarMessage}
      />
    </ReadBox>
  );
}
