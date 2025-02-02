import * as React from "react"
import * as TabsPrimitive from "@radix-ui/react-tabs"

import { cn } from '@/utils'

const Tabs = TabsPrimitive.Root

const TabsList = React.forwardRef(({ className, ...props }, ref) => (
  <TabsPrimitive.List
    ref={ref}
    className={cn(
      "flex border border-r-0 rounded-sm overflow-hidden",
      className
    )}
    {...props}
  />
));
TabsList.displayName = TabsPrimitive.List.displayName;

const TabsTrigger = React.forwardRef(({ className, ...props }, ref) => (
  <TabsPrimitive.Trigger
    ref={ref}
    className={cn(
      " flex-1 p-2 border-r text-center cursor-pointer text-sm font-medium transition-colors", // Base styles
      "bg-white text-gray-500 hover:bg-gray-50", // Inactive state
      "data-[state=active]:bg-gray-50 data-[state=active]:shadow-inner data-[state=active]:text-gray-900", // Active state

      className
    )}
    {...props}
  />
));
// "data-[state=active]:bg-primary/90 data-[state=active]:text-white data-[state=active]:shadow-lg",
TabsTrigger.displayName = TabsPrimitive.Trigger.displayName;

const TabsContent = React.forwardRef(({ className, ...props }, ref) => (
  <TabsPrimitive.Content
    ref={ref}
    className={cn(
      "p-2 mt-2 border-gray-300",
      className
    )}
    {...props}
  />
));
TabsContent.displayName = TabsPrimitive.Content.displayName;

export { Tabs, TabsList, TabsTrigger, TabsContent }


