nimport { v4wp } from "@kucrut/vite-for-wp";
import { svelte } from "@sveltejs/vite-plugin-svelte";

export default {
    plugins: [
        svelte({
            // compilerOptions: { // Removed compatibility option
            //     // Använd Svelte 4 component API för kompatibilitet
            //     // compatibility: {
            //     //     componentApi: 4,
            //     // },
            // },
        }),
        v4wp({
            input: ["app/src/main.js", "app/src/admin.js"],
            outDir: "app/dist"
        }),
    ],
    server: {
        // origin: "http://localhost:5173", // Removed this line
        host: true, // Ensure Vite listens on all network interfaces
        headers: {
            "Access-Control-Allow-Origin": "*",
        },
    },
    build: {
        outDir: 'app/dist', // Ensure outDir is also specified here
        manifest: true,
        rollupOptions: {
            output: {
                format: 'iife' // Important for WP compatibility
            }
        }
    }
};