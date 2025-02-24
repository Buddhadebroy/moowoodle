<?php
defined('ABSPATH') || exit;

// Extract the argument provided by template loader.
$args = wp_parse_args($args ?? [], [
    'product_id' => 0,
    'group_id' => 0,
    'group_item_id'  => 0,
]);
extract($args);
echo 'Product ID: ' . esc_html($product_id) . '<br>';
echo 'Group ID: ' . esc_html($group_id) . '<br>';
echo 'Item ID: ' . esc_html($group_item_id) . '<br>';

global $wpdb;

// Step 1: Fetch group_item_id based on the product_id
$group_item_id_query = $wpdb->prepare(
    "
    SELECT id
    FROM {$wpdb->prefix}moowoodle_group_items
    WHERE product_id = %d
    ",
    $product_id
);

$group_item_id_result = $wpdb->get_var($group_item_id_query);
?>
<!-- Add User Button -->
<button id="addUserBtn" class="add-user-button"><?php _e( 'Add User', 'moowoodle' ); ?></button>
<?php

if ( $group_item_id_result ) {
    // Step 2: Fetch enrollment data based on the group_item_id
    $query = $wpdb->prepare(
        "
        SELECT e.user_email, e.date
        FROM {$wpdb->prefix}moowoodle_enrollment e
        WHERE e.group_item_id = %d
        ",
        $group_item_id
    );

    $results = $wpdb->get_results($query);

    // Start the HTML table
    echo '<table style="width: 100%; border-collapse: collapse;">';
    echo '<thead>';
    echo '<tr>';
    echo '<th style="border: 1px solid #ddd; padding: 8px;">' . __( 'Username', 'moowoodle' ) . '</th>';
    echo '<th style="border: 1px solid #ddd; padding: 8px;">' . __( 'Email', 'moowoodle' ) . '</th>';
    echo '<th style="border: 1px solid #ddd; padding: 8px;">' . __( 'Enrollment Date', 'moowoodle' ) . '</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';

    if ( ! empty( $results ) ) {
        // Loop through results and display them in the table
        foreach ( $results as $row ) {
            // Fetch username based on user_email
            $user = get_user_by( 'email', $row->user_email );
            $username = $user ? $user->user_login : __( 'Unknown User', 'moowoodle' );

            echo '<tr>';
            echo '<td style="border: 1px solid #ddd; padding: 8px;">' . esc_html( $username ) . '</td>';
            echo '<td style="border: 1px solid #ddd; padding: 8px;">' . esc_html( $row->user_email ) . '</td>';
            echo '<td style="border: 1px solid #ddd; padding: 8px;">' . esc_html( date( 'Y-m-d H:i:s', strtotime( $row->date ) ) ) . '</td>';
            echo '</tr>';
        }

    } else {
        echo '<tr>';
        echo '<td colspan="3" style="border: 1px solid #ddd; padding: 8px; text-align: center;">' . __( 'No enrollment data available for this product.', 'moowoodle' ) . '</td>';
        echo '</tr>';
    }

    echo '</tbody>';
    echo '</table>';
}

?>

<!-- Modal Structure -->
<div id="addUserModal" class="modal" style="display: none;">
    <div class="modal-content">
        <span class="close-modal" id="closeModal">&times;</span>
        <h2><?php _e( 'Add New User', 'moowoodle' ); ?></h2>
        <form id="add-user-form">
            <label for="user_name"><?php _e( 'Name:', 'moowoodle' ); ?></label>
            <input type="text" id="user_name" name="user_name" required>

            <label for="user_email"><?php _e( 'Email:', 'moowoodle' ); ?></label>
            <input type="email" id="user_email" name="user_email" required>
            <input type="hidden" id="product_id" name="product_id" value="<?php echo esc_attr($product_id); ?>">
            <input type="hidden" id="group_id" name="group_id" value="<?php echo esc_attr($group_id); ?>">
            <input type="hidden" id="group_item_id" name="group_item_id" value="<?php echo esc_attr($group_item_id); ?>">
            <button type="submit"><?php _e( 'Add', 'moowoodle' ); ?></button>
        </form>
        <div id="addUserMessage" style="color: green; margin-top: 10px;"></div>
    </div>
</div>
