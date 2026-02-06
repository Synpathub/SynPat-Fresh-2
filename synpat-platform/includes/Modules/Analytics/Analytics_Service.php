<?php
/**
 * Analytics Service
 *
 * @package SynPatPlatform\Modules\Analytics
 */

namespace SynPat\Modules\Analytics;

use WP_Error;

/**
 * Analytics Service Class
 */
class Analytics_Service {

	/**
	 * Get dashboard statistics
	 *
	 * @return array Dashboard statistics.
	 */
	public function get_dashboard_stats() {
		global $wpdb;

		$patents_table    = $wpdb->prefix . 'synpat_patents';
		$portfolios_table = $wpdb->prefix . 'synpat_portfolios';

		// Total counts.
		$total_patents    = $wpdb->get_var( "SELECT COUNT(*) FROM {$patents_table}" );
		$total_portfolios = $wpdb->get_var( "SELECT COUNT(*) FROM {$portfolios_table}" );

		// Active patents.
		$active_patents = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$patents_table} WHERE status = %s",
				'active'
			)
		);

		// Pending patents.
		$pending_patents = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$patents_table} WHERE status = %s",
				'pending'
			)
		);

		// Expired patents.
		$expired_patents = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$patents_table} WHERE status = %s",
				'expired'
			)
		);

		// Patents by status.
		$patents_by_status = $wpdb->get_results(
			"SELECT status, COUNT(*) as count FROM {$patents_table} GROUP BY status",
			ARRAY_A
		);

		// Recent patents (last 30 days).
		$recent_patents = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$patents_table} WHERE created_at >= DATE_SUB(NOW(), INTERVAL %d DAY)",
				30
			)
		);

		// Calculate trends.
		$previous_month_patents = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$patents_table} WHERE created_at >= DATE_SUB(NOW(), INTERVAL %d DAY) AND created_at < DATE_SUB(NOW(), INTERVAL %d DAY)",
				60,
				30
			)
		);

		$trend_percentage = 0;
		if ( $previous_month_patents > 0 ) {
			$trend_percentage = ( ( $recent_patents - $previous_month_patents ) / $previous_month_patents ) * 100;
		}

		return array(
			'total_patents'         => (int) $total_patents,
			'total_portfolios'      => (int) $total_portfolios,
			'active_patents'        => (int) $active_patents,
			'pending_patents'       => (int) $pending_patents,
			'expired_patents'       => (int) $expired_patents,
			'patents_by_status'     => $patents_by_status,
			'recent_patents'        => (int) $recent_patents,
			'trend_percentage'      => round( $trend_percentage, 2 ),
			'generated_at'          => current_time( 'mysql' ),
		);
	}

	/**
	 * Get portfolio analytics
	 *
	 * @param int $portfolio_id Portfolio ID.
	 * @return array|WP_Error Portfolio analytics or error.
	 */
	public function get_portfolio_analytics( $portfolio_id ) {
		global $wpdb;

		if ( ! $portfolio_id ) {
			return new WP_Error( 'invalid_portfolio', __( 'Invalid portfolio ID', 'synpat-platform' ) );
		}

		$portfolio_table         = $wpdb->prefix . 'synpat_portfolios';
		$patents_table           = $wpdb->prefix . 'synpat_patents';
		$portfolio_patents_table = $wpdb->prefix . 'synpat_portfolio_patents';

		// Check if portfolio exists.
		$portfolio = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$portfolio_table} WHERE id = %d",
				$portfolio_id
			),
			ARRAY_A
		);

		if ( ! $portfolio ) {
			return new WP_Error( 'portfolio_not_found', __( 'Portfolio not found', 'synpat-platform' ) );
		}

		// Total patents in portfolio.
		$total_patents = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$portfolio_patents_table} WHERE portfolio_id = %d",
				$portfolio_id
			)
		);

		// Patents by status in portfolio.
		$patents_by_status = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT p.status, COUNT(*) as count 
				FROM {$portfolio_patents_table} pp 
				JOIN {$patents_table} p ON pp.patent_id = p.id 
				WHERE pp.portfolio_id = %d 
				GROUP BY p.status",
				$portfolio_id
			),
			ARRAY_A
		);

		// Average patent age.
		$avg_patent_age = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT AVG(DATEDIFF(NOW(), p.filing_date)) 
				FROM {$portfolio_patents_table} pp 
				JOIN {$patents_table} p ON pp.patent_id = p.id 
				WHERE pp.portfolio_id = %d AND p.filing_date IS NOT NULL",
				$portfolio_id
			)
		);

		// Recent additions (last 30 days).
		$recent_additions = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$portfolio_patents_table} 
				WHERE portfolio_id = %d AND created_at >= DATE_SUB(NOW(), INTERVAL %d DAY)",
				$portfolio_id,
				30
			)
		);

		// Technology distribution (if technology field exists).
		$tech_distribution = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT p.technology, COUNT(*) as count 
				FROM {$portfolio_patents_table} pp 
				JOIN {$patents_table} p ON pp.patent_id = p.id 
				WHERE pp.portfolio_id = %d AND p.technology IS NOT NULL 
				GROUP BY p.technology 
				ORDER BY count DESC 
				LIMIT 10",
				$portfolio_id
			),
			ARRAY_A
		);

		return array(
			'portfolio_id'       => $portfolio_id,
			'portfolio_name'     => $portfolio['name'],
			'total_patents'      => (int) $total_patents,
			'patents_by_status'  => $patents_by_status,
			'avg_patent_age'     => round( (float) $avg_patent_age, 2 ),
			'recent_additions'   => (int) $recent_additions,
			'tech_distribution'  => $tech_distribution,
			'generated_at'       => current_time( 'mysql' ),
		);
	}

	/**
	 * Get patent analytics
	 *
	 * @param int $patent_id Patent ID.
	 * @return array|WP_Error Patent analytics or error.
	 */
	public function get_patent_analytics( $patent_id ) {
		global $wpdb;

		if ( ! $patent_id ) {
			return new WP_Error( 'invalid_patent', __( 'Invalid patent ID', 'synpat-platform' ) );
		}

		$patents_table = $wpdb->prefix . 'synpat_patents';

		// Get patent details.
		$patent = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$patents_table} WHERE id = %d",
				$patent_id
			),
			ARRAY_A
		);

		if ( ! $patent ) {
			return new WP_Error( 'patent_not_found', __( 'Patent not found', 'synpat-platform' ) );
		}

		// Calculate patent age.
		$patent_age = null;
		if ( ! empty( $patent['filing_date'] ) ) {
			$filing_date = new \DateTime( $patent['filing_date'] );
			$now         = new \DateTime();
			$patent_age  = $now->diff( $filing_date )->days;
		}

		// Years until expiration (assuming 20 years from filing).
		$years_until_expiration = null;
		if ( ! empty( $patent['filing_date'] ) ) {
			$filing_date            = new \DateTime( $patent['filing_date'] );
			$expiration_date        = clone $filing_date;
			$expiration_date->modify( '+20 years' );
			$now                    = new \DateTime();
			$years_until_expiration = $now->diff( $expiration_date )->y;
		}

		// Count portfolios containing this patent.
		$portfolio_patents_table = $wpdb->prefix . 'synpat_portfolio_patents';
		$portfolio_count         = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$portfolio_patents_table} WHERE patent_id = %d",
				$patent_id
			)
		);

		// Count documents.
		$documents_table = $wpdb->prefix . 'synpat_documents';
		$document_count  = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$documents_table} WHERE entity_type = %s AND entity_id = %d",
				'patent',
				$patent_id
			)
		);

		// Activity log count.
		$activity_table  = $wpdb->prefix . 'synpat_activity_log';
		$activity_count  = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$activity_table} WHERE entity_type = %s AND entity_id = %d",
				'patent',
				$patent_id
			)
		);

		return array(
			'patent_id'              => $patent_id,
			'patent_number'          => $patent['patent_number'],
			'title'                  => $patent['title'],
			'status'                 => $patent['status'],
			'patent_age_days'        => (int) $patent_age,
			'years_until_expiration' => (int) $years_until_expiration,
			'portfolio_count'        => (int) $portfolio_count,
			'document_count'         => (int) $document_count,
			'activity_count'         => (int) $activity_count,
			'filing_date'            => $patent['filing_date'],
			'issue_date'             => $patent['issue_date'] ?? null,
			'generated_at'           => current_time( 'mysql' ),
		);
	}

	/**
	 * Get analytics summary
	 *
	 * @param array $args Analytics arguments.
	 * @return array Analytics summary.
	 */
	public function get_analytics_summary( $args = array() ) {
		$defaults = array(
			'date_from' => date( 'Y-m-d', strtotime( '-30 days' ) ),
			'date_to'   => date( 'Y-m-d' ),
		);

		$args = wp_parse_args( $args, $defaults );

		global $wpdb;

		$patents_table = $wpdb->prefix . 'synpat_patents';

		// Patents filed within date range.
		$patents_filed = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$patents_table} WHERE filing_date BETWEEN %s AND %s",
				$args['date_from'],
				$args['date_to']
			)
		);

		// Patents issued within date range.
		$patents_issued = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$patents_table} WHERE issue_date BETWEEN %s AND %s",
				$args['date_from'],
				$args['date_to']
			)
		);

		return array(
			'date_range'      => array(
				'from' => $args['date_from'],
				'to'   => $args['date_to'],
			),
			'patents_filed'   => (int) $patents_filed,
			'patents_issued'  => (int) $patents_issued,
			'generated_at'    => current_time( 'mysql' ),
		);
	}
}
