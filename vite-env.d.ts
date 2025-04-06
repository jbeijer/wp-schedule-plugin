/// <reference types="svelte" />
/// <reference types="vite/client" />

declare module "*.svg" {
    const content: any;
    export default content;
}

// Schedule frontend data interface
interface WpScheduleFrontendData {
    scheduleData?: any;
    // Andra egenskaper som behövs för schemaläggningsfunktionen
}

// Schedule admin data interface
interface WpScheduleAdminData {
    scheduleItems?: any[];
    nonce: string;
    ajaxUrl: string;
    // Andra admin-relaterade egenskaper
}

declare global {
    interface Window {
        wpScheduleFrontendData?: WpScheduleFrontendData;
        wpScheduleAdminData?: WpScheduleAdminData;
    }
}