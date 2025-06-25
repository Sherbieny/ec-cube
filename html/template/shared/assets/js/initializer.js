const BoostrapUtils = require('./bootstrap-utils');

document.addEventListener(
    'DOMContentLoaded',
    () => {
        console.log('shared JS initialized');
        const bootstrapUtils = new BoostrapUtils();
        bootstrapUtils.init();
    },
    false
);
