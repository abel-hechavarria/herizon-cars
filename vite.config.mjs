import { defineConfig } from 'vite';
import { resolve } from 'node:path';
export default defineConfig({
  base: './',
  esbuild: { jsxFactory: 'wp.element.createElement', jsxFragment: 'wp.element.Fragment' },
  build: {
    outDir: 'dist', emptyOutDir: true, manifest: true, cssCodeSplit: true,
    rollupOptions: {
      preserveEntrySignatures: 'strict',
      input: {
        main: resolve(import.meta.dirname, 'src/js/main.js'),
        widgets: resolve(import.meta.dirname, 'src/js/widgets.js'),
        'widget-styles': resolve(import.meta.dirname, 'src/scss/widgets.scss'),
        'button-editor': resolve(import.meta.dirname, 'src/js/button-editor.jsx'),
        'button-elementor': resolve(import.meta.dirname, 'src/js/button-elementor.js'),
        'button-styles': resolve(import.meta.dirname, 'src/scss/button.scss'),
        'process-styles': resolve(import.meta.dirname, 'src/scss/process.scss'),
        'import-form': resolve(import.meta.dirname, 'src/js/import-form.js'),
        'import-form-styles': resolve(import.meta.dirname, 'src/scss/import-form.scss'),
      },
      output: { entryFileNames: 'assets/[name]-[hash].js', chunkFileNames: 'assets/[name]-[hash].js', assetFileNames: 'assets/[name]-[hash][extname]' },
    },
  },
});
