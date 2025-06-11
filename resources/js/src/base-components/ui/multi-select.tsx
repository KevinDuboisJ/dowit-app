import * as React from 'react';
import { cva, type VariantProps } from 'class-variance-authority';
import {
  CheckIcon,
  XCircle,
  ChevronDown,
  XIcon,
} from 'lucide-react';

import { cn } from '@/utils'
import {
  Separator,
  Button,
  Badge,
  Popover,
  PopoverContent,
  PopoverTrigger,
  Command,
  CommandEmpty,
  CommandGroup,
  CommandItem,
  CommandList,
  Input,
  ScrollArea,
} from '@/base-components';

import { debounce } from 'lodash';

/**
 * Variants for the multi-select component to handle different styles.
 * Uses class-variance-authority (cva) to define different styles based on "variant" prop.
 */
const multiSelectVariants = cva(
  "m-1 transition ease-in-out delay-150 hover:-translate-y-1 hover:scale-110 duration-300",
  {
    variants: {
      variant: {
        default:
          "border-transparent bg-primary text-primary-foreground shadow hover:bg-primary/80",
        secondary:
          "border-foreground/10 bg-secondary text-secondary-foreground hover:bg-secondary/80",
        destructive:
          "border-transparent bg-destructive text-destructive-foreground hover:bg-destructive/80",
        inverted:
          "inverted",
      },
    },
    defaultVariants: {
      variant: "default",
    },
  }
);

interface Option {
  label: string;
  value: string;
  icon?: React.ComponentType<{ className?: string }>;
}

interface MultiSelectProps
  extends React.ButtonHTMLAttributes<HTMLButtonElement>,
  VariantProps<typeof multiSelectVariants> {
  options: Option[];
  onValueChange: (value: Option[]) => void;
  handleInputOnChange?: (value: string) => void;
  selectedValues?: Option[];
  placeholder?: string;
  animation?: number;
  maxCount?: number;
  modalPopover?: boolean;
  asChild?: boolean;
  className?: string;
  maxSelection?: number;
}

export const MultiSelect = React.forwardRef<
  HTMLDivElement,
  MultiSelectProps
>(
  (
    {
      options,
      onValueChange,
      handleInputOnChange,
      variant,
      selectedValues = [],
      maxSelection = 10,
      placeholder = "Select options",
      animation = 0,
      maxCount = 10,
      modalPopover = false,
      asChild = false,
      className,
      ...props
    },
    ref
  ) => {
    const [inputValue, setInputValue] = React.useState('');
    const [isPopoverOpen, setIsPopoverOpen] = React.useState(false);
    const [isAnimating, setIsAnimating] = React.useState(false);

    const toggleOption = (option: Option) => {
      // Prevent adding more options if max selection is reached
      if (!selectedValues.some((item) => item.value === option.value) &&
        selectedValues.length >= maxSelection) {
        return;
      }

      const newSelectedValues = selectedValues.some((item) => item.value === option.value)
        ? selectedValues.filter((item) => item.value !== option.value)
        : [...selectedValues, option];

      onValueChange(newSelectedValues);
    };

    const handleClear = () => {
      onValueChange([]);
    };

    const clearExtraOptions = () => {
      const newSelectedValues = selectedValues.slice(0, maxCount);
      onValueChange(newSelectedValues);
    };

    const toggleAll = () => {
      if (selectedValues.length === options.length) {
        handleClear();
      } else {
        onValueChange(options);
      }
    };

    const handleInputChange = (e) => {

      const inputValue = e.target.value;
      setInputValue(inputValue);

      if (handleInputOnChange) {
        handleInputOnChange(inputValue);
      }

      // Open the popover if there is input
      setIsPopoverOpen(inputValue.length > 0);
    }

    const handlePopoverOpenChange = (open) => {

      setIsPopoverOpen(open)

      if (!open) {
        setInputValue('')
      }
    }


    // const filteredOptions = inputValue
    //   ? options.filter((option) =>
    //     option.label.toLowerCase().includes(inputValue.toLowerCase())
    //   )
    //   : options;

    const filteredOptions = options;

    return (
      <>
        <div
          ref={ref}
          className={cn(
            "flex flex-col w-full rounded border bg-white",
            className
          )}
          {...props}
        >
          {selectedValues.length > 0 && (
            <div className="flex flex-wrap items-center">
              {selectedValues.slice(0, maxCount).map((item) => {
                const IconComponent = item?.icon;
                return (
                  <Badge
                    key={item.value}
                    className={cn(
                      isAnimating ? "animate-bounce" : "",
                      multiSelectVariants({ variant })
                    )}
                    style={{ animationDuration: `${animation}s` }}
                  >
                    {IconComponent && (
                      <IconComponent className="h-4 w-4 mr-2" />
                    )}
                    {item.label}
                    <XCircle
                      className="ml-2 h-4 w-4 cursor-pointer"
                      onClick={(event) => {
                        event.stopPropagation();
                        toggleOption(item);
                      }}
                    />
                  </Badge>
                );
              })}
              {selectedValues.length > maxCount && (
                <Badge
                  className={cn(
                    "bg-transparent text-foreground border-foreground/1 hover:bg-transparent",
                    isAnimating ? "animate-bounce" : "",
                    multiSelectVariants({ variant })
                  )}
                  style={{ animationDuration: `${animation}s` }}
                >
                  {`+ ${selectedValues.length - maxCount} more`}
                  <XCircle
                    className="ml-2 h-4 w-4 cursor-pointer"
                    onClick={(event) => {
                      event.stopPropagation();
                      clearExtraOptions();
                    }}
                  />
                </Badge>
              )}
            </div>
          )}
          <div className="flex items-center">
            <Input
              value={inputValue}
              onChange={handleInputChange}
              placeholder={placeholder}
              className="border-none shadow-none flex-1 h-8 text-sm"
            />
            {selectedValues.length > 0 && (
              <XIcon
                className="h-4 mx-2 cursor-pointer text-muted-foreground"
                onClick={(event) => {
                  event.stopPropagation();
                  handleClear();
                }}
              />
            )}
            <Separator orientation="vertical" className="h-5 mx-1" />
            <ChevronDown className="h-4 cursor-pointer text-muted-foreground mx-2" onClick={() => setIsPopoverOpen(inputValue.length > 0)} />
          </div>
        </div>
        <Popover
          open={isPopoverOpen}
          onOpenChange={handlePopoverOpenChange}
          modal={modalPopover}
        >
          {isPopoverOpen && <PopoverTrigger />}
          {isPopoverOpen && (
            <PopoverContent
              className="min-w-44 p-0 -mt-[1.35rem] overflow-y-auto"
              align="start"
              onEscapeKeyDown={() => setIsPopoverOpen(false)}
              onOpenAutoFocus={(e) => e.preventDefault()}
            >
              <Command shouldFilter={false}>
                <CommandList>
                  {inputValue.length > 0 && filteredOptions.length === 0 && (
                    <CommandEmpty>Geen resultaten gevonden.</CommandEmpty>
                  )}
                  <CommandGroup>
                    {!handleInputOnChange && (
                      <CommandItem
                        key="all"
                        onSelect={toggleAll}
                        className="gap-1 text-sm cursor-pointer"
                      >
                        <div
                          className={cn(
                            "mr-2 flex h-[14px] w-[14px] items-center justify-center rounded-sm border border-primary",
                            options.length > 0 &&
                              selectedValues.length === options.length
                              ? "bg-primary text-primary-foreground"
                              : "opacity-50 [&_svg]:invisible"
                          )}
                        >
                          <CheckIcon />
                        </div>
                        <span>(Alles selecteren)</span>
                      </CommandItem>
                    )}
                    {filteredOptions.map((option) => {
                      const isSelected = selectedValues.some((selectedOption) => selectedOption.value === option.value);
                      const isDisabled = !isSelected && selectedValues.length >= maxSelection;

                      return (
                        <CommandItem
                          key={option.value}
                          onSelect={() => !isDisabled && toggleOption(option)}
                          className={cn(
                            { "opacity-50 cursor-not-allowed": isDisabled },
                            "gap-1 text-sm cursor-pointer"
                          )}
                          value={option.value}
                        >
                          <div
                            className={cn(
                              "mr-2 flex h-[14px] w-[14px] items-center justify-center rounded-sm border border-primary",
                              isSelected
                                ? "bg-primary text-primary-foreground"
                                : "opacity-50 [&_svg]:invisible"
                            )}
                          >
                            <CheckIcon />
                          </div>
                          {option.icon && (
                            <option.icon className="mr-2 h-2 w-2 text-muted-foreground" />
                          )}
                          <span>{option.label}</span>
                        </CommandItem>
                      );
                    })}
                  </CommandGroup>
                </CommandList>
              </Command>
            </PopoverContent>
          )}
        </Popover>
      </>
    );
  }
);

MultiSelect.displayName = "MultiSelect";