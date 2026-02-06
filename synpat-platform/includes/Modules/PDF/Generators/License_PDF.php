<?php
/**
 * License PDF Generator
 *
 * @package SynPatPlatform\Modules\PDF\Generators
 */

namespace SynPat\Modules\PDF\Generators;

/**
 * License PDF Generator Class
 */
class License_PDF {

	/**
	 * Generate license agreement PDF
	 *
	 * @param array $license_data License data.
	 * @param array $options      Generation options.
	 * @return string PDF file path.
	 * @throws \Exception If PDF generation fails.
	 */
	public function generate( $license_data, $options = array() ) {
		if ( ! class_exists( '\Mpdf\Mpdf' ) ) {
			throw new \Exception( __( 'mPDF library not found. Please install it via Composer.', 'synpat-platform' ) );
		}

		$mpdf = new \Mpdf\Mpdf( array(
			'mode'        => 'utf-8',
			'format'      => 'A4',
			'margin_top'  => 25,
			'margin_left' => 20,
		) );

		$html = $this->generate_html( $license_data );

		$mpdf->WriteHTML( $html );

		$upload_info = wp_upload_dir();
		$pdf_dir     = $upload_info['basedir'] . '/synpat-pdfs';

		if ( ! file_exists( $pdf_dir ) ) {
			wp_mkdir_p( $pdf_dir );
		}

		$filename = 'license-' . $license_data['id'] . '-' . time() . '.pdf';
		$filepath = $pdf_dir . '/' . $filename;

		$mpdf->Output( $filepath, \Mpdf\Output\Destination::FILE );

		return $filepath;
	}

	/**
	 * Generate HTML content for PDF
	 *
	 * @param array $license_data License data.
	 * @return string HTML content.
	 */
	private function generate_html( $license_data ) {
		ob_start();
		?>
		<!DOCTYPE html>
		<html>
		<head>
			<style>
				body { font-family: 'Times New Roman', serif; font-size: 11pt; line-height: 1.6; }
				h1 { text-align: center; font-size: 18pt; margin-bottom: 30px; text-transform: uppercase; }
				h2 { font-size: 14pt; margin-top: 25px; border-bottom: 1px solid #000; }
				.parties { margin: 20px 0; }
				.section { margin: 15px 0; }
				.signature-block { margin-top: 50px; }
				.signature-line { border-top: 1px solid #000; width: 250px; margin-top: 50px; }
				table { width: 100%; margin: 10px 0; }
				th, td { padding: 8px; text-align: left; }
			</style>
		</head>
		<body>
			<h1><?php echo esc_html__( 'Patent License Agreement', 'synpat-platform' ); ?></h1>

			<div class="parties">
				<p><strong><?php echo esc_html__( 'This License Agreement', 'synpat-platform' ); ?></strong> 
				<?php echo esc_html__( 'is entered into as of', 'synpat-platform' ); ?> 
				<?php echo esc_html( $license_data['effective_date'] ?? date( 'F j, Y' ) ); ?></p>

				<p><strong><?php echo esc_html__( 'Between:', 'synpat-platform' ); ?></strong></p>
				<p><strong><?php echo esc_html__( 'Licensor:', 'synpat-platform' ); ?></strong> 
				<?php echo esc_html( $license_data['licensor'] ?? '[Licensor Name]' ); ?></p>
				<p><strong><?php echo esc_html__( 'Licensee:', 'synpat-platform' ); ?></strong> 
				<?php echo esc_html( $license_data['licensee'] ?? '[Licensee Name]' ); ?></p>
			</div>

			<h2><?php echo esc_html__( '1. Licensed Patents', 'synpat-platform' ); ?></h2>
			<div class="section">
				<p><?php echo esc_html__( 'The following patents are subject to this license agreement:', 'synpat-platform' ); ?></p>
				<table>
					<tr>
						<th><?php echo esc_html__( 'Patent Number', 'synpat-platform' ); ?></th>
						<td><?php echo esc_html( $license_data['patent_number'] ?? 'N/A' ); ?></td>
					</tr>
					<tr>
						<th><?php echo esc_html__( 'Title', 'synpat-platform' ); ?></th>
						<td><?php echo esc_html( $license_data['patent_title'] ?? 'N/A' ); ?></td>
					</tr>
				</table>
			</div>

			<h2><?php echo esc_html__( '2. Grant of License', 'synpat-platform' ); ?></h2>
			<div class="section">
				<p><?php echo esc_html__( 'Licensor hereby grants to Licensee a license under the Licensed Patents.', 'synpat-platform' ); ?></p>
				<p><strong><?php echo esc_html__( 'License Type:', 'synpat-platform' ); ?></strong> 
				<?php echo esc_html( $license_data['license_type'] ?? 'Exclusive' ); ?></p>
				<p><strong><?php echo esc_html__( 'Territory:', 'synpat-platform' ); ?></strong> 
				<?php echo esc_html( $license_data['territory'] ?? 'Worldwide' ); ?></p>
			</div>

			<h2><?php echo esc_html__( '3. Financial Terms', 'synpat-platform' ); ?></h2>
			<div class="section">
				<p><strong><?php echo esc_html__( 'License Fee:', 'synpat-platform' ); ?></strong> 
				<?php echo esc_html( $license_data['license_fee'] ?? 'N/A' ); ?></p>
				<p><strong><?php echo esc_html__( 'Royalty Rate:', 'synpat-platform' ); ?></strong> 
				<?php echo esc_html( $license_data['royalty_rate'] ?? 'N/A' ); ?></p>
			</div>

			<h2><?php echo esc_html__( '4. Term and Termination', 'synpat-platform' ); ?></h2>
			<div class="section">
				<p><strong><?php echo esc_html__( 'Effective Date:', 'synpat-platform' ); ?></strong> 
				<?php echo esc_html( $license_data['effective_date'] ?? date( 'F j, Y' ) ); ?></p>
				<p><strong><?php echo esc_html__( 'Expiration Date:', 'synpat-platform' ); ?></strong> 
				<?php echo esc_html( $license_data['expiration_date'] ?? 'N/A' ); ?></p>
			</div>

			<div class="signature-block">
				<p><strong><?php echo esc_html__( 'Licensor Signature:', 'synpat-platform' ); ?></strong></p>
				<div class="signature-line"></div>
				<p><?php echo esc_html__( 'Name:', 'synpat-platform' ); ?> _______________________</p>
				<p><?php echo esc_html__( 'Date:', 'synpat-platform' ); ?> _______________________</p>

				<p style="margin-top: 40px;"><strong><?php echo esc_html__( 'Licensee Signature:', 'synpat-platform' ); ?></strong></p>
				<div class="signature-line"></div>
				<p><?php echo esc_html__( 'Name:', 'synpat-platform' ); ?> _______________________</p>
				<p><?php echo esc_html__( 'Date:', 'synpat-platform' ); ?> _______________________</p>
			</div>
		</body>
		</html>
		<?php
		return ob_get_clean();
	}
}
