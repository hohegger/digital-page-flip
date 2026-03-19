import { defineConfig } from 'vite';

export default defineConfig({
  build: {
    outDir: 'Resources/Public/Build',
    emptyOutDir: true,
    manifest: true,
    rollupOptions: {
      input: {
        flipbook: 'Resources/Private/TypeScript/flipbook-init.ts',
        styles: 'Resources/Private/Css/flipbook.css',
      },
      output: {
        entryFileNames: 'js/[name]-[hash].js',
        chunkFileNames: 'js/[name]-[hash].js',
        assetFileNames: 'assets/[name]-[hash][extname]',
      },
    },
  },
});
