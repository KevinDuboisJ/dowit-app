import preset from './vendor/filament/filament/tailwind.config.preset'

export default {
    presets: [preset],
    content: [
        './app/Filament/**/*.php',
        './resources/views/filament/**/*.blade.php',
        './vendor/filament/**/*.blade.php',
        './vendor/awcodes/filament-tiptap-editor/resources/**/*.blade.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Geist', 'Roboto', 'Helvetica', 'Arial', 'sans-serif'],
            },
            colors: {

                app: {
                    background: {
                        DEFAULT: 'hsl(0 0% 98%)',
                        //secondary: withOpacityValue('--app-background-secondary'),
                    }

                }

            },
        },
    },
}