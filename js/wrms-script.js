jQuery(document).ready(function($) {
    $('#sync-products').on('click', function() {
        syncProducts();
    });

    $('#remove-rankmath-meta').on('click', function() {
        removeMeta();
    });

    function syncProducts() {
        var totalProducts = 0;
        var processedProducts = 0;

        $('#sync-log').css('background-color', 'white'); // Set background color before processing starts

        $.ajax({
            url: ajaxurl,
            method: 'POST',
            data: {
                action: 'wrms_get_product_count'
            },
            success: function(response) {
                totalProducts = response.data.count;
                $('#sync-count').text('Processing 0 of ' + totalProducts + ' products');
                $('#sync-loader').show();
                $('#sync-log').html(''); // Clear log area

                processNextProduct();
            }
        });

        function processNextProduct() {
            $.ajax({
                url: ajaxurl,
                method: 'POST',
                data: {
                    action: 'wrms_sync_next_product'
                },
                success: function(response) {
                    if (response.success && response.data.processed > 0) {
                        processedProducts += response.data.processed;
                        $('#sync-count').text('Processing ' + processedProducts + ' of ' + totalProducts + ' products');

                        // Update log area
                        $('#sync-log').append('<p>Processed product ' + processedProducts + ': ' + response.data.product.title + ' (ID: ' + response.data.product.id + ')</p>');
                        $('#sync-log').scrollTop($('#sync-log')[0].scrollHeight); // Scroll to bottom

                        if (processedProducts < totalProducts) {
                            processNextProduct();
                        } else {
                            $('#sync-loader').hide();
                            $('#sync-status').append('<p>Products synced successfully!</p>');
                        }
                    } else {
                        $('#sync-loader').hide();
                        $('#sync-status').append('<p>All products are already synced or an error occurred.</p>');
                    }
                },
                error: function(xhr, status, error) {
                    $('#sync-loader').hide();
                    $('#sync-status').append('<p>An error occurred.</p>');
                }
            });
        }
    }

    function removeMeta() {
        var totalProducts = 0;
        var processedProducts = 0;

        $('#sync-log').css('background-color', 'white'); // Set background color before processing starts

        $.ajax({
            url: ajaxurl,
            method: 'POST',
            data: {
                action: 'wrms_get_product_count'
            },
            success: function(response) {
                totalProducts = response.data.count;
                $('#sync-count').text('Processing 0 of ' + totalProducts + ' products');
                $('#sync-loader').show();
                $('#sync-log').html(''); // Clear log area

                processNextProduct();
            }
        });

        function processNextProduct() {
            $.ajax({
                url: ajaxurl,
                method: 'POST',
                data: {
                    action: 'wrms_remove_next_product'
                },
                success: function(response) {
                    if (response.success && response.data.processed > 0) {
                        processedProducts += response.data.processed;
                        $('#sync-count').text('Processing ' + processedProducts + ' of ' + totalProducts + ' products');

                        // Update log area
                        $('#sync-log').append('<p>Removed meta from product ' + processedProducts + ': ' + response.data.product.id + '</p>');
                        $('#sync-log').scrollTop($('#sync-log')[0].scrollHeight); // Scroll to bottom

                        if (processedProducts < totalProducts) {
                            processNextProduct();
                        } else {
                            $('#sync-loader').hide();
                            $('#sync-status').append('<p>RankMath meta information removed from all products!</p>');
                        }
                    } else {
                        $('#sync-loader').hide();
                        $('#sync-status').append('<p>All products have already had their meta removed or an error occurred.</p>');
                    }
                },
                error: function(xhr, status, error) {
                    $('#sync-loader').hide();
                    $('#sync-status').append('<p>An error occurred.</p>');
                }
            });
        }
    }
});