<?php

global $wpdb;
// Get current user ID
$user_id = get_current_user_id();

$groups = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}moowoodle_group WHERE user_id = %d",
        $user_id
    )
);

if ( ! empty( $groups ) ) {
    echo '<h3>' . __( 'My Groups', 'moowoodle' ) . '</h3>';
    echo ' <button id="addGroupBtn" class="add-group-button">' . __( 'Add Group', 'moowoodle' ) . '</button>';
    $products_in_groups = [];
    foreach ( $groups as $group ) {
        echo '<h4>' . esc_html( $group->name ) . '</h4>';
        $group_items = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}moowoodle_group_items WHERE group_id = %d",
				$group->id
			)
		);
        if ( ! empty( $group_items ) ) {
            // Display group items in a table
            echo '<table class="group-items-table" style="width: 100%; border-collapse: collapse; margin-bottom: 20px;">';
            echo '<thead>';
            echo '<tr>';
            echo '<th style="border: 1px solid #ddd; padding: 8px;">' . __( 'Product Name', 'moowoodle' ) . '</th>';
            echo '<th style="border: 1px solid #ddd; padding: 8px;">' . __( 'Total Quantity', 'moowoodle' ) . '</th>';
            echo '<th style="border: 1px solid #ddd; padding: 8px;">' . __( 'Available Quantity', 'moowoodle' ) . '</th>';
            echo '<th style="border: 1px solid #ddd; padding: 8px;">' . __( 'Enroll User', 'moowoodle' ) . '</th>';
            echo '</tr>';
            echo '</thead>';
            echo '<tbody>';

            foreach ($group_items as $item) {
                // Fetch product details
                $product = wc_get_product($item->product_id);
                
                if ($product && !isset($products_in_groups[$item->product_id])) {
                    $products_in_groups[$item->product_id] = $product->get_name();
                }
                
                if ($product) {
                    // Build enroll URL with group_id and item_id
                    $enroll_url = wc_get_endpoint_url('view-enroll', $product->get_id(), wc_get_page_permalink('myaccount'));
                    $enroll_url = add_query_arg([
                        'groupId' => $group->id,
                        'groupItemId'  => $item->id,
                    ], $enroll_url);
            
                    echo '<tr>';
                    echo '<td style="border: 1px solid #ddd; padding: 8px;">' . esc_html($product->get_name()) . '</td>';
                    echo '<td style="border: 1px solid #ddd; padding: 8px;">' . esc_html($item->total_quantity) . '</td>';
                    echo '<td style="border: 1px solid #ddd; padding: 8px;">' . esc_html($item->available_quantity) . '</td>';
                    echo '<td style="border: 1px solid #ddd; padding: 8px;">'
                        . '<a href="' . esc_url($enroll_url) . '"><strong>' . __('view', 'moowoodle') . '</strong></a>'
                        . '</td>';
                    echo '</tr>';
                }
            }
            

            echo '</tbody>';
            echo '</table>';
        } else {
            // If no items found for this group
            echo '<p>' . __( 'No items in this group.', 'moowoodle' ) . '</p>';
        }
    }
} else {
    echo '<p>' . __( 'You have no groups yet.', 'moowoodle' ) . '</p>';
}
?>

<div id="addGroupModal" style="display: none;">
    <div>
        <h2><?php _e( 'Add New Group', 'moowoodle' ); ?></h2>
        <form id="add-group-form">
            <label for="group_name"><?php _e( 'Group Name:', 'moowoodle' ); ?></label>
            <input type="text" id="group_name" name="group_name" required>

            <label for="product_id"><?php _e( 'Select Products:', 'moowoodle' ); ?></label>
            <select id="product_id" name="product_id[]" multiple required>
                <?php if ( ! empty( $products_in_groups ) ) : ?>
                    <?php foreach ( $products_in_groups as $product_id => $product_name ) : ?>
                        <option value="<?php echo esc_attr( $product_id ); ?>">
                            <?php echo esc_html( $product_name ); ?>
                        </option>
                    <?php endforeach; ?>
                <?php else : ?>
                    <option value=""><?php _e( 'No products available', 'moowoodle' ); ?></option>
                <?php endif; ?>
            </select>

            <button type="submit"><?php _e( 'Create Group', 'moowoodle' ); ?></button>
            <button type="button" id="closeModal"><?php _e( 'Cancel', 'moowoodle' ); ?></button>
        </form>
    </div>
</div>

