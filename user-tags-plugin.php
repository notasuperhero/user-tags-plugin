<?php
/** 
 * Plugin name: User Tags Plugin
 * Description: Add, Deletes and Edit Tags to User for Easy Searching 
 * Version: 1.0
 * Author: Arvind Arya
 * Plugin URI:
 * Author URI:
*/

//Let's define the taxonomy first
function register_user_tags_taxonomy() {

    //An array named $labels. This array will hold the labels used for the taxonomy's user interface.
    $labels = array(
        'name' => _x( 'User Tags', 'taxonomy general name', 'user-tags-plugin' ),
        'singular_name' => _x( 'User Tag', 'taxonomy singular name', 'user-tags-plugin' ),
        'search_items'  => __( 'Search User Tags', 'user-tags-plugin' ),
        'all_items'   => __( 'All User Tags', 'user-tags-plugin' ),
        'edit_item'  => __( 'Edit User Tag', 'user-tags-plugin' ),
        'update_item' => __( 'Update User Tag', 'user-tags-plugin' ),
        'add_new_item' => __( 'Add New User Tag', 'user-tags-plugin' ),
        'new_item_name'=> __( 'New User Tag Name', 'user-tags-plugin' ),
        'menu_name' => __( 'User Tags', 'user-tags-plugin' ),
        
    );
    // An array named $args. This array will hold the arguments used when registering the taxonomy.
    
    $args = array(
        'hierarchical'=> false,
        'labels'=> $labels,
        'show_ui'=> true,
        'show_admin_column'=> true,
        'query_var'=> true,
        'rewrite'=> array( 'slug' => 'user-tag' ),
        
    );
    register_taxonomy( 'user_tag', null, $args );

}

//Hooks the Taxonomy 
add_action( 'init', 'register_user_tags_taxonomy', 0 );
// Design the User Table
function add_login_as_user_column( $columns ) {
    $columns['login_as_user'] = 'Login as User';
    $columns['user_tags']      = 'User Tags';
    $columns['total_spent']    = 'Total Spent';
    $columns['last_order']     = 'Last Order';
    $columns['order_count']    = 'Order Count';
    return $columns;
}
add_filter( 'manage_users_columns', 'add_login_as_user_column' );

function hide_posts_column_in_users( $columns ) {
    unset( $columns['posts'] );
    return $columns;
}
add_filter( 'manage_users_columns', 'hide_posts_column_in_users' );

// 2. Populate the custom column data
function populate_custom_user_columns( $value, $column_name, $user_id ) {

 
    switch ( $column_name ) {
        case 'user_tags':
            $user_tags = wp_get_object_terms( $user_id, 'user_tag' );
            $tag_names = array();
            if ( ! empty( $user_tags ) && ! is_wp_error( $user_tags ) ) {
                foreach ( $user_tags as $tag ) {
                    $tag_names[] = esc_html( $tag->name );
                }
                return implode( ', ', $tag_names );
            }
            return '-';
            break;

        case 'total_spent':
            $total_spent = get_user_meta( $user_id, 'total_spent', true );
            return ( ! empty( $total_spent ) ) ? '$' . esc_html( $total_spent ) : '$0.00';
            break;

        case 'last_order':
            $last_order = get_user_meta( $user_id, 'last_order', true );
            return ( ! empty( $last_order ) ) ? esc_html( $last_order ) : '-';
            break;

        case 'order_count':
            $order_count = get_user_meta( $user_id, 'order_count', true );
            return ( ! empty( $order_count ) ) ? esc_html( $order_count ) : '0';
            break;

        case 'login_as_user':
            $user = get_user_by( 'id', $user_id );
            if ( $user ) {
                $login_url = wp_nonce_url( admin_url( 'admin-ajax.php?action=login_as_user&user_id=' . $user_id ), 'login_as_user_' . $user_id );
                return '<a href="' . esc_url( $login_url ) . '" target="_blank">Login</a>';
            }
            break;

        default:
            return $value;
    }
}
add_action( 'manage_users_custom_column', 'populate_custom_user_columns', 10, 3 );


/**
 * Adds the "User Tags" submenu page under the "Users" menu.
 */
//This will Create the SubMenu in "Users" in WordPress Admin.
function user_tags_admin_menu() {
    add_submenu_page(
        'users.php',
        'User Tags',
        'User Tags',
        'manage_options',
        'user-tags',
        'my_render_user_tags_page'

    );
    }
    // Hooks the user_tags_admin_menu() function to the admin_menu action. This ensures that the function is executed when WordPress builds the admin menu.
    add_action( 'admin_menu', 'user_tags_admin_menu' );
    
    
    //Render the User Tags Page 

     function my_render_user_tags_page() {
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'User Tags', 'user-tag-plugin' ); ?></h1>
    
            <?php if ( isset( $_GET['success'] ) && 'tag_added' === $_GET['success'] ) : ?>
                <div class="notice notice-success is-dismissible">
                    <p><?php esc_html_e( 'User tag added successfully!', 'user-tag-plugin' ); ?></p>
                    <button type="button" class="notice-dismiss"><span class="screen-reader-text"><?php esc_html_e( 'Dismiss this notice.', 'user-tag-plugin' ); ?></span></button>
                </div>
            <?php elseif ( isset( $_GET['bulk_deleted'] ) ) : ?>
                <div class="notice notice-success is-dismissible">
                    <p><?php printf( esc_html__( '%d user tags deleted successfully!', 'user-tag-plugin' ), absint( $_GET['bulk_deleted'] ) ); ?></p>
                    <button type="button" class="notice-dismiss"><span class="screen-reader-text"><?php esc_html_e( 'Dismiss this notice.', 'user-tag-plugin' ); ?></span></button>
                </div>
            <?php elseif ( isset( $_GET['error'] ) ) : ?>
                <div class="notice notice-error is-dismissible">
                    <p><?php
                        if ( 'empty_name' === $_GET['error'] ) {
                            esc_html_e( 'Error: Tag name cannot be empty.', 'user-tag-plugin' );
                        } elseif ( 'term_exists' === $_GET['error'] ) {
                            esc_html_e( 'Error: A tag with this name or slug already exists.', 'user-tag-plugin' );
                        } else {
                            printf( esc_html__( 'Error: Could not add user tag. %s', 'user-tag-plugin' ), esc_html( $_GET['error'] ) );
                        }
                        ?></p>
                    <button type="button" class="notice-dismiss"><span class="screen-reader-text"><?php esc_html_e( 'Dismiss this notice.', 'user-tag-plugin' ); ?></span></button>
                </div>
            <?php endif; ?>
    
            <div id="ajax-response"></div>
    
            <div id="col-container" class="wp-clearfix">
    
                <div id="col-left">
                    <div class="col-wrap">
                        <div class="form-wrap">
                            <h2><?php esc_html_e( 'Add New User Tag', 'user-tag-plugin' ); ?></h2>
                            <form id="addtag" method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=user-tags' ) ); ?>" class="validate">
                                <?php wp_nonce_field( 'add-user-tag', 'add-user-tag-nonce' ); ?>
                                <input type="hidden" name="action" value="add-user-tag">
                                <div class="form-field form-required term-name-wrap">
                                    <label for="tag-name"><?php esc_html_e( 'Name', 'user-tag-plugin' ); ?></label>
                                    <input name="tag-name" id="tag-name" type="text" value="" size="40" aria-required="true" aria-describedby="name-description">
                                    <p id="name-description"><?php esc_html_e( 'The name is how it appears on your site.', 'user-tag-plugin' ); ?></p>
                                </div>
                                <div class="form-field term-slug-wrap">
                                    <label for="tag-slug"><?php esc_html_e( 'Slug', 'user-tag-plugin' ); ?></label>
                                    <input name="slug" id="tag-slug" type="text" value="" size="40" aria-describedby="slug-description">
                                    <p id="slug-description"><?php esc_html_e( 'The “slug” is the URL-friendly version of the name. It is usually all lowercase and contains only letters, numbers, and hyphens.', 'user-tag-plugin' ); ?></p>
                                </div>
                                <div class="form-field term-description-wrap">
                                    <label for="tag-description"><?php esc_html_e( 'Description', 'user-tag-plugin' ); ?></label>
                                    <textarea name="description" id="tag-description" rows="5" cols="40" aria-describedby="description-description"></textarea>
                                    <p id="description-description"><?php esc_html_e( 'The description is not prominent by default; however, some themes may show it.', 'user-tag-plugin' ); ?></p>
                                </div>
                                <p class="submit">
                                    <input type="submit" name="submit" id="submit" class="button button-primary" value="<?php esc_attr_e( 'Add New User Tag', 'user-tag-plugin' ); ?>">
                                    <span class="spinner"></span>
                                </p>
                            </form>
                        </div>
                    </div>
                </div>
    
                <div id="col-right">
                    <div class="col-wrap">
                        <form id="posts-filter" method="post">
                            <?php wp_nonce_field( 'bulk-user-tags', 'bulk-user-tags-nonce' ); ?>
                            <input type="hidden" name="taxonomy" value="user_tag">
    
                            <div class="tablenav top">
                                <div class="alignleft actions bulkactions">
                                    <label for="bulk-action-selector-top" class="screen-reader-text"><?php esc_html_e( 'Select bulk action', 'user-tag-plugin' ); ?></label>
                                    <select name="action" id="bulk-action-selector-top">
                                        <option value="-1"><?php esc_html_e( 'Bulk actions', 'user-tag-plugin' ); ?></option>
                                        <option value="delete"><?php esc_html_e( 'Delete', 'user-tag-plugin' ); ?></option>
                                    </select>
                                    <input type="submit" name="bulk_action" id="doaction" class="button action" value="<?php esc_attr_e( 'Apply', 'user-tag-plugin' ); ?>">
                                </div>
                                <br class="clear">
                            </div>
    
                            <h2 class="screen-reader-text"><?php esc_html_e( 'User Tags list', 'user-tag-plugin' ); ?></h2>
                            <table class="wp-list-table widefat fixed striped table-view-list tags">
                                <thead>
                                    <tr>
                                        <td id="cb" class="manage-column column-cb check-column">
                                            <input id="cb-select-all-1" type="checkbox">
                                            <label for="cb-select-all-1"><span class="screen-reader-text"><?php esc_html_e( 'Select All', 'user-tag-plugin' ); ?></span></label>
                                        </td>
                                        <th scope="col" id="name" class="manage-column column-name column-primary sortable asc">
                                            <span><?php esc_html_e( 'Name', 'user-tag-plugin' ); ?></span><span class="sorting-indicator"></span>
                                        </th>
                                        <th scope="col" id="description" class="manage-column column-description sortable desc">
                                            <span><?php esc_html_e( 'Description', 'user-tag-plugin' ); ?></span><span class="sorting-indicator"></span>
                                        </th>
                                        <th scope="col" id="slug" class="manage-column column-slug sortable desc">
                                            <span><?php esc_html_e( 'Slug', 'user-tag-plugin' ); ?></span><span class="sorting-indicator"></span>
                                        </th>
                                        <th scope="col" id="users" class="manage-column column-users sortable desc">
                                            <span><?php esc_html_e( 'Users', 'user-tag-plugin' ); ?></span><span class="sorting-indicator"></span>
                                        </th>
                                    </tr>
                                </thead>
                                <tbody id="the-list" data-wp-lists="list:user_tag">
                                    <?php
                                    $taxonomy = 'user_tag';
                                    $terms = get_terms( $taxonomy, array( 'hide_empty' => false ) );
    
                                    if ( $terms && ! is_wp_error( $terms ) ) {
                                        foreach ( $terms as $term ) {
                                            $user_count = count( get_objects_in_term( $term->term_id, $taxonomy ) );
                                            echo '<tr id="tag-' . esc_attr( $term->term_id ) . '">';
                                            echo '<th scope="row" class="check-column">';
                                            echo '<input type="checkbox" name="delete_tags[]" value="' . esc_attr( $term->term_id ) . '">';
                                            echo '</th>';
                                            echo '<td class="name column-name has-row-actions column-primary" data-colname="' . esc_attr__( 'Name', 'user-tag-plugin' ) . '">';
                                            echo '<strong><a class="row-title" href="' . esc_url( admin_url( 'term.php?taxonomy=' . $taxonomy . '&tag_ID=' . $term->term_id . '&post_type=user' ) ) . '" title="' . esc_attr__( 'Edit “', 'user-tag-plugin' ) . $term->name . '”">' . esc_html( $term->name ) . '</a></strong>';
                                            echo '<div class="row-actions">';
                                            echo '<span class="edit"><a href="' . esc_url( admin_url( 'term.php?taxonomy=' . $taxonomy . '&tag_ID=' . $term->term_id . '&post_type=user' ) ) . '">' . esc_html__( 'Edit', 'user-tag-plugin' ) . '</a> | </span>';
                                            echo '<span class="delete"><a class="delete-tag" href="' . esc_url( wp_nonce_url( admin_url( 'admin.php?action=delete-tag&taxonomy=' . $taxonomy . '&tag_ID=' . $term->term_id . '&_wp_http_referer=' . urlencode( wp_unslash( $_SERVER['REQUEST_URI'] ) ) ), 'delete-tag_' . $term->term_id ) ) . '">' . esc_html__( 'Delete', 'user-tag-plugin' ) . '</a></span>';
                                            echo '</div>';
                                            echo '<button type="button" class="toggle-row"><span class="screen-reader-text">' . esc_html__( 'Show more details', 'user-tag-plugin' ) . '</span></button>';
                                            echo '</td>';
                                            echo '<td class="description column-description" data-colname="' . esc_attr__( 'Description', 'user-tag-plugin' ) . '">' . esc_html( $term->description ) . '</td>';
                                            echo '<td class="slug column-slug" data-colname="' . esc_attr__( 'Slug', 'user-tag-plugin' ) . '">' . esc_html( $term->slug ) . '</td>';
                                            echo '<td class="users column-users" data-colname="' . esc_attr__( 'Users', 'user-tag-plugin' ) . '">' . absint( $user_count ) . '</td>';
                                            echo '</tr>';
                                        }
                                    } else {
                                        echo '<tr class="no-items"><td class="colspanchange" colspan="5">' . esc_html__( 'No user tags found.', 'user-tag-plugin' ) . '</td></tr>';
                                    }
                                    ?>
                                </tbody>
    
                                <tfoot>
                                    <tr>
                                        <td class="manage-column column-cb check-column">
                                            <input id="cb-select-all-2" type="checkbox">
                                            <label for="cb-select-all-2"><span class="screen-reader-text"><?php esc_html_e( 'Select All', 'user-tag-plugin' ); ?></span></label>
                                        </td>
                                        <th scope="col" class="manage-column column-name column-primary sorted asc">
                                            <span><?php esc_html_e( 'Name', 'user-tag-plugin' ); ?></span><span class="sorting-indicator"></span>
                                        </th>
                                        <th scope="col" class="manage-column column-description sortable desc">
                                            <span><?php esc_html_e( 'Description', 'user-tag-plugin' ); ?></span><span class="sorting-indicator"></span>
                                        </th>
                                        <th scope="col" class="manage-column column-slug sortable desc">
                                            <span><?php esc_html_e( 'Slug', 'user-tag-plugin' ); ?></span><span class="sorting-indicator"></span>
                                        </th>
                                        <th scope="col" class="manage-column column-users sortable desc">
                                            <span><?php esc_html_e( 'Users', 'user-tag-plugin' ); ?></span><span class="sorting-indicator"></span>
                                        </th>
                                    </tr>
                                </tfoot>
                            </table>
    
                            <div class="tablenav bottom">
                                <div class="alignleft actions bulkactions">
                                    <label for="bulk-action-selector-bottom" class="screen-reader-text"><?php esc_html_e( 'Select bulk action', 'user-tag-plugin' ); ?></label>
                                    <select name="action2" id="bulk-action-selector-bottom">
                                        <option value="-1"><?php esc_html_e( 'Bulk actions', 'user-tag-plugin' ); ?></option>
                                        <option value="delete"><?php esc_html_e( 'Delete', 'user-tag-plugin' ); ?></option>
                                    </select>
                                    <input type="submit" name="bulk_action2" id="doaction2" class="button action" value="<?php esc_attr_e( 'Apply', 'user-tag-plugin' ); ?>">
                                </div>
                                <br class="clear">
                            </div>
                        </form>
                    </div>
                </div>
    
            </div>
        </div>
        <?php
    }

    function my_handle_add_user_tag() {
        error_log( 'my_handle_add_user_tag() function called.' );
        error_log( '$_POST data: ' . print_r( $_POST, true ) );
    
        $nonce_verified = isset( $_POST['add-user-tag-nonce'] ) && wp_verify_nonce( $_POST['add-user-tag-nonce'], 'add-user-tag' );
        error_log( 'Nonce verification result: ' . ( $nonce_verified ? 'true' : 'false' ) );
    
        if ( ! $nonce_verified ) {
            error_log( 'Nonce verification failed. Exiting.' );
            return;
        }
    
        $has_capability = current_user_can( 'manage_options' ); // Changed to manage_options
        error_log( 'Capability check (manage_options): ' . ( $has_capability ? 'true' : 'false' ) );
    
        if ( ! $has_capability ) {
            error_log( 'User does not have manage_options capability. Exiting.' );
            wp_die( esc_html__( 'You do not have permission to manage user tags.', 'user-tag-plugin' ) );
            return;
        }
    
        $name        = sanitize_text_field( $_POST['tag-name'] );
        $slug        = sanitize_title( $_POST['slug'] );
        $description = sanitize_textarea_field( $_POST['description'] );
        $taxonomy    = 'user_tag';
    
        error_log( 'Data before wp_insert_term(): Name: ' . $name . ', Slug: ' . $slug . ', Taxonomy: ' . $taxonomy );
    
        $inserted = wp_insert_term(
            $name,
            $taxonomy,
            array(
                'slug'        => $slug,
                'description' => $description,
            )
        );
    
        error_log( 'wp_insert_term() result: ' . print_r( $inserted, true ) );
    
        if ( is_wp_error( $inserted ) ) {
            error_log( 'Error inserting term: ' . $inserted->get_error_message() . ' (Code: ' . $inserted->get_error_code() . ')' );
            wp_redirect( esc_url_raw( add_query_arg( 'error', $inserted->get_error_code(), admin_url( 'admin.php?page=user-tags' ) ) ) );
        } else {
            error_log( 'Term inserted with ID: ' . $inserted['term_id'] );
            wp_redirect( esc_url_raw( add_query_arg( 'success', 'tag_added', admin_url( 'admin.php?page=user-tags' ) ) ) );
        }
    }
    add_action( 'admin_init', 'my_handle_add_user_tag' );


      /**
 * Add custom fields to the "Add New User" form (with interactive tag selection).
 * Hook: user_new_form
 */
function my_add_user_custom_fields_new_user() {
    ?>
    <h3><?php esc_html_e( 'Additional User Information', 'user-tag-plugin' ); ?></h3>
    <table class="form-table">
        <tr>
            <th><label for="user_tags_container"><?php esc_html_e( 'User Tags', 'user-tag-plugin' ); ?></label></th>
            <td>
                <div id="available-user-tags">
                    <?php
                    $taxonomy = 'user_tag';
                    $terms = get_terms( $taxonomy, array( 'hide_empty' => false ) );
                    if ( $terms && ! is_wp_error( $terms ) ) {
                        foreach ( $terms as $term ) {
                            echo '<span class="user-tag-option" data-tag-id="' . esc_attr( $term->term_id ) . '">' . esc_html( $term->name ) . '</span> ';
                        }
                    } else {
                        esc_html_e( 'No user tags available yet.', 'user-tag-plugin' );
                    }
                    ?>
                </div>
                <div id="selected-user-tags">
                    <p><?php esc_html_e( 'Selected Tags:', 'user-tag-plugin' ); ?></p>
                    </div>
                <input type="hidden" name="user_tags" id="selected-user-tag-ids" value="">
                <p class="description"><?php esc_html_e( 'Select existing user tags for this user.', 'user-tag-plugin' ); ?></p>
            </td>
        </tr>
        <tr>
            <th><label for="total_spent"><?php esc_html_e( 'Total Spent', 'user-tag-plugin' ); ?></label></th>
            <td>
                <input type="text" name="total_spent" id="total_spent" value="" class="regular-text">
                <p class="description"><?php esc_html_e( 'Optional: Total amount spent by the user.', 'user-tag-plugin' ); ?></p>
            </td>
        </tr>
        <tr>
            <th><label for="last_order"><?php esc_html_e( 'Last Order', 'user-tag-plugin' ); ?></label></th>
            <td>
                <input type="text" name="last_order" id="last_order" value="" class="regular-text">
                <p class="description"><?php esc_html_e( 'Optional: Last order identifier (e.g., #123).', 'user-tag-plugin' ); ?></p>
            </td>
        </tr>
        <tr>
            <th><label for="order_count"><?php esc_html_e( 'Order Count', 'user-tag-plugin' ); ?></label></th>
            <td>
                <input type="number" name="order_count" id="order_count" value="" class="regular-text">
                <p class="description"><?php esc_html_e( 'Optional: Number of orders placed by the user.', 'user-tag-plugin' ); ?></p>
            </td>
        </tr>
    </table>
    <style type="text/css">
        #available-user-tags .user-tag-option {
            display: inline-block;
            padding: 5px 10px;
            background-color: #f0f0f0;
            border: 1px solid #ccc;
            border-radius: 3px;
            margin-right: 5px;
            margin-bottom: 5px;
            cursor: pointer;
        }
        #available-user-tags .user-tag-option.selected {
            background-color: #e0e0e0;
        }
        #selected-user-tags .selected-tag {
            display: inline-block;
            padding: 5px 10px;
            background-color: #e0e0e0;
            border: 1px solid #bbb;
            border-radius: 3px;
            margin-right: 5px;
            margin-bottom: 5px;
        }
        #selected-user-tags .remove-tag {
            display: inline-block;
            margin-left: 5px;
            cursor: pointer;
            color: #888;
        }
        #selected-user-tags .remove-tag:hover {
            color: #333;
        }
    </style>
    <script type="text/javascript">
        document.addEventListener('DOMContentLoaded', function() {
            const availableTagsContainer = document.getElementById('available-user-tags');
            const selectedTagsContainer = document.getElementById('selected-user-tags');
            const selectedTagIdsInput = document.getElementById('selected-user-tag-ids');
            const firstNameInput = document.getElementById('first_name');
            const selectedTagIds = [];

            if (availableTagsContainer && selectedTagsContainer && selectedTagIdsInput && document.getElementById('createuser')) {
                availableTagsContainer.addEventListener('click', function(event) {
                    if (event.target.classList.contains('user-tag-option')) {
                        const tagElement = event.target;
                        const tagId = tagElement.dataset.tagId;
                        const tagName = tagElement.textContent;

                        if (!selectedTagIds.includes(tagId)) {
                            selectedTagIds.push(tagId);
                            selectedTagIdsInput.value = selectedTagIds.join(',');

                            const selectedTag = document.createElement('span');
                            selectedTag.classList.add('selected-tag');
                            selectedTag.dataset.tagId = tagId;
                            selectedTag.textContent = tagName;

                            const removeTag = document.createElement('span');
                            removeTag.classList.add('remove-tag');
                            removeTag.textContent = ' ✕'; // You can use an actual cross icon here

                            removeTag.addEventListener('click', function() {
                                const index = selectedTagIds.indexOf(tagId);
                                if (index > -1) {
                                    selectedTagIds.splice(index, 1);
                                    selectedTagIdsInput.value = selectedTagIds.join(',');
                                    selectedTagsContainer.removeChild(selectedTag);
                                    tagElement.classList.remove('selected'); // Optional: remove visual selection from available tags
                                }
                            });

                            selectedTag.appendChild(removeTag);
                            selectedTagsContainer.appendChild(selectedTag);
                            tagElement.classList.add('selected'); // Optional: add visual selection to available tags
                        }
                    }
                });

                document.getElementById('createuser').addEventListener('submit', function(event) {
                    if (firstNameInput && firstNameInput.value.trim() === '') {
                        alert('First Name is a required field.');
                        event.preventDefault();
                    }
                });
            }
        });
    </script>
    <?php
}
add_action( 'user_new_form', 'my_add_user_custom_fields_new_user' );

/**
 * Save custom fields when a new user is created (updated for new tag selection - NO comma-separated tags).
 * Hook: user_register
 */
function my_save_user_custom_fields_new_user( $user_id ) {
    if ( isset( $_POST['total_spent'] ) ) {
        update_user_meta( $user_id, 'total_spent', sanitize_text_field( $_POST['total_spent'] ) );
    }
    if ( isset( $_POST['last_order'] ) ) {
        update_user_meta( $user_id, 'last_order', sanitize_text_field( $_POST['last_order'] ) );
    }
    if ( isset( $_POST['order_count'] ) ) {
        update_user_meta( $user_id, 'order_count', absint( $_POST['order_count'] ) );
    }
    if ( isset( $_POST['user_tags'] ) ) {
        $selected_tag_ids = array_map( 'absint', explode( ',', sanitize_text_field( $_POST['user_tags'] ) ) );
        wp_set_object_terms( $user_id, $selected_tag_ids, 'user_tag' );

        // Get the tag names based on the IDs to save as meta
        $tag_names = array();
        foreach ( $selected_tag_ids as $tag_id ) {
            $term = get_term_by( 'id', $tag_id, 'user_tag' );
            if ( $term && ! is_wp_error( $term ) ) {
                $tag_names[] = $term->name;
            }
        }

        // Serialize the array of tag names for saving as meta
        $serialized_tags = implode( ',', $tag_names ); // Or serialize( $tag_names );

        // Update the user meta for meta_tags with the tag names
        update_user_meta( $user_id, 'meta_tags', $serialized_tags );

    } else {
        wp_set_object_terms( $user_id, array(), 'user_tag' ); // Clear tags if none selected
        delete_user_meta( $user_id, 'meta_tags' ); // Clear meta_tags
    
    }
}
add_action( 'user_register', 'my_save_user_custom_fields_new_user' );


 //Add the dropdown
   
 function filter_users_by_tags_dropdown( $which ) {
    if ( 'top' === $which ) {
        ?>
        <div id="user-tag-filter-container" class="alignleft actions" style="display: inline-block; margin-left: 20px;">
            <label for="filter_user_tag" class="screen-reader-text"><?php _e( 'Filter by User Tags', 'your-text-domain' ); ?></label>
            <select name="filter_user_tag" id="filter_user_tag" style="visibility: hidden; width: 1px; height: 1px; overflow: hidden;position: absolute; left: -9999px;" style="margin-right: 10px;">
    <option value=""><?php _e( 'Filter by User Meta Tags...', 'your-text-domain' ); ?></option>';
    <?php
    $all_meta_tags = array();
    $users = get_users( array( 'fields' => 'ID' ) );
    foreach ( $users as $user_id ) {
        $meta_tags_string = get_user_meta( $user_id, 'meta_tags', true );
        if ( $meta_tags_string ) {
            $tags_array = explode( ',', $meta_tags_string );
            $all_meta_tags = array_merge( $all_meta_tags, array_map( 'trim', $tags_array ) ); // Trim whitespace
        }
    }
    $unique_meta_tags = array_unique( $all_meta_tags );
    sort( $unique_meta_tags );
    foreach ( $unique_meta_tags as $tag ) {
        $selected = ( isset( $_GET['filter_user_tag'] ) && $_GET['filter_user_tag'] === $tag ) ? ' selected="selected"' : '';
        echo '<option value="' . esc_attr( $tag ) . '"' . $selected . '>' . esc_html( $tag ) . '</option>';
    }
    ?>
</select>

            <?php submit_button( __( 'Filter', 'your-text-domain' ), 'button', 'filter_action', false , array( 'style' => 'visibility: hidden; position: absolute; left: -9999px;' )); ?>
        </div>
        <?php
    }
}

add_action( 'restrict_manage_users', 'filter_users_by_tags_dropdown' );
//Function helps querying the db for the dropdown query
function filter_users_by_meta_tags_query( $query ) {
    global $pagenow;

    if ( 'users.php' === $pagenow && isset( $_GET['filter_action'] ) && $_GET['filter_action'] === 'Filter' && isset( $_GET['filter_user_tag'] ) && ! empty( $_GET['filter_user_tag'] ) ) {
        $selected_tag = sanitize_text_field( $_GET['filter_user_tag'] );
        $query->set( 'meta_query', array(
            array(
                'key' => 'meta_tags', // Target the 'meta_tags' user meta key
                'value' => $selected_tag,
                'compare' => 'LIKE',
            ),
        ) );
    }
}
add_action( 'pre_get_users', 'filter_users_by_meta_tags_query' );

// Add Second Dropdown
function add_blank_user_tags_filter_html( $which ) {
    global $pagenow;
    if ( 'users.php' === $pagenow ) {
      
        ?>
        <label for="filter-by-user-tag" class="screen-reader-text"><?php _e( 'Filter users by tag', 'your-text-domain' ); ?></label>
        <select name="user_tag_filter" id="filter-by-user-tag" style="vertical-align: middle; margin-right: 10px;">
            <option value=""><?php _e( 'Filter by User Tags...', 'your-text-domain' ); ?></option>
            <?php
            $taxonomy = 'user_tag';
            $terms = get_terms( $taxonomy );

            if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
                foreach ( $terms as $term ) {
                    $selected = ( isset( $_GET['user_tag_filter'] ) && $_GET['user_tag_filter'] == $term->slug ) ? 'selected="selected"' : '';
                    echo '<option value="' . esc_attr( $term->slug ) . '" ' . $selected . '>' . esc_html( $term->name ) . '</option>';
                }
            }
            ?>
        </select>
        <?php
        // KEEP THIS LINE to generate your "Filter" button
        submit_button( __( 'Filter', 'your-text-domain' ), 'button', 'filter_action2', false );
        ?>
        <?php
    }
}
add_action( 'restrict_manage_users', 'add_blank_user_tags_filter_html' );


//Enquing Select2
function enqueue_select2_assets_for_users( $hook ) {
    if ( 'users.php' === $hook ) {
        $plugin_url = plugin_dir_url( __FILE__ );
        $plugin_version = get_plugin_data( __FILE__ )['Version']; // Make sure your plugin has a Version header

        wp_enqueue_style( 'select2', $plugin_url . 'select2/css/select2.min.css', array(), '4.1.0-rc.0' );
        wp_enqueue_script( 'select2', $plugin_url . 'select2/js/select2.min.js', array( 'jquery' ), '4.1.0-rc.0', true );
        wp_enqueue_script( 'user-tag-filter-script', $plugin_url . 'js/user-tag-filter.js', array( 'select2' ), $plugin_version, true );
        wp_localize_script( 'user-tag-filter-script', 'userTagFilter', array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'search_user_tags_nonce' ),
        ) );
    }
}
add_action( 'admin_enqueue_scripts', 'enqueue_select2_assets_for_users' );

//Makes the filter searchable
function search_user_tags_callback() {
    // Verify the security nonce
    check_ajax_referer( 'search_user_tags_nonce', 'nonce' );

    // Get the search term from the AJAX request
    $search_term = sanitize_text_field( $_GET['q'] );
    $taxonomy = 'user_tag';

    // Query user tags that match the search term
    $terms = get_terms( array(
        'taxonomy' => $taxonomy,
        'name__like' => $search_term,
        'hide_empty' => false, // Or true, depending on your needs
        'number' => 10, // Limit the number of results
    ) );

    $results = array();
    if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
        foreach ( $terms as $term ) {
            $results[] = array(
                'id' => $term->term_id, // You can also use 'slug' here if preferred
                'slug' => $term->slug,
                'name' => $term->name,
            );
        }
    }

    // Send the JSON response back to Select2
    wp_send_json( $results );

    // Always remember to wp_die() at the end of an AJAX callback
    wp_die();
}
add_action( 'wp_ajax_search_user_tags', 'search_user_tags_callback' );


?>