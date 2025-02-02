import { useState, useCallback, useRef, useEffect, useMemo } from 'react';
import { debounce } from 'lodash';
import { router } from '@inertiajs/react';
import axios from 'axios';
import { toast } from 'sonner'
import { delay } from '@/utils'
import { format } from 'date-fns-tz'

export const useAxiosFetchByInput = ({
  url, // The endpoint to fetch from
  method = 'post', // HTTP method
  queryKey = 'userInput', // Query key for the input
  debounceDelay = 200, // Debounce delay
}) => {
  const [list, setList] = useState([]);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);

  const fetchList = useCallback(
    debounce(async (input) => {
      if (input.length > 0) {
        setLoading(true);
        setError(null);
        try {
          const response = await axios({
            method,
            url,
            data: { [queryKey]: input },
          });

          // Assuming the response contains the data list directly
          setList(response.data ?? []);
        } catch (err) {
          console.error('Error fetching list:', err);
          setError(err.message || 'An error occurred');
        } finally {
          setLoading(false);
        }
      } else {
        setList([]);
      }
    }, debounceDelay),
    [url, method, queryKey, debounceDelay]
  );

  return { list, fetchList, loading, error };
};

export const useInertiaFetchByInput = ({ only, method = 'post', queryKey = 'userInput', debounceDelay = 200 }) => {
  const [list, setList] = useState([]);

  const fetchList = useCallback(
    debounce((input) => {
      if (input.length > 0) {
        router.reload({
          method: method,
          data: { [queryKey]: input },
          only: only,
          onSuccess: (response) => {
            setList(response.props[only] ?? []);
          },
        });
      } else {
        setList([]);
      }
    }, debounceDelay),
    [only, method]
  );

  return { list, fetchList };
};



// Inertia.js seems to conflate the onSuccess when multiple calls are made at the same time. That why this custom hook needs all the partials reloads at once
export const useInertiaFetchList = ({ only, payload = {}, method = 'post', eager = false }) => {

  const [list, setList] = useState([]);

  const fetchList = useCallback(() => {
    router.reload({
      method: method,
      only: only,
      data: payload,
      onSuccess: (response) => {
        if (!Array.isArray(only) || only.length === 0) {
          console.error('`only` must be a non-empty array');
          return;
        }

        const data =
          only.length > 1
            ? only.reduce((acc, key) => {
              acc[key] = response.props[key] ?? [];
              return acc;
            }, {})
            : response.props[only[0]] ?? [];

        setList(data);
      },
    });

  }, [payload]);

  useEffect(() => {
    if (eager) {
      fetchList(); // Fetch immediately if eager is true
    }
  }, []);

  return { list, fetchList };
};

export const useInertiaFetchListReload = ({ only, data, method = 'post' }) => {

  const [list, setList] = useState([]);

  const fetchList = () => {
    router.reload({
      method: method,
      only: only,
      data: data,
      onFinish: (visit) => { console.log(visit) },
      onSuccess: (response) => {
        data = {}
        // Assuming `only` is an array
        only.forEach((key) => {
          // Set each `key` in the list with the corresponding response value or an empty array
          data[key] = response.props[key] ?? [];
        });

        setList(data)
      },
    })
  };

  return { list, fetchList };
};

export const inertiaResourceSync = (resource) => {
  // Fetch updated task data
  router.reload({
    only: resource,
    onError: (error) => console.error("Error fetching announcements:", error),
  });
}

export const useFetchList = ({ url, payload = {}, method = "post", eager = false }) => {
  const [list, setList] = useState([]);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);

  const fetchList = useCallback(async () => {
    setLoading(true);
    setError(null);

    try {
      const response = await axios({
        url,
        method,
        data: payload,
      });

      if (response.status === 200) {
        // Set the list
        setList(response.data || []);
      }


    } catch (err) {
      setError(err.message || "An error occurred while fetching the list");
      console.error(err);
    } finally {
      setLoading(false);
    }
  }, [url, payload, method]);

  useEffect(() => {
    if (eager) {
      fetchList(); // Fetch immediately if eager is true
    }
  }, [fetchList, eager]);

  return { list, fetchList, loading, error };
};


export const getRecord = async ({ url }) => {

  try {
    const response = await axios.get(url);

    if (response.status === 200) {
      return response.data;
    } else {
      console.error(`Unexpected response status: ${response.status}`);
      return null;
    }
  } catch (error) {
    console.error(`Failed to fetch data from ${url}:`, error);
    return null;
  }
};

export const updateTask = async (values, row, options = {}) => {

  const { onBefore, onSuccess, onComplete, onError } = options;
  const originalRow = { ...row };
  //const updatedAtISOString = format(new Date(), "yyyy-MM-dd'T'HH:mm:ssXXX", { timeZone: 'Europe/Paris' });
  const updatedAtISOString = new Date().toISOString();

  // Optimistically update the UI
  if (onBefore) {

    await onBefore({
      originalRow: originalRow,
      updatedAt: updatedAtISOString,
    });
  }
  // console.log('delayed started')
  // await delay(7000);
  // console.log('delayed ended')

  try {
    const response = await axios.post(
      `/task/${originalRow.id}/update`,
      {
        ...values,
        beforeUpdateAt: originalRow.updated_at, // Include the task updated_at before it was updated
        updated_at: updatedAtISOString,
      }
    );

    if (response.status === 200) {
      // Use the callback to update the row
      if (onSuccess) onSuccess({ updatedRow: response.data });
      if (onComplete) onComplete(true);
    }

  } catch (error) {
    
    if (onComplete) onComplete(false);
    console.error("Error response:", error.response); // Debugging

    // Revert to the original state if the update fails
    if (onError) onError({ originalRow: originalRow });

    if (error.response) {
      if (error.response.status === 422) {
        console.log("Validation Errors:", error.response.data.errors); // Debug
        toast.error(error.response.data.message, { duration: 6000 });
      } else if (error.response.status === 409) {
        toast.error(error.response.data.message, { duration: 6000 });
        if (onRowUpdate) onRowUpdate(error.response.data.latestData);
      } else {
        toast.error("Er is een netwerkfout opgetreden. Probeer het alstublieft opnieuw");
      }
    } else {
      console.error("Request failed but no response received:", error);
      toast.error("Er is een onbekende fout opgetreden. Controleer uw netwerkverbinding.");
    }


  }
};