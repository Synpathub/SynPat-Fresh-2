<?php
/**
 * Dashboard Widgets Class
 *
 * Handles dashboard widgets and dashboard page rendering.
 *
 * @package SynPatPlatform
 */

namespace SynPat\Admin;

/**
 * Dashboard Class
 */
class Dashboard {

	/**
	 * Render the dashboard page.
	 */
	public function render() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'synpat-platform' ) );
		}

		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			
			<div class="synpat-dashboard">
				<div class="synpat-dashboard-widgets">
					<?php
					$this->render_statistics_widget();
					$this->render_recent_activity_widget();
					$this->render_quick_actions_widget();
					?>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render statistics overview widget.
	 */
	private function render_statistics_widget() {
		$stats = $this->get_statistics();
		?>
		<div class="synpat-widget synpat-stats-widget">
			<h2><?php esc_html_e( 'Statistics Overview', 'synpat-platform' ); ?></h2>
			<div class="synpat-stats-grid">
				<div class="synpat-stat-item">
					<div class="synpat-stat-icon">
						<span class="dashicons dashicons-portfolio"></span>
					</div>
					<div class="synpat-stat-content">
						<div class="synpat-stat-number"><?php echo esc_html( $stats['portfolios'] ); ?></div>
						<div class="synpat-stat-label"><?php esc_html_e( 'Portfolios', 'synpat-platform' ); ?></div>
					</div>
				</div>

				<div class="synpat-stat-item">
					<div class="synpat-stat-icon">
						<span class="dashicons dashicons-media-document"></span>
					</div>
					<div class="synpat-stat-content">
						<div class="synpat-stat-number"><?php echo esc_html( $stats['patents'] ); ?></div>
						<div class="synpat-stat-label"><?php esc_html_e( 'Patents', 'synpat-platform' ); ?></div>
					</div>
				</div>

				<div class="synpat-stat-item">
					<div class="synpat-stat-icon">
						<span class="dashicons dashicons-admin-page"></span>
					</div>
					<div class="synpat-stat-content">
						<div class="synpat-stat-number"><?php echo esc_html( $stats['licenses'] ); ?></div>
						<div class="synpat-stat-label"><?php esc_html_e( 'Licenses', 'synpat-platform' ); ?></div>
					</div>
				</div>

				<div class="synpat-stat-item">
					<div class="synpat-stat-icon">
						<span class="dashicons dashicons-yes-alt"></span>
					</div>
					<div class="synpat-stat-content">
						<div class="synpat-stat-number"><?php echo esc_html( $stats['due_diligence'] ); ?></div>
						<div class="synpat-stat-label"><?php esc_html_e( 'Due Diligence', 'synpat-platform' ); ?></div>
					</div>
				</div>

				<div class="synpat-stat-item">
					<div class="synpat-stat-icon">
						<span class="dashicons dashicons-groups"></span>
					</div>
					<div class="synpat-stat-content">
						<div class="synpat-stat-number"><?php echo esc_html( $stats['customers'] ); ?></div>
						<div class="synpat-stat-label"><?php esc_html_e( 'Customers', 'synpat-platform' ); ?></div>
					</div>
				</div>

				<div class="synpat-stat-item">
					<div class="synpat-stat-icon">
						<span class="dashicons dashicons-building"></span>
					</div>
					<div class="synpat-stat-content">
						<div class="synpat-stat-number"><?php echo esc_html( $stats['companies'] ); ?></div>
						<div class="synpat-stat-label"><?php esc_html_e( 'Companies', 'synpat-platform' ); ?></div>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render recent activity widget.
	 */
	private function render_recent_activity_widget() {
		$activities = $this->get_recent_activity();
		?>
		<div class="synpat-widget synpat-activity-widget">
			<h2><?php esc_html_e( 'Recent Activity', 'synpat-platform' ); ?></h2>
			<div class="synpat-activity-list">
				<?php if ( ! empty( $activities ) ) : ?>
					<?php foreach ( $activities as $activity ) : ?>
						<div class="synpat-activity-item">
							<div class="synpat-activity-icon">
								<span class="dashicons dashicons-<?php echo esc_attr( $activity['icon'] ); ?>"></span>
							</div>
							<div class="synpat-activity-content">
								<div class="synpat-activity-text"><?php echo esc_html( $activity['text'] ); ?></div>
								<div class="synpat-activity-time"><?php echo esc_html( $activity['time'] ); ?></div>
							</div>
						</div>
					<?php endforeach; ?>
				<?php else : ?>
					<p class="synpat-no-activity"><?php esc_html_e( 'No recent activity.', 'synpat-platform' ); ?></p>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Render quick actions widget.
	 */
	private function render_quick_actions_widget() {
		?>
		<div class="synpat-widget synpat-actions-widget">
			<h2><?php esc_html_e( 'Quick Actions', 'synpat-platform' ); ?></h2>
			<div class="synpat-actions-grid">
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=synpat-portfolios' ) ); ?>" class="synpat-action-button">
					<span class="dashicons dashicons-plus"></span>
					<?php esc_html_e( 'Add Portfolio', 'synpat-platform' ); ?>
				</a>

				<a href="<?php echo esc_url( admin_url( 'admin.php?page=synpat-patents' ) ); ?>" class="synpat-action-button">
					<span class="dashicons dashicons-plus"></span>
					<?php esc_html_e( 'Add Patent', 'synpat-platform' ); ?>
				</a>

				<a href="<?php echo esc_url( admin_url( 'admin.php?page=synpat-due-diligence' ) ); ?>" class="synpat-action-button">
					<span class="dashicons dashicons-plus"></span>
					<?php esc_html_e( 'New Due Diligence', 'synpat-platform' ); ?>
				</a>

				<a href="<?php echo esc_url( admin_url( 'admin.php?page=synpat-licenses' ) ); ?>" class="synpat-action-button">
					<span class="dashicons dashicons-plus"></span>
					<?php esc_html_e( 'Add License', 'synpat-platform' ); ?>
				</a>

				<a href="<?php echo esc_url( admin_url( 'admin.php?page=synpat-reports' ) ); ?>" class="synpat-action-button">
					<span class="dashicons dashicons-chart-bar"></span>
					<?php esc_html_e( 'Generate Report', 'synpat-platform' ); ?>
				</a>

				<a href="<?php echo esc_url( admin_url( 'admin.php?page=synpat-settings' ) ); ?>" class="synpat-action-button">
					<span class="dashicons dashicons-admin-settings"></span>
					<?php esc_html_e( 'Settings', 'synpat-platform' ); ?>
				</a>
			</div>
		</div>
		<?php
	}

	/**
	 * Get statistics data.
	 *
	 * @return array
	 */
	private function get_statistics() {
		global $wpdb;

		// TODO: Replace with actual database queries
		return array(
			'portfolios'    => 0,
			'patents'       => 0,
			'licenses'      => 0,
			'due_diligence' => 0,
			'customers'     => 0,
			'companies'     => 0,
		);
	}

	/**
	 * Get recent activity.
	 *
	 * @return array
	 */
	private function get_recent_activity() {
		// TODO: Replace with actual activity log queries
		return array();
	}
}
