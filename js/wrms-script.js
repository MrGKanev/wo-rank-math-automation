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

    // Sync Categories
    $('#sync-categories').click(function() {
        $('#progress-bar').show();
        $('#sync-loader').show();
        syncCategories();
    });

    // Sync Pages
    $('#sync-pages').click(function() {
        $('#progress-bar').show();
        $('#sync-loader').show();
        syncPages();
    });

    // Sync Media
    $('#sync-media').click(function() {
        $('#progress-bar').show();
        $('#sync-loader').show();
        syncMedia();
    });

    // Remove Product Meta
    $('#remove-product-meta').click(function() {
        $('#progress-bar').show();
        $('#sync-loader').show();
        removeProductMeta();
    });

    // Remove Category Meta
    $('#remove-category-meta').click(function() {
        $('#progress-bar').show();
        $('#sync-loader').show();
        removeCategoryMeta();
    });

    // Remove Page Meta
    $('#remove-page-meta').click(function() {
        $('#progress-bar').show();
        $('#sync-loader').show();
        removePageMeta();
    });

    // Remove Media Meta
    $('#remove-media-meta').click(function() {
        $('#progress-bar').show();
        $('#sync-loader').show();
        removeMediaMeta();
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
                    $('.wrms-stats-box').html(`
                        <h2>Plugin Statistics</h2>
                        <p>Total Products: <span id="total-products">${stats.total_products}</span></p>
                        <p>Synced Products: <span id="synced-products">${stats.synced_products}</span></p>
                        <p>Total Pages: <span id="total-pages">${stats.total_pages}</span></p>
                        <p>Synced Pages: <span id="synced-pages">${stats.synced_pages}</span></p>
                        <p>Total Media: <span id="total-media">${stats.total_media}</span></p>
                        <p>Synced Media: <span id="synced-media">${stats.synced_media}</span></p>
                        <p>Total Categories: <span id="total-categories">${stats.total_categories}</span></p>
                        <p>Synced Categories: <span id="synced-categories">${stats.synced_categories}</span></p>
                        <p>Total Items: <span id="total-items">${stats.total_items}</span></p>
                        <p>Total Synced: <span id="total-synced">${stats.total_synced}</span></p>
                        <p>Sync Percentage: <span id="sync-percentage">${stats.sync_percentage}%</span></p>
                        <p>Last Updated: <span id="last-updated">${stats.last_updated}</span></p>
                    `);
                    $('.wrms-stats-box').append('<button id="update-stats" class="button button-secondary">Update Statistics</button>');
                } else {
                    alert('Failed to update statistics. Please try again.');
                }
            },
            error: function(xhr, status, error) {
                alert('An error occurred. Please try again. Error: ' + error);
                console.log(xhr.responseText);
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
            url: wrms_data.ajax_url,
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
            url: wrms_data.ajax_url,
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
                    $('#progress-bar-fill').css('width', '0%'); // Reset progress bar

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
                url: wrms_data.ajax_url,
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
                        $('#progress-bar-fill').css('width', progress + '%');

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

    function syncCategories() {
        $('#sync-loader').show();
        $('#sync-log').html(''); // Clear log area
        $('#progress-bar-fill').css('width', '0%'); // Reset progress bar

        $.ajax({
            url: wrms_data.ajax_url,
            method: 'POST',
            data: {
                action: 'wrms_sync_categories',
                nonce: wrms_data.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('#sync-count').text('Synced ' + response.data.synced + ' of ' + response.data.total + ' categories');
                    $('#sync-log').append('<p>Categories synced successfully!</p>');
                    $('#sync-log').scrollTop($('#sync-log')[0].scrollHeight);

                    // Update progress bar
                    var progress = (response.data.synced / response.data.total) * 100;
                    $('#progress-bar-fill').css('width', progress + '%');
                } else {
                    $('#sync-status').append('<p>Error syncing categories: ' + response.data.message + '</p>');
                }
                $('#sync-loader').hide();
            },
            error: function(xhr, status, error) {
                $('#sync-loader').hide();
                $('#sync-status').append('<p>An error occurred during category syncing: ' + error + '</p>');
            }
        });
    }

    function syncPages() {
        $('#sync-loader').show();
        $('#sync-log').html(''); // Clear log area
        $('#progress-bar-fill').css('width', '0%'); // Reset progress bar

        $.ajax({
            url: wrms_data.ajax_url,
            method: 'POST',
            data: {
                action: 'wrms_sync_pages',
                nonce: wrms_data.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('#sync-count').text('Synced ' + response.data.synced + ' of ' + response.data.total + ' pages');
                    $('#sync-log').append('<p>Pages synced successfully!</p>');
                    $('#sync-log').scrollTop($('#sync-log')[0].scrollHeight);

                    // Update progress bar
                    var progress = (response.data.synced / response.data.total) * 100;
                    $('#progress-bar-fill').css('width', progress + '%');
                } else {
                    $('#sync-status').append('<p>Error syncing pages: ' + response.data.message + '</p>');
                }
                $('#sync-loader').hide();
            },
            error: function(xhr, status, error) {
                $('#sync-loader').hide();
                $('#sync-status').append('<p>An error occurred during page syncing: ' + error + '</p>');
            }
        });
    }

    function syncMedia() {
        $('#sync-loader').show();
        $('#sync-log').html(''); // Clear log area
        $('#progress-bar-fill').css('width', '0%'); // Reset progress bar

        $.ajax({
            url: wrms_data.ajax_url,
            method: 'POST',
            data: {
                action: 'wrms_sync_media',
                nonce: wrms_data.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('#sync-count').text('Synced ' + response.data.synced + ' of ' + response.data.total + ' media items');
                    $('#sync-log').append('<p>Media items synced successfully!</p>');
                    $('#sync-log').scrollTop($('#sync-log')[0].scrollHeight);

                    // Update progress bar
                    var progress = (response.data.synced / response.data.total) * 100;
                    $('#progress-bar-fill').css('width', progress + '%');
                } else {
                    $('#sync-status').append('<p>Error syncing media: ' + response.data.message + '</p>');
                }
                $('#sync-loader').hide();
            },
            error: function(xhr, status, error) {
                $('#sync-loader').hide();
                $('#sync-status').append('<p>An error occurred during media syncing: ' + error + '</p>');
            }
        });
    }

    function removeProductMeta() {
        $('#sync-loader').show();
        $('#sync-log').html(''); // Clear log area
        $('#progress-bar-fill').css('width', '0%'); // Reset progress bar

        $.ajax({
            url: wrms_data.ajax_url,
            method: 'POST',
            data: {
                action: 'wrms_remove_product_meta',
                nonce: wrms_data.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('#sync-count').text('Removed meta from ' + response.data.removed + ' of ' + response.data.total + ' products');
                    $('#sync-log').append('<p>Product meta removed successfully!</p>');
                    $('#sync-log').scrollTop($('#sync-log')[0].scrollHeight);

                    // Update progress bar
                    var progress = (response.data.removed / response.data.total) * 100;
                    $('#progress-bar-fill').css('width', progress + '%');
                } else {
                    $('#sync-status').append('<p>Error removing product meta: ' + response.data.message + '</p>');
                }
                $('#sync-loader').hide();
            },
            error: function(xhr, status, error) {
                $('#sync-loader').hide();
                $('#sync-status').append('<p>An error occurred during product meta removal: ' + error + '</p>');
            }
        });
    }

function removeCategoryMeta() {
        $('#sync-loader').show();
        $('#sync-log').html(''); // Clear log area
        $('#progress-bar-fill').css('width', '0%'); // Reset progress bar

        $.ajax({
            url: wrms_data.ajax_url,
            method: 'POST',
            data: {
                action: 'wrms_remove_category_meta',
                nonce: wrms_data.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('#sync-count').text('Removed meta from ' + response.data.removed + ' of ' + response.data.total + ' categories');
                    $('#sync-log').append('<p>Category meta removed successfully!</p>');
                    $('#sync-log').scrollTop($('#sync-log')[0].scrollHeight);

                    // Update progress bar
                    var progress = (response.data.removed / response.data.total) * 100;
                    $('#progress-bar-fill').css('width', progress + '%');
                } else {
                    $('#sync-status').append('<p>Error removing category meta: ' + response.data.message + '</p>');
                }
                $('#sync-loader').hide();
            },
            error: function(xhr, status, error) {
                $('#sync-loader').hide();
                $('#sync-status').append('<p>An error occurred during category meta removal: ' + error + '</p>');
            }
        });
    }

    function removePageMeta() {
        $('#sync-loader').show();
        $('#sync-log').html(''); // Clear log area
        $('#progress-bar-fill').css('width', '0%'); // Reset progress bar

        $.ajax({
            url: wrms_data.ajax_url,
            method: 'POST',
            data: {
                action: 'wrms_remove_page_meta',
                nonce: wrms_data.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('#sync-count').text('Removed meta from ' + response.data.removed + ' of ' + response.data.total + ' pages');
                    $('#sync-log').append('<p>Page meta removed successfully!</p>');
                    $('#sync-log').scrollTop($('#sync-log')[0].scrollHeight);

                    // Update progress bar
                    var progress = (response.data.removed / response.data.total) * 100;
                    $('#progress-bar-fill').css('width', progress + '%');
                } else {
                    $('#sync-status').append('<p>Error removing page meta: ' + response.data.message + '</p>');
                }
                $('#sync-loader').hide();
            },
            error: function(xhr, status, error) {
                $('#sync-loader').hide();
                $('#sync-status').append('<p>An error occurred during page meta removal: ' + error + '</p>');
            }
        });
    }

    function removeMediaMeta() {
        $('#sync-loader').show();
        $('#sync-log').html(''); // Clear log area
        $('#progress-bar-fill').css('width', '0%'); // Reset progress bar

        $.ajax({
            url: wrms_data.ajax_url,
            method: 'POST',
            data: {
                action: 'wrms_remove_media_meta',
                nonce: wrms_data.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('#sync-count').text('Removed meta from ' + response.data.removed + ' of ' + response.data.total + ' media items');
                    $('#sync-log').append('<p>Media meta removed successfully!</p>');
                    $('#sync-log').scrollTop($('#sync-log')[0].scrollHeight);

                    // Update progress bar
                    var progress = (response.data.removed / response.data.total) * 100;
                    $('#progress-bar-fill').css('width', progress + '%');
                } else {
                    $('#sync-status').append('<p>Error removing media meta: ' + response.data.message + '</p>');
                }
                $('#sync-loader').hide();
            },
            error: function(xhr, status, error) {
                $('#sync-loader').hide();
                $('#sync-status').append('<p>An error occurred during media meta removal: ' + error + '</p>');
            }
        });
    }
});