import { defineConfig, loadEnv } from 'vite'
import react from '@vitejs/plugin-react'
import laravel from 'laravel-vite-plugin'
import path from 'path'
import fs from 'fs';

export default defineConfig(({ command, mode }) => {

  // Load environment variables based on the `mode`
  const env = loadEnv(mode, process.cwd(), '');

  const config = {
    plugins: [
      laravel({
        input: [
          'resources/js/src/app.jsx',
          'resources/js/src/filament/app.js',
          'resources/css/filament.css'
        ],
        refresh: true
      }),

      react(),
    ],
    resolve: {
      alias: {
        '@': path.resolve(__dirname, './resources/js/src'),
        '@images': path.resolve(__dirname, './resources/images'),
        '@css': path.resolve(__dirname, './resources/css'),
        '@json': path.resolve(__dirname, './resources/json')
      }
    },

  };

  if (command === 'serve') {
    // Dev specific config
    Object.assign(config, {
      watch: {
        usePolling: true
      },
      server: {
        host: '0.0.0.0', // This makes the server accessible externally
        port: 3001,      // Optional: specify a port (default is 3000)
        https: {
          key: fs.readFileSync('C:/wamp64/bin/apache/apache2.4.54.2/conf/ssl.key/server.key'),
          cert: fs.readFileSync('C:/wamp64/bin/apache/apache2.4.54.2/conf/ssl.crt/server.crt'),
        },
        hmr: {
          host: env.VITE_APP_URL.replace('https://',''),  // Replace with your development machine's IP address
          port: 3001,              // Ensure this matches the Vite server port
        },
      },
    })

  } else {
      // Production specific config
  }
  return config
})