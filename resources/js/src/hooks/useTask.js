import { useState, useMemo } from "react";

export const useTask = (initialData, filters) => {

  const [tasks, setTasks] = useState(initialData.data);
  const [pagination, setPagination] = useState(() => {
    const { data, ...rest } = initialData;
    return rest;
  });

  // Compute `todoTasks`, `openTasks`, and merged `tasks` together in one go
  const { todoTasks, openTasks, sortedTasks } = useMemo(() => {
    const tasksData = tasks || [];
    const todo = [];
    const open = [];

    tasksData.forEach((task) => {
      if (task.status.name === "Completed" && filters?.status_id?.value !== "Completed") return;

      if (task.capabilities.isAssignedToCurrentUser) {
        todo.push(task);
      } else {
        open.push(task);
      }
    });

    // Sort both arrays by start_date_time (descending)
    const sortByStartDateTime = (a, b) => new Date(b.start_date_time) - new Date(a.start_date_time);
    todo.sort(sortByStartDateTime);
    open.sort(sortByStartDateTime);

    return {
      todoTasks: todo,
      openTasks: open,
      sortedTasks: [...todo, ...open],
    };
  }, [tasks]); // Runs only when `tasks` change


  // mergeTasks: Replace tasks with new tasks, preserving the object reference for unchanged tasks and removing tasks that no longer exist.
  const mergeTasks = (newTasks) => {
    setTasks((prevTasks) => {
      // Create a map for quick lookup of previous tasks by their unique id.
      const prevTasksMap = new Map(prevTasks.map(task => [task.id, task]));

      // Map over new tasks:
      return newTasks.map(newTask => {
        const prevTask = prevTasksMap.get(newTask.id);
        // If the task exists and is unchanged, reuse the previous object reference.
        if (prevTask && shallowEqual(prevTask, newTask)) {
          return prevTask;
        }
        // Otherwise, return the new task.
        return newTask;
      });
    });
  };

  return { tasks: sortedTasks, setTasks: setTasks, mergeTasks: mergeTasks, todoTasks, openTasks, pagination, setPagination };

};

function shallowEqual(obj1, obj2) {
  if (obj1 === obj2) return true;
  if (typeof obj1 !== 'object' || obj1 === null ||
    typeof obj2 !== 'object' || obj2 === null) {
    return false;
  }
  const keys1 = Object.keys(obj1);
  const keys2 = Object.keys(obj2);
  if (keys1.length !== keys2.length) return false;
  for (const key of keys1) {
    if (obj1[key] !== obj2[key]) {
      return false;
    }
  }
  return true;
}
