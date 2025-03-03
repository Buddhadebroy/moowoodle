jQuery(function ($) {
    console.log("Gift Someone checkbox script loaded");

    function toggleGiftFields() {
        if ($('#billing-MooWoodle-gift_someone').is(':checked')) {
            console.log("Gift Someone checkbox is checked");
            $('.wc-block-components-address-form__MooWoodle-full_name').show();
            $('.wc-block-components-address-form__MooWoodle-email_address').show();
        } else {
            console.log("Gift Someone checkbox is unchecked");
            $('.wc-block-components-address-form__MooWoodle-full_name').hide();
            $('.wc-block-components-address-form__MooWoodle-email_address').hide();
        }
    }

    $('.wc-block-components-address-form__MooWoodle-full_name, .wc-block-components-address-form__MooWoodle-email_address').hide();

    $(document).on('change', '#billing-MooWoodle-gift_someone', toggleGiftFields);

    setTimeout(toggleGiftFields, 800);
});
