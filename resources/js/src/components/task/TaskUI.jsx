import React, { useState } from 'react';
import cn from 'classnames';

export function TaskIcon({ iconName, className }) {
  const [isError, setIsError] = useState(false);

  if (!iconName || isError) return null;

  // Remove 'az-' prefix if present
  const cleanedIconName = iconName.replace(/^az-/, '');
  const iconUrl = `/images/icons/${cleanedIconName}.svg`;

  return (
    <img
      src={iconUrl}
      alt={iconName}
      className={cn("w-3.5 h-3.5 mr-1", className)}
      onError={() => setIsError(true)}
    />
  );
}