import React, { useState, useRef, forwardRef, useContext, useImperativeHandle } from "react";
import { cn } from '@/utils';

interface SegmentButtonRef {
  value: string;
  setValue: (value: string) => void;
}

interface SegmentButtonContextProps {
  value: string;
  setValue: (value: string) => void;
  disabled: boolean;
}

const SegmentButtonContext = React.createContext<SegmentButtonContextProps | null>(null);

interface SegmentButtonProps {
  defaultValue?: string;
  disabled?: boolean;
  onValueChange?: (value: string) => void;
  children: React.ReactNode;
}

export const SegmentButton = forwardRef<SegmentButtonRef, SegmentButtonProps>(
  ({ defaultValue = "", disabled = false, onValueChange, children }, ref) => {
    const internalRef = useRef(defaultValue);
    const [selectedValue, setSelectedValue] = useState(defaultValue);

    useImperativeHandle(ref, () => ({
      value: internalRef.current,
      setValue: (val: string) => {
        internalRef.current = val;
        setSelectedValue(val);
        onValueChange?.(val);
      },
    }));

    const handleValueChange = (value: string) => {
      if (disabled) return;
      internalRef.current = value;
      setSelectedValue(value);
      onValueChange?.(value);
    };

    return (
      <SegmentButtonContext.Provider
        value={{
          value: selectedValue,
          setValue: handleValueChange,
          disabled,
        }}
      >
        <div className="text-xs space-y-2">{children}</div>
      </SegmentButtonContext.Provider>
    );
  }
);

SegmentButton.displayName = "SegmentButton";

interface SegmentHeaderProps {
  children: React.ReactNode;
}

export const SegmentHeader: React.FC<SegmentHeaderProps> = ({ children }) => {
  const context = useContext(SegmentButtonContext);
  if (!context) {
    throw new Error("SegmentHeader must be used within a SegmentButton");
  }

  const { disabled } = context;

  return (
    <label
      className={cn(
        "text-sm font-medium",
        disabled ? "text-gray-400" : "text-gray-700"
      )}
    >
      {children}
    </label>
  );
};

interface SegmentInputProps {
  value: string;
  label: string;
  disabled?: boolean;
}

export const SegmentInput: React.FC<SegmentInputProps> = ({
  value,
  label,
  disabled: inputDisabled = false,
}) => {
  const context = useContext(SegmentButtonContext);
  if (!context) {
    throw new Error("SegmentInput must be used within a SegmentButton");
  }

  const { value: selectedValue, setValue, disabled: globalDisabled } = context;

  const isDisabled = globalDisabled || inputDisabled;

  return (
    <button
      type="button"
      onClick={() => !isDisabled && setValue(value)}
      disabled={isDisabled}
      className={cn(
        "min-w-24 px-2 py-1 rounded border", // Base classes
        {
          "bg-gray-200 text-gray-400 border-gray-300 cursor-not-allowed": isDisabled,
          "bg-blue-100 text-blue-600 border-blue-500": selectedValue === value,
          "bg-white text-gray-600 border-gray-300 hover:bg-gray-100":
            !isDisabled && selectedValue !== value,
        }
      )}
    >
      {label}
    </button>
  );
};

interface SegmentInputContainerProps {
  children: React.ReactNode;
  className?: string;
}

export const SegmentInputContainer: React.FC<SegmentInputContainerProps> = ({
  children,
  className,
}) => {
  return (
    <div className={cn("flex flex-wrap items-center gap-2", className)}>
      {children}
    </div>
  );
};
