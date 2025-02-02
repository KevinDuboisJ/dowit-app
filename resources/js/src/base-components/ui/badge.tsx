import * as React from "react"
import { cva, type VariantProps } from "class-variance-authority"

import { cn } from "@/utils"

const badgeVariants = cva(
  "inline-flex items-center rounded-md border px-2.5 py-0.5 text-xs font-medium transition-colors focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2",
  {
    variants: {
      variant: {
        default:
          "border-transparent bg-primary text-primary-foreground shadow hover:bg-primary/80",
        secondary:
          "border-transparent bg-secondary text-secondary-foreground hover:bg-secondary/80",
        destructive:
          "border-transparent bg-destructive text-destructive-foreground shadow hover:bg-destructive/80",
        outline:
          "text-foreground",
        progress:
          "font-medium text-xs rounded bg-indigo-50 text-gray-900 border border-indigo-200 hover:bg-indigo-100/50",
        success:
          "font-normal text-xs rounded bg-success/20 text-success border border-success/20 hover:bg-success/20",

        Added:
          "font-medium text-xs rounded bg-indigo-50 text-gray-900 border border-indigo-200 hover:bg-indigo-100/50",
        Replaced:
          "font-medium text-xs rounded bg-indigo-50 text-gray-900 border border-indigo-200 hover:bg-indigo-100/50",
        InProgress:
          "font-medium text-xs rounded bg-indigo-50 text-gray-900 border border-indigo-200 hover:bg-indigo-100/50",
        WaitingForSomeone:
          "font-normal text-xs rounded bg-success/20 text-success border border-success/20 hover:bg-success/20",
        Completed:
          "font-normal text-xs rounded bg-success/20 text-success border border-success/20 hover:bg-success/20",
        Skipped:
          "font-normal text-xs rounded bg-success/20 text-success border border-success/20 hover:bg-success/20",
      },
    },
    defaultVariants: {
      variant: "default",
    },
  }
)

export interface BadgeProps
  extends React.HTMLAttributes<HTMLDivElement>,
  VariantProps<typeof badgeVariants> { }

function Badge({ className, variant, ...props }: BadgeProps) {
  return (
    <div className={cn(badgeVariants({ variant }), className)} {...props} />
  )
}

export { Badge, badgeVariants }
