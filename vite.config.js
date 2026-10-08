import { defineConfig } from 'vite';
const entry = process.env.QRP_ENTRY || 'admin';
export default defineConfig({build:{outDir:'qr-pelplin/assets/dist',emptyOutDir:entry==='admin',lib:{entry:`src/${entry}.js`,formats:['iife'],name:'QRP',fileName:()=>`${entry}.js`},rollupOptions:{output:{inlineDynamicImports:true}}}});
