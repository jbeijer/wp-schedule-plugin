import apiFetch from '@wordpress/api-fetch';

// Configure apiFetch using the localized wpApiSettings object
// This ensures it uses the correct root URL and nonce.
if ( window.wpApiSettings?.root && window.wpApiSettings?.nonce ) {
    apiFetch.use( apiFetch.createRootURLMiddleware( window.wpApiSettings.root ) );
    apiFetch.use( apiFetch.createNonceMiddleware( window.wpApiSettings.nonce ) );
    console.log('apiFetch configured with root:', window.wpApiSettings.root, 'and nonce.'); // DEBUG
} else {
    console.error( 'wpApiSettings object not found or incomplete. API Fetch may not work correctly.' );
}

// Export the pre-configured instance for use in components
export default apiFetch;