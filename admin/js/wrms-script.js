jQuery(document).ready(function ($) {
  // Tab functionality
  $(".wrms-tab-link").click(function () {
    var tabId = $(this).data("tab");

    $(".wrms-tab-link").removeClass("active");
    $(".wrms-tab-pane").removeClass("active");

    $(this).addClass("active");
    $("#" + tabId).addClass("active");
  });

  // Sync Products
  $("#sync-products").click(function () {
    $("#progress-bar").show();
    $("#sync-loader").show();
    syncProducts();
  });

  // Sync Categories
  $("#sync-categories").click(function () {
    $("#progress-bar").show();
    $("#sync-loader").show();
    syncCategories();
  });

  // Sync Pages
  $("#sync-pages").click(function () {
    $("#progress-bar").show();
    $("#sync-loader").show();
    syncPages();
  });

  // Sync Media
  $("#sync-media").click(function () {
    $("#progress-bar").show();
    $("#sync-loader").show();
    syncMedia();
  });

  // Sync Posts
  $("#sync-posts").click(function () {
    $("#progress-bar").show();
    $("#sync-loader").show();
    syncPosts();
  });

  // Remove Product Meta
  $("#remove-product-meta").click(function () {
    $("#progress-bar").show();
    $("#sync-loader").show();
    removeProductMeta();
  });

  // Remove Category Meta
  $("#remove-category-meta").click(function () {
    $("#progress-bar").show();
    $("#sync-loader").show();
    removeCategoryMeta();
  });

  // Remove Page Meta
  $("#remove-page-meta").click(function () {
    $("#progress-bar").show();
    $("#sync-loader").show();
    removePageMeta();
  });

  // Remove Media Meta
  $("#remove-media-meta").click(function () {
    $("#progress-bar").show();
    $("#sync-loader").show();
    removeMediaMeta();
  });

  // Remove Post Meta
  $("#remove-post-meta").click(function () {
    $("#progress-bar").show();
    $("#sync-loader").show();
    removePostMeta();
  });

  // Auto-sync toggle
  $("#wrms_auto_sync").on("change", function () {
    updateAutoSync($(this).is(":checked"));
  });

  // Update Statistics
  $("#update-stats").on("click", function (e) {
    e.preventDefault();
    updateStats();
  });

  // Download URLs
  $("#download-urls").click(function (e) {
    e.preventDefault();
    $("#progress-bar").show();
    var urlTypes = $('input[name="url_types[]"]:checked')
      .map(function () {
        return this.value;
      })
      .get();

    if (urlTypes.length === 0) {
      $("#download-status").text(
        "Please select at least one URL type to download."
      );
      return;
    }

    downloadUrls(urlTypes);
  });

  function updateAutoSync(isChecked) {
    $.ajax({
      url: wrms_data.ajax_url,
      method: "POST",
      data: {
        action: "wrms_update_auto_sync",
        auto_sync: isChecked ? 1 : 0,
        nonce: wrms_data.nonce,
      },
      success: function (response) {
        if (!response.success) {
          alert("Error updating auto-sync setting: " + response.data.message);
        }
      },
      error: function (xhr, status, error) {
        alert("Error updating auto-sync setting: " + error);
      },
    });
  }

  function updateStats() {
    var button = $("#update-stats");
    button.prop("disabled", true).text("Updating...");

    $.ajax({
      url: wrms_data.ajax_url,
      type: "POST",
      data: {
        action: "wrms_update_stats",
        nonce: wrms_data.nonce,
      },
      success: function (response) {
        if (response.success) {
          var stats = response.data;
          $("#total-products").text(stats.total_products);
          $("#synced-products").text(stats.synced_products);
          $("#total-pages").text(stats.total_pages);
          $("#synced-pages").text(stats.synced_pages);
          $("#total-media").text(stats.total_media);
          $("#synced-media").text(stats.synced_media);
          $("#total-categories").text(stats.total_categories);
          $("#synced-categories").text(stats.synced_categories);
          $("#total-posts").text(stats.total_posts);
          $("#synced-posts").text(stats.synced_posts);
          $("#total-items").text(stats.total_items);
          $("#total-synced").text(stats.total_synced);
          $("#sync-percentage").text(stats.sync_percentage + "%");
          $("#last-updated").text(
            new Date(stats.timestamp * 1000).toLocaleString()
          );
        } else {
          alert("Failed to update statistics. Please try again.");
        }
      },
      error: function (xhr, status, error) {
        alert("An error occurred. Please try again. Error: " + error);
        console.log(xhr.responseText);
      },
      complete: function () {
        button.prop("disabled", false).text("Update Statistics");
      },
    });
  }

  function syncProducts() {
    var totalProducts = 0;
    var processedProducts = 0;

    $.ajax({
      url: wrms_data.ajax_url,
      method: "POST",
      data: {
        action: "wrms_get_product_count",
        nonce: wrms_data.nonce,
      },
      success: function (response) {
        if (response.success) {
          totalProducts = response.data.count;
          $("#sync-count").text(
            "Processing 0 of " + totalProducts + " products"
          );
          $("#sync-loader").show();
          $("#sync-log").html(""); // Clear log area
          $("#progress-bar-fill").css("width", "0%"); // Reset progress bar

          processNextProduct();
        } else {
          $("#sync-status").append(
            "<p>Error retrieving product count: " +
              response.data.message +
              "</p>"
          );
        }
      },
      error: function (xhr, status, error) {
        $("#sync-status").append(
          "<p>Error retrieving product count: " + error + "</p>"
        );
      },
    });

    function processNextProduct() {
      $.ajax({
        url: wrms_data.ajax_url,
        method: "POST",
        data: {
          action: "wrms_sync_next_product",
          nonce: wrms_data.nonce,
        },
        success: function (response) {
          if (response.success && response.data.processed > 0) {
            processedProducts += response.data.processed;
            $("#sync-count").text(
              "Processing " +
                processedProducts +
                " of " +
                totalProducts +
                " products"
            );

            // Update log area
            $("#sync-log").append(
              "<p>Processed product " +
                processedProducts +
                ": " +
                response.data.product.title +
                " (ID: " +
                response.data.product.id +
                ")</p>"
            );
            $("#sync-log").scrollTop($("#sync-log")[0].scrollHeight); // Scroll to bottom

            // Update progress bar
            var progress = (processedProducts / totalProducts) * 100;
            $("#progress-bar-fill").css("width", progress + "%");

            if (processedProducts < totalProducts) {
              processNextProduct();
            } else {
              $("#sync-loader").hide();
              $("#sync-status").append("<p>Products synced successfully!</p>");
              updateStats(); // Update statistics after successful sync
            }
          } else if (!response.success) {
            $("#sync-loader").hide();
            $("#sync-status").append(
              "<p>Error processing product: " + response.data.message + "</p>"
            );
            updateStats(); // Update statistics even if there's an error
          } else {
            $("#sync-loader").hide();
            $("#sync-status").append(
              "<p>All products are already synced or an error occurred.</p>"
            );
            updateStats(); // Update statistics after sync completion
          }
        },
        error: function (xhr, status, error) {
          $("#sync-loader").hide();
          $("#sync-status").append(
            "<p>An error occurred during syncing: " + error + "</p>"
          );
          updateStats(); // Update statistics even if there's an error
        },
      });
    }
  }

  function syncCategories() {
    $("#sync-loader").show();
    $("#sync-log").html(""); // Clear log area
    $("#progress-bar-fill").css("width", "0%"); // Reset progress bar

    $.ajax({
      url: wrms_data.ajax_url,
      method: "POST",
      data: {
        action: "wrms_sync_categories",
        nonce: wrms_data.nonce,
      },
      success: function (response) {
        if (response.success) {
          $("#sync-count").text(
            "Synced " +
              response.data.synced +
              " of " +
              response.data.total +
              " categories"
          );
          $("#sync-log").append(
            "<p>Processed category: " +
              response.data.category.name +
              " (ID: " +
              response.data.category.id +
              ")</p>"
          );
          $("#sync-log").scrollTop($("#sync-log")[0].scrollHeight);

          // Update progress bar
          var progress = (response.data.synced / response.data.total) * 100;
          $("#progress-bar-fill").css("width", progress + "%");

          if (response.data.synced < response.data.total) {
            syncCategories();
          } else {
            $("#sync-loader").hide();
            $("#sync-status").append("<p>Categories synced successfully!</p>");
            updateStats(); // Update statistics after successful sync
          }
        } else {
          $("#sync-status").append(
            "<p>Error syncing categories: " + response.data.message + "</p>"
          );
        }
        $("#sync-loader").hide();
        updateStats(); // Always update statistics
      },
      error: function (xhr, status, error) {
        $("#sync-loader").hide();
        $("#sync-status").append(
          "<p>An error occurred during category syncing: " + error + "</p>"
        );
        updateStats(); // Update statistics even if there's an error
      },
    });
  }

  function syncPages() {
    $("#sync-loader").show();
    $("#sync-log").html(""); // Clear log area
    $("#progress-bar-fill").css("width", "0%"); // Reset progress bar

    $.ajax({
      url: wrms_data.ajax_url,
      method: "POST",
      data: {
        action: "wrms_sync_pages",
        nonce: wrms_data.nonce,
      },
      success: function (response) {
        if (response.success) {
          $("#sync-count").text(
            "Synced " +
              response.data.synced +
              " of " +
              response.data.total +
              " pages"
          );
          $("#sync-log").append(
            "<p>Processed page: " +
              response.data.page.title +
              " (ID: " +
              response.data.page.id +
              ")</p>"
          );
          $("#sync-log").scrollTop($("#sync-log")[0].scrollHeight);

          // Update progress bar
          var progress = (response.data.synced / response.data.total) * 100;
          $("#progress-bar-fill").css("width", progress + "%");

          if (response.data.synced < response.data.total) {
            syncPages();
          } else {
            $("#sync-loader").hide();
            $("#sync-status").append("<p>Pages synced successfully!</p>");
            updateStats(); // Update statistics after successful sync
          }
        } else {
          $("#sync-status").append(
            "<p>Error syncing pages: " + response.data.message + "</p>"
          );
        }
        $("#sync-loader").hide();
        updateStats(); // Always update statistics
      },
      error: function (xhr, status, error) {
        $("#sync-loader").hide();
        $("#sync-status").append(
          "<p>An error occurred during page syncing: " + error + "</p>"
        );
        updateStats(); // Update statistics even if there's an error
      },
    });
  }

  function syncMedia() {
    $("#sync-loader").show();
    $("#sync-log").html(""); // Clear log area
    $("#progress-bar-fill").css("width", "0%"); // Reset progress bar

    $.ajax({
      url: wrms_data.ajax_url,
      method: "POST",
      data: {
        action: "wrms_sync_media",
        nonce: wrms_data.nonce,
      },
      success: function (response) {
        if (response.success) {
          $("#sync-count").text(
            "Synced " +
              response.data.synced +
              " of " +
              response.data.total +
              " media items"
          );
          $("#sync-log").append(
            "<p>Processed media: " +
              response.data.media.title +
              " (ID: " +
              response.data.media.id +
              ")</p>"
          );
          $("#sync-log").scrollTop($("#sync-log")[0].scrollHeight);

          // Update progress bar
          var progress = (response.data.synced / response.data.total) * 100;
          $("#progress-bar-fill").css("width", progress + "%");

          if (response.data.synced < response.data.total) {
            syncMedia();
          } else {
            $("#sync-loader").hide();
            $("#sync-status").append("<p>Media items synced successfully!</p>");
            updateStats(); // Update statistics after successful sync
          }
        } else {
          $("#sync-status").append(
            "<p>Error syncing media: " + response.data.message + "</p>"
          );
        }
        $("#sync-loader").hide();
        updateStats(); // Always update statistics
      },
      error: function (xhr, status, error) {
        $("#sync-loader").hide();
        $("#sync-status").append(
          "<p>An error occurred during media syncing: " + error + "</p>"
        );
        updateStats(); // Update statistics even if there's an error
      },
    });
  }

  function syncPosts() {
    $("#sync-loader").show();
    $("#sync-log").html(""); // Clear log area
    $("#progress-bar-fill").css("width", "0%"); // Reset progress bar

    $.ajax({
      url: wrms_data.ajax_url,
      method: "POST",
      data: {
        action: "wrms_sync_posts",
        nonce: wrms_data.nonce,
      },
      success: function (response) {
        if (response.success) {
          $("#sync-count").text(
            "Synced " +
              response.data.synced +
              " of " +
              response.data.total +
              " posts"
          );
          $("#sync-log").append(
            "<p>Processed post: " +
              response.data.post.title +
              " (ID: " +
              response.data.post.id +
              ")</p>"
          );
          $("#sync-log").scrollTop($("#sync-log")[0].scrollHeight);

          // Update progress bar
          var progress = (response.data.synced / response.data.total) * 100;
          $("#progress-bar-fill").css("width", progress + "%");

          if (response.data.synced < response.data.total) {
            syncPosts();
          } else {
            $("#sync-loader").hide();
            $("#sync-status").append("<p>Posts synced successfully!</p>");
            updateStats(); // Update statistics after successful sync
          }
        } else {
          $("#sync-status").append(
            "<p>Error syncing posts: " + response.data.message + "</p>"
          );
        }
        $("#sync-loader").hide();
        updateStats(); // Always update statistics
      },
      error: function (xhr, status, error) {
        $("#sync-loader").hide();
        $("#sync-status").append(
          "<p>An error occurred during post syncing: " + error + "</p>"
        );
        updateStats(); // Update statistics even if there's an error
      },
    });
  }

  function removeProductMeta() {
    $("#sync-loader").show();
    $("#sync-log").html(""); // Clear log area
    $("#progress-bar-fill").css("width", "0%"); // Reset progress bar

    $.ajax({
      url: wrms_data.ajax_url,
      method: "POST",
      data: {
        action: "wrms_remove_product_meta",
        nonce: wrms_data.nonce,
      },
      success: function (response) {
        if (response.success) {
          $("#sync-count").text(
            "Removed meta from " +
              response.data.removed +
              " of " +
              response.data.total +
              " products"
          );
          $("#sync-log").append("<p>Product meta removed successfully!</p>");
          $("#sync-log").scrollTop($("#sync-log")[0].scrollHeight);

          // Update progress bar
          var progress = (response.data.removed / response.data.total) * 100;
          $("#progress-bar-fill").css("width", progress + "%");

          updateStats(); // Update statistics after successful removal
        } else {
          $("#sync-status").append(
            "<p>Error removing product meta: " + response.data.message + "</p>"
          );
        }
        $("#sync-loader").hide();
        updateStats(); // Always update statistics
      },
      error: function (xhr, status, error) {
        $("#sync-loader").hide();
        $("#sync-status").append(
          "<p>An error occurred during product meta removal: " + error + "</p>"
        );
        updateStats(); // Update statistics even if there's an error
      },
    });
  }

  function removeCategoryMeta() {
    $("#sync-loader").show();
    $("#sync-log").html(""); // Clear log area
    $("#progress-bar-fill").css("width", "0%"); // Reset progress bar

    $.ajax({
      url: wrms_data.ajax_url,
      method: "POST",
      data: {
        action: "wrms_remove_category_meta",
        nonce: wrms_data.nonce,
      },
      success: function (response) {
        if (response.success) {
          $("#sync-count").text(
            "Removed meta from " +
              response.data.removed +
              " of " +
              response.data.total +
              " categories"
          );
          $("#sync-log").append("<p>Category meta removed successfully!</p>");
          $("#sync-log").scrollTop($("#sync-log")[0].scrollHeight);

          // Update progress bar
          var progress = (response.data.removed / response.data.total) * 100;
          $("#progress-bar-fill").css("width", progress + "%");

          updateStats(); // Update statistics after successful removal
        } else {
          $("#sync-status").append(
            "<p>Error removing category meta: " + response.data.message + "</p>"
          );
        }
        $("#sync-loader").hide();
        updateStats(); // Always update statistics
      },
      error: function (xhr, status, error) {
        $("#sync-loader").hide();
        $("#sync-status").append(
          "<p>An error occurred during category meta removal: " + error + "</p>"
        );
        updateStats(); // Update statistics even if there's an error
      },
    });
  }

  function removePageMeta() {
    $("#sync-loader").show();
    $("#sync-log").html(""); // Clear log area
    $("#progress-bar-fill").css("width", "0%"); // Reset progress bar

    $.ajax({
      url: wrms_data.ajax_url,
      method: "POST",
      data: {
        action: "wrms_remove_page_meta",
        nonce: wrms_data.nonce,
      },
      success: function (response) {
        if (response.success) {
          $("#sync-count").text(
            "Removed meta from " +
              response.data.removed +
              " of " +
              response.data.total +
              " pages"
          );
          $("#sync-log").append("<p>Page meta removed successfully!</p>");
          $("#sync-log").scrollTop($("#sync-log")[0].scrollHeight);

          // Update progress bar
          var progress = (response.data.removed / response.data.total) * 100;
          $("#progress-bar-fill").css("width", progress + "%");

          updateStats(); // Update statistics after successful removal
        } else {
          $("#sync-status").append(
            "<p>Error removing page meta: " + response.data.message + "</p>"
          );
        }
        $("#sync-loader").hide();
        updateStats(); // Always update statistics
      },
      error: function (xhr, status, error) {
        $("#sync-loader").hide();
        $("#sync-status").append(
          "<p>An error occurred during page meta removal: " + error + "</p>"
        );
        updateStats(); // Update statistics even if there's an error
      },
    });
  }

  function removeMediaMeta() {
    $("#sync-loader").show();
    $("#sync-log").html(""); // Clear log area
    $("#progress-bar-fill").css("width", "0%"); // Reset progress bar

    $.ajax({
      url: wrms_data.ajax_url,
      method: "POST",
      data: {
        action: "wrms_remove_media_meta",
        nonce: wrms_data.nonce,
      },
      success: function (response) {
        if (response.success) {
          $("#sync-count").text(
            "Removed meta from " +
              response.data.removed +
              " of " +
              response.data.total +
              " media items"
          );
          $("#sync-log").append("<p>Media meta removed successfully!</p>");
          $("#sync-log").scrollTop($("#sync-log")[0].scrollHeight);

          // Update progress bar
          var progress = (response.data.removed / response.data.total) * 100;
          $("#progress-bar-fill").css("width", progress + "%");

          updateStats(); // Update statistics after successful removal
        } else {
          $("#sync-status").append(
            "<p>Error removing media meta: " + response.data.message + "</p>"
          );
        }
        $("#sync-loader").hide();
        updateStats(); // Always update statistics
      },
      error: function (xhr, status, error) {
        $("#sync-loader").hide();
        $("#sync-status").append(
          "<p>An error occurred during media meta removal: " + error + "</p>"
        );
        updateStats(); // Update statistics even if there's an error
      },
    });
  }

  function removePostMeta() {
    $("#sync-loader").show();
    $("#sync-log").html(""); // Clear log area
    $("#progress-bar-fill").css("width", "0%"); // Reset progress bar

    $.ajax({
      url: wrms_data.ajax_url,
      method: "POST",
      data: {
        action: "wrms_remove_post_meta",
        nonce: wrms_data.nonce,
      },
      success: function (response) {
        if (response.success) {
          $("#sync-count").text(
            "Removed meta from " +
              response.data.removed +
              " of " +
              response.data.total +
              " posts"
          );
          $("#sync-log").append("<p>Post meta removed successfully!</p>");
          $("#sync-log").scrollTop($("#sync-log")[0].scrollHeight);

          // Update progress bar
          var progress = (response.data.removed / response.data.total) * 100;
          $("#progress-bar-fill").css("width", progress + "%");

          updateStats(); // Update statistics after successful removal
        } else {
          $("#sync-status").append(
            "<p>Error removing post meta: " + response.data.message + "</p>"
          );
        }
        $("#sync-loader").hide();
        updateStats(); // Always update statistics
      },
      error: function (xhr, status, error) {
        $("#sync-loader").hide();
        $("#sync-status").append(
          "<p>An error occurred during post meta removal: " + error + "</p>"
        );
        updateStats(); // Update statistics even if there's an error
      },
    });
  }

  function downloadUrls(urlTypes) {
    $("#download-loader").show();
    $("#download-log").html(""); // Clear log area
    $("#download-progress-bar-fill").css("width", "0%"); // Reset progress bar

    var offset = 0;
    var chunkSize = 2000;

    function downloadChunk() {
      $.ajax({
        url: wrms_data.ajax_url,
        type: "POST",
        data: {
          action: "wrms_get_urls",
          nonce: wrms_data.nonce,
          offset: offset,
          chunk_size: chunkSize,
          url_types: urlTypes,
        },
        success: function (response) {
          if (response.success && response.data.urls.length > 0) {
            // Create and download the file
            var blob = new Blob([response.data.urls.join("\n")], {
              type: "text/plain",
            });
            var link = document.createElement("a");
            link.href = window.URL.createObjectURL(blob);
            link.download =
              "wordpress_urls_" +
              offset +
              "-" +
              (offset + response.data.urls.length) +
              ".txt";
            link.click();

            // Update status
            $("#download-count").text(
              "Downloaded URLs " +
                offset +
                " to " +
                (offset + response.data.urls.length)
            );
            $("#download-log").append(
              "<p>Downloaded URLs " +
                offset +
                " to " +
                (offset + response.data.urls.length) +
                "</p>"
            );
            $("#download-log").scrollTop($("#download-log")[0].scrollHeight);

            // Update progress bar
            var progress =
              ((offset + response.data.urls.length) / response.data.total) *
              100;
            $("#download-progress-bar-fill").css("width", progress + "%");

            // Move to next chunk
            offset += chunkSize;
            downloadChunk();
          } else {
            $("#download-count").text("All URLs have been downloaded.");
            $("#download-log").append("<p>All URLs have been downloaded.</p>");
            $("#download-loader").hide();
          }
        },
        error: function (xhr, status, error) {
          $("#download-loader").hide();
          $("#download-status").append(
            "<p>An error occurred during URL download: " + error + "</p>"
          );
        },
      });
    }

    downloadChunk();
  }
});