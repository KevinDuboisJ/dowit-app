const colors = require('tailwindcss/colors');
const {
  toRGB,
  withOpacityValue,
} = require('@left4code/tw-starter/dist/js/tailwind-config-helper');

module.exports = {
  content: [
    './resources/**/*.{php,html,js,jsx,ts,tsx,vue,blade.php}',
    './node_modules/@left4code/tw-starter/**/*.js',
  ],
  darkMode: 'class',
  theme: {
    extend: {
      fontFamily: {
        sans: ['Inter', 'Roboto', 'Helvetica', 'Arial', 'sans-serif'],
        inter: ['Inter'],
        roboto: ['Roboto'],
      },
      fontSize: {
        base: 'var(--font-base-size, 16px)',
        xs: 'calc(var(--font-base-size) * 0.75)',
        sm: 'calc(var(--font-base-size) * 0.875)',
        lg: 'calc(var(--font-base-size) * 1.125)',
        xl: 'calc(var(--font-base-size) * 1.25)',

      },
      colors: {
        'azmPrimary': '#008F92',
        'whiteBlue': '#F7FAFC',
        'whiteGrey': '#E6E6E6',
        'infOrange': '#F7931E',
        rgb: toRGB({
          inherit: colors.inherit,
          current: colors.current,
          transparent: colors.transparent,
          black: colors.black,
          white: colors.white,
          slate: colors.slate,
          gray: colors.gray,
          zinc: colors.zinc,
          neutral: colors.neutral,
          stone: colors.stone,
          red: colors.red,
          orange: colors.orange,
          amber: colors.amber,
          yellow: colors.yellow,
          lime: colors.lime,
          green: colors.green,
          emerald: colors.emerald,
          teal: colors.teal,
          cyan: colors.cyan,
          sky: colors.sky,
          blue: colors.blue,
          indigo: colors.indigo,
          violet: colors.violet,
          purple: colors.purple,
          fuchsia: colors.fuchsia,
          pink: colors.pink,
          rose: colors.rose,
          sky: colors.sky,
          stone: colors.stone,
          neutral: colors.neutral,
          gray: colors.gray,
          slate: colors.slate,
        }),
        // primary: withOpacityValue('--color-primary'),
        //secondary: withOpacityValue('--color-secondary'),
        success: withOpacityValue('--color-success'),
        info: withOpacityValue('--color-info'),
        warning: withOpacityValue('--color-warning'),
        pending: withOpacityValue('--color-pending'),
        danger: withOpacityValue('--color-danger'),
        light: withOpacityValue('--color-light'),
        dark: withOpacityValue('--color-dark'),
        slate: {
          50: withOpacityValue('--color-slate-50'),
          100: withOpacityValue('--color-slate-100'),
          200: withOpacityValue('--color-slate-200'),
          300: withOpacityValue('--color-slate-300'),
          400: withOpacityValue('--color-slate-400'),
          500: withOpacityValue('--color-slate-500'),
          600: withOpacityValue('--color-slate-600'),
          700: withOpacityValue('--color-slate-700'),
          800: withOpacityValue('--color-slate-800'),
          900: withOpacityValue('--color-slate-900'),
        },
        darkmode: {
          50: withOpacityValue('--color-darkmode-50'),
          100: withOpacityValue('--color-darkmode-100'),
          200: withOpacityValue('--color-darkmode-200'),
          300: withOpacityValue('--color-darkmode-300'),
          400: withOpacityValue('--color-darkmode-400'),
          500: withOpacityValue('--color-darkmode-500'),
          600: withOpacityValue('--color-darkmode-600'),
          700: withOpacityValue('--color-darkmode-700'),
          800: withOpacityValue('--color-darkmode-800'),
          900: withOpacityValue('--color-darkmode-900'),
        },
        border: 'hsl(var(--border))',
        input: 'hsl(var(--input))',
        ring: 'hsl(var(--ring))',
        background: 'hsl(var(--background))',
        foreground: 'hsl(var(--foreground))',
        primary: {
          DEFAULT: 'hsl(var(--primary))',
          foreground: 'hsl(var(--primary-foreground))',
        },
        secondary: {
          DEFAULT: 'hsl(var(--secondary))',
          foreground: 'hsl(var(--secondary-foreground))',
        },
        destructive: {
          DEFAULT: 'hsl(var(--destructive))',
          foreground: 'hsl(var(--destructive-foreground))',
        },
        muted: {
          DEFAULT: 'hsl(var(--muted))',
          foreground: 'hsl(var(--muted-foreground))',
        },
        accent: {
          DEFAULT: 'hsl(var(--accent))',
          foreground: 'hsl(var(--accent-foreground))',
        },
        popover: {
          DEFAULT: 'hsl(var(--popover))',
          foreground: 'hsl(var(--popover-foreground))',
        },
        card: {
          DEFAULT: 'hsl(var(--card))',
          foreground: 'hsl(var(--card-foreground))',
        },
        maxWidth: {
          '1/4': '25%',
          '1/2': '50%',
          '3/4': '75%',
        },
        strokeWidth: {
          0.5: 0.5,
          1.5: 1.5,
          2.5: 2.5,
        },

        app: {
          background: {
            DEFAULT: withOpacityValue('--app-background'),
            secondary: withOpacityValue('--app-background-secondary'),
          }

        }

      },
      dropShadow: {
        'md2': '0.1px 0.1px 2px rgb(0 0 0 / 25%);',
      },
      boxShadow: {
        't-sm': '0 -1px 2px 0 rgba(0, 0, 0, 0.05)',
        't-md': '0 -4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06)',
        't-lg': '0 -10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05)',
        't-xl': '0 -20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04)',
        't-2xl': '0 -25px 50px -12px rgba(0, 0, 0, 0.25)',
        't-3xl': '0 -35px 60px -15px rgba(0, 0, 0, 0.3)',
      },
      borderRadius: {
        lg: `var(--radius)`,
        md: `calc(var(--radius) - 2px)`,
        sm: 'calc(var(--radius) - 4px)',
      },
      keyframes: {
        'accordion-down': {
          from: { height: '0' },
          to: { height: 'var(--radix-accordion-content-height)' },
        },
        'accordion-up': {
          from: { height: 'var(--radix-accordion-content-height)' },
          to: { height: '0' },
        },
      },
      animation: {
        'accordion-down': 'accordion-down 0.2s ease-out',
        'accordion-up': 'accordion-up 0.2s ease-out',
      },
    },
  },
  plugins: [
    require('@tailwindcss/forms'),
    require('tailwindcss-animate')
  ],
  variants: {
    extend: {
      boxShadow: ['dark'],
    },
  },
};