jQuery(document).ready(function($) {
    // Tab functionality
    $('.wrms-tab-link').click(function() {
        var tabId = $(this).data('tab');
        
        $('.wrms-tab-link').removeClass('active');
        $('.wrms-tab-pane').removeClass('active');
        
        $(this).addClass('active');
        $('#' + tabId).addClass('active');
    });

    // Sync Products
    $('#sync-products').click(function() {
        $('#progress-bar').show();
        $('#sync-loader').show();
        syncProducts();
    });

    // Remove RankMath Meta
    $('#remove-rankmath-meta').click(function() {
        removeMeta();
    });

    // Auto-sync toggle
    $('#wrms_auto_sync').on('change', function() {
        updateAutoSync($(this).is(':checked'));
    });

    // Update Statistics
    $('#update-stats').on('click', function(e) {
        e.preventDefault();
        var button = $(this);
        button.prop('disabled', true).text('Updating...');

        $.ajax({
            url: wrms_data.ajax_url,
            type: 'POST',
            data: {
                action: 'wrms_update_stats',
                nonce: wrms_data.nonce
            },
            success: function(response) {
                if (response.success) {
                    var stats = response.data;
                    $('.wrms-stats-box p:eq(0)').text('Total Products: ' + stats.total_products);
                    $('.wrms-stats-box p:eq(1)').text('Synced Products: ' + stats.synced_count);
                    $('.wrms-stats-box p:eq(2)').text('Unsynced Products: ' + stats.unsynced_count);
                    $('.wrms-stats-box p:eq(3)').text('Sync Percentage: ' + stats.sync_percentage + '%');
                    $('.wrms-stats-box p:eq(4)').text('Last Updated: ' + stats.last_updated);
                } else {
                    alert('Failed to update statistics. Please try again.');
                }
            },
            error: function() {
                alert('An error occurred. Please try again.');
            },
            complete: function() {
                button.prop('disabled', false).text('Update Statistics');
            }
        });
    });

    // Download URLs
    $('#download-urls').click(function(e) {
        e.preventDefault();
        $('#progress-bar').show();
        var urlTypes = $('input[name="url_types[]"]:checked').map(function() {
            return this.value;
        }).get();

        if (urlTypes.length === 0) {
            $('#download-status').text('Please select at least one URL type to download.');
            return;
        }

        var offset = 0;
        var chunkSize = 2000;
        
        function downloadChunk() {
            $.ajax({
                url: wrms_data.ajax_url,
                type: 'POST',
                data: {
                    action: 'wrms_get_urls',
                    nonce: wrms_data.nonce,
                    offset: offset,
                    chunk_size: chunkSize,
                    url_types: urlTypes
                },
                success: function(response) {
                    if (response.success && response.data.urls.length > 0) {
                        // Create and download the file
                        var blob = new Blob([response.data.urls.join('\n')], {type: 'text/plain'});
                        var link = document.createElement('a');
                        link.href = window.URL.createObjectURL(blob);
                        link.download = 'wordpress_urls_' + offset + '-' + (offset + response.data.urls.length) + '.txt';
                        link.click();
                        
                        // Update status
                        $('#download-status').text('Downloaded URLs ' + offset + ' to ' + (offset + response.data.urls.length));
                        
                        // Move to next chunk
                        offset += chunkSize;
                        downloadChunk();
                    } else {
                        $('#download-status').text('All URLs have been downloaded.');
                        $('#progress-bar').hide();
                    }
                },
                error: function() {
                    $('#download-status').text('An error occurred. Please try again.');
                    $('#progress-bar').hide();
                }
            });
        }
        
        downloadChunk();
    });

    function updateAutoSync(isChecked) {
        $.ajax({
            url: ajaxurl,
            method: 'POST',
            data: {
                action: 'wrms_update_auto_sync',
                auto_sync: isChecked ? 1 : 0,
                nonce: wrms_data.nonce
            },
            success: function(response) {
                if (response.success) {
                    alert('Auto-sync setting updated successfully.');
                } else {
                    alert('Error updating auto-sync setting: ' + response.data.message);
                }
            },
            error: function(xhr, status, error) {
                alert('Error updating auto-sync setting: ' + error);
            }
        });
    }

    function syncProducts() {
        var totalProducts = 0;
        var processedProducts = 0;

        $.ajax({
            url: ajaxurl,
            method: 'POST',
            data: {
                action: 'wrms_get_product_count',
                nonce: wrms_data.nonce
            },
            success: function(response) {
                if (response.success) {
                    totalProducts = response.data.count;
                    $('#sync-count').text('Processing 0 of ' + totalProducts + ' products');
                    $('#sync-loader').show();
                    $('#sync-log').html(''); // Clear log area
                    $('#progress-bar-fill').width('0%'); // Reset progress bar

                    processNextProduct();
                } else {
                    $('#sync-status').append('<p>Error retrieving product count: ' + response.data.message + '</p>');
                }
            },
            error: function(xhr, status, error) {
                $('#sync-status').append('<p>Error retrieving product count: ' + error + '</p>');
            }
        });

        function processNextProduct() {
            $.ajax({
                url: ajaxurl,
                method: 'POST',
                data: {
                    action: 'wrms_sync_next_product',
                    nonce: wrms_data.nonce
                },
                success: function(response) {
                    if (response.success && response.data.processed > 0) {
                        processedProducts += response.data.processed;
                        $('#sync-count').text('Processing ' + processedProducts + ' of ' + totalProducts + ' products');

                        // Update log area
                        $('#sync-log').append('<p>Processed product ' + processedProducts + ': ' + response.data.product.title + ' (ID: ' + response.data.product.id + ')</p>');
                        $('#sync-log').scrollTop($('#sync-log')[0].scrollHeight); // Scroll to bottom

                        // Update progress bar
                        var progress = (processedProducts / totalProducts) * 100;
                        $('#progress-bar-fill').width(progress + '%');

                        if (processedProducts < totalProducts) {
                            processNextProduct();
                        } else {
                            $('#sync-loader').hide();
                            $('#sync-status').append('<p>Products synced successfully!</p>');
                        }
                    } else if (!response.success) {
                        $('#sync-loader').hide();
                        $('#sync-status').append('<p>Error processing product: ' + response.data.message + '</p>');
                    } else {
                        $('#sync-loader').hide();
                        $('#sync-status').append('<p>All products are already synced or an error occurred.</p>');
                    }
                },
                error: function(xhr, status, error) {
                    $('#sync-loader').hide();
                    $('#sync-status').append('<p>An error occurred during syncing: ' + error + '</p>');
                }
            });
        }
    }

    function removeMeta() {
        var totalProducts = 0;
        var processedProducts = 0;

        $.ajax({
            url: ajaxurl,
            method: 'POST',
            data: {
                action: 'wrms_get_product_count',
                nonce: wrms_data.nonce
            },
            success: function(response) {
                if (response.success) {
                    totalProducts = response.data.count;
                    $('#sync-count').text('Processing 0 of ' + totalProducts + ' products');
                    $('#sync-loader').show();
                    $('#sync-log').html(''); // Clear log area
                    $('#progress-bar-fill').width('0%'); // Reset progress bar

                    processNextProduct();
                } else {
                    $('#sync-status').append('<p>Error retrieving product count: ' + response.data.message + '</p>');
                }
            },
            error: function(xhr, status, error) {
                $('#sync-status').append('<p>Error retrieving product count: ' + error + '</p>');
            }
        });

        function processNextProduct() {
            $.ajax({
                url: ajaxurl,
                method: 'POST',
                data: {
                    action: 'wrms_remove_next_product',
                    nonce: wrms_data.nonce
                },
                success: function(response) {
                    if (response.success && response.data.processed > 0) {
                        processedProducts += response.data.processed;
                        $('#sync-count').text('Processing ' + processedProducts + ' of ' + totalProducts + ' products');

                        // Update log area
                        $('#sync-log').append('<p>Removed meta from product ' + processedProducts + ': ' + response.data.product.id + '</p>');
                        $('#sync-log').scrollTop($('#sync-log')[0].scrollHeight); // Scroll to bottom

                        // Update progress bar
                        var progress = (processedProducts / totalProducts) * 100;
                        $('#progress-bar-fill').width(progress + '%');

                        if (processedProducts < totalProducts) {
                            processNextProduct();
                        } else {
                            $('#sync-loader').hide();
                            $('#sync-status').append('<p>RankMath meta information removed from all products!</p>');
                        }
                    } else if (!response.success) {
                        $('#sync-loader').hide();
                        $('#sync-status').append('<p>Error processing product: ' + response.data.message + '</p>');
                    } else {
                        $('#sync-loader').hide();
                        $('#sync-status').append('<p>All products have already had their meta removed or an error occurred.</p>');
                    }
                },
                error: function(xhr, status, error) {
                    $('#sync-loader').hide();
                    $('#sync-status').append('<p>An error occurred during meta removal: ' + error + '</p>');
                }
            });
        }
    }
});