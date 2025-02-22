jQuery(document).ready(function($) {
    var modal = document.getElementById("addGroupModal");

    window.onclick = function(event) {
        if (event.target == modal) {
            modal.style.display = "none";
        }
    };

    // Show the modal when the "Add User" button is clicked
    $('#addGroupBtn').on('click', function() {
        $('#addGroupModal').show();
    });

    // Close the modal
    $('#closeModal').on('click', function() {
        $('#addGroupModal').hide();
    });


    // Handle form submission (with AJAX)
    $('#add-group-form').on('submit', function(e) {
        e.preventDefault();
        
        var groupName = $('#group_name').val();
        var productId = $('#product_id').val();
        
        $.ajax({
            url: group_frontend_data.ajax_url,
            type: 'POST',
            data: {
                action: 'create_new_group_with_product',
                group_name: groupName,
                product_id: productId
            },
            success: function(response) {
                if (response.success) {
                    alert('Group created successfully: ' + response.data.group_name);
                    location.reload();
                } else {
                    alert('Error: ' + response.data.message);
                }
            },
        });
    });

    // Show the modal when the "Add User" button is clicked
    $('#addUserBtn').on('click', function() {
        $('#addUserModal').show();
    });

    // Close the modal
    $('#closeModal').on('click', function() {
        $('#addUserModal').hide();
    });

    // Handle form submission via AJAX
    $('#add-user-form').on('submit', function(event) {
        event.preventDefault();

        var user_name = $('#user_name').val();
        var user_email = $('#user_email').val();
        var productId = $('#product_id').val();
        var groupId = $('#group_id').val();
        var groupItemId = $('#group_item_id').val();

        // AJAX request
        $.ajax({
            type: 'POST',
            url: group_frontend_data.ajax_url,  // This URL will be localized in PHP
            data: {
                action: 'moowoodle_add_user',
                user_name: user_name,
                user_email: user_email,
                product_id: productId,
                group_id: groupId,
                group_item_id: groupItemId,
            },
            success: function(response) {
                console.log(response);
                var messageDiv = $('#addUserMessage');

                if (response.success) {
                    messageDiv.css('color', 'green').html(response.data.message);
                } else {
                    messageDiv.css('color', 'red').html(response.data.message);
                }

                // Hide modal after a short delay
                setTimeout(function() {
                    $('#addUserModal').hide();
                    $('#add-user-form')[0].reset();
                    messageDiv.html('');
                }, 2000);
            }
        });
    });
});