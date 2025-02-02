import * as React from "react"

import { cn } from '@/utils'

const Input = React.forwardRef(({ className, type, ...props }, ref) => {
  return (
    (<input
      type={type}
      className={cn(
        "flex h-8 w-full rounded border border-slate-200 bg-transparent px-3 py-1 text-xs shadow-sm transition-colors focus-within:outline-none focus-within:ring-0 file:border-0 file:bg-transparent file:text-xs file:font-medium placeholder:text-slate-500 focus-visible:outline-none focus-visible:ring-0 focus-visible:ring-slate-950 disabled:cursor-not-allowed disabled:opacity-50 dark:border-slate-800 dark:placeholder:text-slate-400 dark:focus-visible:ring-slate-300 focus:border-0 focus:border-transparent",
        className
      )}
      ref={ref}
      {...props} />)
  );
})
Input.displayName = "Input"

export { Input }