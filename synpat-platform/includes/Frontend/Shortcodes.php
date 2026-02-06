<?php
/**
 * Shortcodes Class
 *
 * Handles registration and rendering of all SynPat shortcodes.
 *
 * @package SynPat
 * @subpackage Frontend
 * @since 1.0.0
 */

namespace SynPat\Frontend;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Shortcodes Class
 *
 * Registers and handles all plugin shortcodes.
 *
 * @since 1.0.0
 */
class Shortcodes {

	/**
	 * Template loader instance
	 *
	 * @var Template_Loader
	 */
	private $template_loader;

	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 *
	 * @param Template_Loader $template_loader Template loader instance.
	 */
	public function __construct( $template_loader = null ) {
		$this->template_loader = $template_loader;
	}

	/**
	 * Initialize shortcodes
	 *
	 * Registers all shortcodes with WordPress.
	 *
	 * @since 1.0.0
	 */
	public function init() {
		add_shortcode( 'synpat_portfolio_list', array( $this, 'portfolio_list' ) );
		add_shortcode( 'synpat_portfolio', array( $this, 'single_portfolio' ) );
		add_shortcode( 'synpat_patent_search', array( $this, 'patent_search' ) );
		add_shortcode( 'synpat_customer_dashboard', array( $this, 'customer_dashboard' ) );
	}

	/**
	 * Portfolio list shortcode handler
	 *
	 * Displays a grid or list of portfolios.
	 *
	 * @since 1.0.0
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string Shortcode output.
	 */
	public function portfolio_list( $atts ) {
		// Parse shortcode attributes.
		$atts = shortcode_atts(
			array(
				'limit'      => 10,
				'columns'    => 3,
				'layout'     => 'grid',
				'orderby'    => 'date',
				'order'      => 'DESC',
				'category'   => '',
				'show_filter' => 'yes',
			),
			$atts,
			'synpat_portfolio_list'
		);

		// Sanitize attributes.
		$limit      = absint( $atts['limit'] );
		$columns    = absint( $atts['columns'] );
		$layout     = sanitize_key( $atts['layout'] );
		$orderby    = sanitize_key( $atts['orderby'] );
		$order      = sanitize_key( $atts['order'] );
		$category   = sanitize_text_field( $atts['category'] );
		$show_filter = 'yes' === $atts['show_filter'];

		// Start output buffering.
		ob_start();

		// Query portfolios.
		$args = array(
			'post_type'      => 'synpat_portfolio',
			'posts_per_page' => $limit,
			'orderby'        => $orderby,
			'order'          => $order,
			'post_status'    => 'publish',
		);

		// Add category filter if specified.
		if ( ! empty( $category ) ) {
			$args['tax_query'] = array(
				array(
					'taxonomy' => 'portfolio_category',
					'field'    => 'slug',
					'terms'    => $category,
				),
			);
		}

		$portfolios = new \WP_Query( $args );

		// Prepare template data.
		$data = array(
			'portfolios'  => $portfolios,
			'columns'     => $columns,
			'layout'      => $layout,
			'show_filter' => $show_filter,
		);

		// Load template.
		if ( $this->template_loader ) {
			$this->template_loader->get_template_part( 'portfolio/list', null, $data );
		} else {
			// Fallback if no template loader.
			if ( $portfolios->have_posts() ) {
				echo '<div class="synpat-portfolio-list synpat-layout-' . esc_attr( $layout ) . ' synpat-columns-' . esc_attr( $columns ) . '">';
				
				while ( $portfolios->have_posts() ) {
					$portfolios->the_post();
					echo '<div class="synpat-portfolio-item">';
					echo '<h3>' . esc_html( get_the_title() ) . '</h3>';
					echo '<div class="synpat-portfolio-excerpt">' . wp_kses_post( get_the_excerpt() ) . '</div>';
					echo '<a href="' . esc_url( get_permalink() ) . '" class="synpat-portfolio-link">' . esc_html__( 'View Details', 'synpat' ) . '</a>';
					echo '</div>';
				}
				
				echo '</div>';
				wp_reset_postdata();
			} else {
				echo '<p>' . esc_html__( 'No portfolios found.', 'synpat' ) . '</p>';
			}
		}

		return ob_get_clean();
	}

	/**
	 * Single portfolio shortcode handler
	 *
	 * Displays a single portfolio by ID.
	 *
	 * @since 1.0.0
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string Shortcode output.
	 */
	public function single_portfolio( $atts ) {
		// Parse shortcode attributes.
		$atts = shortcode_atts(
			array(
				'id'   => 0,
				'show_title' => 'yes',
				'show_meta'  => 'yes',
			),
			$atts,
			'synpat_portfolio'
		);

		// Sanitize attributes.
		$portfolio_id = absint( $atts['id'] );
		$show_title   = 'yes' === $atts['show_title'];
		$show_meta    = 'yes' === $atts['show_meta'];

		// Validate portfolio ID.
		if ( empty( $portfolio_id ) ) {
			return '<p>' . esc_html__( 'Please provide a valid portfolio ID.', 'synpat' ) . '</p>';
		}

		// Get portfolio post.
		$portfolio = get_post( $portfolio_id );

		// Check if portfolio exists and is published.
		if ( ! $portfolio || 'synpat_portfolio' !== $portfolio->post_type || 'publish' !== $portfolio->post_status ) {
			return '<p>' . esc_html__( 'Portfolio not found.', 'synpat' ) . '</p>';
		}

		// Start output buffering.
		ob_start();

		// Prepare template data.
		$data = array(
			'portfolio'  => $portfolio,
			'show_title' => $show_title,
			'show_meta'  => $show_meta,
		);

		// Load template.
		if ( $this->template_loader ) {
			$this->template_loader->get_template_part( 'portfolio/single', null, $data );
		} else {
			// Fallback if no template loader.
			echo '<div class="synpat-single-portfolio" id="portfolio-' . esc_attr( $portfolio_id ) . '">';
			
			if ( $show_title ) {
				echo '<h2>' . esc_html( $portfolio->post_title ) . '</h2>';
			}
			
			echo '<div class="synpat-portfolio-content">' . wp_kses_post( apply_filters( 'the_content', $portfolio->post_content ) ) . '</div>';
			
			echo '</div>';
		}

		return ob_get_clean();
	}

	/**
	 * Patent search form shortcode handler
	 *
	 * Displays a patent search form.
	 *
	 * @since 1.0.0
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string Shortcode output.
	 */
	public function patent_search( $atts ) {
		// Parse shortcode attributes.
		$atts = shortcode_atts(
			array(
				'placeholder' => __( 'Search patents...', 'synpat' ),
				'button_text' => __( 'Search', 'synpat' ),
				'show_filters' => 'yes',
			),
			$atts,
			'synpat_patent_search'
		);

		// Sanitize attributes.
		$placeholder  = sanitize_text_field( $atts['placeholder'] );
		$button_text  = sanitize_text_field( $atts['button_text'] );
		$show_filters = 'yes' === $atts['show_filters'];

		// Start output buffering.
		ob_start();

		// Prepare template data.
		$data = array(
			'placeholder'  => $placeholder,
			'button_text'  => $button_text,
			'show_filters' => $show_filters,
		);

		// Load template.
		if ( $this->template_loader ) {
			$this->template_loader->get_template_part( 'patent/search-form', null, $data );
		} else {
			// Fallback if no template loader.
			?>
			<div class="synpat-patent-search">
				<form method="get" class="synpat-search-form" role="search">
					<div class="synpat-search-field">
						<input 
							type="search" 
							name="s" 
							class="synpat-search-input" 
							placeholder="<?php echo esc_attr( $placeholder ); ?>"
							value="<?php echo esc_attr( get_search_query() ); ?>"
						/>
						<input type="hidden" name="post_type" value="synpat_patent" />
						<button type="submit" class="synpat-search-button">
							<?php echo esc_html( $button_text ); ?>
						</button>
					</div>
					<?php if ( $show_filters ) : ?>
						<div class="synpat-search-filters">
							<!-- Additional filters can be added here -->
						</div>
					<?php endif; ?>
				</form>
				<div class="synpat-search-results"></div>
			</div>
			<?php
		}

		return ob_get_clean();
	}

	/**
	 * Customer dashboard shortcode handler
	 *
	 * Displays a customer portal/dashboard.
	 *
	 * @since 1.0.0
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string Shortcode output.
	 */
	public function customer_dashboard( $atts ) {
		// Parse shortcode attributes.
		$atts = shortcode_atts(
			array(
				'show_menu' => 'yes',
				'default_tab' => 'overview',
			),
			$atts,
			'synpat_customer_dashboard'
		);

		// Sanitize attributes.
		$show_menu   = 'yes' === $atts['show_menu'];
		$default_tab = sanitize_key( $atts['default_tab'] );

		// Check if user is logged in.
		if ( ! is_user_logged_in() ) {
			ob_start();
			?>
			<div class="synpat-login-required">
				<p><?php esc_html_e( 'Please log in to access your dashboard.', 'synpat' ); ?></p>
				<?php wp_login_form(); ?>
			</div>
			<?php
			return ob_get_clean();
		}

		// Get current user.
		$current_user = wp_get_current_user();

		// Start output buffering.
		ob_start();

		// Prepare template data.
		$data = array(
			'current_user' => $current_user,
			'show_menu'    => $show_menu,
			'default_tab'  => $default_tab,
		);

		// Load template.
		if ( $this->template_loader ) {
			$this->template_loader->get_template_part( 'dashboard/customer', null, $data );
		} else {
			// Fallback if no template loader.
			?>
			<div class="synpat-customer-dashboard">
				<?php if ( $show_menu ) : ?>
					<nav class="synpat-dashboard-menu">
						<ul>
							<li><a href="#overview" data-tab="overview"><?php esc_html_e( 'Overview', 'synpat' ); ?></a></li>
							<li><a href="#portfolios" data-tab="portfolios"><?php esc_html_e( 'Portfolios', 'synpat' ); ?></a></li>
							<li><a href="#patents" data-tab="patents"><?php esc_html_e( 'Patents', 'synpat' ); ?></a></li>
							<li><a href="#profile" data-tab="profile"><?php esc_html_e( 'Profile', 'synpat' ); ?></a></li>
						</ul>
					</nav>
				<?php endif; ?>
				
				<div class="synpat-dashboard-content">
					<div class="synpat-dashboard-tab" data-tab-content="overview">
						<h2><?php esc_html_e( 'Welcome back,', 'synpat' ); ?> <?php echo esc_html( $current_user->display_name ); ?></h2>
						<p><?php esc_html_e( 'Your dashboard overview will appear here.', 'synpat' ); ?></p>
					</div>
				</div>
			</div>
			<?php
		}

		return ob_get_clean();
	}
}
