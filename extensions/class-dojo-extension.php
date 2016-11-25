<?php
/**
 * dojo extension base class
 */

if ( ! defined( 'ABSPATH' ) ) { die(); }

class Dojo_Extension extends Dojo_WP_base {
	private $title;
	private $version;

	protected function __construct( $title, $version = '' ) {
		$this->title = $title;
		$this->version = $version;
	}

	/**
	 * Get title of extension
	 *
	 * @return string
	 */
	public function title() {
		return $this->title;
	}

	/**
	 * Get version of extension
	 *
	 * @return string
	 */
	public function version() {
		return $this->version;
	}

	/**
	 * Get installer instance for this extension
	 *
	 * @return Dojo_Installer_Base derived class or null if installer doesn't exist
	 */
	public function installer() {
		$class = get_class( $this ) . '_Installer';
		if ( class_exists( $class ) ) {
			return $this->get_instance( $class );
		}
		return null;
	}

	/**
	 * Get model instance for this extension
	 *
	 * @return Dojo_Model_Base derived class or null if installer doesn't exist
	 */
	public function model() {
		$class = get_class( $this ) . '_Model';
		if ( class_exists( $class ) ) {
			return $this->get_instance( $class );
		}
		return null;
	}

	/**
	 * Log an event to the event log. If user_id not set will default to current user.
	 *
	 * @param string $event
	 * @param int $user_id
	 *
	 * @return void
	 */
	public function log_event( $event, $user_id = null ) {
		if ( null == $user_id ) {
			$user = wp_get_current_user();
			if ( $user ) {
				$user_id = $user->ID;
			}
		}

		Dojo::instance()->log_event( $event, $user_id, get_class( $this ) );
	}

	/**
	 * Gets the path of the derived extension with trailing slash
	 * @param string $file Optional relative path file to append
	 *
	 * @return string
	 */
	protected function path( $file = '' ) {
		$class = get_class( $this );
		return Dojo_Loader::get_class_path( $class ) . $file;
	}

	/**
	 * Gets the url of the derived extension with trailing slash and optionally appended file
	 * @param string $file Optional relative path file to append
	 *
	 * @return string
	 */
	protected function url( $file = '' ) {
		$class = get_class( $this );
		return Dojo_Loader::get_class_url( $class ) . $file;
	}

	/**
	 * Registers custom pages for this extension. Pages passed in as name => slug pairs where name is the name of
	 * the registration and slug is the root level url slug. Two methods have to be implemented matching the name:
	 * custom_page_<name>( $path ) should render the content of the page and return true if path is valid.
	 * custom_page_title<name>( $path ) should return the title of the page.
	 * The path passed into the callbacks is the url path following the root slug. The slug is not included in path.
	 *
	 * @param array( name => slug ) $pages
	 *
	 * @return void
	 */
	protected function register_custom_pages( $pages ) {
		$dojo = Dojo::instance();
		foreach ( $pages as $name => $slug ) {
			$dojo->register_custom_page(
				$slug,
				array ( $this, 'custom_page_' . $name ),
				array ( $this, 'custom_page_title_' . $name )
			);
		}
	}

	/**
	 * Enqueues ajax urls for use in client side javascript. This may be called any time before wp_footer action.
	 * Example server side from membership extension:
	 *   $this->enqueue_ajax( 'my_method' );
	 *
	 * Example in javascript:
	 *   $.post( dojo.ajax( 'membership', 'my_method' ), ... );
	 *
	 * @param array | string $methods
	 */
	protected function enqueue_ajax( $methods ) {
		if ( ! is_array( $methods ) ) {
			$methods = array( $methods );
		}
		foreach ( $methods as $method ) {
			$url = $this->ajax( $method );
			$target = strtolower( substr( get_class( $this ), 5 ) );
			Dojo::instance()->enqueue_ajax( $target . '::' . $method, $url );
		}
	}

	/**
	 * Enqueues a parameter for use in client side javascript as dojo.param(name)
	 *
	 * @param $name
	 * @param $value
	 */
	protected function enqueue_param( $name, $value ) {
		Dojo::instance()->enqueue_param( $name, $value );
	}

	/**
	 * Enqueues the javascript for this extension to the footer.
	 * If in admin screens will enqueue both regular and admin js if available.
	 * This is automatically called when any view is rendered on this extension.
	 */
	protected function enqueue_js() {
		static $js_enqueued = false;

		if ( ! $js_enqueued ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				$file_extension = '.js';
			} else {
				$file_extension = '.min.js';
			}
			$file = $this->path( 'js/dist/extension' . $file_extension );
			$file_admin = $this->path( 'js/dist/extension-admin' .$file_extension );
			$url = $this->url( 'js/dist/extension' . $file_extension );
			$url_admin = $this->url( 'js/dist/extension-admin' .$file_extension );

			if ( file_exists( $file ) ) {
				wp_enqueue_script(
					get_class( $this ),
					$url,
					$this->get_js_dependencies(),
					null,
					true
				);
			}
			if ( is_admin() && file_exists( $file_admin ) ) {
				wp_enqueue_script(
					get_class( $this ) . '-admin',
					$url_admin,
					array(),
					null,
					true
				);
			}
			$js_enqueued = true;
		}
	}

	protected function get_js_dependencies() {
		return array( 'jquery' );
	}

	/**
	 * Renders a view from the views subfolder of the derived extension.
	 * Rendering any view will cause the javascript for this extension to be enqueued in the footer.
	 *
	 * @param string $view Name of the view to render
	 *
	 * @return string Rendered content
	 */
	protected function render( $view ) {
		$this->enqueue_js();

		// add settings to view context
		$settings = Dojo_Settings::instance();
		$path = $this->path() . 'views/';
		ob_start();
		include $path . $view . '.php';
		return ob_get_clean();
	}

	/**
	 * Renders a settings option for a basic checkbox
	 *
	 * @param string $id option id
	 * @param string $label Text label to the right of the checkbox
	 *
	 * @return void
	 */
	protected function render_option_checkbox( $id, $label ) {
		?>
		<p>
			<label for="<?php echo esc_attr( $id ) ?>">
				<input type="checkbox" id="<?php echo esc_attr( $id ) ?>" name="dojo_options[<?php echo esc_attr( $id ) ?>]" value="1" <?php checked( $this->get_setting( $id ), '1' ) ?> />
				<?php echo $label ?>
			</label>
		</p>
		<?php
	}

	/**
	 * Renders a settings option for a regular text field
	 *
	 * @param string $id option id
	 * @param string $label
	 *
	 * @return void
	 */
	protected function render_option_regular_text( $id, $label = '' ) {
		?>
		<?php if ( '' != $label ) : ?>
			<?php echo $label ?><br />
		<?php endif; ?>
		<input type="text" id="<?php echo esc_attr( $id ) ?>" name="dojo_options[<?php echo esc_attr( $id ) ?>]" class="regular-text" value="<?php echo esc_attr( $this->get_setting( $id ) ) ?>" />
		<?php
	}
}
 
