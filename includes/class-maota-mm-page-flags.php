<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Adds an "Include in llms.txt" checkbox to Pages — as a meta box on the
 * edit screen and as a toggleable column in the Pages list table — so
 * site owners can curate which pages appear in the virtual llms.txt.
 */
class Maota_MM_Page_Flags {

	const META_KEY  = '_maota_mm_include_in_llms';
	const AJAX_ACTION = 'maota_mm_toggle_llms_include';
	const NONCE_ACTION = 'maota_mm_toggle_llms';

	public function __construct() {
		// Pages don't support excerpts by default in WP core (only Posts do),
		// but get_the_excerpt() is what feeds the llms.txt Key Pages summary —
		// enable it so editors can set one manually via Preferences > Panels.
		add_action( 'init', array( $this, 'add_excerpt_support' ) );

		add_action( 'add_meta_boxes', array( $this, 'register_meta_box' ) );
		add_action( 'save_post_page', array( $this, 'save_meta_box' ), 10, 2 );

		add_filter( 'manage_page_posts_columns', array( $this, 'add_column' ) );
		add_action( 'manage_page_posts_custom_column', array( $this, 'render_column' ), 10, 2 );

		add_action( 'admin_print_footer_scripts-edit.php', array( $this, 'print_column_script' ) );
		add_action( 'wp_ajax_' . self::AJAX_ACTION, array( $this, 'handle_ajax_toggle' ) );
	}

	public static function is_included( $post_id ) {
		return '1' === get_post_meta( $post_id, self::META_KEY, true );
	}

	public function add_excerpt_support() {
		add_post_type_support( 'page', 'excerpt' );
	}

	/* --- Meta box on the Page edit screen --- */

	public function register_meta_box() {
		add_meta_box(
			'maota_mm_llms_include',
			__( 'Maota Metamarkup', 'maota-metamarkup' ),
			array( $this, 'render_meta_box' ),
			'page',
			'side',
			'default'
		);
	}

	public function render_meta_box( $post ) {
		wp_nonce_field( 'maota_mm_save_llms_include', 'maota_mm_llms_include_nonce' );
		$checked = self::is_included( $post->ID );
		?>
		<label>
			<input type="checkbox" name="maota_mm_include_in_llms" value="1" <?php checked( $checked ); ?> />
			<?php esc_html_e( 'Include in llms.txt', 'maota-metamarkup' ); ?>
		</label>
		<p class="description"><?php esc_html_e( 'Lists this page under "Key Pages" in the virtual llms.txt file read by AI agents.', 'maota-metamarkup' ); ?></p>
		<?php
	}

	public function save_meta_box( $post_id, $post ) {
		if ( ! isset( $_POST['maota_mm_llms_include_nonce'] ) || ! wp_verify_nonce( wp_unslash( $_POST['maota_mm_llms_include_nonce'] ), 'maota_mm_save_llms_include' ) ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! current_user_can( 'edit_page', $post_id ) ) {
			return;
		}

		update_post_meta( $post_id, self::META_KEY, isset( $_POST['maota_mm_include_in_llms'] ) ? '1' : '0' );
	}

	/* --- Pages list table column --- */

	public function add_column( $columns ) {
		$columns['maota_mm_llms'] = __( 'llms.txt', 'maota-metamarkup' );
		return $columns;
	}

	public function render_column( $column, $post_id ) {
		if ( 'maota_mm_llms' !== $column ) {
			return;
		}
		printf(
			'<input type="checkbox" class="maota-mm-llms-toggle" data-post-id="%1$d" %2$s />',
			(int) $post_id,
			checked( self::is_included( $post_id ), true, false )
		);
	}

	public function print_column_script() {
		$screen = get_current_screen();
		if ( ! $screen || 'edit-page' !== $screen->id ) {
			return;
		}
		$nonce   = wp_create_nonce( self::NONCE_ACTION );
		$action  = self::AJAX_ACTION;
		?>
		<script>
		document.addEventListener( 'change', function ( event ) {
			if ( ! event.target.classList.contains( 'maota-mm-llms-toggle' ) ) {
				return;
			}
			var checkbox = event.target;
			var data = new FormData();
			data.append( 'action', <?php echo wp_json_encode( $action ); ?> );
			data.append( 'nonce', <?php echo wp_json_encode( $nonce ); ?> );
			data.append( 'post_id', checkbox.dataset.postId );
			data.append( 'include', checkbox.checked ? '1' : '0' );
			checkbox.disabled = true;
			fetch( ajaxurl, { method: 'POST', credentials: 'same-origin', body: data } )
				.finally( function () { checkbox.disabled = false; } );
		} );
		</script>
		<?php
	}

	public function handle_ajax_toggle() {
		check_ajax_referer( self::NONCE_ACTION, 'nonce' );

		$post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
		if ( ! $post_id || ! current_user_can( 'edit_page', $post_id ) ) {
			wp_send_json_error( null, 403 );
		}

		$include = ! empty( $_POST['include'] ) ? '1' : '0';
		update_post_meta( $post_id, self::META_KEY, $include );

		wp_send_json_success( array( 'included' => $include ) );
	}
}
