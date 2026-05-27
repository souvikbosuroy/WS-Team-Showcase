<?php
/**
 * Plugin Name:  Souvik WS Team Showcase
 * Plugin URI:   https://souvik.dev
 * Description:  Professional Elementor Pro custom widget — manual or dynamic (ACF) team grid with per-element GSAP animations, filter bar, popup modal, lazy load, dark mode, and full Style/Advanced control coverage.
 * Version:      2.0.0
 * Author:       Souvik
 * Author URI:   https://souvik.dev
 * Text Domain:  souvik-ws-team-showcase
 * Domain Path:  /languages
 * Requires at least: 5.8
 * Tested up to:     6.5
 * Requires PHP: 7.4
 */

defined( 'ABSPATH' ) || exit;

/**
 * Main plugin entry-point — singleton.
 *
 * @since 2.0.0
 */
final class Souvik_WS_Team_Showcase_Plugin {

	/** @var self|null */
	private static ?self $instance = null;

	/** Plugin root directory with trailing slash. */
	public static string $dir;

	/** Plugin root URL with trailing slash. */
	public static string $url;

	/** Plugin version. */
	public const VERSION = '2.0.0';

	/** -----------------------------------------------------------------------
	 *  Bootstrap
	 * ---------------------------------------------------------------------- */

	public static function get_instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		self::$dir = plugin_dir_path( __FILE__ );
		self::$url = plugin_dir_url( __FILE__ );

		add_action( 'plugins_loaded', [ $this, 'bootstrap' ] );
		add_action( 'init', [ self::class, 'register_team_member_post_type' ] );
		add_action( 'acf/init', [ $this, 'register_acf_fields' ] );
		add_action( 'acf/init', [ $this, 'sync_showcase_to_acf_db' ], 20 );
		add_action( 'admin_menu', [ $this, 'register_settings_page' ] );
		add_action( 'admin_init', [ $this, 'save_settings_page' ] );
	}

	/**
	 * Called on plugins_loaded — validates Elementor exists, then boots.
	 */
	public function bootstrap(): void {
		load_plugin_textdomain(
			'souvik-ws-team-showcase',
			false,
			dirname( plugin_basename( __FILE__ ) ) . '/languages'
		);

		// Hard dependency: Elementor must be active.
		if ( ! did_action( 'elementor/loaded' ) ) {
			add_action( 'admin_notices', [ $this, 'elementor_missing_notice' ] );
			add_action( 'admin_init', [ $this, 'deactivate_self' ] );
			return;
		}

		$this->init();
	}

	/**
	 * Renders an admin notice if Elementor is not installed and active.
	 */
	public function elementor_missing_notice(): void {
		$message = sprintf(
			esc_html__( 'Souvik WS Team Showcase requires %1$sElementor%2$s to be installed and active. The plugin has been deactivated automatically.', 'souvik-ws-team-showcase' ),
			'<strong>',
			'</strong>'
		);
		echo '<div class="notice notice-error is-dismissible"><p>' . $message . '</p></div>';
	}

	/**
	 * Deactivates this plugin dynamically.
	 */
	public function deactivate_self(): void {
		deactivate_plugins( plugin_basename( __FILE__ ) );
		if ( isset( $_GET['activate'] ) ) {
			unset( $_GET['activate'] );
		}
	}

	/**
	 * Loads all plugin components.
	 *
	 * NOTE: class-widget.php is required INSIDE elementor/widgets/register —
	 * not here — because its class declaration extends \Elementor\Widget_Base,
	 * which PHP resolves at the moment the file is first parsed. Elementor's
	 * autoloader only maps Widget_Base after the elementor/widgets/register
	 * action fires. Requiring the file any earlier causes a fatal
	 * "Class not found" error.
	 */
	public function init(): void {
		// Minimum Elementor version guard.
		if ( ! defined( 'ELEMENTOR_VERSION' ) || version_compare( ELEMENTOR_VERSION, '3.10.0', '<' ) ) {
			add_action(
				'admin_notices',
				function (): void {
					echo '<div class="notice notice-error"><p>' .
						esc_html__( 'Souvik WS Team Showcase requires Elementor 3.10 or later.', 'souvik-ws-team-showcase' ) .
						'</p></div>';
				}
			);
			return;
		}

		// Load non-Elementor helpers immediately — no Widget_Base dependency.
		require_once self::$dir . 'includes/class-asset-enqueuer.php';
		require_once self::$dir . 'includes/class-query.php';

		// Register widget — load widget class INSIDE this callback so that
		// Elementor's autoloader is guaranteed to have mapped Widget_Base.
		add_action(
			'elementor/widgets/register',
			function ( $widgets_manager ): void {
				require_once Souvik_WS_Team_Showcase_Plugin::$dir . 'includes/class-widget.php';
				$widgets_manager->register( new Souvik_WS_Team_Showcase_Widget() );
			}
		);

		// Register assets on the front-end.
		add_action( 'wp_enqueue_scripts', [ Souvik_WS_Asset_Enqueuer::class, 'register_all' ] );

		// Elementor editor preview hooks.
		add_action( 'elementor/preview/enqueue_scripts', [ Souvik_WS_Asset_Enqueuer::class, 'register_all' ] );
		add_action( 'elementor/preview/enqueue_styles',  [ Souvik_WS_Asset_Enqueuer::class, 'register_styles' ] );
	}

	/**
	 * Plugin activation handler.
	 */
	public static function activate(): void {
		self::register_team_member_post_type();
		self::register_team_department_taxonomy();
		flush_rewrite_rules();
	}

	/**
	 * Plugin deactivation handler.
	 */
	public static function deactivate(): void {
		self::unregister_team_member_post_type();
		self::unregister_team_department_taxonomy();
		flush_rewrite_rules();
	}

	/**
	 * Registers the team-member custom post type.
	 */
	public static function register_team_member_post_type(): void {
		// If ACF manages this post type in the database, let ACF register it
		if ( function_exists( 'acf_get_post_type' ) && acf_get_post_type( 'team-member' ) ) {
			self::register_team_department_taxonomy();
			return;
		}

		$labels = [
			'name'                  => esc_html__( 'Team Members', 'souvik-ws-team-showcase' ),
			'singular_name'         => esc_html__( 'Team Member', 'souvik-ws-team-showcase' ),
			'menu_name'             => esc_html__( 'Team Members', 'souvik-ws-team-showcase' ),
			'name_admin_bar'        => esc_html__( 'Team Member', 'souvik-ws-team-showcase' ),
			'add_new'               => esc_html__( 'Add New', 'souvik-ws-team-showcase' ),
			'add_new_item'          => esc_html__( 'Add New Team Member', 'souvik-ws-team-showcase' ),
			'new_item'              => esc_html__( 'New Team Member', 'souvik-ws-team-showcase' ),
			'edit_item'             => esc_html__( 'Edit Team Member', 'souvik-ws-team-showcase' ),
			'view_item'             => esc_html__( 'View Team Member', 'souvik-ws-team-showcase' ),
			'all_items'             => esc_html__( 'All Team Members', 'souvik-ws-team-showcase' ),
			'search_items'          => esc_html__( 'Search Team Members', 'souvik-ws-team-showcase' ),
			'parent_item_colon'     => esc_html__( 'Parent Team Member:', 'souvik-ws-team-showcase' ),
			'not_found'             => esc_html__( 'No team members found.', 'souvik-ws-team-showcase' ),
			'not_found_in_trash'    => esc_html__( 'No team members found in Trash.', 'souvik-ws-team-showcase' ),
		];

		$args = [
			'labels'             => $labels,
			'public'             => true,
			'publicly_queryable' => true,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'show_in_nav_menus'  => false,
			'show_in_admin_bar'  => true,
			'show_in_rest'       => true,
			'has_archive'        => false,
			'rewrite'            => [ 'slug' => 'team-member' ],
			'capability_type'    => 'post',
			'map_meta_cap'       => true,
			'hierarchical'       => false,
			'supports'           => [ 'title', 'editor', 'excerpt', 'thumbnail' ],
			'menu_icon'          => 'dashicons-groups',
		];

		register_post_type( 'team-member', $args );
		self::register_team_department_taxonomy();
	}

	/**
	 * Registers the team-department taxonomy.
	 */
	public static function register_team_department_taxonomy(): void {
		$tax_slug = get_option( 'souvik_ws_team_taxonomy_slug', 'department' );
		$tax_label_plural = get_option( 'souvik_ws_team_taxonomy_label', 'Departments' );
		$tax_label_singular = get_option( 'souvik_ws_team_taxonomy_singular_label', 'Department' );

		// If ACF manages this taxonomy in the database, let ACF register it
		if ( function_exists( 'acf_get_taxonomy' ) && acf_get_taxonomy( $tax_slug ) ) {
			return;
		}

		$labels = [
			'name'              => esc_html( $tax_label_plural ),
			'singular_name'     => esc_html( $tax_label_singular ),
			'search_items'      => sprintf( esc_html__( 'Search %s', 'souvik-ws-team-showcase' ), $tax_label_plural ),
			'all_items'         => sprintf( esc_html__( 'All %s', 'souvik-ws-team-showcase' ), $tax_label_plural ),
			'parent_item'       => sprintf( esc_html__( 'Parent %s', 'souvik-ws-team-showcase' ), $tax_label_singular ),
			'parent_item_colon' => sprintf( esc_html__( 'Parent %s:', 'souvik-ws-team-showcase' ), $tax_label_singular ),
			'edit_item'         => sprintf( esc_html__( 'Edit %s', 'souvik-ws-team-showcase' ), $tax_label_singular ),
			'update_item'       => sprintf( esc_html__( 'Update %s', 'souvik-ws-team-showcase' ), $tax_label_singular ),
			'add_new_item'      => sprintf( esc_html__( 'Add New %s', 'souvik-ws-team-showcase' ), $tax_label_singular ),
			'new_item_name'     => sprintf( esc_html__( 'New %s Name', 'souvik-ws-team-showcase' ), $tax_label_singular ),
			'menu_name'         => esc_html( $tax_label_plural ),
		];

		$args = [
			'hierarchical'      => true,
			'labels'            => $labels,
			'show_ui'           => true,
			'show_admin_column' => true,
			'query_var'         => true,
			'show_in_rest'      => true,
			'rewrite'           => [ 'slug' => $tax_slug ],
		];

		register_taxonomy( $tax_slug, [ 'team-member' ], $args );
	}

	/**
	 * Unregisters the team-member post type during deactivation.
	 */
	public static function unregister_team_member_post_type(): void {
		if ( post_type_exists( 'team-member' ) ) {
			unregister_post_type( 'team-member' );
		}
	}

	/**
	 * Unregisters the team-department taxonomy.
	 */
	public static function unregister_team_department_taxonomy(): void {
		$tax_slug = get_option( 'souvik_ws_team_taxonomy_slug', 'department' );
		if ( taxonomy_exists( $tax_slug ) ) {
			unregister_taxonomy( $tax_slug );
		}
	}

	/**
	 * Registers the ACF local field group for team members.
	 */
	public function register_acf_fields(): void {
		if ( ! function_exists( 'acf_add_local_field_group' ) ) {
			return;
		}

		acf_add_local_field_group([
			'key' => 'group_souvik_team_member',
			'title' => esc_html__( 'Team Member', 'souvik-ws-team-showcase' ),
			'fields' => [
				[
					'key' => 'field_souvik_member_name',
					'label' => esc_html__( 'Name', 'souvik-ws-team-showcase' ),
					'name' => 'member_name',
					'type' => 'text',
					'required' => 0,
				],
				[
					'key' => 'field_souvik_member_designation',
					'label' => esc_html__( 'Designation', 'souvik-ws-team-showcase' ),
					'name' => 'member_designation',
					'type' => 'text',
					'required' => 0,
				],
				[
					'key' => 'field_souvik_member_department',
					'label' => esc_html__( 'Department', 'souvik-ws-team-showcase' ),
					'name' => 'member_department',
					'type' => 'text',
					'required' => 0,
				],
				[
					'key' => 'field_souvik_member_short_bio',
					'label' => esc_html__( 'Short Bio', 'souvik-ws-team-showcase' ),
					'name' => 'member_short_bio',
					'type' => 'textarea',
					'required' => 0,
				],
				[
					'key' => 'field_souvik_member_photo',
					'label' => esc_html__( 'Photo', 'souvik-ws-team-showcase' ),
					'name' => 'member_photo',
					'type' => 'image',
					'return_format' => 'array',
					'preview_size' => 'medium',
					'library' => 'all',
				],
				[
					'key' => 'field_souvik_member_social_links',
					'label' => esc_html__( 'Social Links', 'souvik-ws-team-showcase' ),
					'name' => 'member_social_links',
					'type' => 'repeater',
					'sub_fields' => [
						[
							'key' => 'field_souvik_social_platform',
							'label' => esc_html__( 'Platform', 'souvik-ws-team-showcase' ),
							'name' => 'platform',
							'type' => 'text',
						],
						[
							'key' => 'field_souvik_social_url',
							'label' => esc_html__( 'URL', 'souvik-ws-team-showcase' ),
							'name' => 'url',
							'type' => 'url',
						],
						[
							'key' => 'field_souvik_social_icon_source',
							'label' => esc_html__( 'Icon Source', 'souvik-ws-team-showcase' ),
							'name' => 'icon_source',
							'type' => 'select',
							'choices' => [
								'default' => esc_html__( 'Default', 'souvik-ws-team-showcase' ),
								'custom' => esc_html__( 'Custom', 'souvik-ws-team-showcase' ),
							],
							'default_value' => 'default',
						],
						[
							'key' => 'field_souvik_social_custom_icon',
							'label' => esc_html__( 'Custom Icon', 'souvik-ws-team-showcase' ),
							'name' => 'custom_icon',
							'type' => 'text',
						],
						[
							'key' => 'field_souvik_social_custom_image',
							'label' => esc_html__( 'Custom Image', 'souvik-ws-team-showcase' ),
							'name' => 'custom_image',
							'type' => 'image',
							'return_format' => 'array',
						],
					],
					'min' => 0,
					'max' => 0,
				],
				[
					'key' => 'field_souvik_member_button_text',
					'label' => esc_html__( 'Button Label', 'souvik-ws-team-showcase' ),
					'name' => 'member_button_text',
					'type' => 'text',
				],
				[
					'key' => 'field_souvik_member_button_url',
					'label' => esc_html__( 'Button URL', 'souvik-ws-team-showcase' ),
					'name' => 'member_button_url',
					'type' => 'url',
				],
			],
			'location' => [
				[
					[
						'param' => 'post_type',
						'operator' => '==',
						'value' => 'team-member',
					],
				],
			],
			'menu_order' => 0,
			'position' => 'normal',
			'style' => 'default',
			'label_placement' => 'top',
			'instruction_placement' => 'label',
			'hide_on_screen' => '',
			'active' => true,
			'description' => esc_html__( 'Team member fields used by the Souvik WS Team Showcase widget.', 'souvik-ws-team-showcase' ),
		]);
	}

	/**
	 * Synchronizes the Custom Post Type, Custom Taxonomy, and ACF Fields to the ACF database
	 * so they are visible and editable in the ACF Dashboard UI.
	 */
	public function sync_showcase_to_acf_db(): void {
		if ( ! function_exists( 'acf_update_post_type' ) || ! function_exists( 'acf_update_taxonomy' ) || ! function_exists( 'acf_update_field_group' ) ) {
			return;
		}

		// 1. Sync Post Type "team-member"
		if ( ! acf_get_post_type( 'team-member' ) ) {
			$post_type = [
				'key' => 'post_type_souvik_team_member',
				'title' => 'Team Members',
				'post_type' => 'team-member',
				'active' => true,
				'public' => true,
				'show_ui' => true,
				'show_in_menu' => true,
				'show_in_nav_menus' => false,
				'show_in_rest' => true,
				'supports' => [ 'title', 'editor', 'excerpt', 'thumbnail' ],
				'menu_icon' => 'dashicons-groups',
				'rewrite' => [
					'permalink_structure' => '/team-member/%post_id%/',
					'slug' => 'team-member',
					'with_front' => true,
					'feeds' => false,
					'pages' => true,
				],
				'labels' => [
					'name' => 'Team Members',
					'singular_name' => 'Team Member',
					'menu_name' => 'Team Members',
					'name_admin_bar' => 'Team Member',
					'add_new' => 'Add New',
					'add_new_item' => 'Add New Team Member',
					'new_item' => 'New Team Member',
					'edit_item' => 'Edit Team Member',
					'view_item' => 'View Team Member',
					'all_items' => 'All Team Members',
					'search_items' => 'Search Team Members',
					'not_found' => 'No team members found.',
					'not_found_in_trash' => 'No team members found in Trash.',
				],
			];
			if ( function_exists( 'acf_validate_post_type' ) ) {
				$post_type = acf_validate_post_type( $post_type );
			}
			acf_update_post_type( $post_type );
			if ( function_exists( 'acf_flush_post_type_cache' ) ) {
				acf_flush_post_type_cache( $post_type );
			}
		}

		// 2. Sync Taxonomy
		$tax_slug = get_option( 'souvik_ws_team_taxonomy_slug', 'department' );
		$tax_label_plural = get_option( 'souvik_ws_team_taxonomy_label', 'Departments' );
		$tax_label_singular = get_option( 'souvik_ws_team_taxonomy_singular_label', 'Department' );

		if ( ! acf_get_taxonomy( $tax_slug ) ) {
			$taxonomy = [
				'key' => 'taxonomy_souvik_team_dept',
				'title' => $tax_label_plural,
				'taxonomy' => $tax_slug,
				'active' => true,
				'hierarchical' => true,
				'post_types' => [ 'team-member' ],
				'show_ui' => true,
				'show_in_menu' => true,
				'show_in_nav_menus' => false,
				'show_admin_column' => true,
				'show_in_rest' => true,
				'rewrite' => [
					'slug' => $tax_slug,
					'with_front' => true,
					'hierarchical' => true,
				],
				'labels' => [
					'name' => $tax_label_plural,
					'singular_name' => $tax_label_singular,
					'search_items' => 'Search ' . $tax_label_plural,
					'all_items' => 'All ' . $tax_label_plural,
					'parent_item' => 'Parent ' . $tax_label_singular,
					'parent_item_colon' => 'Parent ' . $tax_label_singular . ':',
					'edit_item' => 'Edit ' . $tax_label_singular,
					'update_item' => 'Update ' . $tax_label_singular,
					'add_new_item' => 'Add New ' . $tax_label_singular,
					'new_item_name' => 'New ' . $tax_label_singular . ' Name',
					'menu_name' => $tax_label_plural,
				],
			];
			if ( function_exists( 'acf_validate_taxonomy' ) ) {
				$taxonomy = acf_validate_taxonomy( $taxonomy );
			}
			acf_update_taxonomy( $taxonomy );
			if ( function_exists( 'acf_flush_taxonomy_cache' ) ) {
				acf_flush_taxonomy_cache( $taxonomy );
			}
		}

		// 3. Sync Field Group
		if ( ! acf_get_field_group( 'group_souvik_team_member' ) ) {
			$field_group = [
				'key' => 'group_souvik_team_member',
				'title' => 'Team Member',
				'fields' => [
					[
						'key' => 'field_souvik_member_name',
						'label' => 'Name',
						'name' => 'member_name',
						'type' => 'text',
						'required' => 0,
					],
					[
						'key' => 'field_souvik_member_designation',
						'label' => 'Designation',
						'name' => 'member_designation',
						'type' => 'text',
						'required' => 0,
					],
					[
						'key' => 'field_souvik_member_department',
						'label' => 'Department',
						'name' => 'member_department',
						'type' => 'text',
						'required' => 0,
					],
					[
						'key' => 'field_souvik_member_short_bio',
						'label' => 'Short Bio',
						'name' => 'member_short_bio',
						'type' => 'textarea',
						'required' => 0,
					],
					[
						'key' => 'field_souvik_member_photo',
						'label' => 'Photo',
						'name' => 'member_photo',
						'type' => 'image',
						'return_format' => 'array',
						'preview_size' => 'medium',
						'library' => 'all',
					],
					[
						'key' => 'field_souvik_member_social_links',
						'label' => 'Social Links',
						'name' => 'member_social_links',
						'type' => 'repeater',
						'sub_fields' => [
							[
								'key' => 'field_souvik_social_platform',
								'label' => 'Platform',
								'name' => 'platform',
								'type' => 'text',
							],
							[
								'key' => 'field_souvik_social_url',
								'label' => 'URL',
								'name' => 'url',
								'type' => 'url',
							],
							[
								'key' => 'field_souvik_social_icon_source',
								'label' => 'Icon Source',
								'name' => 'icon_source',
								'type' => 'select',
								'choices' => [
									'default' => 'Default',
									'custom' => 'Custom',
								],
								'default_value' => 'default',
							],
							[
								'key' => 'field_souvik_social_custom_icon',
								'label' => 'Custom Icon',
								'name' => 'custom_icon',
								'type' => 'text',
							],
							[
								'key' => 'field_souvik_social_custom_image',
								'label' => 'Custom Image',
								'name' => 'custom_image',
								'type' => 'image',
								'return_format' => 'array',
							],
						],
						'min' => 0,
						'max' => 0,
					],
					[
						'key' => 'field_souvik_member_button_text',
						'label' => 'Button Label',
						'name' => 'member_button_text',
						'type' => 'text',
					],
					[
						'key' => 'field_souvik_member_button_url',
						'label' => 'Button URL',
						'name' => 'member_button_url',
						'type' => 'url',
					],
				],
				'location' => [
					[
						[
							'param' => 'post_type',
							'operator' => '==',
							'value' => 'team-member',
						],
					],
				],
				'menu_order' => 0,
				'position' => 'normal',
				'style' => 'default',
				'label_placement' => 'top',
				'instruction_placement' => 'label',
				'hide_on_screen' => '',
				'active' => true,
				'description' => 'Team member fields used by the Souvik WS Team Showcase widget.',
			];
			if ( function_exists( 'acf_validate_field_group' ) ) {
				$field_group = acf_validate_field_group( $field_group );
			}
			acf_update_field_group( $field_group );
		}
	}

	/**
	 * Registers the Showcase Settings submenu under the Team Members menu.
	 */
	public function register_settings_page(): void {
		add_submenu_page(
			'edit.php?post_type=team-member',
			esc_html__( 'Showcase Settings', 'souvik-ws-team-showcase' ),
			esc_html__( 'Settings', 'souvik-ws-team-showcase' ),
			'manage_options',
			'souvik-ws-team-settings',
			[ $this, 'render_settings_page_html' ]
		);
	}

	/**
	 * Handles saving settings from the Showcase Settings page.
	 */
	public function save_settings_page(): void {
		if ( ! is_admin() || ! current_user_can( 'manage_options' ) ) {
			return;
		}

		if ( ! isset( $_POST['souvik_ws_team_settings_nonce'] ) || ! wp_verify_nonce( $_POST['souvik_ws_team_settings_nonce'], 'souvik_ws_team_settings_save' ) ) {
			return;
		}

		// Save Custom Taxonomy Settings
		if ( isset( $_POST['taxonomy_slug'] ) ) {
			$old_slug = get_option( 'souvik_ws_team_taxonomy_slug', 'department' );
			$new_slug = sanitize_key( $_POST['taxonomy_slug'] ) ?: 'department';
			update_option( 'souvik_ws_team_taxonomy_slug', $new_slug );

			// If slug changed, delete old ACF taxonomy to prevent duplication/orphans
			if ( $old_slug !== $new_slug && function_exists( 'acf_get_taxonomy' ) && function_exists( 'acf_delete_taxonomy' ) ) {
				$old_tax = acf_get_taxonomy( $old_slug );
				if ( $old_tax ) {
					acf_delete_taxonomy( $old_slug );
				}
			}
		}

		if ( isset( $_POST['taxonomy_label'] ) ) {
			update_option( 'souvik_ws_team_taxonomy_label', sanitize_text_field( $_POST['taxonomy_label'] ) ?: 'Departments' );
		}

		if ( isset( $_POST['taxonomy_singular_label'] ) ) {
			update_option( 'souvik_ws_team_taxonomy_singular_label', sanitize_text_field( $_POST['taxonomy_singular_label'] ) ?: 'Department' );
		}

		// Save Custom Social Brand SVG Overrides
		if ( isset( $_POST['custom_icons'] ) && is_array( $_POST['custom_icons'] ) ) {
			$sanitized_icons = [];
			foreach ( $_POST['custom_icons'] as $platform => $svg_markup ) {
				$sanitized_icons[ sanitize_key( $platform ) ] = wp_kses( $svg_markup, [
					'svg'  => [ 'xmlns' => true, 'viewbox' => true, 'width' => true, 'height' => true, 'fill' => true, 'stroke' => true ],
					'path' => [ 'd' => true, 'fill' => true, 'stroke' => true ],
				] );
			}
			update_option( 'souvik_ws_team_custom_icons', $sanitized_icons );
		}

		// Sync settings back to ACF Database immediately if function exists
		if ( function_exists( 'acf_update_taxonomy' ) ) {
			$new_slug = get_option( 'souvik_ws_team_taxonomy_slug', 'department' );
			$tax_label_plural = get_option( 'souvik_ws_team_taxonomy_label', 'Departments' );
			$tax_label_singular = get_option( 'souvik_ws_team_taxonomy_singular_label', 'Department' );

			$taxonomy_data = [
				'key' => 'taxonomy_souvik_team_dept',
				'title' => $tax_label_plural,
				'taxonomy' => $new_slug,
				'active' => true,
				'hierarchical' => true,
				'post_types' => [ 'team-member' ],
				'show_ui' => true,
				'show_in_menu' => true,
				'show_in_nav_menus' => false,
				'show_admin_column' => true,
				'show_in_rest' => true,
				'rewrite' => [
					'slug' => $new_slug,
					'with_front' => true,
					'hierarchical' => true,
				],
				'labels' => [
					'name' => $tax_label_plural,
					'singular_name' => $tax_label_singular,
					'search_items' => 'Search ' . $tax_label_plural,
					'all_items' => 'All ' . $tax_label_plural,
					'parent_item' => 'Parent ' . $tax_label_singular,
					'parent_item_colon' => 'Parent ' . $tax_label_singular . ':',
					'edit_item' => 'Edit ' . $tax_label_singular,
					'update_item' => 'Update ' . $tax_label_singular,
					'add_new_item' => 'Add New ' . $tax_label_singular,
					'new_item_name' => 'New ' . $tax_label_singular . ' Name',
					'menu_name' => $tax_label_plural,
				],
			];
			if ( function_exists( 'acf_validate_taxonomy' ) ) {
				$taxonomy_data = acf_validate_taxonomy( $taxonomy_data );
			}
			acf_update_taxonomy( $taxonomy_data );
			if ( function_exists( 'acf_flush_taxonomy_cache' ) ) {
				acf_flush_taxonomy_cache( $taxonomy_data );
			}
		}

		flush_rewrite_rules();

		wp_safe_redirect( add_query_arg( 'settings-updated', 'true', wp_get_referer() ) );
		exit;
	}

	/**
	 * Renders the HTML for the Showcase Settings page.
	 */
	public function render_settings_page_html(): void {
		$tax_slug = get_option( 'souvik_ws_team_taxonomy_slug', 'department' );
		$tax_label_plural = get_option( 'souvik_ws_team_taxonomy_label', 'Departments' );
		$tax_label_singular = get_option( 'souvik_ws_team_taxonomy_singular_label', 'Department' );
		$custom_icons = get_option( 'souvik_ws_team_custom_icons', [] );

		$platforms = [
			'facebook'  => 'Facebook',
			'twitter'   => 'Twitter / X',
			'linkedin'  => 'LinkedIn',
			'instagram' => 'Instagram',
			'youtube'   => 'YouTube',
			'github'    => 'GitHub',
			'dribbble'  => 'Dribbble',
			'behance'   => 'Behance',
		];
		?>
		<div class="wrap souvik-ws-settings-wrap" style="max-width: 900px; margin: 30px auto; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;">
			<h1 style="display:none;"></h1>
			
			<?php if ( isset( $_GET['settings-updated'] ) ) : ?>
				<div class="notice notice-success is-dismissible" style="border-left-color: #4f46e5; border-radius: 6px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);">
					<p style="font-weight: 600; color: #1e1b4b; margin: 8px 0;"><?php esc_html_e( 'Settings updated and rewrite rules flushed successfully!', 'souvik-ws-team-showcase' ); ?></p>
				</div>
			<?php endif; ?>

			<div class="souvik-ws-settings-header" style="background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%); padding: 30px; border-radius: 12px; color: #fff; margin-bottom: 24px; box-shadow: 0 10px 15px -3px rgba(79, 70, 229, 0.2);">
				<h2 style="margin: 0; font-size: 28px; font-weight: 700; color: #fff;"><?php esc_html_e( 'Souvik WS Team Showcase Settings', 'souvik-ws-team-showcase' ); ?></h2>
				<p style="margin: 8px 0 0; opacity: 0.9; font-size: 14px;"><?php esc_html_e( 'Configure your showcase custom taxonomy and override global brand SVGs.', 'souvik-ws-team-showcase' ); ?></p>
			</div>

			<form method="post" action="" style="background: #fff; padding: 30px; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); border: 1px solid #e5e7eb;">
				<?php wp_nonce_field( 'souvik_ws_team_settings_save', 'souvik_ws_team_settings_nonce' ); ?>

				<h3 style="margin-top: 0; padding-bottom: 12px; border-bottom: 2px solid #f3f4f6; font-size: 18px; font-weight: 600; color: #1f2937;"><?php esc_html_e( '1. Custom Taxonomy Configuration', 'souvik-ws-team-showcase' ); ?></h3>
				<p style="font-size: 13px; color: #6b7280; margin-bottom: 20px;">
					<?php esc_html_e( 'By default, team members are categorized using the "department" taxonomy. If you wish to use a different taxonomy (e.g. "team-groups", "role-categories"), you can change it here. This taxonomy registers against the Team Member CPT and will automatically synchronize inside the ACF Post Type dashboard and the Elementor Query controls.', 'souvik-ws-team-showcase' ); ?>
				</p>

				<table class="form-table" role="presentation" style="margin-bottom: 30px; width: 100%;">
					<tr>
						<th scope="row" style="padding: 15px 0; width: 220px; font-weight: 600; color: #4b5563;"><label for="taxonomy_slug"><?php esc_html_e( 'Taxonomy Slug', 'souvik-ws-team-showcase' ); ?></label></th>
						<td style="padding: 15px 0;">
							<input name="taxonomy_slug" type="text" id="taxonomy_slug" value="<?php echo esc_attr( $tax_slug ); ?>" class="regular-text" style="border: 1px solid #d1d5db; border-radius: 6px; padding: 8px 12px; font-family: monospace;" required />
							<p class="description" style="margin-top: 5px; color: #9ca3af; font-size: 12px;"><?php esc_html_e( 'Use lowercase alphanumeric characters and underscores only. Recommended: department or team-group', 'souvik-ws-team-showcase' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row" style="padding: 15px 0; font-weight: 600; color: #4b5563;"><label for="taxonomy_label"><?php esc_html_e( 'Plural Label', 'souvik-ws-team-showcase' ); ?></label></th>
						<td style="padding: 15px 0;">
							<input name="taxonomy_label" type="text" id="taxonomy_label" value="<?php echo esc_attr( $tax_label_plural ); ?>" class="regular-text" style="border: 1px solid #d1d5db; border-radius: 6px; padding: 8px 12px;" required />
						</td>
					</tr>
					<tr>
						<th scope="row" style="padding: 15px 0; font-weight: 600; color: #4b5563;"><label for="taxonomy_singular_label"><?php esc_html_e( 'Singular Label', 'souvik-ws-team-showcase' ); ?></label></th>
						<td style="padding: 15px 0;">
							<input name="taxonomy_singular_label" type="text" id="taxonomy_singular_label" value="<?php echo esc_attr( $tax_label_singular ); ?>" class="regular-text" style="border: 1px solid #d1d5db; border-radius: 6px; padding: 8px 12px;" required />
						</td>
					</tr>
				</table>

				<h3 style="padding-top: 15px; margin-top: 30px; padding-bottom: 12px; border-bottom: 2px solid #f3f4f6; font-size: 18px; font-weight: 600; color: #1f2937;"><?php esc_html_e( '2. Global Brand SVG Overrides', 'souvik-ws-team-showcase' ); ?></h3>
				<p style="font-size: 13px; color: #6b7280; margin-bottom: 20px;">
					<?php esc_html_e( 'Customize the default SVG icons for each brand globally. To override the default icon (including the fixed GitHub logo), paste the custom inline SVG tag below (containing path definitions and appropriate viewBox dimensions). Leaving it blank will fall back to the built-in, highly refined SVGs.', 'souvik-ws-team-showcase' ); ?>
				</p>

				<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px;">
					<?php foreach ( $platforms as $key => $name ) : ?>
						<?php $val = $custom_icons[ $key ] ?? ''; ?>
						<div style="background: #f9fafb; padding: 15px; border-radius: 8px; border: 1px solid #f3f4f6;">
							<label for="icon_<?php echo esc_attr( $key ); ?>" style="display: block; font-weight: 600; color: #374151; margin-bottom: 8px;">
								<?php echo esc_html( $name ); ?> <?php esc_html_e( 'Default SVG', 'souvik-ws-team-showcase' ); ?>
							</label>
							<textarea name="custom_icons[<?php echo esc_attr( $key ); ?>]" id="icon_<?php echo esc_attr( $key ); ?>" rows="3" style="width: 100%; font-family: monospace; font-size: 11px; border: 1px solid #d1d5db; border-radius: 6px; padding: 8px; box-sizing: border-box;" placeholder="e.g. &lt;svg viewBox=&quot;0 0 512 512&quot;&gt;&lt;path d=&quot;...&quot;/&gt;&lt;/svg&gt;"><?php echo esc_textarea( $val ); ?></textarea>
						</div>
					<?php endforeach; ?>
				</div>

				<div style="padding-top: 20px; border-top: 1px solid #f3f4f6; display: flex; justify-content: flex-end;">
					<button type="submit" class="button button-primary" style="background: #4f46e5; border-color: #4338ca; font-weight: 600; padding: 6px 20px; height: auto; border-radius: 6px; font-size: 14px; box-shadow: 0 4px 6px -1px rgba(79,70,229,0.2);">
						<?php esc_html_e( 'Save Showcase Settings', 'souvik-ws-team-showcase' ); ?>
					</button>
				</div>
			</form>
		</div>
		<?php
	}
}

register_activation_hook( __FILE__, [ Souvik_WS_Team_Showcase_Plugin::class, 'activate' ] );
register_deactivation_hook( __FILE__, [ Souvik_WS_Team_Showcase_Plugin::class, 'deactivate' ] );

Souvik_WS_Team_Showcase_Plugin::get_instance();
