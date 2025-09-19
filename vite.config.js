import { defineConfig } from 'vite'
import laravel from 'laravel-vite-plugin'
import react from '@vitejs/plugin-react'

export default defineConfig({
  plugins: [
    react(),
    laravel({ 
      input: 'resources/js/app.jsx', 
      refresh: true,
      detectTls: false
    }),
  ],
  build: {
    manifest: true,
    outDir: 'public/build',
    rollupOptions: {
      input: 'resources/js/app.jsx'
    }
  }
})