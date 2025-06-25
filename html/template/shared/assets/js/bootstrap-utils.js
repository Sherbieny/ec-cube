class BoostrapUtils {
    /**
     * Initialize all utilities (to be called on DOMContentLoaded)
     */
    init() {
        console.log('Bootstrap utils initialized');
        this.initModalBlurFix();
    }

    /**
     * Fix Bootstrap modal aria-hidden/focus warning by blurring focus on close
     * Applies to all modals on the page (admin and front)
     */
    initModalBlurFix() {
        // Listen to 'hide.bs.modal' so blur happens before modal is hidden
        document.addEventListener(
            'hide.bs.modal',
            function (event) {
                // Blur the active element if it's focusable
                if (document.activeElement && typeof document.activeElement.blur === 'function') {
                    document.activeElement.blur();
                }
            },
            true
        );
    }
}

module.exports = BoostrapUtils;
