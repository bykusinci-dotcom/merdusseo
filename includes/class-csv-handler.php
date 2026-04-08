<?php
defined( 'ABSPATH' ) || exit;

class MerdusSEO_CSV_Handler {

	/* ── Export ──────────────────────────────────────────────────────── */
	public static function export( array $results ): void {
		$filename = 'merdusseo-meta-export-' . date( 'Y-m-d' ) . '.csv';

		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );

		$output = fopen( 'php://output', 'w' );

		/* BOM for Excel UTF-8 compatibility */
		fputs( $output, "\xEF\xBB\xBF" );

		/* Headers */
		fputcsv( $output, [ 'post_id', 'post_title', 'url', 'post_type', 'meta_title', 'meta_description', 'h1', 'issues' ] );

		foreach ( $results as $row ) {
			fputcsv( $output, [
				$row['post_id'],
				$row['title'],
				$row['url'],
				$row['type'],
				$row['meta_title'],
				$row['meta_desc'],
				$row['h1'],
				implode( ' | ', $row['issues'] ),
			] );
		}

		fclose( $output );
		exit;
	}

	/* ── Import ──────────────────────────────────────────────────────── */
	/** Returns [ 'updated' => N, 'errors' => [] ] */
	public static function import( array $file ): array {
		$result = [ 'updated' => 0, 'errors' => [] ];

		if ( empty( $file['tmp_name'] ) || ! is_uploaded_file( $file['tmp_name'] ) ) {
			$result['errors'][] = 'Geçersiz dosya yükleme.';
			return $result;
		}

		$handle = fopen( $file['tmp_name'], 'r' );
		if ( ! $handle ) {
			$result['errors'][] = 'Dosya açılamadı.';
			return $result;
		}

		/* Read header row */
		$headers = fgetcsv( $handle );
		if ( ! $headers ) {
			fclose( $handle );
			$result['errors'][] = 'CSV başlık satırı okunamadı.';
			return $result;
		}

		/* Normalize headers */
		$headers = array_map( 'trim', $headers );
		/* Remove BOM from first header if present */
		$headers[0] = ltrim( $headers[0], "\xEF\xBB\xBF" );

		$col = array_flip( $headers );

		if ( ! isset( $col['post_id'] ) ) {
			fclose( $handle );
			$result['errors'][] = '"post_id" sütunu bulunamadı. Lütfen merdusseo tarafından dışa aktarılan CSV formatını kullanın.';
			return $result;
		}

		while ( ( $row = fgetcsv( $handle ) ) !== false ) {
			$post_id = isset( $col['post_id'] ) ? (int) $row[ $col['post_id'] ] : 0;
			if ( ! $post_id ) continue;

			$post = get_post( $post_id );
			if ( ! $post ) {
				$result['errors'][] = "Post ID {$post_id} bulunamadı — atlandı.";
				continue;
			}

			$meta_title = isset( $col['meta_title'] )       ? sanitize_text_field( $row[ $col['meta_title'] ] ) : '';
			$meta_desc  = isset( $col['meta_description'] ) ? sanitize_textarea_field( $row[ $col['meta_description'] ] ) : '';

			MerdusSEO_Meta_Analyzer::save_meta( $post_id, $meta_title, $meta_desc );
			$result['updated']++;
		}

		fclose( $handle );
		return $result;
	}
}
