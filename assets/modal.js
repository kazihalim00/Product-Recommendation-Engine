// In assets/modal.js
jQuery(document).ready(function ($) {
    // When the trigger button is clicked
    $('#pre-show-modal').on('click', function (e) {
        e.preventDefault();
        var modalContent = $('#pre-modal-content-wrapper').html();
        if (modalContent.trim().length > 0) {
            var modalHTML = `<div class="pre-modal-overlay"><div class="pre-modal-content"><button class="pre-modal-close">&times;</button>${modalContent}</div></div>`;
            $('body').append(modalHTML);
            $('.pre-modal-overlay').fadeIn(300);
        }
    });

    // Close the modal
    $('body').on('click', '.pre-modal-close, .pre-modal-overlay', function (e) {
        if ($(e.target).is('.pre-modal-content') || $(e.target).closest('.pre-modal-content').length > 0) {
            if (!$(e.target).is('.pre-modal-close')) {
                return;
            }
        }
        $('.pre-modal-overlay').fadeOut(300, function () { $(this).remove(); });
    });
});