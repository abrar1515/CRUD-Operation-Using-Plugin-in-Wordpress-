jQuery(document).ready(function($) {
    // Submit form via AJAX
    $('#abrar-contact-form').on('submit', function(e) {
        e.preventDefault();
        var formData = $(this).serialize();
        $.ajax({
            type: 'POST',
            dataType: 'json',
            url: ajax_object.ajax_url,
            data: 'action=contact_form&' + formData,
            success: function(response) {
                alert('Form submitted successfully.');
            }
        });

    });

    // Delete button click event
    $(document).on('click', '.delete-button', function() {
        var entryId = $(this).data('id');
        $.ajax({
            url: ajax_object.ajax_url,
            type: 'POST',
            data: {
                action: 'delete_entry',
                id: entryId
            },
            success: function(response) {
                var data = JSON.parse(response);
                if (data.success) {
                    // Remove the entry from the table
                    $('button[data-id="' + entryId + '"]').closest('tr').remove();
                } else {
                    console.log('Failed to delete entry');
                }
            },
            error: function(xhr, status, error) {
                console.error(xhr.responseText);
            }
        });
    });

    // Edit button click event
    $(document).on('click', '.edit-button', function() {
        var entryId = $(this).data('id');
        var entryName = $(this).closest('tr').find('td:nth-child(2)').text();
        var entryPhone = $(this).closest('tr').find('td:nth-child(3)').text();
        var entryEmail = $(this).closest('tr').find('td:nth-child(4)').text();

        $('#edit-id').val(entryId);
        $('#edit-name').val(entryName);
        $('#edit-phone').val(entryPhone);
        $('#edit-email').val(entryEmail);
    });


    // Save changes button click event
    $('#edit-save').on('click', function() {
        var entryId = $('#edit-id').val();
        var name = $('#edit-name').val();
        var phone = $('#edit-phone').val();
        var email = $('#edit-email').val();
        // Update entry via AJAX
        $.ajax({
            url: ajax_object.ajax_url,
            type: 'POST',
            data: {
                action: 'edit_entry',
                id: entryId,
                name: name,
                phone: phone,
                email: email
            },
            success: function(response) {
                var data = JSON.parse(response);
                if (data.success) {
                    // Update entry in the table
                    var row = $('button[data-id="' + entryId + '"]').closest('tr');
                    row.find('td:eq(1)').text(name);
                    row.find('td:eq(2)').text(phone);
                    row.find('td:eq(3)').text(email);
                    $('#editModal').modal('hide');
                } else {
                    console.log('Failed to update entry');
                }
            },
            error: function(xhr, status, error) {
                console.error(xhr.responseText);
            }
        });
    });
});
