import { useEffect, useCallback, useState, useRef } from 'react';
import { usePage } from '@inertiajs/react';
import axios from 'axios';

export const useWebSocket = () => {
  const { user } = usePage().props;
  const [newEvent, setNewEvent] = useState(null);
  const processedEventIds = useRef(new Set());

  // Store and update the latest event
  const onEvent = useCallback((event) => {

    if (!shouldProcessEvent(processedEventIds.current, event, user)) return;

    setNewEvent(event); // Updates state when an event arrives
    console.log('Event is processed');

  }, []);

  // Connect WebSocket
  const connectWebSocket = useCallback(() => {

    if (!user?.id) return;
    try {
      window.Echo.private(`user.${user.id}`).listen('BroadcastEvent', onEvent);

      user.teams.forEach((team) => {
        window.Echo.private(`team.${team.id}`).listen('BroadcastEvent', onEvent);
      });
    } catch (error) {
      console.error('WebSocket connection error:', error);
    }
  }, [user]);

  // Workaround for connectivity issues
  const reconnectWebSocket = useCallback(async () => {
    try {
      await axios.get(import.meta.env.VITE_APP_URL + ':6002', { mode: 'no-cors' });
    } catch (error) {
      // Silently handle error
    }
    connectWebSocket();
  }, [connectWebSocket]);

  // Set up WebSocket on mount, clean up on unmount
  useEffect(() => {
    reconnectWebSocket();
    connectWebSocket();

    return () => {
      if (user?.id) {
        window.Echo.leave(`user.${user.id}`);
      }
      user?.teams?.forEach((team) => {
        window.Echo.leave(`team.${team.id}`);
      });
    };
  }, [user]);

  return { onEvent, newEvent }; // Expose onEvent and newEvent
};

// Determines whether a broadcast event should be processed
export const shouldProcessEvent = (processedEventIds, event, user) => {

  const MAX_CACHE_SIZE = 10;

  console.log("Processing check for WebSocket event:", event);

  if (event.createdBy === user.id && event.source === 'dashboard') {
    console.log("WebSocket event created by the current user, skipped:", event);
    return false;
  }

  if (processedEventIds.has(event.id)) {
    console.log("WebSocket event already processed, skipped:", event);
    return false;
  }

  // Add to cache (limit to last 10)
  processedEventIds.add(event.id);

  if (processedEventIds.size > MAX_CACHE_SIZE) {
    // Convert Set to array, keep only last 10, then convert back to Set
    processedEventIds = new Set([...processedEventIds].slice(-MAX_CACHE_SIZE));
  }

  return true;

};