jQuery(document).ready(function($) {
    var $select2 = $('#filter-by-user-tag').select2({
        placeholder: 'Filter by user tags...',
        ajax: {
            url: userTagFilter.ajax_url,
            dataType: 'json',
            delay: 0,
            data: function(params) {
                return {
                    q: params.term,
                    action: 'search_user_tags',
                    nonce: userTagFilter.nonce
                };
            },
            processResults: function(data) {
                return {
                    results: $.map(data, function(item) {
                        return {
                            id: item.slug,
                            text: item.name
                        }
                    })
                };
            },
            cache: true
        },
        minimumInputLength: 0
    });


      // Function to get URL parameters
      function getUrlParameter(name) {
        name = name.replace(/[\[]/, '\\[').replace(/[\]]/, '\\]');
        var regex = new RegExp('[\\?&]' + name + '=([^&#]*)');
        var results = regex.exec(location.search);
        return results === null ? '' : decodeURIComponent(results[1].replace(/\+/g, ' '));
    };

    // Check if filter_user_tag parameter exists in the URL and select it in Select2
    var selectedTagFromURL = getUrlParameter('filter_user_tag');

    if (selectedTagFromURL) {
        var newOption = new Option(selectedTagFromURL, selectedTagFromURL, true, true);
        $select2.append(newOption).trigger('change');
    }
    $select2.on('select2:select', function (e) {
        var data = e.params.data;
        // 'data' will contain information about the selected option, such as:
        // data.id (the value of the <option>)
        // data.text (the text content of the <option>)
        // ... other properties depending on your data source
        console.log("Ron",data.text);
    });
    


 $('input[name="filter_action2"]').on('click', function(e) {
        e.preventDefault(); // Prevent the default form submission

        // Get the selected value from the second dropdowns
       
        var selectedTag = $('#filter-by-user-tag').val();
        var selectedText = $('#filter-by-user-tag option:selected').text();
       
     
        console.log('Filter button clicked for first dropdown. Selected tag:', selectedText);

        var firstDropdown = $('#filter_user_tag');

        // Set the value of the FIRST dropdown to the selected value from Select2
        firstDropdown.val(selectedText);

        // Log the value of the first dropdown to confirm it's set
        console.log('First dropdown value set to:', firstDropdown.val());
        $('input[name="filter_action"]').trigger('click');

        // At this point, you would typically perform your filtering logic using JavaScript
        // or make an AJAX request to the server. For now, we are just logging.
    });

    $('input[name="filter_action"]').on('click', function(e) {
      //  e.preventDefault();
        console.log('Filter button for first dropdown was pressed.');
        // You can add logic here if needed for the first dropdown's filter.
    });
});