(function($) {
    'use strict';
    
    $(document).ready(function() {
        var form = $('#extreme-text-replacer-form');
        var searchField = $('#etr-search-text');
        var warningDiv = $('#etr-warning');
        var submitButton = $('#etr-submit');
        var dryRunCheckbox = $('#etr-dry-run');
        
        // Update button text on page load and when checkbox changes
        function updateButtonText() {
            submitButton.text(dryRunCheckbox.is(':checked') ? 'Preview Changes' : 'Replace Text');
        }
        
        // Initial button text update
        updateButtonText();
        
        // Listen for checkbox changes
        dryRunCheckbox.on('change', updateButtonText);
        var spinner = $('.spinner');
        var resultsDiv = $('#etr-results');
        var dangerousPatterns = extremeTextReplacer.dangerous_patterns;
        
        // Check for potentially dangerous replacements
        searchField.on('change keyup', function() {
            var searchText = $(this).val();
            var isDangerous = false;
            
            // Check if search text contains any dangerous patterns
            for (var i = 0; i < dangerousPatterns.length; i++) {
                if (searchText.indexOf(dangerousPatterns[i]) !== -1) {
                    isDangerous = true;
                    break;
                }
            }
            
            // Show warning if dangerous
            if (isDangerous) {
                warningDiv.show();
            } else {
                warningDiv.hide();
            }
        });
        
        // Handle form submission
        form.on('submit', function(e) {
            e.preventDefault();
            
            // Get form data
            var searchText = searchField.val();
            var replaceText = $('#etr-replace-text').val();
            var categoryId = $('#etr-category').val();
            var isDryRun = $('#etr-dry-run').is(':checked');
            
            // Update button text based on dry run status
            submitButton.text(isDryRun ? 'Preview Changes' : 'Replace Text');
            
            // Validate
            if (!searchText) {
                alert('Please enter text to search for.');
                return;
            }
            
            // Confirm if warning is visible
            if (warningDiv.is(':visible')) {
                if (!confirm('Warning: You are replacing text that could break your site. Are you sure you want to continue?')) {
                    return;
                }
            }
            
            // Show spinner
            spinner.addClass('is-active');
            resultsDiv.html('');
            
            // Perform AJAX request
            $.ajax({
                url: extremeTextReplacer.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'extreme_text_replace',
                    nonce: extremeTextReplacer.nonce,
                    search_text: searchText,
                    replace_text: replaceText,
                    category_id: categoryId,
                    dry_run: isDryRun
                },
                success: function(response) {
                    spinner.removeClass('is-active');
                    
                    if (response.success) {
                        var resultHtml = '<div class="updated"><p>' + response.data.message + '</p></div>';
                        
                        // Add preview table if it's a dry run and there are results
                        if (response.data.is_dry_run && response.data.preview_data && response.data.preview_data.length > 0) {
                            resultHtml += '<div class="etr-preview-data"><h3>Preview of Changes:</h3>';
                            resultHtml += '<table class="wp-list-table widefat fixed striped">';
                            resultHtml += '<thead><tr><th>Post ID</th><th>Post Title</th><th>Occurrences</th></tr></thead><tbody>';
                            
                            // Add rows for each affected post
                            $.each(response.data.preview_data, function(index, item) {
                                resultHtml += '<tr>';
                                resultHtml += '<td>' + item.post_id + '</td>';
                                resultHtml += '<td><a href="post.php?post=' + item.post_id + '&action=edit" target="_blank">' + item.post_title + '</a></td>';
                                resultHtml += '<td>' + item.occurrences + '</td>';
                                resultHtml += '</tr>';
                            });
                            
                            resultHtml += '</tbody></table>';
                            resultHtml += '<p class="submit"><button type="button" id="etr-apply-changes" class="button button-primary">Apply These Changes</button></p>';
                            resultHtml += '</div>';
                        }
                        
                        resultsDiv.html(resultHtml);
                        
                        // Add event handler for the apply changes button
                        $('#etr-apply-changes').on('click', function() {
                            // Uncheck dry run and submit the form again
                            $('#etr-dry-run').prop('checked', false);
                            form.submit();
                        });
                    } else {
                        resultsDiv.html('<div class="error"><p>' + response.data.message + '</p></div>');
                    }
                },
                error: function() {
                    spinner.removeClass('is-active');
                    resultsDiv.html('<div class="error"><p>An error occurred. Please try again.</p></div>');
                }
            });
        });
    });
})(jQuery);