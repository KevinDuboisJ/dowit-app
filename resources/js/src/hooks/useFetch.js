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

export const inertiaResourceSync = (resource, options = {}) => {

  const { onSuccess } = options;

  router.reload({
    only: resource,
    onSuccess: (response) => onSuccess?.(response.props),
    onError: (error) => console.error("Error from inertiaResourceSync:", error),
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

// Global counter to track update requests.
let currentUpdateSequence = 0;

export const updateTask = async (values, row, options = {}) => {
  const { onBefore, onSuccess, onComplete, onError } = options;
  const updatedAtISOString = new Date().toISOString().split(".")[0] + "Z";

  // Increment the counter and capture the sequence for this request.
  currentUpdateSequence++;
  const thisRequestSequence = currentUpdateSequence;

  // Optimistic UI update
  onBefore?.({
    original: row,
    updatedAt: updatedAtISOString,
  });
console.log(values)
  try {
    const response = await axios.post(`/task/${row.id}/update`, {
      ...values,
      beforeUpdateAt: row.updated_at,
      updated_at: updatedAtISOString,
    });

    // Only apply the response if this is the latest request.
    if (thisRequestSequence === currentUpdateSequence && response.status === 200) {
      onSuccess?.(response.data);
    } else {
      console.log("Stale response ignored");
    }
  } catch (error) {
    if (thisRequestSequence !== currentUpdateSequence) {
      // Ignore error from an outdated request.
      console.log("Stale error ignored");
    } else {
      console.error("Task Update Error:", error);
      onError?.({ original: row, ...error?.response });

      if (!error.response) {
        toast.error("Netwerkfout. Controleer uw verbinding.");
      } else {
        const { status, data } = error.response;
        if (status === 422) {
          console.log("Validation Errors:", data.errors);
          toast.error(data.message, { duration: 6000 });
        } else if (status === 409) {
          toast.error(data.message, { duration: 6000 });
        } else {
          toast.error("Er is een fout opgetreden. Probeer opnieuw.");
        }
      }
    }
  } finally {
    if (thisRequestSequence === currentUpdateSequence) {
      onComplete?.(true);
    }
  }
};