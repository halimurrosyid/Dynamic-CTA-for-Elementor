/**
 * Dynamic CTA Admin JavaScript
 */
(function($) {
    'use strict';

    $(document).ready(function() {
        var $noticeBox = $('#dynamic-cta-notice');

        function showNotice(message, type) {
            type = type || 'info';
            $noticeBox.removeClass('notice-info notice-success notice-error')
                       .addClass('notice-' + type)
                       .find('p').text(message);
            $noticeBox.slideDown();
            $('html, body').animate({ scrollTop: 0 }, 'fast');
        }

        // Open Add Modal
        $('.btn-open-add-modal').on('click', function() {
            $('#modal-form-title').text('Add Area Mapping');
            $('#field-mapping-id').val(0);
            $('#dcta-save-form')[0].reset();
            $('#modal-mapping-form').fadeIn('fast');
        });

        // Edit Button Click
        $(document).on('click', '.btn-edit-mapping', function() {
            var id = $(this).data('id');
            var keyword = $(this).data('keyword');
            var area = $(this).data('area');
            var url = $(this).data('url');

            $('#modal-form-title').text('Edit Area Mapping');
            $('#field-mapping-id').val(id);
            $('#field-keyword').val(keyword);
            $('#field-area-name').val(area);
            $('#field-destination-url').val(url);

            $('#modal-mapping-form').fadeIn('fast');
        });

        // Close Modals
        $('.dcta-modal-close').on('click', function() {
            $('.dcta-modal-overlay').fadeOut('fast');
        });

        // Submit Save Mapping AJAX
        $('.btn-save-mapping-submit').on('click', function() {
            var $btn = $(this);
            var formData = $('#dcta-save-form').serializeArray();

            var data = {
                action: 'dynamic_cta_save_mapping',
                nonce: dynamic_cta_admin.nonce
            };

            $.each(formData, function(i, field) {
                data[field.name] = field.value;
            });

            $btn.prop('disabled', true).text('Saving...');

            $.post(dynamic_cta_admin.ajax_url, data, function(res) {
                $btn.prop('disabled', false).text('Save Mapping');
                if (res.success) {
                    $('#modal-mapping-form').fadeOut('fast');
                    showNotice(res.data.message, 'success');
                    setTimeout(function() {
                        location.reload();
                    }, 1000);
                } else {
                    alert(res.data.message || 'Error occurred');
                }
            }).fail(function() {
                $btn.prop('disabled', false).text('Save Mapping');
                alert('Server request failed.');
            });
        });

        // Delete Single Mapping AJAX
        $(document).on('click', '.btn-delete-mapping', function() {
            if (!confirm(dynamic_cta_admin.strings.confirm_delete)) {
                return;
            }

            var id = $(this).data('id');
            var $row = $(this).closest('tr');

            $.post(dynamic_cta_admin.ajax_url, {
                action: 'dynamic_cta_delete_mapping',
                nonce: dynamic_cta_admin.nonce,
                id: id
            }, function(res) {
                if (res.success) {
                    $row.fadeOut('fast', function() {
                        $(this).remove();
                    });
                    showNotice(res.data.message, 'success');
                } else {
                    alert(res.data.message || 'Error deleting mapping.');
                }
            });
        });

        // Clear All Mappings
        $('.btn-clear-all-mappings').on('click', function() {
            if (!confirm('Are you sure you want to clear ALL area mappings? This will purge the table completely.')) {
                return;
            }

            var $btn = $(this);
            $btn.prop('disabled', true).text('Clearing...');

            $.post(dynamic_cta_admin.ajax_url, {
                action: 'dynamic_cta_clear_all_mappings',
                nonce: dynamic_cta_admin.nonce
            }, function(res) {
                $btn.prop('disabled', false).text('Clear All Mappings');
                if (res.success) {
                    showNotice(res.data.message, 'success');
                    setTimeout(function() {
                        location.reload();
                    }, 1000);
                } else {
                    alert(res.data.message || 'Failed to clear mappings.');
                }
            });
        });

        // Direct 1-Click Auto Detect Scan Button
        $('.btn-run-auto-detect').on('click', function() {
            var $btn = $(this);
            var origHtml = $btn.html();

            $btn.prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span> ' + dynamic_cta_admin.strings.scanning);

            $.post(dynamic_cta_admin.ajax_url, {
                action: 'dynamic_cta_auto_detect',
                nonce: dynamic_cta_admin.nonce
            }, function(res) {
                $btn.prop('disabled', false).html(origHtml);
                if (res.success) {
                    showNotice(res.data.message, 'success');
                    setTimeout(function() {
                        location.reload();
                    }, 1500);
                } else {
                    alert(res.data.message || 'Auto-detection failed.');
                }
            }).fail(function() {
                $btn.prop('disabled', false).html(origHtml);
                alert('Auto-detection request failed.');
            });
        });

        // Open Import CSV Modal
        $('.btn-open-import-modal').on('click', function() {
            $('#modal-import-csv').fadeIn('fast');
        });

        // Submit CSV Import Form
        $('.btn-submit-import').on('click', function() {
            var fileInput = $('#field-csv-file')[0];
            if (!fileInput.files.length) {
                alert('Please select a CSV file first.');
                return;
            }

            var formData = new FormData();
            formData.append('action', 'dynamic_cta_import_csv');
            formData.append('nonce', dynamic_cta_admin.nonce);
            formData.append('csv_file', fileInput.files[0]);

            var $btn = $(this);
            $btn.prop('disabled', true).text('Uploading...');

            $.ajax({
                url: dynamic_cta_admin.ajax_url,
                type: 'POST',
                data: formData,
                contentType: false,
                processData: false,
                success: function(res) {
                    $btn.prop('disabled', false).text('Upload & Import');
                    if (res.success) {
                        $('#modal-import-csv').fadeOut('fast');
                        showNotice(res.data.message, 'success');
                        setTimeout(function() {
                            location.reload();
                        }, 1500);
                    } else {
                        alert(res.data.message || 'CSV Import failed.');
                    }
                },
                error: function() {
                    $btn.prop('disabled', false).text('Upload & Import');
                    alert('CSV Upload error.');
                }
            });
        });

        // Confirm clear stats history
        $('.btn-confirm-clear-stats').on('click', function(e) {
            if (!confirm(dynamic_cta_admin.strings.confirm_clear_stats)) {
                e.preventDefault();
            }
        });
    });
})(jQuery);
