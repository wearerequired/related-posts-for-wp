<?php

if ( !defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

class SRP_Hook_Link_Related_Screen extends SRP_Hook {
	protected $tag = 'admin_menu';

	public function run() {
		$this->check_if_allowed();

		$this->handle_create_link();
		$this->handle_bulk_link();

		// Add Page
		add_submenu_page( null, 'Link_Related_Screen', 'Link_Related_Screen', 'edit_posts', 'srp_link_related', array( $this, 'content' ) );
	}

	/**
	 * Check if the current user is allowed to create an existing link for this connection
	 */
	private function check_if_allowed() {
		if ( !current_user_can( 'edit_posts' ) ) {
			wp_die( 'There was a problem loading this page, you may not have the necessary permissions.' );
		}
	}

	/**
	 * Handle the create link action
	 */
	private function handle_create_link() {

		// Check if link is chosen
		if ( isset( $_GET['sp_post_link'] ) ) {

			// Check if all vars are set
			if ( !isset( $_GET['sp_pt_link'] ) || !isset( $_GET['sp_parent'] ) || !isset( $_GET['sp_post_link'] ) ) {
				return;
			}

			// Check if user is allowed to do this
			if ( !current_user_can( SP_Cap_Manager::get_capability( $_GET['sp_post_link'] ) ) ) {
				return;
			}

			// Get parent
			$parent = SP_Parent_Param::get_current_parent( $_GET['sp_parent'] );

			// Create link
			$post_link_manager = new SP_Post_Link_Manager();

			// Check what way we're linking
			if ( 1 == $parent[2] ) {
				// Create a 'backwards' child < parent link
				$post_link_manager->add( $_GET['sp_pt_link'], $_GET['sp_post_link'], $parent[0] );
			} else {
				// Create a 'normal' parent > child link
				$post_link_manager->add( $_GET['sp_pt_link'], $parent[0], $_GET['sp_post_link'] );
			}

			// Send back
			$redirect_url = get_admin_url() . "post.php?post={$parent[0]}&action=edit";

			// Check if parent as a ptl
			if ( isset( $parent[1] ) && $parent[1] != '' ) {
				$redirect_url .= '&sp_pt_link=' . $parent[1];
			}

			// Check if there are any parents left
			$sp_parent_rest = SP_Parent_Param::strip_sp_parent_parent( $_GET['sp_parent'] );
			if ( $sp_parent_rest != '' ) {
				$redirect_url .= '&sp_parent=' . $sp_parent_rest;
			}

			wp_redirect( $redirect_url );
			exit;
		}

	}

	/**
	 * Handle the bulk creation of links
	 */
	private function handle_bulk_link() {

		if ( isset( $_POST['srp_view'] ) ) {

			// Get parent
			$parent = SP_Parent_Param::get_current_parent( $_GET['sp_parent'] );

			// Check if user is allowed to do this
			if ( !current_user_can( SP_Cap_Manager::get_capability( $parent ) ) ) {
				return;
			}

			// Post Link Manager
			$post_link_manager = new SP_Post_Link_Manager();

			if ( count( $_POST['srp_view'] ) > 0 ) {
				foreach ( $_POST['srp_view'] as $bulk_post ) {

					// Check what way we're linking
					if ( 1 == $parent[2] ) {
						// Create a 'backwards' child < parent link
						$post_link_manager->add( $_GET['sp_pt_link'], $bulk_post, $parent[0] );
					} else {
						// Create a 'normal' parent > child link
						$post_link_manager->add( $_GET['sp_pt_link'], $parent[0], $bulk_post );
					}

				}
			}

			// Send back
			$redirect_url = get_admin_url() . "post.php?post={$parent[0]}&action=edit";

			// Check if parent as a ptl
			if ( isset( $parent[1] ) && $parent[1] != '' ) {
				$redirect_url .= '&sp_pt_link=' . $parent[1];
			}

			// Check if there are any parents left
			$sp_parent_rest = SP_Parent_Param::strip_sp_parent_parent( $_GET['sp_parent'] );
			if ( $sp_parent_rest != '' ) {
				$redirect_url .= '&sp_parent=' . $sp_parent_rest;
			}

			wp_redirect( $redirect_url );
			exit;

		}

	}

	/**
	 * The screen content
	 */
	public function content() {

		if ( !isset( $_GET['srp_parent'] ) ) {
			wp_die( "Can't load page, no parent set. Please contact support and provide them this message" );
		}

		// Parent
		$parent = $_GET['srp_parent'];

		// Setup cancel URL
		$cancel_url = get_admin_url() . "post.php?post={$parent}&action=edit";

		// Catch search string
		$search = null;
		if ( isset( $_POST['s'] ) && $_POST['s'] != '' ) {
			$search = $_POST['s'];
		}

		?>
		<div class="wrap">
			<h2>
				<?php _e( 'Posts', 'simple-related-posts' ); ?>
				<a href="<?php echo $cancel_url; ?>" class="add-new-h2"><?php _e( 'Cancel linking', 'simple-related-posts' ); ?></a>
			</h2>

			<form id="sp-list-table-form" method="post">
				<input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
				<?php
				// Create the link table
				$list_table = new SRP_Link_Related_Table();

				// Set the search
				$list_table->set_search( $search );

				// Load the items
				$list_table->prepare_items();

				// Add the search box
				$list_table->search_box( __( 'Search', 'post-connector' ), 'sp-search' );

				// Display the table
				$list_table->display();
				?>
			</form>
		</div>

	<?php
	}
}