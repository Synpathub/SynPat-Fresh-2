<?php
/**
 * Settings Page Class
 *
 * Handles plugin settings using WordPress Settings API.
 *
 * @package SynPatPlatform
 */

namespace SynPat\Admin;

/**
 * Settings Class
 */
class Settings {

	/**
	 * Option group name
	 *
	 * @var string
	 */
	private $option_group = 'synpat_settings';

	/**
	 * Settings page slug
	 *
	 * @var string
	 */
	private $page_slug = 'synpat-settings';

	/**
	 * Initialize settings.
	 */
	public function __construct() {
		add_action( 'admin_init', array( $this, 'register_settings' ) );
	}

	/**
	 * Register all settings.
	 */
	public function register_settings() {
		// General Settings Section
		add_settings_section(
			'synpat_general_section',
			__( 'General Settings', 'synpat-platform' ),
			array( $this, 'render_general_section' ),
			$this->page_slug
		);

		// Patent Settings Section
		add_settings_section(
			'synpat_patent_section',
			__( 'Patent Settings', 'synpat-platform' ),
			array( $this, 'render_patent_section' ),
			$this->page_slug
		);

		// API Settings Section
		add_settings_section(
			'synpat_api_section',
			__( 'API Settings', 'synpat-platform' ),
			array( $this, 'render_api_section' ),
			$this->page_slug
		);

		// Email Settings Section
		add_settings_section(
			'synpat_email_section',
			__( 'Email Settings', 'synpat-platform' ),
			array( $this, 'render_email_section' ),
			$this->page_slug
		);

		// Register individual settings
		$this->register_general_settings();
		$this->register_patent_settings();
		$this->register_api_settings();
		$this->register_email_settings();
	}

	/**
	 * Register general settings.
	 */
	private function register_general_settings() {
		register_setting( $this->option_group, 'synpat_enable_logging', array(
			'type'              => 'boolean',
			'sanitize_callback' => array( $this, 'sanitize_checkbox' ),
			'default'           => true,
		) );

		add_settings_field(
			'synpat_enable_logging',
			__( 'Enable Activity Logging', 'synpat-platform' ),
			array( $this, 'render_checkbox_field' ),
			$this->page_slug,
			'synpat_general_section',
			array(
				'label_for'   => 'synpat_enable_logging',
				'description' => __( 'Log all platform activities for audit purposes.', 'synpat-platform' ),
			)
		);

		register_setting( $this->option_group, 'synpat_enable_cache', array(
			'type'              => 'boolean',
			'sanitize_callback' => array( $this, 'sanitize_checkbox' ),
			'default'           => true,
		) );

		add_settings_field(
			'synpat_enable_cache',
			__( 'Enable Caching', 'synpat-platform' ),
			array( $this, 'render_checkbox_field' ),
			$this->page_slug,
			'synpat_general_section',
			array(
				'label_for'   => 'synpat_enable_cache',
				'description' => __( 'Cache frequently accessed data to improve performance.', 'synpat-platform' ),
			)
		);

		register_setting( $this->option_group, 'synpat_items_per_page', array(
			'type'              => 'integer',
			'sanitize_callback' => array( $this, 'sanitize_number' ),
			'default'           => 20,
		) );

		add_settings_field(
			'synpat_items_per_page',
			__( 'Items Per Page', 'synpat-platform' ),
			array( $this, 'render_number_field' ),
			$this->page_slug,
			'synpat_general_section',
			array(
				'label_for'   => 'synpat_items_per_page',
				'description' => __( 'Number of items to display per page in lists.', 'synpat-platform' ),
				'min'         => 10,
				'max'         => 100,
			)
		);
	}

	/**
	 * Register patent settings.
	 */
	private function register_patent_settings() {
		register_setting( $this->option_group, 'synpat_default_patent_status', array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => 'pending',
		) );

		add_settings_field(
			'synpat_default_patent_status',
			__( 'Default Patent Status', 'synpat-platform' ),
			array( $this, 'render_select_field' ),
			$this->page_slug,
			'synpat_patent_section',
			array(
				'label_for'   => 'synpat_default_patent_status',
				'description' => __( 'Default status for new patents.', 'synpat-platform' ),
				'options'     => array(
					'pending'  => __( 'Pending', 'synpat-platform' ),
					'approved' => __( 'Approved', 'synpat-platform' ),
					'active'   => __( 'Active', 'synpat-platform' ),
					'expired'  => __( 'Expired', 'synpat-platform' ),
				),
			)
		);

		register_setting( $this->option_group, 'synpat_auto_track_renewal', array(
			'type'              => 'boolean',
			'sanitize_callback' => array( $this, 'sanitize_checkbox' ),
			'default'           => true,
		) );

		add_settings_field(
			'synpat_auto_track_renewal',
			__( 'Auto-Track Renewal Dates', 'synpat-platform' ),
			array( $this, 'render_checkbox_field' ),
			$this->page_slug,
			'synpat_patent_section',
			array(
				'label_for'   => 'synpat_auto_track_renewal',
				'description' => __( 'Automatically track and notify about patent renewal dates.', 'synpat-platform' ),
			)
		);
	}

	/**
	 * Register API settings.
	 */
	private function register_api_settings() {
		register_setting( $this->option_group, 'synpat_enable_rest_api', array(
			'type'              => 'boolean',
			'sanitize_callback' => array( $this, 'sanitize_checkbox' ),
			'default'           => false,
		) );

		add_settings_field(
			'synpat_enable_rest_api',
			__( 'Enable REST API', 'synpat-platform' ),
			array( $this, 'render_checkbox_field' ),
			$this->page_slug,
			'synpat_api_section',
			array(
				'label_for'   => 'synpat_enable_rest_api',
				'description' => __( 'Enable REST API endpoints for external integrations.', 'synpat-platform' ),
			)
		);

		register_setting( $this->option_group, 'synpat_api_key', array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => '',
		) );

		add_settings_field(
			'synpat_api_key',
			__( 'API Key', 'synpat-platform' ),
			array( $this, 'render_text_field' ),
			$this->page_slug,
			'synpat_api_section',
			array(
				'label_for'   => 'synpat_api_key',
				'description' => __( 'API key for external patent databases (e.g., USPTO, EPO).', 'synpat-platform' ),
				'type'        => 'password',
			)
		);
	}

	/**
	 * Register email settings.
	 */
	private function register_email_settings() {
		register_setting( $this->option_group, 'synpat_enable_email_notifications', array(
			'type'              => 'boolean',
			'sanitize_callback' => array( $this, 'sanitize_checkbox' ),
			'default'           => true,
		) );

		add_settings_field(
			'synpat_enable_email_notifications',
			__( 'Enable Email Notifications', 'synpat-platform' ),
			array( $this, 'render_checkbox_field' ),
			$this->page_slug,
			'synpat_email_section',
			array(
				'label_for'   => 'synpat_enable_email_notifications',
				'description' => __( 'Send email notifications for important events.', 'synpat-platform' ),
			)
		);

		register_setting( $this->option_group, 'synpat_notification_email', array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_email',
			'default'           => get_option( 'admin_email' ),
		) );

		add_settings_field(
			'synpat_notification_email',
			__( 'Notification Email', 'synpat-platform' ),
			array( $this, 'render_text_field' ),
			$this->page_slug,
			'synpat_email_section',
			array(
				'label_for'   => 'synpat_notification_email',
				'description' => __( 'Email address to receive notifications.', 'synpat-platform' ),
				'type'        => 'email',
			)
		);
	}

	/**
	 * Render the settings page.
	 */
	public function render() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'synpat-platform' ) );
		}

		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

			<?php settings_errors(); ?>

			<form method="post" action="options.php">
				<?php
				settings_fields( $this->option_group );
				do_settings_sections( $this->page_slug );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Render general section description.
	 */
	public function render_general_section() {
		echo '<p>' . esc_html__( 'Configure general platform settings.', 'synpat-platform' ) . '</p>';
	}

	/**
	 * Render patent section description.
	 */
	public function render_patent_section() {
		echo '<p>' . esc_html__( 'Configure patent-specific settings.', 'synpat-platform' ) . '</p>';
	}

	/**
	 * Render API section description.
	 */
	public function render_api_section() {
		echo '<p>' . esc_html__( 'Configure API and integration settings.', 'synpat-platform' ) . '</p>';
	}

	/**
	 * Render email section description.
	 */
	public function render_email_section() {
		echo '<p>' . esc_html__( 'Configure email notification settings.', 'synpat-platform' ) . '</p>';
	}

	/**
	 * Render checkbox field.
	 *
	 * @param array $args Field arguments.
	 */
	public function render_checkbox_field( $args ) {
		$option = get_option( $args['label_for'], false );
		?>
		<label>
			<input type="checkbox" 
				   id="<?php echo esc_attr( $args['label_for'] ); ?>" 
				   name="<?php echo esc_attr( $args['label_for'] ); ?>" 
				   value="1" 
				   <?php checked( $option, true ); ?> />
			<?php if ( ! empty( $args['description'] ) ) : ?>
				<span class="description"><?php echo esc_html( $args['description'] ); ?></span>
			<?php endif; ?>
		</label>
		<?php
	}

	/**
	 * Render text field.
	 *
	 * @param array $args Field arguments.
	 */
	public function render_text_field( $args ) {
		$option = get_option( $args['label_for'], '' );
		$type   = isset( $args['type'] ) ? $args['type'] : 'text';
		?>
		<input type="<?php echo esc_attr( $type ); ?>" 
			   id="<?php echo esc_attr( $args['label_for'] ); ?>" 
			   name="<?php echo esc_attr( $args['label_for'] ); ?>" 
			   value="<?php echo esc_attr( $option ); ?>" 
			   class="regular-text" />
		<?php if ( ! empty( $args['description'] ) ) : ?>
			<p class="description"><?php echo esc_html( $args['description'] ); ?></p>
		<?php endif; ?>
		<?php
	}

	/**
	 * Render number field.
	 *
	 * @param array $args Field arguments.
	 */
	public function render_number_field( $args ) {
		$option = get_option( $args['label_for'], 20 );
		$min    = isset( $args['min'] ) ? $args['min'] : 1;
		$max    = isset( $args['max'] ) ? $args['max'] : 100;
		?>
		<input type="number" 
			   id="<?php echo esc_attr( $args['label_for'] ); ?>" 
			   name="<?php echo esc_attr( $args['label_for'] ); ?>" 
			   value="<?php echo esc_attr( $option ); ?>" 
			   min="<?php echo esc_attr( $min ); ?>" 
			   max="<?php echo esc_attr( $max ); ?>" 
			   class="small-text" />
		<?php if ( ! empty( $args['description'] ) ) : ?>
			<p class="description"><?php echo esc_html( $args['description'] ); ?></p>
		<?php endif; ?>
		<?php
	}

	/**
	 * Render select field.
	 *
	 * @param array $args Field arguments.
	 */
	public function render_select_field( $args ) {
		$option = get_option( $args['label_for'], '' );
		?>
		<select id="<?php echo esc_attr( $args['label_for'] ); ?>" 
				name="<?php echo esc_attr( $args['label_for'] ); ?>">
			<?php foreach ( $args['options'] as $value => $label ) : ?>
				<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $option, $value ); ?>>
					<?php echo esc_html( $label ); ?>
				</option>
			<?php endforeach; ?>
		</select>
		<?php if ( ! empty( $args['description'] ) ) : ?>
			<p class="description"><?php echo esc_html( $args['description'] ); ?></p>
		<?php endif; ?>
		<?php
	}

	/**
	 * Sanitize checkbox value.
	 *
	 * @param mixed $value Input value.
	 * @return bool
	 */
	public function sanitize_checkbox( $value ) {
		return ! empty( $value ) ? true : false;
	}

	/**
	 * Sanitize number value.
	 *
	 * @param mixed $value Input value.
	 * @return int
	 */
	public function sanitize_number( $value ) {
		$number = absint( $value );
		return max( 1, min( 100, $number ) );
	}
}
