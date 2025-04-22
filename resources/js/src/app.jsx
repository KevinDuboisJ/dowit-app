// import React from "react";
import '@css/app.css';
import './echo';
import { createRoot } from 'react-dom/client';
import { createInertiaApp } from "@inertiajs/react"
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import Layout from '@/layouts/Layout.jsx'


createInertiaApp({
  
  resolve: async (name) => {
    const page = await resolvePageComponent(`./pages/${name}.jsx`, import.meta.glob('./pages/**/*.{js,jsx}'));
    page.default.layout = name.startsWith('Login') ? undefined : (page => <Layout children={page} />)
    return page;
  },

  setup({ el, App, props }) {
    return createRoot(el).render(<App {...props} />)
  },

  progress: {
    // The delay after which the progress bar will appear, in milliseconds...
    delay: 300,

    // The color of the progress bar...
    color: '#29d',

    // Whether to include the default NProgress styles...
    includeCSS: true,

    // Whether the NProgress spinner will be shown...
    showSpinner: false,
  },
});
