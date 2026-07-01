import type { VariantProps } from "class-variance-authority"
import { cva } from "class-variance-authority"

export { default as Button } from "./Button.vue"

export const buttonVariants = cva(
  "inline-flex items-center justify-center gap-1.5 whitespace-nowrap rounded-md text-sm font-medium shadow-xs transition-all disabled:pointer-events-none disabled:opacity-50 [&_svg]:pointer-events-none [&_svg:not([class*='size-'])]:size-3.5 shrink-0 [&_svg]:shrink-0 outline-none focus-visible:border-ring focus-visible:ring-ring/45 focus-visible:ring-[3px] aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40 aria-invalid:border-destructive",
  {
    variants: {
      variant: {
        default:
          "border border-primary/15 bg-primary text-primary-foreground shadow-primary/20 hover:bg-primary/90 hover:shadow-md hover:shadow-primary/20",
        destructive:
          "border border-destructive/15 bg-destructive text-white shadow-destructive/15 hover:bg-destructive/90 hover:shadow-md hover:shadow-destructive/20 focus-visible:ring-destructive/20 dark:focus-visible:ring-destructive/40",
        outline:
          "border border-border/80 bg-card shadow-xs hover:border-primary/30 hover:bg-primary/10 hover:text-primary dark:bg-input/30 dark:border-input dark:hover:bg-primary/15",
        secondary:
          "border border-secondary/15 bg-secondary text-secondary-foreground shadow-secondary/15 hover:bg-secondary/90 hover:shadow-md hover:shadow-secondary/20",
        ghost:
          "shadow-none hover:bg-primary/10 hover:text-primary dark:hover:bg-primary/15",
        link: "text-primary underline-offset-4 hover:underline",
      },
      size: {
        "default": "h-9 px-4 py-2 has-[>svg]:px-3.5",
        "sm": "h-8 rounded-md gap-1 px-3.5 has-[>svg]:px-3",
        "lg": "h-10 rounded-md px-6 has-[>svg]:px-5",
        "icon": "size-9",
        "icon-sm": "size-8",
        "icon-lg": "size-10",
      },
    },
    defaultVariants: {
      variant: "default",
      size: "default",
    },
  },
)
export type ButtonVariants = VariantProps<typeof buttonVariants>
