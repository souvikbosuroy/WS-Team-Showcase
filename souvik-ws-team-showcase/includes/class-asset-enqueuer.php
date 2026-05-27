<?php
/**
 * Asset registration, conditional GSAP enqueue, and CSS enqueue.
 *
 * GSAP rule: NEVER wp_enqueue globally.
 * — Register all GSAP modules once.
 * — Enqueue only the subset the widget's saved animation settings actually need.
 *
 * @package Souvik_WS_Team_Showcase
 * @since   2.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Handles script + style registration and conditional enqueue.
 */
final class Souvik_WS_Asset_Enqueuer {

	/** CDN base for GSAP 3.12.5 */
	private const GSAP_CDN = 'https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/';

	/** Text presets that require TextPlugin */
	private const TEXT_PRESETS = [ 'stagger_chars', 'count_up', 'scramble' ];

	/** Easings that require CustomEase plugin */
	private const CUSTOM_EASE_STRINGS = [ 'back.out', 'elastic.out', 'CustomEase' ];

	/** Element animation keys */
	private const ELEM_KEYS = [
		'card', 'image', 'name', 'designation',
		'badge', 'bio', 'socials', 'button', 'filter', 'popup',
	];

	// -----------------------------------------------------------------------
	// Public API
	// -----------------------------------------------------------------------

	/**
	 * Register CSS + JS assets. Never enqueue GSAP here.
	 * Called on wp_enqueue_scripts and elementor/preview/enqueue_scripts.
	 */
	public static function register_all(): void {
		self::register_styles();
		self::register_scripts();
	}

	/**
	 * Register only the CSS (called from elementor/preview/enqueue_styles too).
	 */
	public static function register_styles(): void {
		wp_register_style(
			'souvik-ws-team-css',
			Souvik_WS_Team_Showcase_Plugin::$url . 'assets/css/souvik-ws-team.css',
			[],
			Souvik_WS_Team_Showcase_Plugin::VERSION . '.' . time()
		);
		wp_enqueue_style( 'souvik-ws-team-css' );
	}

	/**
	 * Register widget JS + all GSAP modules (never enqueued here).
	 */
	public static function register_scripts(): void {
		// Widget JS — depends on nothing by default; GSAP added conditionally.
		wp_register_script(
			'souvik-ws-team-js',
			Souvik_WS_Team_Showcase_Plugin::$url . 'assets/js/souvik-ws-team.js',
			[],
			Souvik_WS_Team_Showcase_Plugin::VERSION,
			true
		);
		wp_enqueue_script( 'souvik-ws-team-js' );

		// GSAP modules — register only.
		wp_register_script(
			'souvik-ws-gsap-core',
			self::GSAP_CDN . 'gsap.min.js',
			[],
			'3.12.5',
			true
		);
		wp_register_script(
			'souvik-ws-gsap-st',
			self::GSAP_CDN . 'ScrollTrigger.min.js',
			[ 'souvik-ws-gsap-core' ],
			'3.12.5',
			true
		);
		wp_register_script(
			'souvik-ws-gsap-text',
			self::GSAP_CDN . 'TextPlugin.min.js',
			[ 'souvik-ws-gsap-core' ],
			'3.12.5',
			true
		);
		wp_register_script(
			'souvik-ws-gsap-ease',
			self::GSAP_CDN . 'CustomEase.min.js',
			[ 'souvik-ws-gsap-core' ],
			'3.12.5',
			true
		);
	}

	/**
	 * Inspect widget settings and enqueue only the GSAP modules actually needed.
	 * Called from Souvik_WS_Team_Showcase_Widget::render().
	 *
	 * @param array<string,mixed> $settings Widget settings from get_settings_for_display().
	 */
	public static function enqueue_for_widget( array $settings ): void {
		// Ensure base assets are always enqueued when a widget renders.
		if ( ! wp_style_is( 'souvik-ws-team-css', 'enqueued' ) ) {
			wp_enqueue_style( 'souvik-ws-team-css' );
		}
		if ( ! wp_script_is( 'souvik-ws-team-js', 'enqueued' ) ) {
			wp_enqueue_script( 'souvik-ws-team-js' );
		}

		$needs_gsap = false;
		$needs_st   = false;
		$needs_text = false;
		$needs_ease = false;

		foreach ( self::ELEM_KEYS as $key ) {
			$enabled = ( ( $settings[ 'anim_' . $key . '_enable' ] ?? 'no' ) === 'yes' );
			if ( ! $enabled ) {
				continue;
			}

			$needs_gsap = true;
			$needs_st   = true;
			$needs_ease = true;
		}

		if ( $needs_gsap ) {
			wp_enqueue_script( 'souvik-ws-gsap-core' );
		}
		if ( $needs_st ) {
			wp_enqueue_script( 'souvik-ws-gsap-st' );
		}
		if ( $needs_text ) {
			wp_enqueue_script( 'souvik-ws-gsap-text' );
		}
		if ( $needs_ease ) {
			wp_enqueue_script( 'souvik-ws-gsap-ease' );
		}
	}
}
