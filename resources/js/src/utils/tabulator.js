import React from 'react';
import { createRoot } from 'react-dom/client';

export function reactFormatter(JSX) {
  return function customFormatter(cell, formatterParams, onRendered) {
    // cell - the cell component
    // formatterParams - parameters set for the column
    // onRendered - function to call when the formatter has been rendered
    const renderFn = () => {
      const cellEl = cell.getElement();
      if (cellEl) {
        const formatterCell = cellEl.querySelector('.formatterCell');
        if (formatterCell) {
          const CompWithMoreProps = React.cloneElement(JSX, { cell });


          // Create a root for the formatterCell if it doesn't already have one
          let root = formatterCell._reactRoot;
          if (!root) {
            root = createRoot(formatterCell);
            formatterCell._reactRoot = root; // Store the root for potential future re-renders
          }

          // Render the component
          root.render(CompWithMoreProps);
        }
      }
    };

    onRendered(renderFn); // initial render only.

    setTimeout(() => {
      renderFn(); // render every time cell value changed.
    }, 0);
    return '<div class="formatterCell"></div>';
  };
}