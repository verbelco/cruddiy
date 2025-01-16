import { Typography } from '@mui/material';
import { AxiosError } from 'axios';
import { useEffect, useState } from 'react';
import { useNavigate, useParams } from 'react-router-dom';

import { showDateTime } from '../../../util/dateTime.ts';
import { showNumber } from '../../../util/number.ts';
import { parseReferentieUrl } from '../../../util/url.tsx';
import { ReadBox, ReadPaper } from '../shared/components/ReadComponents.tsx';
import ShowDialog from '../shared/components/showDialog.tsx';
import ShowHeader from '../shared/components/showHeader.tsx';
import ShowReadColumns from '../shared/components/showReadColumns.tsx';
import { batterijniveauLabels } from './components/labels.ts';
import { batterijniveauTooltips } from './components/tooltips.ts';
import {
  useDeleteBatterijniveau,
  useShowBatterijniveau,
} from './hooks/useBatterijniveauQuery.ts';
import { Batterijniveau } from './types.template.ts';

export default function BatterijniveauShow() {
  const { id } = useParams<{ id: string }>();
  const navigate = useNavigate();
  const [open, setOpen] = useState(false);
  const [showSnackbar, setShowSnackbar] = useState(false);
  const [snackbarMessage, setSnackbarMessage] = useState('');
  const [batterijniveauData, setBatterijniveauData] =
    useState<Batterijniveau | null>(null);

  const { data: batterijniveau, isLoading } = useShowBatterijniveau(
    Number(id),
    {
      enabled: !!id,
    },
  );

  const { mutateAsync: deleteBatterijniveau } = useDeleteBatterijniveau();

  useEffect(() => {
    if (batterijniveau) {
      setBatterijniveauData(batterijniveau);
    }
  }, [batterijniveau]);

  if (isLoading || !batterijniveauData) {
    return <Typography>Loading...</Typography>;
  }

  const handleDelete = async () => {
    try {
      if (batterijniveauData) {
        await deleteBatterijniveau(batterijniveauData.id);
        navigate('/manager/batterijniveau');
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

  const batterijniveauDetails = [
    {
      label: batterijniveauLabels.id,
      value: batterijniveauData.id,
      tooltip: batterijniveauTooltips.id,
    },
    {
      label: batterijniveauLabels.sensor,
      value: parseReferentieUrl(
        'sensor',
        batterijniveauData.sensor?.id,
        `${batterijniveauData.sensor?.sensor_type?.naam ?? ''} ${batterijniveauData.sensor?.serienummer ?? ''}`,
      ),
      tooltip: batterijniveauTooltips.sensor,
    },
    {
      label: batterijniveauLabels.Spanning,
      value: showNumber(batterijniveauData.Spanning, 0),
      tooltip: batterijniveauTooltips.Spanning,
    },
    {
      label: batterijniveauLabels.MeetMoment,
      value: showDateTime(batterijniveauData.MeetMoment),
      tooltip: batterijniveauTooltips.MeetMoment,
    },
  ];

  return (
    <ReadBox>
      <ReadPaper>
        <ShowHeader
          id={id}
          className={'Batterijniveau'}
          linkName={'batterijniveau'}
          navigate={navigate}
          setOpen={setOpen}
        />
        <ShowReadColumns data={batterijniveauDetails} />
      </ReadPaper>

      <ShowDialog
        className={'Batterijniveau'}
        instanceName={`${batterijniveauData.sensor?.sensor_type?.naam ?? ''} ${batterijniveauData.sensor?.serienummer ?? ''} ${showDateTime(batterijniveauData.MeetMoment)}`}
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
