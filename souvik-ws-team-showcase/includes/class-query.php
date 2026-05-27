<?php
/**
 * WP_Query builder for Dynamic (ACF) mode.
 *
 * @package Souvik_WS_Team_Showcase
 * @since   2.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Builds and executes the WP_Query for dynamic team member loading.
 */
final class Souvik_WS_Query {

	/**
	 * Returns an array of WP_Post objects based on widget query settings.
	 *
	 * @param array<string,mixed> $settings Widget settings.
	 * @return WP_Post[]
	 */
	public static function get_members( array $settings ): array {
		$args = [
			'post_type'      => sanitize_key( $settings['query_post_type'] ?? 'team-member' ),
			'posts_per_page' => (int) ( $settings['query_posts_per_page']['size'] ?? -1 ),
			'orderby'        => sanitize_key( $settings['query_orderby'] ?? 'date' ),
			'order'          => 'ASC' === ( $settings['query_order'] ?? 'DESC' ) ? 'ASC' : 'DESC',
			'post_status'    => 'publish',
		];

		$tax  = sanitize_key( $settings['query_tax'] ?? '' );
		$term = sanitize_key( $settings['query_term'] ?? '' );

		if ( $tax && $term ) {
			$args['tax_query'] = [
				[
					'taxonomy' => $tax,
					'field'    => 'slug',
					'terms'    => $term,
				],
			];
		}

		$query = new WP_Query( $args );

		return $query->have_posts() ? $query->posts : [];
	}

	/**
	 * Reads an ACF field (or falls back to post_meta) for a given post ID.
	 *
	 * @param string $field_key ACF field name/key.
	 * @param int    $post_id   Post ID.
	 * @return mixed
	 */
	public static function get_acf_field( string $field_key, int $post_id ) {
		if ( ! $field_key ) {
			return '';
		}

		// Handle WordPress Native post fields
		switch ( $field_key ) {
			case 'post_title':
				return get_the_title( $post_id );
			case 'post_content':
				return get_post_field( 'post_content', $post_id );
			case 'post_excerpt':
				return get_post_field( 'post_excerpt', $post_id );
			case 'post_permalink':
				return get_permalink( $post_id );
			case 'post_thumbnail':
				return get_the_post_thumbnail_url( $post_id, 'full' );
			case 'post_date':
				return get_the_date( '', $post_id );
			case 'post_author':
				$author_id = get_post_field( 'post_author', $post_id );
				return $author_id ? get_the_author_meta( 'display_name', $author_id ) : '';
			case 'post_id':
				return (string) $post_id;
		}

		if ( function_exists( 'get_field' ) ) {
			$acf_val = get_field( $field_key, $post_id );
			if ( null !== $acf_val && false !== $acf_val ) {
				return $acf_val;
			}
		}

		return get_post_meta( $post_id, $field_key, true );
	}
}

