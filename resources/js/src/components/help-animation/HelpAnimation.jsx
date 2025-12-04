import React from 'react'

// Memoized component that only re-renders when `needsHelp` or `isAssignedToCurrentUser` changes.
export const HelpAnimation = React.memo(
  ({ needsHelp, isAssignedToCurrentUser }) => {
    if (!needsHelp || isAssignedToCurrentUser) return null

    return (
      <span className="help-icon">
        <span className="help-icon-core">?</span>
        <span className="help-icon-ring" />
      </span>
    )
  }
)
