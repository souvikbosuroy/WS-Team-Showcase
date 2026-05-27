<?php
/**
 * Elementor widget — controls registration and front-end render.
 *
 * Follows native Elementor Pro patterns exactly:
 *  - Controls grouped by what they affect.
 *  - State-dependent controls use condition arrays.
 *  - Normal/Hover pairs use start_controls_tabs().
 *  - Style tab mirrors Content tab structure.
 *
 * @package Souvik_WS_Team_Showcase
 * @since   2.0.0
 */

defined( 'ABSPATH' ) || exit;

use Elementor\Controls_Manager;
use Elementor\Widget_Base;
use Elementor\Repeater;
use Elementor\Group_Control_Background;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Box_Shadow;

/**
 * Souvik WS Team Showcase Elementor widget.
 */
class Souvik_WS_Team_Showcase_Widget extends Widget_Base {

	// -----------------------------------------------------------------------
	// Identity
	// -----------------------------------------------------------------------

	public function get_name(): string {
		return 'souvik-ws-team-showcase';
	}

	public function get_title(): string {
		return esc_html__( 'Souvik WS Team Showcase', 'souvik-ws-team-showcase' );
	}

	public function get_icon(): string {
		return 'eicon-person';
	}

	public function get_categories(): array {
		return [ 'general' ];
	}

	public function get_keywords(): array {
		return [ 'team', 'members', 'staff', 'showcase', 'acf' ];
	}

	public function get_style_depends(): array {
		return [ 'souvik-ws-team-css' ];
	}

	public function get_script_depends(): array {
		// Base JS always needed; GSAP modules added conditionally in render().
		return [ 'souvik-ws-team-js' ];
	}

	// -----------------------------------------------------------------------
	// Private helpers
	// -----------------------------------------------------------------------

	/** All 10 animatable element keys. */
	private const ELEM_KEYS = [
		'card', 'image', 'name', 'designation',
		'badge', 'bio', 'socials', 'button', 'filter', 'popup',
	];

	private function get_acf_fields_groups(): array {
		$groups = [
			[
				'label' => esc_html__( 'Select Dynamic Field...', 'souvik-ws-team-showcase' ),
				'value' => '',
			],
		];

		// 1. WordPress Native Fields
		$groups[] = [
			'label'   => esc_html__( 'WordPress Native Fields', 'souvik-ws-team-showcase' ),
			'options' => [
				'post_title'     => esc_html__( 'Post Title (Name)', 'souvik-ws-team-showcase' ),
				'post_content'   => esc_html__( 'Post Content (Bio)', 'souvik-ws-team-showcase' ),
				'post_excerpt'   => esc_html__( 'Post Excerpt (Short Bio)', 'souvik-ws-team-showcase' ),
				'post_permalink' => esc_html__( 'Post Permalink (URL)', 'souvik-ws-team-showcase' ),
				'post_thumbnail' => esc_html__( 'Post Featured Image (Photo)', 'souvik-ws-team-showcase' ),
				'post_date'      => esc_html__( 'Post Date', 'souvik-ws-team-showcase' ),
				'post_author'    => esc_html__( 'Author Display Name', 'souvik-ws-team-showcase' ),
				'post_id'        => esc_html__( 'Post ID', 'souvik-ws-team-showcase' ),
			],
		];

		// 2. ACF Grouping Matrix
		if ( function_exists( 'acf_get_field_groups' ) ) {
			$field_groups = acf_get_field_groups();
			if ( ! empty( $field_groups ) && is_array( $field_groups ) ) {
				foreach ( $field_groups as $group ) {
					$fields = acf_get_fields( $group['key'] );
					if ( ! empty( $fields ) && is_array( $fields ) ) {
						$group_options = [];
						foreach ( $fields as $field ) {
							if ( ! empty( $field['name'] ) ) {
								$label = ! empty( $field['label'] ) ? $field['label'] : $field['name'];
								$group_options[ $field['name'] ] = esc_html( $label );
							}
						}

						if ( ! empty( $group_options ) ) {
							$groups[] = [
								'label'   => esc_html__( 'ACF: ', 'souvik-ws-team-showcase' ) . $group['title'],
								'options' => $group_options,
							];
						}
					}
				}
			}
		}

		// 3. Custom Database Meta Option
		$groups[] = [
			'label'   => esc_html__( 'Custom Database Key', 'souvik-ws-team-showcase' ),
			'options' => [
				'custom_meta_key' => esc_html__( 'Custom Meta / Database Key...', 'souvik-ws-team-showcase' ),
			],
		];

		return $groups;
	}



	// -----------------------------------------------------------------------
	// Controls registration
	// -----------------------------------------------------------------------

	protected function register_controls(): void {

		// ===================================================================
		//  CONTENT TAB
		// ===================================================================

		// -------------------------------------------------------------------
		// Data Source switch — top-level section, always visible.
		// -------------------------------------------------------------------
		$this->start_controls_section(
			'section_data_source',
			[
				'label' => esc_html__( 'Data Source', 'souvik-ws-team-showcase' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			]
		);

		$this->add_control(
			'data_source_mode',
			[
				'label'   => esc_html__( 'Source', 'souvik-ws-team-showcase' ),
				'type'    => Controls_Manager::SELECT,
				'options' => [
					'manual'  => esc_html__( 'Manual', 'souvik-ws-team-showcase' ),
					'dynamic' => esc_html__( 'Dynamic (ACF)', 'souvik-ws-team-showcase' ),
				],
				'default' => 'manual',
			]
		);

		$this->end_controls_section();

		// -------------------------------------------------------------------
		// Team Members repeater (Manual mode only).
		// -------------------------------------------------------------------
		$this->start_controls_section(
			'section_team_members',
			[
				'label'     => esc_html__( 'Team Members', 'souvik-ws-team-showcase' ),
				'tab'       => Controls_Manager::TAB_CONTENT,
				'condition' => [ 'data_source_mode' => 'manual' ],
			]
		);

		$socials_repeater = new Repeater();
		$socials_repeater->add_control(
			'platform',
			[
				'label'   => esc_html__( 'Platform', 'souvik-ws-team-showcase' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'linkedin',
				'options' => [
					'facebook'  => 'Facebook',
					'twitter'   => 'Twitter / X',
					'linkedin'  => 'LinkedIn',
					'instagram' => 'Instagram',
					'youtube'   => 'YouTube',
					'github'    => 'GitHub',
					'dribbble'  => 'Dribbble',
					'behance'   => 'Behance',
				],
			]
		);
		$socials_repeater->add_control(
			'icon_source',
			[
				'label'   => esc_html__( 'Icon Source', 'souvik-ws-team-showcase' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'default',
				'options' => [
					'default' => esc_html__( 'Platform Default', 'souvik-ws-team-showcase' ),
					'icon'    => esc_html__( 'Elementor Icon Library', 'souvik-ws-team-showcase' ),
					'image'   => esc_html__( 'Custom Image / SVG', 'souvik-ws-team-showcase' ),
				],
			]
		);
		$socials_repeater->add_control(
			'custom_icon',
			[
				'label'     => esc_html__( 'Custom Icon', 'souvik-ws-team-showcase' ),
				'type'      => Controls_Manager::ICONS,
				'default'   => [
					'value'   => 'fab fa-linkedin',
					'library' => 'fa-brands',
				],
				'condition' => [ 'icon_source' => 'icon' ],
			]
		);
		$socials_repeater->add_control(
			'custom_image',
			[
				'label'     => esc_html__( 'Upload Custom Image / SVG', 'souvik-ws-team-showcase' ),
				'type'      => Controls_Manager::MEDIA,
				'default'   => [
					'url' => '',
				],
				'condition' => [ 'icon_source' => 'image' ],
			]
		);
		$socials_repeater->add_control(
			'url',
			[
				'label'         => esc_html__( 'URL', 'souvik-ws-team-showcase' ),
				'type'          => Controls_Manager::URL,
				'show_external' => true,
				'default'       => [ 'url' => '' ],
			]
		);

		$member_repeater = new Repeater();

		$member_repeater->add_control(
			'photo',
			[
				'label' => esc_html__( 'Photo', 'souvik-ws-team-showcase' ),
				'type'  => Controls_Manager::MEDIA,
			]
		);

		$member_repeater->add_control(
			'name',
			[
				'label'   => esc_html__( 'Name', 'souvik-ws-team-showcase' ),
				'type'    => Controls_Manager::TEXT,
				'default' => esc_html__( 'Jane Doe', 'souvik-ws-team-showcase' ),
			]
		);

		$member_repeater->add_control(
			'designation',
			[
				'label'   => esc_html__( 'Designation', 'souvik-ws-team-showcase' ),
				'type'    => Controls_Manager::TEXT,
				'default' => esc_html__( 'Product Lead', 'souvik-ws-team-showcase' ),
			]
		);

		$member_repeater->add_control(
			'department',
			[
				'label'   => esc_html__( 'Department', 'souvik-ws-team-showcase' ),
				'type'    => Controls_Manager::TEXT,
				'default' => esc_html__( 'Engineering', 'souvik-ws-team-showcase' ),
			]
		);

		$member_repeater->add_control(
			'bio',
			[
				'label'   => esc_html__( 'Short Bio', 'souvik-ws-team-showcase' ),
				'type'    => Controls_Manager::TEXTAREA,
				'rows'    => 3,
				'default' => esc_html__( 'A short bio about this team member goes here.', 'souvik-ws-team-showcase' ),
			]
		);

		$member_repeater->add_control(
			'socials',
			[
				'label'       => esc_html__( 'Social Links', 'souvik-ws-team-showcase' ),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $socials_repeater->get_controls(),
				'title_field' => '{{{ platform }}}',
			]
		);

		$member_repeater->add_control(
			'button_label',
			[
				'label'   => esc_html__( 'Button Label', 'souvik-ws-team-showcase' ),
				'type'    => Controls_Manager::TEXT,
				'default' => esc_html__( 'View Profile', 'souvik-ws-team-showcase' ),
			]
		);

		$member_repeater->add_control(
			'button_url',
			[
				'label'         => esc_html__( 'Button URL', 'souvik-ws-team-showcase' ),
				'type'          => Controls_Manager::URL,
				'show_external' => true,
				'default'       => [ 'url' => '#' ],
			]
		);

		$this->add_control(
			'team_members',
			[
				'label'              => esc_html__( 'Members', 'souvik-ws-team-showcase' ),
				'type'               => Controls_Manager::REPEATER,
				'fields'             => $member_repeater->get_controls(),
				'title_field'        => '{{{ name }}}',
				'frontend_available' => true,
				'default'            => [
					[
						'name'        => 'Alex Rivera',
						'designation' => 'CEO & Co-founder',
						'department'  => 'Leadership',
						'bio'         => 'Visionary leader with 15+ years driving product strategy and global growth.',
						'button_label'=> 'View Profile',
					],
					[
						'name'        => 'Morgan Chen',
						'designation' => 'Head of Engineering',
						'department'  => 'Engineering',
						'bio'         => 'Full-stack architect passionate about scalable systems and developer experience.',
						'button_label'=> 'View Profile',
					],
					[
						'name'        => 'Sam Patel',
						'designation' => 'Lead Designer',
						'department'  => 'Design',
						'bio'         => 'Craft-first designer obsessed with pixel-perfect interfaces and accessibility.',
						'button_label'=> 'View Profile',
					],
				],
			]
		);

		$this->end_controls_section();

		// -------------------------------------------------------------------
		// Layout — always visible.
		// -------------------------------------------------------------------
		$this->start_controls_section(
			'section_layout',
			[
				'label' => esc_html__( 'Layout', 'souvik-ws-team-showcase' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			]
		);

		$this->add_control(
			'columns',
			[
				'label'   => esc_html__( 'Columns', 'souvik-ws-team-showcase' ),
				'type'    => Controls_Manager::CHOOSE,
				'default' => '3',
				'options' => [
					'1' => [ 'title' => '1', 'icon' => 'eicon-columns' ],
					'2' => [ 'title' => '2', 'icon' => 'eicon-columns' ],
					'3' => [ 'title' => '3', 'icon' => 'eicon-columns' ],
					'4' => [ 'title' => '4', 'icon' => 'eicon-columns' ],
					'5' => [ 'title' => '5', 'icon' => 'eicon-columns' ],
					'6' => [ 'title' => '6', 'icon' => 'eicon-columns' ],
				],
			]
		);

		$this->add_control(
			'card_style',
			[
				'label'   => esc_html__( 'Card Style', 'souvik-ws-team-showcase' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'classic',
				'options' => [
					'classic' => esc_html__( 'Classic', 'souvik-ws-team-showcase' ),
					'overlay' => esc_html__( 'Overlay', 'souvik-ws-team-showcase' ),
					'minimal' => esc_html__( 'Minimal', 'souvik-ws-team-showcase' ),
				],
			]
		);

		$this->add_control(
			'image_position',
			[
				'label'   => esc_html__( 'Image Position', 'souvik-ws-team-showcase' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'top',
				'options' => [
					'top'   => esc_html__( 'Top', 'souvik-ws-team-showcase' ),
					'left'  => esc_html__( 'Left', 'souvik-ws-team-showcase' ),
					'right' => esc_html__( 'Right', 'souvik-ws-team-showcase' ),
				],
			]
		);

		$this->add_control(
			'equal_height',
			[
				'label'   => esc_html__( 'Equal Height Cards', 'souvik-ws-team-showcase' ),
				'type'    => Controls_Manager::SWITCHER,
				'default' => 'yes',
			]
		);

		$this->add_control(
			'column_gap',
			[
				'label'   => esc_html__( 'Column Gap', 'souvik-ws-team-showcase' ),
				'type'    => Controls_Manager::SLIDER,
				'default' => [ 'size' => 30 ],
				'range'   => [ 'px' => [ 'min' => 0, 'max' => 80, 'step' => 2 ] ],
				'selectors' => [
					'{{WRAPPER}} .souvik-ws-team-grid' => '--souvik-ws-col-gap: {{SIZE}}px;',
				],
			]
		);

		$this->add_control(
			'row_gap',
			[
				'label'   => esc_html__( 'Row Gap', 'souvik-ws-team-showcase' ),
				'type'    => Controls_Manager::SLIDER,
				'default' => [ 'size' => 30 ],
				'range'   => [ 'px' => [ 'min' => 0, 'max' => 80, 'step' => 2 ] ],
				'selectors' => [
					'{{WRAPPER}} .souvik-ws-team-grid' => '--souvik-ws-row-gap: {{SIZE}}px;',
				],
			]
		);

		$this->add_control(
			'grid_align_self_heading',
			[
				'label'     => esc_html__( 'Card Grid Self-Alignment', 'souvik-ws-team-showcase' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->add_responsive_control(
			'card_align_self',
			[
				'label'     => esc_html__( 'Align Self (Row-axis)', 'souvik-ws-team-showcase' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => '',
				'options'   => [
					''           => esc_html__( 'Default', 'souvik-ws-team-showcase' ),
					'auto'        => 'auto',
					'flex-start'  => 'flex-start',
					'center'      => 'center',
					'flex-end'    => 'flex-end',
					'stretch'     => 'stretch',
					'baseline'    => 'baseline',
				],
				'selectors' => [
					'{{WRAPPER}} .souvik-ws-team-card' => 'align-self: {{VALUE}};',
				],
			]
		);

		$this->add_responsive_control(
			'card_justify_self',
			[
				'label'     => esc_html__( 'Justify Self (Column-axis)', 'souvik-ws-team-showcase' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => '',
				'options'   => [
					''           => esc_html__( 'Default', 'souvik-ws-team-showcase' ),
					'auto'        => 'auto',
					'start'       => 'start',
					'center'      => 'center',
					'end'         => 'end',
					'stretch'     => 'stretch',
				],
				'selectors' => [
					'{{WRAPPER}} .souvik-ws-team-card' => 'justify-self: {{VALUE}};',
				],
			]
		);

		$this->end_controls_section();

		// -------------------------------------------------------------------
		// Filter Bar.
		// -------------------------------------------------------------------
		$this->start_controls_section(
			'section_filter_bar',
			[
				'label' => esc_html__( 'Filter Bar', 'souvik-ws-team-showcase' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			]
		);

		$this->add_control(
			'filter_show',
			[
				'label'   => esc_html__( 'Show Filter Bar', 'souvik-ws-team-showcase' ),
				'type'    => Controls_Manager::SWITCHER,
				'default' => 'no',
			]
		);

		$this->add_control(
			'filter_style',
			[
				'label'     => esc_html__( 'Filter Style', 'souvik-ws-team-showcase' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'pills',
				'options'   => [
					'pills'    => esc_html__( 'Pills', 'souvik-ws-team-showcase' ),
					'tabs'     => esc_html__( 'Tabs', 'souvik-ws-team-showcase' ),
					'dropdown' => esc_html__( 'Dropdown', 'souvik-ws-team-showcase' ),
				],
				'condition' => [ 'filter_show' => 'yes' ],
			]
		);

		$this->add_control(
			'filter_all_label',
			[
				'label'     => esc_html__( '"All" Label', 'souvik-ws-team-showcase' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => esc_html__( 'All', 'souvik-ws-team-showcase' ),
				'condition' => [ 'filter_show' => 'yes' ],
			]
		);

		$this->add_control(
			'filter_sticky',
			[
				'label'       => esc_html__( 'Sticky Filter Bar', 'souvik-ws-team-showcase' ),
				'type'        => Controls_Manager::SWITCHER,
				'label_on'    => esc_html__( 'Yes', 'souvik-ws-team-showcase' ),
				'label_off'   => esc_html__( 'No', 'souvik-ws-team-showcase' ),
				'return_value'=> 'yes',
				'default'     => 'no',
				'description' => esc_html__( 'Sticks the filter bar to the top of the viewport while scrolling within the widget area.', 'souvik-ws-team-showcase' ),
				'condition'   => [ 'filter_show' => 'yes' ],
			]
		);

		$this->end_controls_section();

		// -------------------------------------------------------------------
		// Popup / Modal.
		// -------------------------------------------------------------------
		$this->start_controls_section(
			'section_popup',
			[
				'label' => esc_html__( 'Popup / Modal', 'souvik-ws-team-showcase' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			]
		);

		$this->add_control(
			'popup_enable',
			[
				'label'   => esc_html__( 'Enable Popup Modal', 'souvik-ws-team-showcase' ),
				'type'    => Controls_Manager::SWITCHER,
				'default' => 'no',
			]
		);

		$this->add_control(
			'popup_trigger',
			[
				'label'     => esc_html__( 'Open on', 'souvik-ws-team-showcase' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'card_click',
				'options'   => [
					'card_click' => esc_html__( 'Card Click', 'souvik-ws-team-showcase' ),
					'button'     => esc_html__( 'Button Click', 'souvik-ws-team-showcase' ),
				],
				'condition' => [ 'popup_enable' => 'yes' ],
			]
		);

		$this->add_control(
			'popup_size',
			[
				'label'     => esc_html__( 'Popup Size', 'souvik-ws-team-showcase' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'md',
				'options'   => [
					'sm'         => esc_html__( 'Small', 'souvik-ws-team-showcase' ),
					'md'         => esc_html__( 'Medium', 'souvik-ws-team-showcase' ),
					'lg'         => esc_html__( 'Large', 'souvik-ws-team-showcase' ),
					'fullscreen' => esc_html__( 'Fullscreen', 'souvik-ws-team-showcase' ),
				],
				'condition' => [ 'popup_enable' => 'yes' ],
			]
		);

		$this->add_control(
			'popup_fields',
			[
				'label'     => esc_html__( 'Fields to show in Popup', 'souvik-ws-team-showcase' ),
				'type'      => Controls_Manager::SELECT2,
				'multiple'  => true,
				'default'   => [ 'name', 'designation', 'department', 'bio', 'socials', 'button' ],
				'options'   => [
					'name'        => esc_html__( 'Name', 'souvik-ws-team-showcase' ),
					'designation' => esc_html__( 'Designation', 'souvik-ws-team-showcase' ),
					'department'  => esc_html__( 'Department Badge', 'souvik-ws-team-showcase' ),
					'bio'         => esc_html__( 'Bio', 'souvik-ws-team-showcase' ),
					'socials'     => esc_html__( 'Socials', 'souvik-ws-team-showcase' ),
					'button'      => esc_html__( 'Button', 'souvik-ws-team-showcase' ),
				],
				'condition' => [ 'popup_enable' => 'yes' ],
			]
		);

		$this->end_controls_section();

		// -------------------------------------------------------------------
		// Load More / Pagination.
		// -------------------------------------------------------------------
		$this->start_controls_section(
			'section_pagination',
			[
				'label' => esc_html__( 'Load More / Pagination', 'souvik-ws-team-showcase' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			]
		);

		$this->add_control(
			'pagination_type',
			[
				'label'   => esc_html__( 'Pagination Type', 'souvik-ws-team-showcase' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'none',
				'options' => [
					'load_more' => esc_html__( 'Load More', 'souvik-ws-team-showcase' ),
					'numbers'   => esc_html__( 'Numbers', 'souvik-ws-team-showcase' ),
					'none'      => esc_html__( 'None', 'souvik-ws-team-showcase' ),
				],
			]
		);

		$this->add_control(
			'items_per_page',
			[
				'label'   => esc_html__( 'Items Per Page', 'souvik-ws-team-showcase' ),
				'type'    => Controls_Manager::SLIDER,
				'default' => [ 'size' => 6 ],
				'range'   => [ 'px' => [ 'min' => 1, 'max' => 24, 'step' => 1 ] ],
			]
		);

		$this->add_control(
			'load_more_label',
			[
				'label'     => esc_html__( 'Button Label', 'souvik-ws-team-showcase' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => esc_html__( 'Load More', 'souvik-ws-team-showcase' ),
				'condition' => [ 'pagination_type' => 'load_more' ],
			]
		);

		$this->end_controls_section();

		// -------------------------------------------------------------------
		// Query Settings (Dynamic mode only).
		// -------------------------------------------------------------------
		$this->start_controls_section(
			'section_query',
			[
				'label'     => esc_html__( 'Query Settings', 'souvik-ws-team-showcase' ),
				'tab'       => Controls_Manager::TAB_CONTENT,
				'condition' => [ 'data_source_mode' => 'dynamic' ],
			]
		);

		$post_types = get_post_types( [ 'public' => true ], 'objects' );
		$pt_options = [];
		foreach ( $post_types as $slug => $obj ) {
			$pt_options[ $slug ] = $obj->labels->singular_name ?? $slug;
		}

		$this->add_control(
			'query_post_type',
			[
				'label'   => esc_html__( 'Post Type', 'souvik-ws-team-showcase' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'team-member',
				'options' => $pt_options,
			]
		);

		$this->add_control(
			'query_posts_per_page',
			[
				'label'   => esc_html__( 'Posts Per Page (-1 for All)', 'souvik-ws-team-showcase' ),
				'type'    => Controls_Manager::SLIDER,
				'default' => [ 'size' => -1 ],
				'range'   => [ 'px' => [ 'min' => -1, 'max' => 100, 'step' => 1 ] ],
			]
		);

		$this->add_control(
			'query_orderby',
			[
				'label'   => esc_html__( 'Order By', 'souvik-ws-team-showcase' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'date',
				'options' => [
					'date'       => esc_html__( 'Date', 'souvik-ws-team-showcase' ),
					'title'      => esc_html__( 'Title', 'souvik-ws-team-showcase' ),
					'rand'       => esc_html__( 'Random', 'souvik-ws-team-showcase' ),
					'menu_order' => esc_html__( 'Menu Order', 'souvik-ws-team-showcase' ),
				],
			]
		);

		$this->add_control(
			'query_order',
			[
				'label'   => esc_html__( 'Order', 'souvik-ws-team-showcase' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'DESC',
				'options' => [
					'DESC' => esc_html__( 'Descending', 'souvik-ws-team-showcase' ),
					'ASC'  => esc_html__( 'Ascending', 'souvik-ws-team-showcase' ),
				],
			]
		);

		$this->add_control(
			'query_tax',
			[
				'label'       => esc_html__( 'Taxonomy', 'souvik-ws-team-showcase' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => get_option( 'souvik_ws_team_taxonomy_slug', 'department' ),
				'placeholder' => get_option( 'souvik_ws_team_taxonomy_slug', 'department' ),
			]
		);

		$this->add_control(
			'query_term',
			[
				'label'       => esc_html__( 'Term Slug', 'souvik-ws-team-showcase' ),
				'type'        => Controls_Manager::TEXT,
				'placeholder' => 'engineering',
			]
		);

		$this->end_controls_section();

		// -------------------------------------------------------------------
		// ACF / Dynamic Field Mapping (Dynamic mode only).
		// -------------------------------------------------------------------
		$this->start_controls_section(
			'section_acf_mapping',
			[
				'label'     => esc_html__( 'ACF / Dynamic Field Mapping', 'souvik-ws-team-showcase' ),
				'tab'       => Controls_Manager::TAB_CONTENT,
				'condition' => [ 'data_source_mode' => 'dynamic' ],
			]
		);

		$this->add_control(
			'acf_info_notice',
			[
				'type' => Controls_Manager::RAW_HTML,
				'raw'  => '<div style="background:#f0f4ff;border:1px solid #c5d2f6;border-radius:6px;padding:10px 12px;font-size:12px;line-height:1.6;">'
					. '<strong>' . esc_html__( 'Dynamic Field Selection', 'souvik-ws-team-showcase' ) . '</strong><br>'
					. esc_html__( 'Select the corresponding post field, ACF field, or type a custom meta key below.', 'souvik-ws-team-showcase' )
					. '</div>',
			]
		);

		$acf_fields = [
			'acf_name'         => [ 'label' => esc_html__( 'Name field key', 'souvik-ws-team-showcase' ), 'default' => 'post_title' ],
			'acf_designation'  => [ 'label' => esc_html__( 'Designation field key', 'souvik-ws-team-showcase' ), 'default' => 'member_designation' ],
			'acf_department'   => [ 'label' => esc_html__( 'Department field key', 'souvik-ws-team-showcase' ), 'default' => 'member_department' ],
			'acf_bio'          => [ 'label' => esc_html__( 'Bio field key', 'souvik-ws-team-showcase' ), 'default' => 'member_short_bio' ],
			'acf_photo'        => [ 'label' => esc_html__( 'Photo field key', 'souvik-ws-team-showcase' ), 'default' => 'member_photo' ],
			'acf_social_links' => [ 'label' => esc_html__( 'Social links field key', 'souvik-ws-team-showcase' ), 'default' => 'member_social_links' ],
			'acf_button_label' => [ 'label' => esc_html__( 'Button label field key', 'souvik-ws-team-showcase' ), 'default' => 'member_button_text' ],
			'acf_button_url'   => [ 'label' => esc_html__( 'Button URL field key', 'souvik-ws-team-showcase' ), 'default' => 'member_button_url' ],
		];

		$acf_groups = $this->get_acf_fields_groups();

		foreach ( $acf_fields as $control_id => $data ) {
			$this->add_control(
				$control_id,
				[
					'label'   => $data['label'],
					'type'    => Controls_Manager::SELECT,
					'groups'  => $acf_groups,
					'default' => $data['default'],
				]
			);

			// Custom key text field
			$this->add_control(
				$control_id . '_custom',
				[
					'label'       => sprintf( esc_html__( 'Custom Key for %s', 'souvik-ws-team-showcase' ), $data['label'] ),
					'type'        => Controls_Manager::TEXT,
					'placeholder' => 'e.g. my_custom_meta_field',
					'condition'   => [ $control_id => 'custom_meta_key' ],
				]
			);
		}

		$this->end_controls_section();

		// -------------------------------------------------------------------
		// Layout Elements & HTML/Link Options (Always visible).
		// -------------------------------------------------------------------
		$this->start_controls_section(
			'section_element_options',
			[
				'label' => esc_html__( 'Layout Elements & HTML/Link Options', 'souvik-ws-team-showcase' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			]
		);

		$elements = [
			'name'        => [ 'label' => esc_html__( 'Name Field', 'souvik-ws-team-showcase' ), 'tag' => 'h3', 'has_tag' => true ],
			'designation' => [ 'label' => esc_html__( 'Designation Field', 'souvik-ws-team-showcase' ), 'tag' => 'p', 'has_tag' => true ],
			'department'  => [ 'label' => esc_html__( 'Department Field', 'souvik-ws-team-showcase' ), 'tag' => 'span', 'has_tag' => true ],
			'bio'         => [ 'label' => esc_html__( 'Bio Field', 'souvik-ws-team-showcase' ), 'tag' => 'p', 'has_tag' => true ],
			'photo'       => [ 'label' => esc_html__( 'Photo Field', 'souvik-ws-team-showcase' ), 'tag' => '', 'has_tag' => false ],
		];

		foreach ( $elements as $key => $data ) {
			$this->add_control(
				'heading_elem_' . $key,
				[
					'label'     => '— ' . $data['label'],
					'type'      => Controls_Manager::HEADING,
					'separator' => 'before',
				]
			);

			if ( $data['has_tag'] ) {
				$this->add_control(
					$key . '_html_tag',
					[
						'label'   => esc_html__( 'HTML Tag', 'souvik-ws-team-showcase' ),
						'type'    => Controls_Manager::SELECT,
						'default' => $data['tag'],
						'options' => [
							'h1'   => 'H1',
							'h2'   => 'H2',
							'h3'   => 'H3',
							'h4'   => 'H4',
							'h5'   => 'H5',
							'h6'   => 'H6',
							'div'  => 'div',
							'span' => 'span',
							'p'    => 'p',
						],
					]
				);
			}

			$this->add_control(
				$key . '_link_to',
				[
					'label'   => esc_html__( 'Link To', 'souvik-ws-team-showcase' ),
					'type'    => Controls_Manager::SELECT,
					'default' => 'none',
					'options' => [
						'none'       => esc_html__( 'None', 'souvik-ws-team-showcase' ),
						'member_url' => esc_html__( 'Member Detail Page / URL', 'souvik-ws-team-showcase' ),
						'custom'     => esc_html__( 'Custom URL', 'souvik-ws-team-showcase' ),
						'popup'      => esc_html__( 'Popup Modal', 'souvik-ws-team-showcase' ),
					],
				]
			);

			$this->add_control(
				$key . '_custom_url',
				[
					'label'       => esc_html__( 'Custom URL', 'souvik-ws-team-showcase' ),
					'type'        => Controls_Manager::URL,
					'placeholder' => 'https://example.com',
					'dynamic'     => [ 'active' => true ],
					'condition'   => [ $key . '_link_to' => 'custom' ],
				]
			);
		}

		$this->end_controls_section();

		// -------------------------------------------------------------------
		// Custom / Extra Fields Repeater (Always visible).
		// -------------------------------------------------------------------
		$this->start_controls_section(
			'section_custom_extra_fields',
			[
				'label' => esc_html__( 'Custom & Extra Fields', 'souvik-ws-team-showcase' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			]
		);

		$custom_fields_repeater = new Repeater();

		$custom_fields_repeater->add_control(
			'field_label',
			[
				'label'       => esc_html__( 'Field Label', 'souvik-ws-team-showcase' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__( 'Extra Info', 'souvik-ws-team-showcase' ),
				'placeholder' => 'e.g. Location, Experience',
			]
		);

		$custom_fields_repeater->add_control(
			'manual_value',
			[
				'label'       => esc_html__( 'Manual Mode Value', 'souvik-ws-team-showcase' ),
				'type'        => Controls_Manager::TEXT,
				'description' => esc_html__( 'Value to display in manual mode.', 'souvik-ws-team-showcase' ),
			]
		);

		$custom_fields_repeater->add_control(
			'dynamic_source',
			[
				'label'   => esc_html__( 'Dynamic Mode Source', 'souvik-ws-team-showcase' ),
				'type'    => Controls_Manager::SELECT,
				'groups'  => $acf_groups,
				'default' => '',
			]
		);

		$custom_fields_repeater->add_control(
			'dynamic_source_custom',
			[
				'label'       => esc_html__( 'Custom Meta Key', 'souvik-ws-team-showcase' ),
				'type'        => Controls_Manager::TEXT,
				'placeholder' => 'e.g. member_experience',
				'condition'   => [ 'dynamic_source' => 'custom_meta_key' ],
			]
		);

		$custom_fields_repeater->add_control(
			'field_icon',
			[
				'label' => esc_html__( 'Field Icon', 'souvik-ws-team-showcase' ),
				'type'  => Controls_Manager::ICONS,
			]
		);

		$custom_fields_repeater->add_control(
			'html_tag',
			[
				'label'   => esc_html__( 'HTML Tag', 'souvik-ws-team-showcase' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'p',
				'options' => [
					'h1'   => 'H1',
					'h2'   => 'H2',
					'h3'   => 'H3',
					'h4'   => 'H4',
					'h5'   => 'H5',
					'h6'   => 'H6',
					'div'  => 'div',
					'span' => 'span',
					'p'    => 'p',
				],
			]
		);

		$custom_fields_repeater->add_control(
			'link_to',
			[
				'label'   => esc_html__( 'Link To', 'souvik-ws-team-showcase' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'none',
				'options' => [
					'none'       => esc_html__( 'None', 'souvik-ws-team-showcase' ),
					'member_url' => esc_html__( 'Member Detail Page / URL', 'souvik-ws-team-showcase' ),
					'custom'     => esc_html__( 'Custom URL', 'souvik-ws-team-showcase' ),
					'popup'      => esc_html__( 'Popup Modal', 'souvik-ws-team-showcase' ),
				],
			]
		);

		$custom_fields_repeater->add_control(
			'custom_url',
			[
				'label'       => esc_html__( 'Custom URL', 'souvik-ws-team-showcase' ),
				'type'        => Controls_Manager::URL,
				'placeholder' => 'https://example.com',
				'dynamic'     => [ 'active' => true ],
				'condition'   => [ 'link_to' => 'custom' ],
			]
		);

		$custom_fields_repeater->add_control(
			'display_style',
			[
				'label'   => esc_html__( 'Display Style', 'souvik-ws-team-showcase' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'text',
				'options' => [
					'text'  => esc_html__( 'Default Text', 'souvik-ws-team-showcase' ),
					'badge' => esc_html__( 'Badge Style', 'souvik-ws-team-showcase' ),
					'btn'   => esc_html__( 'Button Style', 'souvik-ws-team-showcase' ),
				],
			]
		);

		$custom_fields_repeater->add_control(
			'show_label',
			[
				'label'        => esc_html__( 'Show Field Label', 'souvik-ws-team-showcase' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Yes', 'souvik-ws-team-showcase' ),
				'label_off'    => esc_html__( 'No', 'souvik-ws-team-showcase' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);



		$this->add_control(
			'custom_extra_fields',
			[
				'label'       => esc_html__( 'Add Extra Field Elements', 'souvik-ws-team-showcase' ),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $custom_fields_repeater->get_controls(),
				'title_field' => '{{{ field_label }}}',
			]
		);

		$this->end_controls_section();

		// ===================================================================
		//  STYLE TAB — mirrors Content tab section structure.
		// ===================================================================

		// -------------------------------------------------------------------
		// Card — Normal / Hover tabs.
		// -------------------------------------------------------------------
		$this->start_controls_section(
			'style_card',
			[
				'label' => esc_html__( 'Card', 'souvik-ws-team-showcase' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->start_controls_tabs( 'card_style_tabs' );

		// --- Normal tab.
		$this->start_controls_tab(
			'card_tab_normal',
			[ 'label' => esc_html__( 'Normal', 'souvik-ws-team-showcase' ) ]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'card_bg',
				'label'    => esc_html__( 'Background', 'souvik-ws-team-showcase' ),
				'types'    => [ 'classic', 'gradient' ],
				'selector' => '{{WRAPPER}} .souvik-ws-team-card',
			]
		);

		$this->add_control(
			'card_border_type',
			[
				'label'   => esc_html__( 'Border Type', 'souvik-ws-team-showcase' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'solid',
				'options' => [
					'none'   => esc_html__( 'None', 'souvik-ws-team-showcase' ),
					'solid'  => 'Solid',
					'dashed' => 'Dashed',
					'dotted' => 'Dotted',
					'double' => 'Double',
				],
				'selectors' => [
					'{{WRAPPER}} .souvik-ws-team-card' => 'border-style: {{VALUE}};',
				],
			]
		);

		$this->add_responsive_control(
			'card_border_width',
			[
				'label'      => esc_html__( 'Border Width', 'souvik-ws-team-showcase' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px' ],
				'selectors'  => [
					'{{WRAPPER}} .souvik-ws-team-card' => 'border-width: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
				'condition'  => [ 'card_border_type!' => 'none' ],
			]
		);

		$this->add_control(
			'card_border_color',
			[
				'label'     => esc_html__( 'Border Color', 'souvik-ws-team-showcase' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .souvik-ws-team-card' => 'border-color: {{VALUE}};',
				],
				'condition' => [ 'card_border_type!' => 'none' ],
			]
		);

		$this->add_responsive_control(
			'card_border_radius',
			[
				'label'      => esc_html__( 'Border Radius', 'souvik-ws-team-showcase' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'default'    => [
					'top'    => 12,
					'right'  => 12,
					'bottom' => 12,
					'left'   => 12,
					'unit'   => 'px',
				],
				'selectors' => [
					'{{WRAPPER}} .souvik-ws-team-card' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'card_padding',
			[
				'label'      => esc_html__( 'Padding', 'souvik-ws-team-showcase' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'default'    => [
					'top'    => 24,
					'right'  => 24,
					'bottom' => 24,
					'left'   => 24,
					'unit'   => 'px',
				],
				'selectors' => [
					'{{WRAPPER}} .souvik-ws-team-card' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'card_box_shadow',
				'label'    => esc_html__( 'Box Shadow', 'souvik-ws-team-showcase' ),
				'selector' => '{{WRAPPER}} .souvik-ws-team-card',
			]
		);

		$this->end_controls_tab();

		// --- Hover tab.
		$this->start_controls_tab(
			'card_tab_hover',
			[ 'label' => esc_html__( 'Hover', 'souvik-ws-team-showcase' ) ]
		);

		$this->add_control(
			'card_hover_bg',
			[
				'label'     => esc_html__( 'Background Color', 'souvik-ws-team-showcase' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .souvik-ws-team-card:hover' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'card_hover_border_color',
			[
				'label'     => esc_html__( 'Border Color', 'souvik-ws-team-showcase' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .souvik-ws-team-card:hover' => 'border-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'card_hover_transform',
			[
				'label'   => esc_html__( 'Transform Preset', 'souvik-ws-team-showcase' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'lift',
				'options' => [
					'none'  => esc_html__( 'None', 'souvik-ws-team-showcase' ),
					'lift'  => esc_html__( 'Lift (translateY)', 'souvik-ws-team-showcase' ),
					'scale' => esc_html__( 'Scale Up', 'souvik-ws-team-showcase' ),
				],
			]
		);

		$this->add_control(
			'card_hover_transition',
			[
				'label'   => esc_html__( 'Transition Duration (ms)', 'souvik-ws-team-showcase' ),
				'type'    => Controls_Manager::SLIDER,
				'default' => [ 'size' => 350 ],
				'range'   => [ 'px' => [ 'min' => 100, 'max' => 1000, 'step' => 50 ] ],
				'selectors' => [
					'{{WRAPPER}} .souvik-ws-team-card' => 'transition-duration: {{SIZE}}ms;',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'card_hover_box_shadow',
				'label'    => esc_html__( 'Box Shadow', 'souvik-ws-team-showcase' ),
				'selector' => '{{WRAPPER}} .souvik-ws-team-card:hover',
			]
		);

		$this->end_controls_tab();
		$this->end_controls_tabs();
		$this->end_controls_section();

		// -------------------------------------------------------------------
		// Image.
		// -------------------------------------------------------------------
		$this->start_controls_section(
			'style_image',
			[
				'label' => esc_html__( 'Image', 'souvik-ws-team-showcase' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'image_object_fit',
			[
				'label'   => esc_html__( 'Object Fit', 'souvik-ws-team-showcase' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'cover',
				'options' => [
					'cover'      => esc_html__( 'Cover', 'souvik-ws-team-showcase' ),
					'contain'    => esc_html__( 'Contain', 'souvik-ws-team-showcase' ),
					'fill'       => esc_html__( 'Fill', 'souvik-ws-team-showcase' ),
					'scale-down' => esc_html__( 'Scale Down', 'souvik-ws-team-showcase' ),
					'none'       => esc_html__( 'None', 'souvik-ws-team-showcase' ),
				],
				'selectors' => [
					'{{WRAPPER}} .souvik-ws-team-card__img' => 'object-fit: {{VALUE}};',
				],
			]
		);

		$this->add_responsive_control(
			'image_width',
			[
				'label'      => esc_html__( 'Width', 'souvik-ws-team-showcase' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%', 'vw' ],
				'range'      => [
					'px' => [ 'min' => 20, 'max' => 1000 ],
					'%'  => [ 'min' => 1, 'max' => 100 ],
				],
				'selectors'  => [
					'{{WRAPPER}} .souvik-ws-team-card__image' => 'width: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .souvik-ws-team-card__img'   => 'width: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'image_max_width',
			[
				'label'      => esc_html__( 'Max Width', 'souvik-ws-team-showcase' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%', 'vw' ],
				'range'      => [
					'px' => [ 'min' => 20, 'max' => 1000 ],
					'%'  => [ 'min' => 1, 'max' => 100 ],
				],
				'selectors'  => [
					'{{WRAPPER}} .souvik-ws-team-card__image' => 'max-width: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .souvik-ws-team-card__img'   => 'max-width: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'image_height',
			[
				'label'      => esc_html__( 'Height', 'souvik-ws-team-showcase' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%', 'vh', 'em' ],
				'range'      => [
					'px' => [ 'min' => 50, 'max' => 800 ],
					'%'  => [ 'min' => 10, 'max' => 100 ],
				],
				'selectors'  => [
					'{{WRAPPER}} .souvik-ws-team-card__image' => 'height: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .souvik-ws-team-card__img'   => 'height: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'image_aspect_ratio',
			[
				'label'   => esc_html__( 'Aspect Ratio', 'souvik-ws-team-showcase' ),
				'type'    => Controls_Manager::SELECT,
				'default' => '',
				'options' => [
					''       => esc_html__( 'Default', 'souvik-ws-team-showcase' ),
					'auto'   => esc_html__( 'Auto', 'souvik-ws-team-showcase' ),
					'1/1'    => '1:1 (Square)',
					'3/2'    => '3:2',
					'4/3'    => '4:3',
					'16/9'   => '16:9',
					'2/3'    => '2:3',
					'3/4'    => '3:4',
					'custom' => esc_html__( 'Custom', 'souvik-ws-team-showcase' ),
				],
				'selectors' => [
					'{{WRAPPER}} .souvik-ws-team-card__image' => 'aspect-ratio: {{VALUE}};',
					'{{WRAPPER}} .souvik-ws-team-card__img'   => 'aspect-ratio: {{VALUE}};',
				],
			]
		);

		$this->add_responsive_control(
			'image_custom_aspect_ratio',
			[
				'label'       => esc_html__( 'Custom Aspect Ratio', 'souvik-ws-team-showcase' ),
				'type'        => Controls_Manager::TEXT,
				'placeholder' => '1/1 or 1.5',
				'condition'   => [ 'image_aspect_ratio' => 'custom' ],
				'selectors'   => [
					'{{WRAPPER}} .souvik-ws-team-card__image' => 'aspect-ratio: {{VALUE}};',
					'{{WRAPPER}} .souvik-ws-team-card__img'   => 'aspect-ratio: {{VALUE}};',
				],
			]
		);

		$this->add_responsive_control(
			'image_border_radius',
			[
				'label'      => esc_html__( 'Border Radius', 'souvik-ws-team-showcase' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .souvik-ws-team-card__image, {{WRAPPER}} .souvik-ws-team-card__img' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'image_hover_zoom',
			[
				'label'   => esc_html__( 'Zoom on Hover', 'souvik-ws-team-showcase' ),
				'type'    => Controls_Manager::SWITCHER,
				'default' => 'yes',
			]
		);

		$this->end_controls_section();

		// -------------------------------------------------------------------
		// Typography — Name.
		// -------------------------------------------------------------------
		$this->start_controls_section(
			'style_name',
			[
				'label' => esc_html__( 'Typography — Name', 'souvik-ws-team-showcase' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->start_controls_tabs( 'name_typo_tabs' );

		// Normal State
		$this->start_controls_tab(
			'name_typo_normal_tab',
			[ 'label' => esc_html__( 'Normal', 'souvik-ws-team-showcase' ) ]
		);

		$this->add_control(
			'name_color',
			[
				'label'     => esc_html__( 'Color', 'souvik-ws-team-showcase' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .souvik-ws-team-card__name' => 'color: {{VALUE}};',
					'{{WRAPPER}} .souvik-ws-team-card__name-link' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'name_typo',
				'label'    => esc_html__( 'Typography', 'souvik-ws-team-showcase' ),
				'selector' => '{{WRAPPER}} .souvik-ws-team-card__name, {{WRAPPER}} .souvik-ws-team-card__name-link',
			]
		);

		$this->end_controls_tab();

		// Hover State
		$this->start_controls_tab(
			'name_typo_hover_tab',
			[ 'label' => esc_html__( 'Hover', 'souvik-ws-team-showcase' ) ]
		);

		$this->add_control(
			'name_color_hover',
			[
				'label'     => esc_html__( 'Color', 'souvik-ws-team-showcase' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .souvik-ws-team-card:hover .souvik-ws-team-card__name' => 'color: {{VALUE}};',
					'{{WRAPPER}} .souvik-ws-team-card__name-link:hover' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'name_typo_hover',
				'label'    => esc_html__( 'Typography', 'souvik-ws-team-showcase' ),
				'selector' => '{{WRAPPER}} .souvik-ws-team-card:hover .souvik-ws-team-card__name, {{WRAPPER}} .souvik-ws-team-card__name-link:hover',
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Text_Shadow::get_type(),
			[
				'name'     => 'name_shadow_hover',
				'selector' => '{{WRAPPER}} .souvik-ws-team-card:hover .souvik-ws-team-card__name, {{WRAPPER}} .souvik-ws-team-card__name-link:hover',
			]
		);

		$this->end_controls_tab();

		// Focus State
		$this->start_controls_tab(
			'name_typo_focus_tab',
			[ 'label' => esc_html__( 'Focus', 'souvik-ws-team-showcase' ) ]
		);

		$this->add_control(
			'name_color_focus',
			[
				'label'     => esc_html__( 'Color', 'souvik-ws-team-showcase' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .souvik-ws-team-card__name:focus, {{WRAPPER}} .souvik-ws-team-card__name:focus-within' => 'color: {{VALUE}};',
					'{{WRAPPER}} .souvik-ws-team-card__name-link:focus' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'name_typo_focus',
				'label'    => esc_html__( 'Typography', 'souvik-ws-team-showcase' ),
				'selector' => '{{WRAPPER}} .souvik-ws-team-card__name:focus, {{WRAPPER}} .souvik-ws-team-card__name:focus-within, {{WRAPPER}} .souvik-ws-team-card__name-link:focus',
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Text_Shadow::get_type(),
			[
				'name'     => 'name_shadow_focus',
				'selector' => '{{WRAPPER}} .souvik-ws-team-card__name:focus, {{WRAPPER}} .souvik-ws-team-card__name:focus-within, {{WRAPPER}} .souvik-ws-team-card__name-link:focus',
			]
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->add_responsive_control(
			'name_spacing',
			[
				'label'      => esc_html__( 'Spacing Bottom', 'souvik-ws-team-showcase' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'em' ],
				'range'      => [ 'px' => [ 'min' => 0, 'max' => 60 ] ],
				'selectors'  => [
					'{{WRAPPER}} .souvik-ws-team-card__name' => 'margin-bottom: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

		// -------------------------------------------------------------------
		// Typography — Designation.
		// -------------------------------------------------------------------
		$this->start_controls_section(
			'style_designation',
			[
				'label' => esc_html__( 'Typography — Designation', 'souvik-ws-team-showcase' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->start_controls_tabs( 'designation_typo_tabs' );

		// Normal State
		$this->start_controls_tab(
			'designation_typo_normal_tab',
			[ 'label' => esc_html__( 'Normal', 'souvik-ws-team-showcase' ) ]
		);

		$this->add_control(
			'designation_color',
			[
				'label'     => esc_html__( 'Color', 'souvik-ws-team-showcase' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .souvik-ws-team-card__role' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'designation_typo',
				'label'    => esc_html__( 'Typography', 'souvik-ws-team-showcase' ),
				'selector' => '{{WRAPPER}} .souvik-ws-team-card__role',
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Text_Shadow::get_type(),
			[
				'name'     => 'designation_shadow',
				'selector' => '{{WRAPPER}} .souvik-ws-team-card__role',
			]
		);

		$this->end_controls_tab();

		// Hover State
		$this->start_controls_tab(
			'designation_typo_hover_tab',
			[ 'label' => esc_html__( 'Hover', 'souvik-ws-team-showcase' ) ]
		);

		$this->add_control(
			'designation_color_hover',
			[
				'label'     => esc_html__( 'Color', 'souvik-ws-team-showcase' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .souvik-ws-team-card:hover .souvik-ws-team-card__role' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'designation_typo_hover',
				'label'    => esc_html__( 'Typography', 'souvik-ws-team-showcase' ),
				'selector' => '{{WRAPPER}} .souvik-ws-team-card:hover .souvik-ws-team-card__role',
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Text_Shadow::get_type(),
			[
				'name'     => 'designation_shadow_hover',
				'selector' => '{{WRAPPER}} .souvik-ws-team-card:hover .souvik-ws-team-card__role',
			]
		);

		$this->end_controls_tab();

		// Focus State
		$this->start_controls_tab(
			'designation_typo_focus_tab',
			[ 'label' => esc_html__( 'Focus', 'souvik-ws-team-showcase' ) ]
		);

		$this->add_control(
			'designation_color_focus',
			[
				'label'     => esc_html__( 'Color', 'souvik-ws-team-showcase' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .souvik-ws-team-card__role:focus' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'designation_typo_focus',
				'label'    => esc_html__( 'Typography', 'souvik-ws-team-showcase' ),
				'selector' => '{{WRAPPER}} .souvik-ws-team-card__role:focus',
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Text_Shadow::get_type(),
			[
				'name'     => 'designation_shadow_focus',
				'selector' => '{{WRAPPER}} .souvik-ws-team-card__role:focus',
			]
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->add_responsive_control(
			'designation_spacing',
			[
				'label'      => esc_html__( 'Spacing Bottom', 'souvik-ws-team-showcase' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'em' ],
				'range'      => [ 'px' => [ 'min' => 0, 'max' => 60 ] ],
				'selectors'  => [
					'{{WRAPPER}} .souvik-ws-team-card__role' => 'margin-bottom: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

		// -------------------------------------------------------------------
		// Typography — Bio.
		// -------------------------------------------------------------------
		$this->start_controls_section(
			'style_bio',
			[
				'label' => esc_html__( 'Typography — Bio', 'souvik-ws-team-showcase' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->start_controls_tabs( 'bio_typo_tabs' );

		// Normal State
		$this->start_controls_tab(
			'bio_typo_normal_tab',
			[ 'label' => esc_html__( 'Normal', 'souvik-ws-team-showcase' ) ]
		);

		$this->add_control(
			'bio_color',
			[
				'label'     => esc_html__( 'Color', 'souvik-ws-team-showcase' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .souvik-ws-team-card__bio' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'bio_typo',
				'label'    => esc_html__( 'Typography', 'souvik-ws-team-showcase' ),
				'selector' => '{{WRAPPER}} .souvik-ws-team-card__bio',
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Text_Shadow::get_type(),
			[
				'name'     => 'bio_shadow',
				'selector' => '{{WRAPPER}} .souvik-ws-team-card__bio',
			]
		);

		$this->end_controls_tab();

		// Hover State
		$this->start_controls_tab(
			'bio_typo_hover_tab',
			[ 'label' => esc_html__( 'Hover', 'souvik-ws-team-showcase' ) ]
		);

		$this->add_control(
			'bio_color_hover',
			[
				'label'     => esc_html__( 'Color', 'souvik-ws-team-showcase' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .souvik-ws-team-card:hover .souvik-ws-team-card__bio' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'bio_typo_hover',
				'label'    => esc_html__( 'Typography', 'souvik-ws-team-showcase' ),
				'selector' => '{{WRAPPER}} .souvik-ws-team-card:hover .souvik-ws-team-card__bio',
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Text_Shadow::get_type(),
			[
				'name'     => 'bio_shadow_hover',
				'selector' => '{{WRAPPER}} .souvik-ws-team-card:hover .souvik-ws-team-card__bio',
			]
		);

		$this->end_controls_tab();

		// Focus State
		$this->start_controls_tab(
			'bio_typo_focus_tab',
			[ 'label' => esc_html__( 'Focus', 'souvik-ws-team-showcase' ) ]
		);

		$this->add_control(
			'bio_color_focus',
			[
				'label'     => esc_html__( 'Color', 'souvik-ws-team-showcase' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .souvik-ws-team-card__bio:focus' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'bio_typo_focus',
				'label'    => esc_html__( 'Typography', 'souvik-ws-team-showcase' ),
				'selector' => '{{WRAPPER}} .souvik-ws-team-card__bio:focus',
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Text_Shadow::get_type(),
			[
				'name'     => 'bio_shadow_focus',
				'selector' => '{{WRAPPER}} .souvik-ws-team-card__bio:focus',
			]
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->add_responsive_control(
			'bio_spacing',
			[
				'label'      => esc_html__( 'Spacing Bottom', 'souvik-ws-team-showcase' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'em' ],
				'range'      => [ 'px' => [ 'min' => 0, 'max' => 60 ] ],
				'selectors'  => [
					'{{WRAPPER}} .souvik-ws-team-card__bio' => 'margin-bottom: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

		// -------------------------------------------------------------------
		// Department Badge.
		// -------------------------------------------------------------------
		$this->start_controls_section(
			'style_badge',
			[
				'label' => esc_html__( 'Department Badge', 'souvik-ws-team-showcase' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'badge_bg',
			[
				'label'     => esc_html__( 'Background', 'souvik-ws-team-showcase' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .souvik-ws-team-dept-badge' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'badge_color',
			[
				'label'     => esc_html__( 'Text Color', 'souvik-ws-team-showcase' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .souvik-ws-team-dept-badge' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_responsive_control(
			'badge_border_radius',
			[
				'label'      => esc_html__( 'Border Radius', 'souvik-ws-team-showcase' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .souvik-ws-team-dept-badge' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'badge_padding',
			[
				'label'      => esc_html__( 'Padding', 'souvik-ws-team-showcase' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em' ],
				'selectors'  => [
					'{{WRAPPER}} .souvik-ws-team-dept-badge' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'badge_typo',
				'label'    => esc_html__( 'Typography', 'souvik-ws-team-showcase' ),
				'selector' => '{{WRAPPER}} .souvik-ws-team-dept-badge',
			]
		);

		$this->end_controls_section();

		// -------------------------------------------------------------------
		// Social Icons.
		// -------------------------------------------------------------------
		$this->start_controls_section(
			'style_socials',
			[
				'label' => esc_html__( 'Social Icons', 'souvik-ws-team-showcase' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'social_icon_color',
			[
				'label'     => esc_html__( 'Icon Color', 'souvik-ws-team-showcase' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .souvik-ws-team-card__social a' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'social_icon_bg',
			[
				'label'     => esc_html__( 'Icon Background', 'souvik-ws-team-showcase' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .souvik-ws-team-card__social a' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'social_icon_size',
			[
				'label'   => esc_html__( 'Icon Size', 'souvik-ws-team-showcase' ),
				'type'    => Controls_Manager::SLIDER,
				'default' => [ 'size' => 16 ],
				'range'   => [ 'px' => [ 'min' => 10, 'max' => 48, 'step' => 1 ] ],
				'selectors' => [
					'{{WRAPPER}} .souvik-ws-team-card__social a' => 'font-size: {{SIZE}}px; width: calc({{SIZE}}px + 18px); height: calc({{SIZE}}px + 18px);',
				],
			]
		);

		$this->add_responsive_control(
			'social_icon_border_radius',
			[
				'label'      => esc_html__( 'Border Radius', 'souvik-ws-team-showcase' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .souvik-ws-team-card__social a' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'social_hover_style',
			[
				'label'     => esc_html__( 'Hover Color Style', 'souvik-ws-team-showcase' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'brand',
				'options'   => [
					'brand'      => esc_html__( 'Individual Brand Colors', 'souvik-ws-team-showcase' ),
					'uniform'    => esc_html__( 'Uniform Custom Color', 'souvik-ws-team-showcase' ),
					'individual' => esc_html__( 'Custom Styles Repeater (Per Icon)', 'souvik-ws-team-showcase' ),
				],
				'separator' => 'before',
			]
		);

		$this->add_control(
			'social_icon_bg_hover',
			[
				'label'     => esc_html__( 'Hover Background', 'souvik-ws-team-showcase' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .souvik-ws-team-card__social.souvik-ws-socials--uniform a:hover' => 'background-color: {{VALUE}} !important; background: {{VALUE}} !important;',
				],
				'condition' => [ 'social_hover_style' => 'uniform' ],
			]
		);

		$this->add_control(
			'social_icon_color_hover',
			[
				'label'     => esc_html__( 'Hover Icon Color', 'souvik-ws-team-showcase' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .souvik-ws-team-card__social.souvik-ws-socials--uniform a:hover' => 'color: {{VALUE}} !important;',
				],
				'condition' => [ 'social_hover_style' => 'uniform' ],
			]
		);

		$social_styles_repeater = new Repeater();

		$social_styles_repeater->add_control(
			'platform',
			[
				'label'   => esc_html__( 'Platform', 'souvik-ws-team-showcase' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'linkedin',
				'options' => [
					'facebook'  => 'Facebook',
					'twitter'   => 'Twitter / X',
					'linkedin'  => 'LinkedIn',
					'instagram' => 'Instagram',
					'youtube'   => 'YouTube',
					'github'    => 'GitHub',
					'dribbble'  => 'Dribbble',
					'behance'   => 'Behance',
				],
			]
		);

		$social_styles_repeater->add_control(
			'icon_color',
			[
				'label'     => esc_html__( 'Icon Color', 'souvik-ws-team-showcase' ),
				'type'      => Controls_Manager::COLOR,
			]
		);

		$social_styles_repeater->add_control(
			'icon_bg',
			[
				'label'     => esc_html__( 'Background Color', 'souvik-ws-team-showcase' ),
				'type'      => Controls_Manager::COLOR,
			]
		);

		$social_styles_repeater->add_control(
			'icon_size',
			[
				'label'   => esc_html__( 'Icon Size (px)', 'souvik-ws-team-showcase' ),
				'type'    => Controls_Manager::SLIDER,
				'range'   => [ 'px' => [ 'min' => 10, 'max' => 48, 'step' => 1 ] ],
			]
		);

		$social_styles_repeater->add_responsive_control(
			'icon_border_radius',
			[
				'label'      => esc_html__( 'Border Radius', 'souvik-ws-team-showcase' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
			]
		);

		$social_styles_repeater->add_control(
			'icon_color_hover',
			[
				'label'     => esc_html__( 'Hover Icon Color', 'souvik-ws-team-showcase' ),
				'type'      => Controls_Manager::COLOR,
			]
		);

		$social_styles_repeater->add_control(
			'icon_bg_hover',
			[
				'label'     => esc_html__( 'Hover Background', 'souvik-ws-team-showcase' ),
				'type'      => Controls_Manager::COLOR,
			]
		);

		$this->add_control(
			'individual_social_styles',
			[
				'label'       => esc_html__( 'Configure Custom Icon Styles', 'souvik-ws-team-showcase' ),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $social_styles_repeater->get_controls(),
				'title_field' => '{{{ platform }}}',
				'condition'   => [ 'social_hover_style' => 'individual' ],
			]
		);

		$this->end_controls_section();

		// -------------------------------------------------------------------
		// Button.
		// -------------------------------------------------------------------
		$this->start_controls_section(
			'style_button',
			[
				'label' => esc_html__( 'Button', 'souvik-ws-team-showcase' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'btn_type',
			[
				'label'   => esc_html__( 'Type', 'souvik-ws-team-showcase' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'filled',
				'options' => [
					'filled'   => esc_html__( 'Filled', 'souvik-ws-team-showcase' ),
					'outlined' => esc_html__( 'Outlined', 'souvik-ws-team-showcase' ),
					'ghost'    => esc_html__( 'Ghost', 'souvik-ws-team-showcase' ),
				],
			]
		);

		$this->start_controls_tabs( 'btn_style_tabs' );

		$this->start_controls_tab(
			'btn_tab_normal',
			[ 'label' => esc_html__( 'Normal', 'souvik-ws-team-showcase' ) ]
		);

		$this->add_control(
			'btn_bg',
			[
				'label'     => esc_html__( 'Background', 'souvik-ws-team-showcase' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .souvik-ws-team-card__btn' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'btn_color',
			[
				'label'     => esc_html__( 'Text Color', 'souvik-ws-team-showcase' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .souvik-ws-team-card__btn' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'btn_border_color',
			[
				'label'     => esc_html__( 'Border Color', 'souvik-ws-team-showcase' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .souvik-ws-team-card__btn' => 'border-color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'btn_tab_hover',
			[ 'label' => esc_html__( 'Hover', 'souvik-ws-team-showcase' ) ]
		);

		$this->add_control(
			'btn_hover_bg',
			[
				'label'     => esc_html__( 'Background', 'souvik-ws-team-showcase' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .souvik-ws-team-card__btn:hover' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'btn_hover_color',
			[
				'label'     => esc_html__( 'Text Color', 'souvik-ws-team-showcase' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .souvik-ws-team-card__btn:hover' => 'color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_tab();
		$this->end_controls_tabs();

		$this->add_responsive_control(
			'btn_border_radius',
			[
				'label'      => esc_html__( 'Border Radius', 'souvik-ws-team-showcase' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .souvik-ws-team-card__btn' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'btn_padding',
			[
				'label'      => esc_html__( 'Padding', 'souvik-ws-team-showcase' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em' ],
				'selectors'  => [
					'{{WRAPPER}} .souvik-ws-team-card__btn' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'btn_typo',
				'label'    => esc_html__( 'Typography', 'souvik-ws-team-showcase' ),
				'selector' => '{{WRAPPER}} .souvik-ws-team-card__btn',
			]
		);

		$this->end_controls_section();

		// -------------------------------------------------------------------
		// Filter Bar Style.
		// -------------------------------------------------------------------
		$this->start_controls_section(
			'style_filter_bar',
			[
				'label' => esc_html__( 'Filter Bar', 'souvik-ws-team-showcase' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'filter_bg',
			[
				'label'     => esc_html__( 'Default Background', 'souvik-ws-team-showcase' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .souvik-ws-filter-btn' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'filter_text_color',
			[
				'label'     => esc_html__( 'Default Text Color', 'souvik-ws-team-showcase' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .souvik-ws-filter-btn' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'filter_active_bg',
			[
				'label'     => esc_html__( 'Active Background', 'souvik-ws-team-showcase' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .souvik-ws-filter-btn.is-active' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'filter_active_color',
			[
				'label'     => esc_html__( 'Active Text Color', 'souvik-ws-team-showcase' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .souvik-ws-filter-btn.is-active' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'filter_bottom_spacing',
			[
				'label'   => esc_html__( 'Bottom Spacing', 'souvik-ws-team-showcase' ),
				'type'    => Controls_Manager::SLIDER,
				'default' => [ 'size' => 24 ],
				'range'   => [ 'px' => [ 'min' => 0, 'max' => 80, 'step' => 2 ] ],
				'selectors' => [
					'{{WRAPPER}} .souvik-ws-filter-bar' => 'margin-bottom: {{SIZE}}px;',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'filter_typo',
				'label'    => esc_html__( 'Typography', 'souvik-ws-team-showcase' ),
				'selector' => '{{WRAPPER}} .souvik-ws-filter-btn',
			]
		);

		$this->add_control(
			'filter_layout_heading',
			[
				'label'     => esc_html__( 'Layout Controls', 'souvik-ws-team-showcase' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->add_responsive_control(
			'filter_alignment',
			[
				'label'     => esc_html__( 'Alignment', 'souvik-ws-team-showcase' ),
				'type'      => Controls_Manager::CHOOSE,
				'options'   => [
					'flex-start' => [
						'title' => esc_html__( 'Left', 'souvik-ws-team-showcase' ),
						'icon'  => 'eicon-text-align-left',
					],
					'center' => [
						'title' => esc_html__( 'Center', 'souvik-ws-team-showcase' ),
						'icon'  => 'eicon-text-align-center',
					],
					'flex-end' => [
						'title' => esc_html__( 'Right', 'souvik-ws-team-showcase' ),
						'icon'  => 'eicon-text-align-right',
					],
				],
				'default'   => 'flex-start',
				'selectors' => [
					'{{WRAPPER}} .souvik-ws-filter-bar' => 'justify-content: {{VALUE}};',
				],
			]
		);

		$this->add_responsive_control(
			'filter_gap',
			[
				'label'      => esc_html__( 'Gap', 'souvik-ws-team-showcase' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'em', 'rem' ],
				'range'      => [
					'px' => [ 'min' => 0, 'max' => 50 ],
				],
				'default'    => [
					'size' => 8,
					'unit' => 'px',
				],
				'selectors'  => [
					'{{WRAPPER}} .souvik-ws-filter-bar' => 'gap: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'filter_padding',
			[
				'label'      => esc_html__( 'Button Padding', 'souvik-ws-team-showcase' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem' ],
				'selectors'  => [
					'{{WRAPPER}} .souvik-ws-filter-btn' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'filter_border_radius',
			[
				'label'      => esc_html__( 'Button Border Radius', 'souvik-ws-team-showcase' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .souvik-ws-filter-btn' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'filter_wrap',
			[
				'label'   => esc_html__( 'Flex Wrap', 'souvik-ws-team-showcase' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'wrap',
				'options' => [
					'nowrap'       => 'No Wrap',
					'wrap'         => 'Wrap',
					'wrap-reverse' => 'Wrap Reverse',
				],
				'selectors' => [
					'{{WRAPPER}} .souvik-ws-filter-bar' => 'flex-wrap: {{VALUE}} !important;',
				],
			]
		);

		$this->add_responsive_control(
			'filter_align_self',
			[
				'label'   => esc_html__( 'Align Self', 'souvik-ws-team-showcase' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'auto',
				'options' => [
					'auto'       => 'Auto',
					'flex-start' => 'Flex Start',
					'center'     => 'Center',
					'flex-end'   => 'Flex End',
					'stretch'    => 'Stretch',
				],
				'selectors' => [
					'{{WRAPPER}} .souvik-ws-filter-bar' => 'align-self: {{VALUE}} !important;',
				],
			]
		);

		$this->add_responsive_control(
			'filter_justify_self',
			[
				'label'   => esc_html__( 'Justify Self', 'souvik-ws-team-showcase' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'auto',
				'options' => [
					'auto'    => 'Auto',
					'start'   => 'Start',
					'center'  => 'Center',
					'end'     => 'End',
					'stretch' => 'Stretch',
				],
				'selectors' => [
					'{{WRAPPER}} .souvik-ws-filter-bar' => 'justify-self: {{VALUE}} !important;',
				],
			]
		);

		$this->add_control(
			'filter_flex_grow',
			[
				'label'     => esc_html__( 'Flex Grow', 'souvik-ws-team-showcase' ),
				'type'      => Controls_Manager::NUMBER,
				'selectors' => [
					'{{WRAPPER}} .souvik-ws-filter-bar' => 'flex-grow: {{VALUE}} !important;',
				],
			]
		);

		$this->add_control(
			'filter_flex_shrink',
			[
				'label'     => esc_html__( 'Flex Shrink', 'souvik-ws-team-showcase' ),
				'type'      => Controls_Manager::NUMBER,
				'selectors' => [
					'{{WRAPPER}} .souvik-ws-filter-bar' => 'flex-shrink: {{VALUE}} !important;',
				],
			]
		);

		$this->end_controls_section();

		// -------------------------------------------------------------------
		// Control Bar (Container wrapping Filter & Controls) Style.
		// -------------------------------------------------------------------
		$this->start_controls_section(
			'style_control_bar',
			[
				'label' => esc_html__( 'Control Bar (Container)', 'souvik-ws-team-showcase' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->start_controls_tabs( 'tabs_control_bar' );

		// Tab A: Layout (Flex Grid Configuration)
		$this->start_controls_tab(
			'tab_control_bar_layout',
			[
				'label' => esc_html__( 'Layout', 'souvik-ws-team-showcase' ),
			]
		);

		$this->add_responsive_control(
			'control_bar_display',
			[
				'label'   => esc_html__( 'Display', 'souvik-ws-team-showcase' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'flex',
				'options' => [
					'flex'        => 'Flex',
					'inline-flex' => 'Inline Flex',
					'none'        => 'Hidden',
				],
				'selectors' => [
					'{{WRAPPER}} .souvik-ws-control-bar' => 'display: {{VALUE}};',
				],
			]
		);

		$this->add_responsive_control(
			'control_bar_direction',
			[
				'label'   => esc_html__( 'Direction', 'souvik-ws-team-showcase' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'row',
				'options' => [
					'row'            => 'Row',
					'row-reverse'    => 'Row Reverse',
					'column'         => 'Column',
					'column-reverse' => 'Column Reverse',
				],
				'selectors' => [
					'{{WRAPPER}} .souvik-ws-control-bar' => 'flex-direction: {{VALUE}};',
				],
			]
		);

		$this->add_responsive_control(
			'control_bar_justify',
			[
				'label'   => esc_html__( 'Justify Content', 'souvik-ws-team-showcase' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'space-between',
				'options' => [
					'flex-start'    => 'Flex Start',
					'center'        => 'Center',
					'flex-end'      => 'Flex End',
					'space-between' => 'Space Between',
					'space-around'  => 'Space Around',
					'space-evenly'  => 'Space Evenly',
				],
				'selectors' => [
					'{{WRAPPER}} .souvik-ws-control-bar' => 'justify-content: {{VALUE}};',
				],
			]
		);

		$this->add_responsive_control(
			'control_bar_align',
			[
				'label'   => esc_html__( 'Align Items', 'souvik-ws-team-showcase' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'center',
				'options' => [
					'flex-start' => 'Flex Start',
					'center'     => 'Center',
					'flex-end'   => 'Flex End',
					'stretch'    => 'Stretch',
					'baseline'   => 'Baseline',
				],
				'selectors' => [
					'{{WRAPPER}} .souvik-ws-control-bar' => 'align-items: {{VALUE}};',
				],
			]
		);

		$this->add_responsive_control(
			'control_bar_wrap',
			[
				'label'   => esc_html__( 'Flex Wrap', 'souvik-ws-team-showcase' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'wrap',
				'options' => [
					'nowrap'       => 'No Wrap',
					'wrap'         => 'Wrap',
					'wrap-reverse' => 'Wrap Reverse',
				],
				'selectors' => [
					'{{WRAPPER}} .souvik-ws-control-bar' => 'flex-wrap: {{VALUE}};',
				],
			]
		);

		$this->add_responsive_control(
			'control_bar_gap',
			[
				'label'      => esc_html__( 'Gap', 'souvik-ws-team-showcase' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'em', 'rem' ],
				'default'    => [
					'size' => 20,
					'unit' => 'px',
				],
				'range'      => [
					'px' => [ 'min' => 0, 'max' => 100 ],
				],
				'selectors'  => [
					'{{WRAPPER}} .souvik-ws-control-bar' => 'gap: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'control_bar_align_self',
			[
				'label'   => esc_html__( 'Align Self', 'souvik-ws-team-showcase' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'auto',
				'options' => [
					'auto'       => 'Auto',
					'flex-start' => 'Flex Start',
					'center'     => 'Center',
					'flex-end'   => 'Flex End',
					'stretch'    => 'Stretch',
				],
				'selectors' => [
					'{{WRAPPER}} .souvik-ws-control-bar' => 'align-self: {{VALUE}};',
				],
			]
		);

		$this->add_responsive_control(
			'control_bar_justify_self',
			[
				'label'   => esc_html__( 'Justify Self', 'souvik-ws-team-showcase' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'auto',
				'options' => [
					'auto'    => 'Auto',
					'start'   => 'Start',
					'center'  => 'Center',
					'end'     => 'End',
					'stretch' => 'Stretch',
				],
				'selectors' => [
					'{{WRAPPER}} .souvik-ws-control-bar' => 'justify-self: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'control_bar_flex_grow',
			[
				'label'     => esc_html__( 'Flex Grow', 'souvik-ws-team-showcase' ),
				'type'      => Controls_Manager::NUMBER,
				'selectors' => [
					'{{WRAPPER}} .souvik-ws-control-bar' => 'flex-grow: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'control_bar_flex_shrink',
			[
				'label'     => esc_html__( 'Flex Shrink', 'souvik-ws-team-showcase' ),
				'type'      => Controls_Manager::NUMBER,
				'selectors' => [
					'{{WRAPPER}} .souvik-ws-control-bar' => 'flex-shrink: {{VALUE}};',
				],
			]
		);

		$this->end_controls_tab();

		// Tab B: Style (Container Aesthetics)
		$this->start_controls_tab(
			'tab_control_bar_style',
			[
				'label' => esc_html__( 'Style', 'souvik-ws-team-showcase' ),
			]
		);

		$this->add_control(
			'control_bar_bg_color',
			[
				'label'     => esc_html__( 'Background Color', 'souvik-ws-team-showcase' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .souvik-ws-control-bar' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_responsive_control(
			'control_bar_padding',
			[
				'label'      => esc_html__( 'Padding', 'souvik-ws-team-showcase' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem' ],
				'selectors'  => [
					'{{WRAPPER}} .souvik-ws-control-bar' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'control_bar_margin',
			[
				'label'      => esc_html__( 'Margin', 'souvik-ws-team-showcase' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem' ],
				'selectors'  => [
					'{{WRAPPER}} .souvik-ws-control-bar' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'control_bar_border_radius',
			[
				'label'      => esc_html__( 'Border Radius', 'souvik-ws-team-showcase' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem' ],
				'selectors'  => [
					'{{WRAPPER}} .souvik-ws-control-bar' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'control_bar_box_shadow',
				'selector' => '{{WRAPPER}} .souvik-ws-control-bar',
			]
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->end_controls_section();

		// -------------------------------------------------------------------
		// Popup Style.
		// -------------------------------------------------------------------
		$this->start_controls_section(
			'style_popup',
			[
				'label' => esc_html__( 'Popup', 'souvik-ws-team-showcase' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'popup_overlay_color',
			[
				'label'     => esc_html__( 'Overlay Color (use RGBA for opacity)', 'souvik-ws-team-showcase' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => 'rgba(0,0,0,0.6)',
				'selectors' => [
					'{{WRAPPER}} .souvik-ws-popup-overlay' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'popup_bg',
			[
				'label'     => esc_html__( 'Modal Background', 'souvik-ws-team-showcase' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#ffffff',
				'selectors' => [
					'{{WRAPPER}} .souvik-ws-popup-modal' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_responsive_control(
			'popup_border_radius',
			[
				'label'      => esc_html__( 'Border Radius', 'souvik-ws-team-showcase' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px' ],
				'default'    => [
					'top'    => 16,
					'right'  => 16,
					'bottom' => 16,
					'left'   => 16,
					'unit'   => 'px',
				],
				'selectors'  => [
					'{{WRAPPER}} .souvik-ws-popup-modal' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'popup_padding',
			[
				'label'      => esc_html__( 'Padding', 'souvik-ws-team-showcase' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em' ],
				'default'    => [
					'top'    => 36,
					'right'  => 36,
					'bottom' => 36,
					'left'   => 36,
					'unit'   => 'px',
				],
				'selectors'  => [
					'{{WRAPPER}} .souvik-ws-popup-modal' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'popup_flex_direction',
			[
				'label'   => esc_html__( 'Flex Direction', 'souvik-ws-team-showcase' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'column',
				'separator' => 'before',
				'options' => [
					'row'            => 'Row',
					'column'         => 'Column',
					'row-reverse'    => 'Row Reverse',
					'column-reverse' => 'Column Reverse',
				],
				'selectors' => [
					'{{WRAPPER}} .souvik-ws-popup-modal__content' => 'display: flex; flex-direction: {{VALUE}};',
				],
			]
		);

		$this->add_responsive_control(
			'popup_justify_content',
			[
				'label'   => esc_html__( 'Justify Content', 'souvik-ws-team-showcase' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'flex-start',
				'options' => [
					'flex-start'    => 'Flex Start',
					'center'        => 'Center',
					'flex-end'      => 'Flex End',
					'space-between' => 'Space Between',
					'space-around'  => 'Space Around',
					'space-evenly'  => 'Space Evenly',
				],
				'selectors' => [
					'{{WRAPPER}} .souvik-ws-popup-modal__content' => 'justify-content: {{VALUE}};',
				],
			]
		);

		$this->add_responsive_control(
			'popup_align_items',
			[
				'label'   => esc_html__( 'Align Items', 'souvik-ws-team-showcase' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'stretch',
				'options' => [
					'flex-start' => 'Flex Start',
					'center'     => 'Center',
					'flex-end'   => 'Flex End',
					'stretch'    => 'Stretch',
				],
				'selectors' => [
					'{{WRAPPER}} .souvik-ws-popup-modal__content' => 'align-items: {{VALUE}};',
				],
			]
		);

		$this->add_responsive_control(
			'popup_gap',
			[
				'label'      => esc_html__( 'Gap', 'souvik-ws-team-showcase' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'em' ],
				'range'      => [ 'px' => [ 'min' => 0, 'max' => 100 ] ],
				'default'    => [ 'size' => 14, 'unit' => 'px' ],
				'selectors'  => [
					'{{WRAPPER}} .souvik-ws-popup-modal__content' => 'gap: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'popup_text_align',
			[
				'label'   => esc_html__( 'Text Alignment', 'souvik-ws-team-showcase' ),
				'type'    => Controls_Manager::CHOOSE,
				'options' => [
					'left' => [
						'title' => esc_html__( 'Left', 'souvik-ws-team-showcase' ),
						'icon'  => 'eicon-text-align-left',
					],
					'center' => [
						'title' => esc_html__( 'Center', 'souvik-ws-team-showcase' ),
						'icon'  => 'eicon-text-align-center',
					],
					'right' => [
						'title' => esc_html__( 'Right', 'souvik-ws-team-showcase' ),
						'icon'  => 'eicon-text-align-right',
					],
					'justify' => [
						'title' => esc_html__( 'Justified', 'souvik-ws-team-showcase' ),
						'icon'  => 'eicon-text-align-justify',
					],
				],
				'selectors' => [
					'{{WRAPPER}} .souvik-ws-popup-modal__content' => 'text-align: {{VALUE}};',
				],
			]
		);

		// Popup Image Wrapper Layout & Styling Controls
		$this->add_control(
			'popup_image_wrapper_heading',
			[
				'label'     => esc_html__( 'Image Wrapper (Popup)', 'souvik-ws-team-showcase' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->add_responsive_control(
			'popup_image_wrapper_width',
			[
				'label'      => esc_html__( 'Width', 'souvik-ws-team-showcase' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%', 'vw' ],
				'range'      => [
					'px' => [ 'min' => 100, 'max' => 1000 ],
					'%'  => [ 'min' => 10, 'max' => 100 ],
				],
				'selectors'  => [
					'{{WRAPPER}} .souvik-ws-popup-modal .souvik-ws-team-card__image' => 'width: {{SIZE}}{{UNIT}} !important; flex: 0 0 {{SIZE}}{{UNIT}} !important;',
				],
			]
		);

		$this->add_responsive_control(
			'popup_image_wrapper_height',
			[
				'label'      => esc_html__( 'Height', 'souvik-ws-team-showcase' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'vh' ],
				'range'      => [
					'px' => [ 'min' => 100, 'max' => 1000 ],
				],
				'selectors'  => [
					'{{WRAPPER}} .souvik-ws-popup-modal .souvik-ws-team-card__image' => 'height: {{SIZE}}{{UNIT}} !important;',
				],
			]
		);

		$this->add_responsive_control(
			'popup_image_wrapper_padding',
			[
				'label'      => esc_html__( 'Padding', 'souvik-ws-team-showcase' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .souvik-ws-popup-modal .souvik-ws-team-card__image' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;',
				],
			]
		);

		$this->add_control(
			'popup_image_wrapper_bg',
			[
				'label'     => esc_html__( 'Background Color', 'souvik-ws-team-showcase' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .souvik-ws-popup-modal .souvik-ws-team-card__image' => 'background-color: {{VALUE}} !important;',
				],
			]
		);

		$this->add_responsive_control(
			'popup_image_wrapper_border_radius',
			[
				'label'      => esc_html__( 'Border Radius', 'souvik-ws-team-showcase' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .souvik-ws-popup-modal .souvik-ws-team-card__image' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;',
				],
			]
		);

		// Popup Content Wrapper Layout & Styling Controls
		$this->add_control(
			'popup_content_wrapper_heading',
			[
				'label'     => esc_html__( 'Content Wrapper (Popup)', 'souvik-ws-team-showcase' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->add_responsive_control(
			'popup_content_wrapper_width',
			[
				'label'      => esc_html__( 'Width', 'souvik-ws-team-showcase' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%', 'vw' ],
				'range'      => [
					'px' => [ 'min' => 100, 'max' => 1000 ],
					'%'  => [ 'min' => 10, 'max' => 100 ],
				],
				'selectors'  => [
					'{{WRAPPER}} .souvik-ws-popup-modal .souvik-ws-popup-info-wrap' => 'width: {{SIZE}}{{UNIT}} !important; flex: 0 0 {{SIZE}}{{UNIT}} !important;',
				],
			]
		);

		$this->add_responsive_control(
			'popup_content_wrapper_height',
			[
				'label'      => esc_html__( 'Height', 'souvik-ws-team-showcase' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'vh' ],
				'range'      => [
					'px' => [ 'min' => 100, 'max' => 1000 ],
				],
				'selectors'  => [
					'{{WRAPPER}} .souvik-ws-popup-modal .souvik-ws-popup-info-wrap' => 'height: {{SIZE}}{{UNIT}} !important;',
				],
			]
		);

		$this->add_responsive_control(
			'popup_content_wrapper_padding',
			[
				'label'      => esc_html__( 'Padding', 'souvik-ws-team-showcase' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .souvik-ws-popup-modal .souvik-ws-popup-info-wrap' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;',
				],
			]
		);

		$this->add_control(
			'popup_content_wrapper_bg',
			[
				'label'     => esc_html__( 'Background Color', 'souvik-ws-team-showcase' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .souvik-ws-popup-modal .souvik-ws-popup-info-wrap' => 'background-color: {{VALUE}} !important;',
				],
			]
		);

		$this->add_responsive_control(
			'popup_content_wrapper_border_radius',
			[
				'label'      => esc_html__( 'Border Radius', 'souvik-ws-team-showcase' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .souvik-ws-popup-modal .souvik-ws-popup-info-wrap' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;',
				],
			]
		);

		$this->add_responsive_control(
			'popup_content_wrapper_direction',
			[
				'label'   => esc_html__( 'Flex Direction', 'souvik-ws-team-showcase' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'column',
				'options' => [
					'row'            => 'Row',
					'column'         => 'Column',
					'row-reverse'    => 'Row Reverse',
					'column-reverse' => 'Column Reverse',
				],
				'selectors' => [
					'{{WRAPPER}} .souvik-ws-popup-modal .souvik-ws-popup-info-wrap' => 'display: flex !important; flex-direction: {{VALUE}} !important;',
				],
			]
		);

		$this->add_responsive_control(
			'popup_content_wrapper_justify',
			[
				'label'   => esc_html__( 'Justify Content', 'souvik-ws-team-showcase' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'flex-start',
				'options' => [
					'flex-start'    => 'Flex Start',
					'center'        => 'Center',
					'flex-end'      => 'Flex End',
					'space-between' => 'Space Between',
					'space-around'  => 'Space Around',
				],
				'selectors' => [
					'{{WRAPPER}} .souvik-ws-popup-modal .souvik-ws-popup-info-wrap' => 'justify-content: {{VALUE}} !important;',
				],
			]
		);

		$this->add_responsive_control(
			'popup_content_wrapper_align',
			[
				'label'   => esc_html__( 'Align Items', 'souvik-ws-team-showcase' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'stretch',
				'options' => [
					'flex-start' => 'Flex Start',
					'center'     => 'Center',
					'flex-end'   => 'Flex End',
					'stretch'    => 'Stretch',
				],
				'selectors' => [
					'{{WRAPPER}} .souvik-ws-popup-modal .souvik-ws-popup-info-wrap' => 'align-items: {{VALUE}} !important;',
				],
			]
		);

		$this->add_responsive_control(
			'popup_content_wrapper_gap',
			[
				'label'      => esc_html__( 'Gap (px)', 'souvik-ws-team-showcase' ),
				'type'       => Controls_Manager::SLIDER,
				'range'      => [ 'px' => [ 'min' => 0, 'max' => 100 ] ],
				'selectors'  => [
					'{{WRAPPER}} .souvik-ws-popup-modal .souvik-ws-popup-info-wrap' => 'gap: {{SIZE}}px !important;',
				],
			]
		);

		// Close button style customization
		$this->add_control(
			'popup_close_heading',
			[
				'label'     => esc_html__( 'Close Button', 'souvik-ws-team-showcase' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->start_controls_tabs( 'popup_close_style_tabs' );

		// Normal tab
		$this->start_controls_tab(
			'popup_close_tab_normal',
			[ 'label' => esc_html__( 'Normal', 'souvik-ws-team-showcase' ) ]
		);

		$this->add_control(
			'popup_close_color',
			[
				'label'     => esc_html__( 'Color', 'souvik-ws-team-showcase' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .souvik-ws-popup-close' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'popup_close_bg',
			[
				'label'     => esc_html__( 'Background Color', 'souvik-ws-team-showcase' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .souvik-ws-popup-close' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_tab();

		// Hover tab
		$this->start_controls_tab(
			'popup_close_tab_hover',
			[ 'label' => esc_html__( 'Hover', 'souvik-ws-team-showcase' ) ]
		);

		$this->add_control(
			'popup_close_color_hover',
			[
				'label'     => esc_html__( 'Color', 'souvik-ws-team-showcase' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .souvik-ws-popup-close:hover' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'popup_close_bg_hover',
			[
				'label'     => esc_html__( 'Background Color', 'souvik-ws-team-showcase' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .souvik-ws-popup-close:hover' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		// Size & Border Radius controls
		$this->add_responsive_control(
			'popup_close_size',
			[
				'label'      => esc_html__( 'Button Size', 'souvik-ws-team-showcase' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [ 'px' => [ 'min' => 20, 'max' => 80 ] ],
				'selectors'  => [
					'{{WRAPPER}} .souvik-ws-popup-close' => 'width: {{SIZE}}px; height: {{SIZE}}px; line-height: {{SIZE}}px;',
				],
			]
		);

		$this->add_responsive_control(
			'popup_close_font_size',
			[
				'label'      => esc_html__( 'Icon Size', 'souvik-ws-team-showcase' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'em' ],
				'range'      => [ 'px' => [ 'min' => 10, 'max' => 40 ] ],
				'selectors'  => [
					'{{WRAPPER}} .souvik-ws-popup-close' => 'font-size: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'popup_close_border_radius',
			[
				'label'      => esc_html__( 'Border Radius', 'souvik-ws-team-showcase' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .souvik-ws-popup-close' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		// Dark Mode Popup Options
		$this->add_control(
			'dark_popup_heading',
			[
				'label'     => esc_html__( 'Dark Mode Custom Styling', 'souvik-ws-team-showcase' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->add_control(
			'dark_popup_bg',
			[
				'label'     => esc_html__( 'Modal Background (Dark Mode)', 'souvik-ws-team-showcase' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#1e2130',
				'selectors' => [
					'body[data-souvik-ws-dark="1"] .souvik-ws-popup-modal, {{WRAPPER}} .souvik-ws-team-wrapper[data-dark="1"] .souvik-ws-popup-modal' => 'background-color: {{VALUE}} !important;',
				],
			]
		);

		$this->add_control(
			'dark_popup_text_color',
			[
				'label'     => esc_html__( 'Text/Title Color (Dark Mode)', 'souvik-ws-team-showcase' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#e2e8f0',
				'selectors' => [
					'body[data-souvik-ws-dark="1"] .souvik-ws-popup-modal, body[data-souvik-ws-dark="1"] .souvik-ws-popup-modal .souvik-ws-team-card__name, body[data-souvik-ws-dark="1"] .souvik-ws-popup-modal .souvik-ws-team-card__role, body[data-souvik-ws-dark="1"] .souvik-ws-popup-modal .souvik-ws-team-card__bio' => 'color: {{VALUE}} !important;',
				],
			]
		);

		$this->add_control(
			'dark_popup_close_color',
			[
				'label'     => esc_html__( 'Close Button Color (Dark Mode)', 'souvik-ws-team-showcase' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#e2e8f0',
				'selectors' => [
					'body[data-souvik-ws-dark="1"] .souvik-ws-popup-close' => 'color: {{VALUE}} !important;',
				],
			]
		);

		$this->add_control(
			'dark_popup_close_bg',
			[
				'label'     => esc_html__( 'Close Button Background (Dark Mode)', 'souvik-ws-team-showcase' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#2d3148',
				'selectors' => [
					'body[data-souvik-ws-dark="1"] .souvik-ws-popup-close' => 'background-color: {{VALUE}} !important;',
				],
			]
		);

		$this->end_controls_section();

		// -------------------------------------------------------------------
		// Controls Bar Style.
		// -------------------------------------------------------------------
		$this->start_controls_section(
			'style_controls_bar',
			[
				'label' => esc_html__( 'Controls Bar (Search & Toggler)', 'souvik-ws-team-showcase' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_responsive_control(
			'controls_bar_width',
			[
				'label'      => esc_html__( 'Width', 'souvik-ws-team-showcase' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%' ],
				'range'      => [
					'px' => [ 'min' => 100, 'max' => 1600 ],
					'%'  => [ 'min' => 10, 'max' => 100 ],
				],
				'selectors'  => [
					'{{WRAPPER}} .souvik-ws-controls-bar' => 'width: 100%; max-width: {{SIZE}}{{UNIT}} !important;',
				],
			]
		);

		$this->add_responsive_control(
			'controls_bar_padding',
			[
				'label'      => esc_html__( 'Padding', 'souvik-ws-team-showcase' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .souvik-ws-controls-bar' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;',
				],
			]
		);

		$this->add_responsive_control(
			'controls_bar_margin',
			[
				'label'      => esc_html__( 'Margin (Bottom)', 'souvik-ws-team-showcase' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'em' ],
				'range'      => [
					'px' => [ 'min' => 0, 'max' => 100 ],
				],
				'selectors'  => [
					'{{WRAPPER}} .souvik-ws-controls-bar' => 'margin-bottom: {{SIZE}}{{UNIT}} !important;',
				],
			]
		);

		$this->add_control(
			'controls_bar_bg',
			[
				'label'     => esc_html__( 'Background Color', 'souvik-ws-team-showcase' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .souvik-ws-controls-bar' => 'background-color: {{VALUE}} !important;',
				],
			]
		);

		$this->add_responsive_control(
			'controls_bar_border_radius',
			[
				'label'      => esc_html__( 'Border Radius', 'souvik-ws-team-showcase' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .souvik-ws-controls-bar' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;',
				],
			]
		);

		$this->add_control(
			'controls_bar_border_color',
			[
				'label'     => esc_html__( 'Border Color', 'souvik-ws-team-showcase' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .souvik-ws-controls-bar' => 'border-color: {{VALUE}} !important;',
				],
			]
		);

		$this->add_responsive_control(
			'controls_bar_border_width',
			[
				'label'      => esc_html__( 'Border Width', 'souvik-ws-team-showcase' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px' ],
				'selectors'  => [
					'{{WRAPPER}} .souvik-ws-controls-bar' => 'border-style: solid !important; border-width: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;',
				],
			]
		);

		// Layout options (Flex layout)
		$this->add_control(
			'controls_bar_layout_heading',
			[
				'label'     => esc_html__( 'Layout Options', 'souvik-ws-team-showcase' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->add_responsive_control(
			'controls_bar_direction',
			[
				'label'   => esc_html__( 'Flex Direction', 'souvik-ws-team-showcase' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'row',
				'options' => [
					'row'            => 'Row',
					'column'         => 'Column',
					'row-reverse'    => 'Row Reverse',
					'column-reverse' => 'Column Reverse',
				],
				'selectors' => [
					'{{WRAPPER}} .souvik-ws-controls-bar' => 'display: flex !important; flex-direction: {{VALUE}} !important;',
				],
			]
		);

		$this->add_responsive_control(
			'controls_bar_justify',
			[
				'label'   => esc_html__( 'Justify Content', 'souvik-ws-team-showcase' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'space-between',
				'options' => [
					'flex-start'    => 'Flex Start',
					'center'        => 'Center',
					'flex-end'      => 'Flex End',
					'space-between' => 'Space Between',
					'space-around'  => 'Space Around',
				],
				'selectors' => [
					'{{WRAPPER}} .souvik-ws-controls-bar' => 'justify-content: {{VALUE}} !important;',
				],
			]
		);

		$this->add_responsive_control(
			'controls_bar_align',
			[
				'label'   => esc_html__( 'Align Items', 'souvik-ws-team-showcase' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'center',
				'options' => [
					'flex-start' => 'Flex Start',
					'center'     => 'Center',
					'flex-end'   => 'Flex End',
					'stretch'    => 'Stretch',
				],
				'selectors' => [
					'{{WRAPPER}} .souvik-ws-controls-bar' => 'align-items: {{VALUE}} !important;',
				],
			]
		);

		$this->add_responsive_control(
			'controls_bar_gap',
			[
				'label'      => esc_html__( 'Gap (px)', 'souvik-ws-team-showcase' ),
				'type'       => Controls_Manager::SLIDER,
				'range'      => [ 'px' => [ 'min' => 0, 'max' => 100 ] ],
				'selectors'  => [
					'{{WRAPPER}} .souvik-ws-controls-bar' => 'gap: {{SIZE}}px !important;',
				],
			]
		);

		$this->add_responsive_control(
			'controls_bar_wrap',
			[
				'label'   => esc_html__( 'Flex Wrap', 'souvik-ws-team-showcase' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'wrap',
				'options' => [
					'nowrap'       => 'No Wrap',
					'wrap'         => 'Wrap',
					'wrap-reverse' => 'Wrap Reverse',
				],
				'selectors' => [
					'{{WRAPPER}} .souvik-ws-controls-bar' => 'flex-wrap: {{VALUE}} !important;',
				],
			]
		);

		$this->add_responsive_control(
			'controls_bar_align_self',
			[
				'label'   => esc_html__( 'Align Self', 'souvik-ws-team-showcase' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'auto',
				'options' => [
					'auto'       => 'Auto',
					'flex-start' => 'Flex Start',
					'center'     => 'Center',
					'flex-end'   => 'Flex End',
					'stretch'    => 'Stretch',
				],
				'selectors' => [
					'{{WRAPPER}} .souvik-ws-controls-bar' => 'align-self: {{VALUE}} !important;',
				],
			]
		);

		$this->add_responsive_control(
			'controls_bar_justify_self',
			[
				'label'   => esc_html__( 'Justify Self', 'souvik-ws-team-showcase' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'auto',
				'options' => [
					'auto'    => 'Auto',
					'start'   => 'Start',
					'center'  => 'Center',
					'end'     => 'End',
					'stretch' => 'Stretch',
				],
				'selectors' => [
					'{{WRAPPER}} .souvik-ws-controls-bar' => 'justify-self: {{VALUE}} !important;',
				],
			]
		);

		$this->add_control(
			'controls_bar_flex_grow',
			[
				'label'     => esc_html__( 'Flex Grow', 'souvik-ws-team-showcase' ),
				'type'      => Controls_Manager::NUMBER,
				'selectors' => [
					'{{WRAPPER}} .souvik-ws-controls-bar' => 'flex-grow: {{VALUE}} !important;',
				],
			]
		);

		$this->add_control(
			'controls_bar_flex_shrink',
			[
				'label'     => esc_html__( 'Flex Shrink', 'souvik-ws-team-showcase' ),
				'type'      => Controls_Manager::NUMBER,
				'selectors' => [
					'{{WRAPPER}} .souvik-ws-controls-bar' => 'flex-shrink: {{VALUE}} !important;',
				],
			]
		);

		$this->end_controls_section();

		// -------------------------------------------------------------------
		// Search Style.
		// -------------------------------------------------------------------
		$this->start_controls_section(
			'style_search',
			[
				'label' => esc_html__( 'Search Box', 'souvik-ws-team-showcase' ),
				'tab'   => Controls_Manager::TAB_STYLE,
				'condition' => [ 'search_enable' => 'yes' ],
			]
		);

		$this->add_responsive_control(
			'search_box_width',
			[
				'label'      => esc_html__( 'Width', 'souvik-ws-team-showcase' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%', 'vw' ],
				'default'    => [
					'size' => 300,
					'unit' => 'px',
				],
				'range'      => [
					'px' => [ 'min' => 100, 'max' => 800 ],
					'%'  => [ 'min' => 10, 'max' => 100 ],
				],
				'selectors'  => [
					'{{WRAPPER}} .souvik-ws-search-wrap' => 'width: {{SIZE}}{{UNIT}}; max-width: 100%;',
				],
			]
		);

		$this->add_responsive_control(
			'search_box_padding',
			[
				'label'      => esc_html__( 'Input Padding', 'souvik-ws-team-showcase' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', 'rem' ],
				'default'    => [
					'top'    => 10,
					'right'  => 16,
					'bottom' => 10,
					'left'   => 40,
					'unit'   => 'px',
					'isLinked' => false,
				],
				'selectors'  => [
					'{{WRAPPER}} .souvik-ws-search-input' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'search_box_margin',
			[
				'label'      => esc_html__( 'Margin', 'souvik-ws-team-showcase' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem' ],
				'selectors'  => [
					'{{WRAPPER}} .souvik-ws-search-wrap' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'search_typography',
				'selector' => '{{WRAPPER}} .souvik-ws-search-input',
			]
		);

		// Normal/Hover Tabs for input
		$this->start_controls_tabs( 'tabs_search_style' );

		// Normal State
		$this->start_controls_tab(
			'tab_search_normal',
			[
				'label' => esc_html__( 'Normal', 'souvik-ws-team-showcase' ),
			]
		);

		$this->add_control(
			'search_text_color',
			[
				'label'     => esc_html__( 'Text Color', 'souvik-ws-team-showcase' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .souvik-ws-search-input' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'search_bg_color',
			[
				'label'     => esc_html__( 'Background Color', 'souvik-ws-team-showcase' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .souvik-ws-search-input' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'search_border_color',
			[
				'label'     => esc_html__( 'Border Color', 'souvik-ws-team-showcase' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .souvik-ws-search-input' => 'border-color: {{VALUE}};',
				],
			]
		);

		$this->add_responsive_control(
			'search_border_width',
			[
				'label'      => esc_html__( 'Border Width', 'souvik-ws-team-showcase' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px' ],
				'selectors'  => [
					'{{WRAPPER}} .souvik-ws-search-input' => 'border-width: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}; border-style: solid;',
				],
			]
		);

		$this->add_responsive_control(
			'search_border_radius',
			[
				'label'      => esc_html__( 'Border Radius', 'souvik-ws-team-showcase' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem' ],
				'selectors'  => [
					'{{WRAPPER}} .souvik-ws-search-input' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'search_box_shadow',
				'selector' => '{{WRAPPER}} .souvik-ws-search-input',
			]
		);

		$this->end_controls_tab();

		// Hover / Focus State
		$this->start_controls_tab(
			'tab_search_hover',
			[
				'label' => esc_html__( 'Focus / Hover', 'souvik-ws-team-showcase' ),
			]
		);

		$this->add_control(
			'search_text_color_hover',
			[
				'label'     => esc_html__( 'Text Color', 'souvik-ws-team-showcase' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .souvik-ws-search-input:focus, {{WRAPPER}} .souvik-ws-search-input:hover' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'search_bg_color_hover',
			[
				'label'     => esc_html__( 'Background Color', 'souvik-ws-team-showcase' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .souvik-ws-search-input:focus, {{WRAPPER}} .souvik-ws-search-input:hover' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'search_border_color_hover',
			[
				'label'     => esc_html__( 'Border Color', 'souvik-ws-team-showcase' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .souvik-ws-search-input:focus, {{WRAPPER}} .souvik-ws-search-input:hover' => 'border-color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'search_box_shadow_hover',
				'selector' => '{{WRAPPER}} .souvik-ws-search-input:focus, {{WRAPPER}} .souvik-ws-search-input:hover',
			]
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		// Placeholder Specific Styles
		$this->add_control(
			'heading_search_placeholder',
			[
				'label'     => esc_html__( 'Placeholder Styling', 'souvik-ws-team-showcase' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->add_control(
			'search_placeholder_color',
			[
				'label'     => esc_html__( 'Placeholder Color', 'souvik-ws-team-showcase' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .souvik-ws-search-input::placeholder' => 'color: {{VALUE}};',
				],
			]
		);

		// Search Icon Specific Styles
		$this->add_control(
			'heading_search_icon',
			[
				'label'     => esc_html__( 'Search Icon Styling', 'souvik-ws-team-showcase' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->add_control(
			'search_icon_color_custom',
			[
				'label'     => esc_html__( 'Icon Color', 'souvik-ws-team-showcase' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .souvik-ws-search-icon' => 'color: {{VALUE}};',
					'{{WRAPPER}} .souvik-ws-search-icon svg' => 'fill: {{VALUE}};',
				],
			]
		);

		$this->add_responsive_control(
			'search_icon_size',
			[
				'label'      => esc_html__( 'Icon Size', 'souvik-ws-team-showcase' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'em' ],
				'range'      => [
					'px' => [ 'min' => 10, 'max' => 40 ],
				],
				'selectors'  => [
					'{{WRAPPER}} .souvik-ws-search-icon' => 'font-size: {{SIZE}}{{UNIT}}; line-height: 1;',
					'{{WRAPPER}} .souvik-ws-search-icon svg' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'search_icon_left_offset',
			[
				'label'      => esc_html__( 'Icon Left Offset', 'souvik-ws-team-showcase' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [
					'px' => [ 'min' => 5, 'max' => 30 ],
				],
				'selectors'  => [
					'{{WRAPPER}} .souvik-ws-search-icon' => 'left: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

		// -------------------------------------------------------------------
		// Dark Mode Switcher Style.
		// -------------------------------------------------------------------
		$this->start_controls_section(
			'style_dark_switcher',
			[
				'label' => esc_html__( 'Dark Mode Switcher', 'souvik-ws-team-showcase' ),
				'tab'   => Controls_Manager::TAB_STYLE,
				'condition' => [
					'dark_mode_enable' => 'yes',
					'dark_mode_switcher' => 'yes',
				],
			]
		);

		$this->add_responsive_control(
			'switcher_alignment',
			[
				'label'     => esc_html__( 'Alignment', 'souvik-ws-team-showcase' ),
				'type'      => Controls_Manager::CHOOSE,
				'options'   => [
					'flex-start' => [ 'title' => esc_html__( 'Left', 'souvik-ws-team-showcase' ), 'icon' => 'eicon-text-align-left' ],
					'center'     => [ 'title' => esc_html__( 'Center', 'souvik-ws-team-showcase' ), 'icon' => 'eicon-text-align-center' ],
					'flex-end'   => [ 'title' => esc_html__( 'Right', 'souvik-ws-team-showcase' ), 'icon' => 'eicon-text-align-right' ],
				],
				'selectors' => [
					'{{WRAPPER}} .souvik-ws-dark-toggle-wrap' => 'justify-content: {{VALUE}};',
				],
			]
		);

		$this->add_responsive_control(
			'switcher_spacing_bottom',
			[
				'label'     => esc_html__( 'Bottom Spacing', 'souvik-ws-team-showcase' ),
				'type'      => Controls_Manager::SLIDER,
				'default'   => [ 'size' => 24 ],
				'range'     => [ 'px' => [ 'min' => 0, 'max' => 80 ] ],
				'selectors' => [
					'{{WRAPPER}} .souvik-ws-dark-toggle-wrap' => 'margin-bottom: {{SIZE}}px;',
				],
			]
		);

		$this->start_controls_tabs( 'switcher_style_tabs' );

		// Light state
		$this->start_controls_tab(
			'switcher_tab_light',
			[ 'label' => esc_html__( 'Light State', 'souvik-ws-team-showcase' ) ]
		);

		$this->add_control(
			'switcher_bg_light',
			[
				'label'     => esc_html__( 'Toggle Background', 'souvik-ws-team-showcase' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .souvik-ws-dark-toggle-btn' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'switcher_border_light',
			[
				'label'     => esc_html__( 'Toggle Border Color', 'souvik-ws-team-showcase' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .souvik-ws-dark-toggle-btn' => 'border-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'switcher_knob_light',
			[
				'label'     => esc_html__( 'Knob Background', 'souvik-ws-team-showcase' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .souvik-ws-dark-toggle-btn::after' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_tab();

		// Dark state
		$this->start_controls_tab(
			'switcher_tab_dark',
			[ 'label' => esc_html__( 'Dark State', 'souvik-ws-team-showcase' ) ]
		);

		$this->add_control(
			'switcher_bg_dark',
			[
				'label'     => esc_html__( 'Toggle Background', 'souvik-ws-team-showcase' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .souvik-ws-team-wrapper[data-dark="1"] .souvik-ws-dark-toggle-btn' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'switcher_border_dark',
			[
				'label'     => esc_html__( 'Toggle Border Color', 'souvik-ws-team-showcase' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .souvik-ws-team-wrapper[data-dark="1"] .souvik-ws-dark-toggle-btn' => 'border-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'switcher_knob_dark',
			[
				'label'     => esc_html__( 'Knob Background', 'souvik-ws-team-showcase' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .souvik-ws-team-wrapper[data-dark="1"] .souvik-ws-dark-toggle-btn::after' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->end_controls_section();


		// -------------------------------------------------------------------
		// Typography — Custom Fields Style Section.
		// -------------------------------------------------------------------
		$this->start_controls_section(
			'style_custom_extra_fields',
			[
				'label' => esc_html__( 'Typography — Custom Fields', 'souvik-ws-team-showcase' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$custom_styles_repeater = new Repeater();

		$custom_styles_repeater->add_control(
			'target_field',
			[
				'label'       => esc_html__( 'Target Custom Field', 'souvik-ws-team-showcase' ),
				'type'        => Controls_Manager::SELECT,
				'default'     => 'custom',
				'description' => esc_html__( 'Type the exact Field Label used in "Custom & Extra Fields" above, or pick by position.', 'souvik-ws-team-showcase' ),
				'options'     => [
					'custom'  => esc_html__( '✎ Match by Field Label (recommended)', 'souvik-ws-team-showcase' ),
					'index_0' => esc_html__( 'Custom Field #1 (by position)', 'souvik-ws-team-showcase' ),
					'index_1' => esc_html__( 'Custom Field #2 (by position)', 'souvik-ws-team-showcase' ),
					'index_2' => esc_html__( 'Custom Field #3 (by position)', 'souvik-ws-team-showcase' ),
					'index_3' => esc_html__( 'Custom Field #4 (by position)', 'souvik-ws-team-showcase' ),
					'index_4' => esc_html__( 'Custom Field #5 (by position)', 'souvik-ws-team-showcase' ),
					'index_5' => esc_html__( 'Custom Field #6 (by position)', 'souvik-ws-team-showcase' ),
					'index_6' => esc_html__( 'Custom Field #7 (by position)', 'souvik-ws-team-showcase' ),
					'index_7' => esc_html__( 'Custom Field #8 (by position)', 'souvik-ws-team-showcase' ),
					'index_8' => esc_html__( 'Custom Field #9 (by position)', 'souvik-ws-team-showcase' ),
					'index_9' => esc_html__( 'Custom Field #10 (by position)', 'souvik-ws-team-showcase' ),
				],
			]
		);

		$custom_styles_repeater->add_control(
			'target_label',
			[
				'label'       => esc_html__( 'Field Label (must match exactly)', 'souvik-ws-team-showcase' ),
				'type'        => Controls_Manager::TEXT,
				'placeholder' => esc_html__( 'e.g. Location, Experience, Skills', 'souvik-ws-team-showcase' ),
				'description' => esc_html__( 'Enter the exact same label you set in "Field Label" in the Content tab.', 'souvik-ws-team-showcase' ),
				'condition'   => [ 'target_field' => 'custom' ],
			]
		);

		$custom_styles_repeater->add_control(
			'field_color',
			[
				'label'     => esc_html__( 'Text/Value Color', 'souvik-ws-team-showcase' ),
				'type'      => Controls_Manager::COLOR,
			]
		);

		$custom_styles_repeater->add_control(
			'field_label_color',
			[
				'label'     => esc_html__( 'Label Color (Bold part)', 'souvik-ws-team-showcase' ),
				'type'      => Controls_Manager::COLOR,
			]
		);

		$custom_styles_repeater->add_control(
			'field_hover_color',
			[
				'label'     => esc_html__( 'Hover Text Color', 'souvik-ws-team-showcase' ),
				'type'      => Controls_Manager::COLOR,
			]
		);

		$custom_styles_repeater->add_control(
			'field_icon_color',
			[
				'label'     => esc_html__( 'Icon Color', 'souvik-ws-team-showcase' ),
				'type'      => Controls_Manager::COLOR,
			]
		);

		$custom_styles_repeater->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'field_typography',
				'label'    => esc_html__( 'Typography', 'souvik-ws-team-showcase' ),
			]
		);

		$custom_styles_repeater->add_control(
			'field_bg_color',
			[
				'label'     => esc_html__( 'Background Color', 'souvik-ws-team-showcase' ),
				'type'      => Controls_Manager::COLOR,
			]
		);

		$custom_styles_repeater->add_control(
			'field_bg_hover_color',
			[
				'label'     => esc_html__( 'Hover Background Color', 'souvik-ws-team-showcase' ),
				'type'      => Controls_Manager::COLOR,
			]
		);

		$custom_styles_repeater->add_responsive_control(
			'field_padding',
			[
				'label'      => esc_html__( 'Padding', 'souvik-ws-team-showcase' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em' ],
			]
		);

		$custom_styles_repeater->add_responsive_control(
			'field_margin',
			[
				'label'      => esc_html__( 'Margin', 'souvik-ws-team-showcase' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em' ],
			]
		);

		$custom_styles_repeater->add_responsive_control(
			'field_border_radius',
			[
				'label'      => esc_html__( 'Border Radius', 'souvik-ws-team-showcase' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
			]
		);

		$custom_styles_repeater->add_control(
			'field_border_color',
			[
				'label'     => esc_html__( 'Border Color', 'souvik-ws-team-showcase' ),
				'type'      => Controls_Manager::COLOR,
			]
		);

		$custom_styles_repeater->add_responsive_control(
			'field_border_width',
			[
				'label'      => esc_html__( 'Border Width', 'souvik-ws-team-showcase' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px' ],
			]
		);

		$this->add_control(
			'custom_fields_styles',
			[
				'label'       => esc_html__( 'Configure Custom Field Styles', 'souvik-ws-team-showcase' ),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $custom_styles_repeater->get_controls(),
				'default'     => [],
				'title_field' => '{{{ target_field === "custom" ? ( target_label || "Unnamed" ) : ( "Field #" + ( parseInt( target_field.replace("index_","") ) + 1 ) ) }}}',
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'style_card_bottom',
			[
				'label' => esc_html__( 'Card Bottom Area', 'souvik-ws-team-showcase' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'card_bottom_bg',
			[
				'label'     => esc_html__( 'Background Color', 'souvik-ws-team-showcase' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .souvik-ws-team-card__bottom' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_responsive_control(
			'card_bottom_padding',
			[
				'label'      => esc_html__( 'Padding', 'souvik-ws-team-showcase' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .souvik-ws-team-card__bottom' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'card_bottom_margin',
			[
				'label'      => esc_html__( 'Margin', 'souvik-ws-team-showcase' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .souvik-ws-team-card__bottom' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Border::get_type(),
			[
				'name'     => 'card_bottom_border',
				'selector' => '{{WRAPPER}} .souvik-ws-team-card__bottom',
			]
		);

		$this->add_responsive_control(
			'card_bottom_border_radius',
			[
				'label'      => esc_html__( 'Border Radius', 'souvik-ws-team-showcase' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .souvik-ws-team-card__bottom' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'card_bottom_layout_heading',
			[
				'label'     => esc_html__( 'Layout Options', 'souvik-ws-team-showcase' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->add_responsive_control(
			'card_bottom_direction',
			[
				'label'   => esc_html__( 'Direction', 'souvik-ws-team-showcase' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'row',
				'options' => [
					'row'    => esc_html__( 'Row (Side-by-Side)', 'souvik-ws-team-showcase' ),
					'column' => esc_html__( 'Column (Stacked)', 'souvik-ws-team-showcase' ),
				],
				'selectors' => [
					'{{WRAPPER}} .souvik-ws-team-card__bottom' => 'flex-direction: {{VALUE}};',
				],
			]
		);

		$this->add_responsive_control(
			'card_bottom_justify',
			[
				'label'   => esc_html__( 'Justify Content', 'souvik-ws-team-showcase' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'space-between',
				'options' => [
					'flex-start'    => esc_html__( 'Start', 'souvik-ws-team-showcase' ),
					'center'        => esc_html__( 'Center', 'souvik-ws-team-showcase' ),
					'flex-end'      => esc_html__( 'End', 'souvik-ws-team-showcase' ),
					'space-between' => esc_html__( 'Space Between', 'souvik-ws-team-showcase' ),
					'space-around'  => esc_html__( 'Space Around', 'souvik-ws-team-showcase' ),
				],
				'selectors' => [
					'{{WRAPPER}} .souvik-ws-team-card__bottom' => 'justify-content: {{VALUE}};',
				],
			]
		);

		$this->add_responsive_control(
			'card_bottom_align',
			[
				'label'   => esc_html__( 'Align Items', 'souvik-ws-team-showcase' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'center',
				'options' => [
					'flex-start' => esc_html__( 'Start', 'souvik-ws-team-showcase' ),
					'center'     => esc_html__( 'Center', 'souvik-ws-team-showcase' ),
					'flex-end'   => esc_html__( 'End', 'souvik-ws-team-showcase' ),
					'stretch'    => esc_html__( 'Stretch', 'souvik-ws-team-showcase' ),
				],
				'selectors' => [
					'{{WRAPPER}} .souvik-ws-team-card__bottom' => 'align-items: {{VALUE}};',
				],
			]
		);

		$this->add_responsive_control(
			'card_bottom_gap',
			[
				'label'      => esc_html__( 'Gap / Spacing', 'souvik-ws-team-showcase' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'em' ],
				'range'      => [
					'px' => [ 'min' => 0, 'max' => 50 ],
				],
				'default'    => [
					'size' => 12,
					'unit' => 'px',
				],
				'selectors'  => [
					'{{WRAPPER}} .souvik-ws-team-card__bottom' => 'gap: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();


		// ===================================================================
		//  ADVANCED TAB
		// ===================================================================

		// -------------------------------------------------------------------
		// Animation — Per Element.
		// -------------------------------------------------------------------
		$this->start_controls_section(
			'section_animations',
			[
				'label' => esc_html__( 'Animation — Per Element', 'souvik-ws-team-showcase' ),
				'tab'   => Controls_Manager::TAB_ADVANCED,
			]
		);

		$elements_to_animate = [
			'card'        => esc_html__( 'Card Wrapper', 'souvik-ws-team-showcase' ),
			'image'       => esc_html__( 'Image', 'souvik-ws-team-showcase' ),
			'name'        => esc_html__( 'Name', 'souvik-ws-team-showcase' ),
			'designation' => esc_html__( 'Designation', 'souvik-ws-team-showcase' ),
			'badge'       => esc_html__( 'Department Badge', 'souvik-ws-team-showcase' ),
			'bio'         => esc_html__( 'Bio Text', 'souvik-ws-team-showcase' ),
			'socials'     => esc_html__( 'Social Icons', 'souvik-ws-team-showcase' ),
			'button'      => esc_html__( 'Button', 'souvik-ws-team-showcase' ),
			'filter'      => esc_html__( 'Filter Bar', 'souvik-ws-team-showcase' ),
			'popup'       => esc_html__( 'Popup Modal', 'souvik-ws-team-showcase' ),
		];

		foreach ( $elements_to_animate as $key => $label ) {
			$this->add_control(
				'anim_' . $key . '_enable',
				[
					'label'        => sprintf( esc_html__( 'Animate %s', 'souvik-ws-team-showcase' ), $label ),
					'type'         => Controls_Manager::SWITCHER,
					'label_on'     => esc_html__( 'Yes', 'souvik-ws-team-showcase' ),
					'label_off'    => esc_html__( 'No', 'souvik-ws-team-showcase' ),
					'return_value' => 'yes',
					'default'      => 'no',
				]
			);
		}

		$this->end_controls_section();

		// -------------------------------------------------------------------
		// Performance.
		// -------------------------------------------------------------------
		$this->start_controls_section(
			'section_perf',
			[
				'label' => esc_html__( 'Performance', 'souvik-ws-team-showcase' ),
				'tab'   => Controls_Manager::TAB_ADVANCED,
			]
		);

		$this->add_control(
			'lazy_load',
			[
				'label'   => esc_html__( 'Lazy load images', 'souvik-ws-team-showcase' ),
				'type'    => Controls_Manager::SWITCHER,
				'default' => 'yes',
			]
		);

		$this->add_control(
			'skeleton_loader',
			[
				'label'   => esc_html__( 'Skeleton loader', 'souvik-ws-team-showcase' ),
				'type'    => Controls_Manager::SWITCHER,
				'default' => 'no',
			]
		);

		$this->end_controls_section();

		// -------------------------------------------------------------------
		// Search.
		// -------------------------------------------------------------------
		$this->start_controls_section(
			'section_search',
			[
				'label' => esc_html__( 'Search', 'souvik-ws-team-showcase' ),
				'tab'   => Controls_Manager::TAB_ADVANCED,
			]
		);
		$this->add_control(
			'search_enable',
			[
				'label'   => esc_html__( 'Enable search box', 'souvik-ws-team-showcase' ),
				'type'    => Controls_Manager::SWITCHER,
				'default' => 'no',
			]
		);

		$this->add_control(
			'search_icon',
			[
				'label' => esc_html__( 'Search Icon', 'souvik-ws-team-showcase' ),
				'type' => Controls_Manager::ICONS,
				'default' => [
					'value' => 'fas fa-search',
					'library' => 'solid',
				],
				'condition' => [ 'search_enable' => 'yes' ],
			]
		);

		$this->add_control(
			'search_placeholder',
			[
				'label'     => esc_html__( 'Placeholder text', 'souvik-ws-team-showcase' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => esc_html__( 'Search team...', 'souvik-ws-team-showcase' ),
				'condition' => [ 'search_enable' => 'yes' ],
			]
		);

		$this->end_controls_section();

		// -------------------------------------------------------------------
		// Dark Mode.
		// -------------------------------------------------------------------
		$this->start_controls_section(
			'section_dark',
			[
				'label' => esc_html__( 'Dark Mode', 'souvik-ws-team-showcase' ),
				'tab'   => Controls_Manager::TAB_ADVANCED,
			]
		);

		$this->add_control(
			'dark_mode_enable',
			[
				'label'   => esc_html__( 'Enable dark mode', 'souvik-ws-team-showcase' ),
				'type'    => Controls_Manager::SWITCHER,
				'default' => 'no',
			]
		);

		$this->add_control(
			'dark_mode_switcher',
			[
				'label'     => esc_html__( 'Add Front-End Dark Mode Toggle', 'souvik-ws-team-showcase' ),
				'type'      => Controls_Manager::SWITCHER,
				'default'   => 'no',
				'condition' => [ 'dark_mode_enable' => 'yes' ],
			]
		);

		$this->add_control(
			'dark_wrapper_bg',
			[
				'label'     => esc_html__( 'Dark Wrapper Background', 'souvik-ws-team-showcase' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#0f172a',
				'selectors' => [
					'{{WRAPPER}} .souvik-ws-team-wrapper[data-dark="1"]' => '--souvik-ws-dark-wrapper-bg: {{VALUE}};',
				],
				'condition' => [ 'dark_mode_enable' => 'yes' ],
			]
		);

		$this->add_control(
			'dark_card_bg',
			[
				'label'     => esc_html__( 'Dark card background', 'souvik-ws-team-showcase' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#1a1a2e',
				'selectors' => [
					'{{WRAPPER}} .souvik-ws-team-wrapper[data-dark="1"] .souvik-ws-team-card' => 'background-color: {{VALUE}};',
				],
				'condition' => [ 'dark_mode_enable' => 'yes' ],
			]
		);

		$this->add_control(
			'dark_text_color',
			[
				'label'     => esc_html__( 'Dark text color', 'souvik-ws-team-showcase' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#e2e8f0',
				'selectors' => [
					'{{WRAPPER}} .souvik-ws-team-wrapper[data-dark="1"] .souvik-ws-team-card__name'  => 'color: {{VALUE}};',
					'{{WRAPPER}} .souvik-ws-team-wrapper[data-dark="1"] .souvik-ws-team-card__role'  => 'color: {{VALUE}};',
					'{{WRAPPER}} .souvik-ws-team-wrapper[data-dark="1"] .souvik-ws-team-card__bio'   => 'color: {{VALUE}};',
				],
				'condition' => [ 'dark_mode_enable' => 'yes' ],
			]
		);

		$this->end_controls_section();

		// -------------------------------------------------------------------
		// Custom CSS.
		// -------------------------------------------------------------------
		$this->start_controls_section(
			'section_custom_css',
			[
				'label' => esc_html__( 'Custom CSS', 'souvik-ws-team-showcase' ),
				'tab'   => Controls_Manager::TAB_ADVANCED,
			]
		);

		$this->add_control(
			'custom_css',
			[
				'label'       => esc_html__( 'CSS', 'souvik-ws-team-showcase' ),
				'type'        => Controls_Manager::CODE,
				'language'    => 'css',
				'rows'        => 12,
				'show_label'  => false,
				'description' => esc_html__( 'Target: .souvik-ws-team-card { }', 'souvik-ws-team-showcase' ),
				'default'     => '.souvik-ws-team-card { }',
			]
		);

		$this->end_controls_section();
	}

	// -----------------------------------------------------------------------
	// Front-end render
	// -----------------------------------------------------------------------

	protected function render(): void {
		$s = $this->get_settings_for_display();

		// 1. Conditionally enqueue GSAP modules.
		Souvik_WS_Asset_Enqueuer::enqueue_for_widget( $s );

		// 2. Build animation config JSON.
		$anim_config = [];
		foreach ( self::ELEM_KEYS as $key ) {
			$anim_config[ $key ] = ( ( $s[ 'anim_' . $key . '_enable' ] ?? 'no' ) === 'yes' );
		}

		// 3. Resolve dynamic mapping keys
		$resolve_key = function( $field_base ) use ( $s ) {
			$val = $s[ $field_base ] ?? '';
			if ( 'custom_meta_key' === $val ) {
				return $s[ $field_base . '_custom' ] ?? '';
			}
			return $val;
		};

		$name_key = $resolve_key( 'acf_name' );
		$designation_key = $resolve_key( 'acf_designation' );
		$department_key = $resolve_key( 'acf_department' );
		$bio_key = $resolve_key( 'acf_bio' );
		$photo_key = $resolve_key( 'acf_photo' );
		$social_key = $resolve_key( 'acf_social_links' );
		$btn_lbl_key = $resolve_key( 'acf_button_label' );
		$btn_url_key = $resolve_key( 'acf_button_url' );

		// 4. Resolve members list.
		$mode    = $s['data_source_mode'] ?? 'manual';
		$members = [];

		if ( 'dynamic' === $mode ) {
			$posts = Souvik_WS_Query::get_members( $s );
			foreach ( $posts as $post ) {
				$photo_raw = Souvik_WS_Query::get_acf_field( $photo_key ?: 'post_thumbnail', $post->ID );
				$photo_url = is_array( $photo_raw ) ? ( $photo_raw['url'] ?? '' ) : (string) $photo_raw;

				$btn_url_raw = Souvik_WS_Query::get_acf_field( $btn_url_key ?: 'post_permalink', $post->ID );
				$btn_url     = is_array( $btn_url_raw ) ? ( $btn_url_raw['url'] ?? '#' ) : ( (string) $btn_url_raw ?: '#' );

				$acf_socials = Souvik_WS_Query::get_acf_field( $social_key ?: 'member_social_links', $post->ID );
				$socials_mapped = [];
				if ( is_array( $acf_socials ) ) {
					foreach ( $acf_socials as $row ) {
						$platform = $row['platform'] ?? '';
						$url_val = $row['url'] ?? '';
						if ( $platform && $url_val ) {
							$custom_icon_raw = $row['custom_icon'] ?? '';
							$custom_icon_formatted = is_array( $custom_icon_raw ) ? $custom_icon_raw : [
								'value' => $custom_icon_raw,
								'library' => str_starts_with( $custom_icon_raw, 'fab' ) ? 'fa-brands' : 'fa-solid',
							];

							$socials_mapped[] = [
								'platform'     => $platform,
								'icon_source'  => $row['icon_source'] ?? 'default',
								'custom_icon'  => $custom_icon_formatted,
								'custom_image' => $row['custom_image'] ?? [],
								'url'          => is_array( $url_val ) ? $url_val : [ 'url' => $url_val ],
							];
						}
					}
				}

				// Resolve CPT taxonomy (defaulting to the configured one) if department value is empty
				$dept_val = (string) Souvik_WS_Query::get_acf_field( $department_key ?: 'member_department', $post->ID );
				if ( ! $dept_val ) {
					$tax_slug = sanitize_key( $s['query_tax'] ?: get_option( 'souvik_ws_team_taxonomy_slug', 'department' ) );
					if ( taxonomy_exists( $tax_slug ) ) {
						$terms = get_the_terms( $post->ID, $tax_slug );
						if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
							$dept_val = $terms[0]->name;
						}
					}
				}

				// Resolve custom extra fields dynamically
				$extra_fields_resolved = [];
				$custom_extras = $s['custom_extra_fields'] ?? [];
				foreach ( $custom_extras as $extra ) {
					$extra_src = $extra['dynamic_source'] ?? '';
					if ( 'custom_meta_key' === $extra_src ) {
						$extra_src = $extra['dynamic_source_custom'] ?? '';
					}
					
					$extra_val = '';
					if ( $extra_src ) {
						$extra_val = Souvik_WS_Query::get_acf_field( $extra_src, $post->ID );
					}
					
					$extra_fields_resolved[] = [
						'label'         => $extra['field_label'] ?? '',
						'value'         => is_array( $extra_val ) ? ($extra_val['url'] ?? '') : (string) $extra_val,
						'icon'          => $extra['field_icon'] ?? [],
						'html_tag'      => $extra['html_tag'] ?? 'p',
						'link_to'       => $extra['link_to'] ?? 'none',
						'custom_url'    => $extra['custom_url'] ?? '',
						'display_style' => $extra['display_style'] ?? 'text',
						'show_label'    => $extra['show_label'] ?? 'yes',
						'_id'           => $extra['_id'] ?? '',
					];
				}

				$members[] = [
					'name'         => (string) Souvik_WS_Query::get_acf_field( $name_key ?: 'post_title', $post->ID ),
					'designation'  => (string) Souvik_WS_Query::get_acf_field( $designation_key ?: 'member_designation', $post->ID ),
					'department'   => $dept_val,
					'bio'          => (string) Souvik_WS_Query::get_acf_field( $bio_key ?: 'member_short_bio', $post->ID ),
					'photo_url'    => $photo_url,
					'button_label' => (string) Souvik_WS_Query::get_acf_field( $btn_lbl_key ?: 'member_button_text', $post->ID ),
					'button_url'   => $btn_url,
					'socials'      => $socials_mapped,
					'extras'       => $extra_fields_resolved,
					'post_id'      => $post->ID,
				];
			}
		} else {
			$manual_members = $s['team_members'] ?? [];
			foreach ( $manual_members as $m ) {
				// Resolve custom extra fields manually
				$extra_fields_resolved = [];
				$custom_extras = $s['custom_extra_fields'] ?? [];
				foreach ( $custom_extras as $extra ) {
					$extra_fields_resolved[] = [
						'label'         => $extra['field_label'] ?? '',
						'value'         => $extra['manual_value'] ?? '',
						'icon'          => $extra['field_icon'] ?? [],
						'html_tag'      => $extra['html_tag'] ?? 'p',
						'link_to'       => $extra['link_to'] ?? 'none',
						'custom_url'    => $extra['custom_url'] ?? '',
						'display_style' => $extra['display_style'] ?? 'text',
						'show_label'    => $extra['show_label'] ?? 'yes',
						'_id'           => $extra['_id'] ?? '',
					];
				}
				$m['extras'] = $extra_fields_resolved;
				$members[] = $m;
			}
		}

		// 5. Computed wrapper attributes.
		$wrap_id   = 'souvik-ws-team-' . $this->get_id();
		$cols      = absint( $s['columns'] ?? 3 );
		$col_gap   = (int) ( $s['column_gap']['size'] ?? 30 );
		$row_gap   = (int) ( $s['row_gap']['size'] ?? 30 );
		$card_style = sanitize_key( $s['card_style'] ?? 'classic' );
		$img_pos   = sanitize_key( $s['image_position'] ?? 'top' );
		$dark      = ( ( $s['dark_mode_enable'] ?? 'no' ) === 'yes' ) ? '1' : '0';
		$hover_tr  = sanitize_key( $s['card_hover_transform'] ?? 'lift' );
		$img_zoom  = ( ( $s['image_hover_zoom'] ?? 'yes' ) === 'yes' ) ? '1' : '0';
		$eq_height = ( ( $s['equal_height'] ?? 'yes' ) === 'yes' ) ? '1' : '0';
		$lazy_load = ( ( $s['lazy_load'] ?? 'yes' ) === 'yes' );
		$skeleton  = ( ( $s['skeleton_loader'] ?? 'no' ) === 'yes' );

		// Filter bar data.
		$filter_show = ( ( $s['filter_show'] ?? 'no' ) === 'yes' );
		$filter_style = sanitize_key( $s['filter_style'] ?? 'pills' );
		$filter_all   = esc_html( $s['filter_all_label'] ?? __( 'All', 'souvik-ws-team-showcase' ) );

		// Popup data.
		$popup_enable  = ( ( $s['popup_enable'] ?? 'no' ) === 'yes' );
		$popup_trigger = sanitize_key( $s['popup_trigger'] ?? 'card_click' );
		$popup_size    = sanitize_key( $s['popup_size'] ?? 'md' );
		$popup_fields  = (array) ( $s['popup_fields'] ?? [] );

		// Pagination.
		$pag_type       = sanitize_key( $s['pagination_type'] ?? 'none' );
		$items_per_page = (int) ( $s['items_per_page']['size'] ?? 6 );
		$load_more_lbl  = esc_html( $s['load_more_label'] ?? __( 'Load More', 'souvik-ws-team-showcase' ) );

		// Search.
		$search_enable = ( ( $s['search_enable'] ?? 'no' ) === 'yes' );
		$search_ph     = esc_attr( $s['search_placeholder'] ?? __( 'Search team...', 'souvik-ws-team-showcase' ) );

		// Custom CSS.
		$custom_css = $s['custom_css'] ?? '';

		// Generate dynamic CSS block for repeaters
		$dynamic_repeater_css = '';

		// A. Social Icon style repeater overrides
		$social_hover_style = sanitize_key( $s['social_hover_style'] ?? 'brand' );
		$social_styles = $s['individual_social_styles'] ?? [];
		if ( 'individual' === $social_hover_style && ! empty( $social_styles ) ) {
			foreach ( $social_styles as $item ) {
				$platform = sanitize_key( $item['platform'] ?? '' );
				if ( ! $platform ) {
					continue;
				}

				$selector = "#{$wrap_id} .souvik-ws-social-icon--{$platform}";
				$item_css = '';

				if ( ! empty( $item['icon_color'] ) ) {
					$item_css .= "color: {$item['icon_color']} !important;";
				}
				if ( ! empty( $item['icon_bg'] ) ) {
					$item_css .= "background-color: {$item['icon_bg']} !important;";
				}
				if ( ! empty( $item['icon_size']['size'] ) ) {
					$size = intval( $item['icon_size']['size'] );
					$item_css .= "font-size: {$size}px !important; width: calc({$size}px + 18px) !important; height: calc({$size}px + 18px) !important; line-height: calc({$size}px + 18px) !important;";
				}
				if ( ! empty( $item['icon_border_radius'] ) ) {
					$unit = $item['icon_border_radius']['unit'] ?? 'px';
					$top = $item['icon_border_radius']['top'] ?? '';
					$right = $item['icon_border_radius']['right'] ?? '';
					$bottom = $item['icon_border_radius']['bottom'] ?? '';
					$left = $item['icon_border_radius']['left'] ?? '';
					if ( $top !== '' || $right !== '' || $bottom !== '' || $left !== '' ) {
						$item_css .= "border-radius: {$top}{$unit} {$right}{$unit} {$bottom}{$unit} {$left}{$unit} !important;";
					}
				}

				if ( $item_css ) {
					$dynamic_repeater_css .= "{$selector} { {$item_css} }\n";
				}

				// Hover overrides
				$hover_css = '';
				if ( ! empty( $item['icon_color_hover'] ) ) {
					$hover_css .= "color: {$item['icon_color_hover']} !important;";
				}
				if ( ! empty( $item['icon_bg_hover'] ) ) {
					$hover_css .= "background-color: {$item['icon_bg_hover']} !important;";
				}

				if ( $hover_css ) {
					$dynamic_repeater_css .= "{$selector}:hover { {$hover_css} }\n";
				}
			}
		}

		// B. Custom Fields Style Repeater Overrides (For typography global compatibility)
		$custom_styles = $s['custom_fields_styles'] ?? [];
		$custom_extras = $s['custom_extra_fields'] ?? [];
		if ( ! empty( $custom_styles ) && ! empty( $custom_extras ) ) {
			$resolve_global_color = function( $val ) {
				if ( ! $val ) {
					return '';
				}
				if ( str_starts_with( $val, 'globals/colors?id=' ) ) {
					$color_id = substr( $val, 18 );
					$kit = \Elementor\Plugin::$instance->kits_manager->get_active_kit();
					if ( $kit ) {
						$kit_settings = $kit->get_settings();
						return $kit_settings[ 'system_colors_' . $color_id ] ?? ( $kit_settings[ 'custom_colors_' . $color_id ] ?? ( $kit_settings[ $color_id ] ?? $val ) );
					}
				}
				return $val;
			};

			foreach ( $custom_styles as $style_item ) {
				$target = $style_item['target_field'] ?? '';
				$target_id = '';
				
				if ( str_starts_with( $target, 'index_' ) ) {
					$idx = intval( substr( $target, 6 ) );
					if ( isset( $custom_extras[ $idx ] ) ) {
						$target_id = $custom_extras[ $idx ]['_id'] ?? '';
					}
				} elseif ( 'custom' === $target ) {
					$tgt_lbl = strtolower( trim( $style_item['target_label'] ?? '' ) );
					if ( $tgt_lbl ) {
						foreach ( $custom_extras as $extra ) {
							$ex_lbl = strtolower( trim( $extra['field_label'] ?? '' ) );
							if ( $ex_lbl === $tgt_lbl ) {
								$target_id = $extra['_id'] ?? '';
								break;
							}
						}
					}
				}
				
				if ( ! $target_id ) {
					continue;
				}
				
				$selector_base = "#{$wrap_id} .elementor-repeater-item-{$target_id}";
				$selector      = "{$selector_base}, {$selector_base} *";
				$item_css      = '';
				
				if ( ! empty( $style_item['field_color'] ) ) {
					$color = $resolve_global_color( $style_item['field_color'] );
					$item_css .= "color: {$color} !important;";
				}
				if ( ! empty( $style_item['field_label_color'] ) ) {
					$lbl_color = $resolve_global_color( $style_item['field_label_color'] );
					$dynamic_repeater_css .= "{$selector_base} .souvik-ws-extra-label { color: {$lbl_color} !important; }\n";
				}
				if ( ! empty( $style_item['field_icon_color'] ) ) {
					$icon_color = $resolve_global_color( $style_item['field_icon_color'] );
					$dynamic_repeater_css .= "{$selector_base} .souvik-ws-extra-icon { color: {$icon_color} !important; }\n";
					$dynamic_repeater_css .= "{$selector_base} .souvik-ws-extra-icon svg { fill: {$icon_color} !important; }\n";
				}
				if ( ! empty( $style_item['field_bg_color'] ) ) {
					$bg_color = $resolve_global_color( $style_item['field_bg_color'] );
					$item_css .= "background-color: {$bg_color} !important;";
				}
				if ( ! empty( $style_item['field_padding'] ) ) {
					$unit = $style_item['field_padding']['unit'] ?? 'px';
					$top = $style_item['field_padding']['top'] ?? '';
					$right = $style_item['field_padding']['right'] ?? '';
					$bottom = $style_item['field_padding']['bottom'] ?? '';
					$left = $style_item['field_padding']['left'] ?? '';
					if ( $top !== '' || $right !== '' || $bottom !== '' || $left !== '' ) {
						$dynamic_repeater_css .= "{$selector_base} { padding: {$top}{$unit} {$right}{$unit} {$bottom}{$unit} {$left}{$unit} !important; }\n";
					}
				}
				if ( ! empty( $style_item['field_margin'] ) ) {
					$unit = $style_item['field_margin']['unit'] ?? 'px';
					$top = $style_item['field_margin']['top'] ?? '';
					$right = $style_item['field_margin']['right'] ?? '';
					$bottom = $style_item['field_margin']['bottom'] ?? '';
					$left = $style_item['field_margin']['left'] ?? '';
					if ( $top !== '' || $right !== '' || $bottom !== '' || $left !== '' ) {
						$dynamic_repeater_css .= "{$selector_base} { margin: {$top}{$unit} {$right}{$unit} {$bottom}{$unit} {$left}{$unit} !important; }\n";
					}
				}
				if ( ! empty( $style_item['field_border_radius'] ) ) {
					$unit = $style_item['field_border_radius']['unit'] ?? 'px';
					$top = $style_item['field_border_radius']['top'] ?? '';
					$right = $style_item['field_border_radius']['right'] ?? '';
					$bottom = $style_item['field_border_radius']['bottom'] ?? '';
					$left = $style_item['field_border_radius']['left'] ?? '';
					if ( $top !== '' || $right !== '' || $bottom !== '' || $left !== '' ) {
						$dynamic_repeater_css .= "{$selector_base} { border-radius: {$top}{$unit} {$right}{$unit} {$bottom}{$unit} {$left}{$unit} !important; }\n";
					}
				}
				if ( ! empty( $style_item['field_border_color'] ) ) {
					$border_color = $resolve_global_color( $style_item['field_border_color'] );
					$dynamic_repeater_css .= "{$selector_base} { border-color: {$border_color} !important; }\n";
				}
				if ( ! empty( $style_item['field_border_width'] ) ) {
					$unit = $style_item['field_border_width']['unit'] ?? 'px';
					$top = $style_item['field_border_width']['top'] ?? '';
					$right = $style_item['field_border_width']['right'] ?? '';
					$bottom = $style_item['field_border_width']['bottom'] ?? '';
					$left = $style_item['field_border_width']['left'] ?? '';
					if ( $top !== '' || $right !== '' || $bottom !== '' || $left !== '' ) {
						$dynamic_repeater_css .= "{$selector_base} { border-style: solid !important; border-width: {$top}{$unit} {$right}{$unit} {$bottom}{$unit} {$left}{$unit} !important; }\n";
					}
				}
				
				// Group Typography — Resolve dynamic elementor global typography
				$typo_val = $style_item['field_typography_typography'] ?? '';
				if ( str_starts_with( $typo_val, 'globals/typography?id=' ) ) {
					$global_id = substr( $typo_val, 22 );
					$kit = \Elementor\Plugin::$instance->kits_manager->get_active_kit();
					if ( $kit ) {
						$kit_settings = $kit->get_settings();
						$global_typo = [];
						if ( ! empty( $kit_settings['system_typography'] ) ) {
							foreach ( $kit_settings['system_typography'] as $sys_typo ) {
								if ( ( $sys_typo['_id'] ?? '' ) === $global_id ) {
									$global_typo = $sys_typo;
									break;
								}
							}
						}
						if ( empty( $global_typo ) && ! empty( $kit_settings['custom_typography'] ) ) {
							foreach ( $kit_settings['custom_typography'] as $cust_typo ) {
								if ( ( $cust_typo['_id'] ?? '' ) === $global_id ) {
									$global_typo = $cust_typo;
									break;
								}
							}
						}
						if ( empty( $global_typo ) ) {
							$global_typo = $kit_settings[ 'system_typography_' . $global_id ] ?? ( $kit_settings[ 'custom_typography_' . $global_id ] ?? [] );
						}

						if ( ! empty( $global_typo ) ) {
							if ( ! empty( $global_typo['font_family'] ) && empty( $style_item['field_typography_font_family'] ) ) {
								$style_item['field_typography_font_family'] = $global_typo['font_family'];
							}
							if ( ! empty( $global_typo['font_size']['size'] ) && empty( $style_item['field_typography_font_size']['size'] ) ) {
								$style_item['field_typography_font_size'] = $global_typo['font_size'];
							}
							if ( ! empty( $global_typo['font_weight'] ) && empty( $style_item['field_typography_font_weight'] ) ) {
								$style_item['field_typography_font_weight'] = $global_typo['font_weight'];
							}
							if ( ! empty( $global_typo['text_transform'] ) && empty( $style_item['field_typography_text_transform'] ) ) {
								$style_item['field_typography_text_transform'] = $global_typo['text_transform'];
							}
							if ( ! empty( $global_typo['font_style'] ) && empty( $style_item['field_typography_font_style'] ) ) {
								$style_item['field_typography_font_style'] = $global_typo['font_style'];
							}
							if ( ! empty( $global_typo['line_height']['size'] ) && empty( $style_item['field_typography_line_height']['size'] ) ) {
								$style_item['field_typography_line_height'] = $global_typo['line_height'];
							}
							if ( ! empty( $global_typo['letter_spacing']['size'] ) && empty( $style_item['field_typography_letter_spacing']['size'] ) ) {
								$style_item['field_typography_letter_spacing'] = $global_typo['letter_spacing'];
							}
						}
					}
				}

				if ( ! empty( $style_item['field_typography_font_family'] ) ) {
					$item_css .= "font-family: {$style_item['field_typography_font_family']} !important;";
				}
				if ( ! empty( $style_item['field_typography_font_size']['size'] ) ) {
					$item_css .= "font-size: {$style_item['field_typography_font_size']['size']}{$style_item['field_typography_font_size']['unit']} !important;";
				}
				if ( ! empty( $style_item['field_typography_font_weight'] ) ) {
					$item_css .= "font-weight: {$style_item['field_typography_font_weight']} !important;";
				}
				if ( ! empty( $style_item['field_typography_text_transform'] ) ) {
					$item_css .= "text-transform: {$style_item['field_typography_text_transform']} !important;";
				}
				if ( ! empty( $style_item['field_typography_font_style'] ) ) {
					$item_css .= "font-style: {$style_item['field_typography_font_style']} !important;";
				}
				if ( ! empty( $style_item['field_typography_line_height']['size'] ) ) {
					$item_css .= "line-height: {$style_item['field_typography_line_height']['size']}{$style_item['field_typography_line_height']['unit']} !important;";
				}
				if ( ! empty( $style_item['field_typography_letter_spacing']['size'] ) ) {
					$item_css .= "letter-spacing: {$style_item['field_typography_letter_spacing']['size']}{$style_item['field_typography_letter_spacing']['unit']} !important;";
				}
				
				if ( $item_css ) {
					$dynamic_repeater_css .= "{$selector} { {$item_css} }\n";
				}
				
				// Hover override CSS
				$hover_css = '';
				if ( ! empty( $style_item['field_hover_color'] ) ) {
					$hover_color = $resolve_global_color( $style_item['field_hover_color'] );
					$hover_css .= "color: {$hover_color} !important;";
				}
				if ( ! empty( $style_item['field_bg_hover_color'] ) ) {
					$bg_hover_color = $resolve_global_color( $style_item['field_bg_hover_color'] );
					$hover_css .= "background-color: {$bg_hover_color} !important;";
				}
				if ( $hover_css ) {
					$dynamic_repeater_css .= "{$selector}:hover { {$hover_css} }\n";
				}
			}
		}



		// 6. Output custom CSS inline.
		if ( $custom_css || $dynamic_repeater_css ) {
			echo '<style>';
			if ( $custom_css ) {
				echo wp_strip_all_tags( $custom_css );
			}
			if ( $dynamic_repeater_css ) {
				echo wp_strip_all_tags( $dynamic_repeater_css );
			}
			echo '</style>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}

		// 7. Wrapper open.
		$wrap_classes = 'souvik-ws-team-wrapper souvik-ws-card-style--' . esc_attr( $card_style ) . ' souvik-ws-img-pos--' . esc_attr( $img_pos );
		if ( $skeleton ) {
			$wrap_classes .= ' is-loading';
		}

		$filter_sticky_attr = ( ( $s['filter_sticky'] ?? 'no' ) === 'yes' ) ? '1' : '0';

		echo '<div'
			. ' class="' . $wrap_classes . '"'
			. ' id="' . esc_attr( $wrap_id ) . '"'
			. ' data-souvik-ws-anim="' . esc_attr( wp_json_encode( $anim_config ) ) . '"'
			. ' data-cols="' . esc_attr( (string) $cols ) . '"'
			. ' data-col-gap="' . esc_attr( (string) $col_gap ) . '"'
			. ' data-row-gap="' . esc_attr( (string) $row_gap ) . '"'
			. ' data-dark="' . esc_attr( $dark ) . '"'
			. ' data-hover-transform="' . esc_attr( $hover_tr ) . '"'
			. ' data-img-zoom="' . esc_attr( $img_zoom ) . '"'
			. ' data-equal-height="' . esc_attr( $eq_height ) . '"'
			. ' data-popup-trigger="' . esc_attr( $popup_trigger ) . '"'
			. ' data-pag-type="' . esc_attr( $pag_type ) . '"'
			. ' data-items-per-page="' . esc_attr( (string) $items_per_page ) . '"'
			. ' data-filter-sticky="' . esc_attr( $filter_sticky_attr ) . '"'
			. '>';

		// 7.5 Control Bar (wrapper for Filter, Search and Toggler)
		$show_control_bar = $filter_show || $search_enable || ( ( $s['dark_mode_enable'] ?? 'no' ) === 'yes' && ( $s['dark_mode_switcher'] ?? 'no' ) === 'yes' );
		if ( $show_control_bar ) {
			echo '<div class="souvik-ws-control-bar">';

			// 8. Filter bar.
			if ( $filter_show ) {
				$departments = array_unique(
					array_filter(
						array_map(
							static function ( $m ) {
								return is_array( $m )
									? ( $m['department'] ?? '' )
									: '';
							},
							$members
						)
					)
				);

				$filter_sticky_class = ( '1' === $filter_sticky_attr ) ? ' souvik-ws-filter-bar--sticky-enabled' : '';
				$filter_class = 'souvik-ws-filter-bar souvik-ws-filter-bar--' . $filter_style . $filter_sticky_class;

				if ( 'dropdown' === $filter_style ) {
					echo '<div class="' . esc_attr( $filter_class ) . '">'
						. '<select class="souvik-ws-filter-select" aria-label="' . esc_attr__( 'Filter by department', 'souvik-ws-team-showcase' ) . '">'
						. '<option value="">' . $filter_all . '</option>';
					foreach ( $departments as $dept ) {
						echo '<option value="' . esc_attr( $dept ) . '">' . esc_html( $dept ) . '</option>';
					}
					echo '</select></div>';
				} else {
					echo '<div class="' . esc_attr( $filter_class ) . '" role="tablist">'
						. '<button class="souvik-ws-filter-btn is-active" data-filter="" role="tab" aria-selected="true">' . $filter_all . '</button>';
					foreach ( $departments as $dept ) {
						echo '<button class="souvik-ws-filter-btn" data-filter="' . esc_attr( $dept ) . '" role="tab" aria-selected="false">' . esc_html( $dept ) . '</button>';
					}
					echo '</div>';
				}
			}

			// 8.5 Search and Toggler wrapper
			if ( $search_enable || ( ( $s['dark_mode_enable'] ?? 'no' ) === 'yes' && ( $s['dark_mode_switcher'] ?? 'no' ) === 'yes' ) ) {
				echo '<div class="souvik-ws-controls-bar">';

				// Search Box
				if ( $search_enable ) {
					$search_icon_html = '';
					if ( ! empty( $s['search_icon']['value'] ) ) {
						ob_start();
						\Elementor\Icons_Manager::render_icon( $s['search_icon'], [ 'aria-hidden' => 'true' ] );
						$search_icon_html = ob_get_clean();
					} else {
						$search_icon_html = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0z"/></svg>';
					}

					echo '<div class="souvik-ws-search-wrap">'
						. '<div class="souvik-ws-search-inner">'
						. '<span class="souvik-ws-search-icon">' . $search_icon_html . '</span>'
						. '<input type="search" class="souvik-ws-search-input" placeholder="' . $search_ph . '" aria-label="' . $search_ph . '">'
						. '</div>'
						. '</div>';
				}

				// Front-End Dark Mode Toggler
				if ( ( $s['dark_mode_enable'] ?? 'no' ) === 'yes' && ( $s['dark_mode_switcher'] ?? 'no' ) === 'yes' ) {
					echo '<div class="souvik-ws-dark-toggle-wrap">'
						. '<button class="souvik-ws-dark-toggle-btn" aria-checked="' . ( $dark === '1' ? 'true' : 'false' ) . '" role="switch" aria-label="' . esc_attr__( 'Toggle dark mode', 'souvik-ws-team-showcase' ) . '">'
						. '<span class="souvik-ws-dark-toggle-icon souvik-ws-dark-toggle-icon--sun">☀️</span>'
						. '<span class="souvik-ws-dark-toggle-icon souvik-ws-dark-toggle-icon--moon">🌙</span>'
						. '</button>'
						. '</div>';
				}

				echo '</div>'; // .souvik-ws-controls-bar
			}

			echo '</div>'; // .souvik-ws-control-bar
		}

		// 9. Skeleton loader placeholder.
		if ( $skeleton ) {
			echo '<div class="souvik-ws-skeleton-grid souvik-ws-team-grid" aria-hidden="true" style="--souvik-ws-cols:' . esc_attr( (string) $cols ) . ';">';
			for ( $i = 0; $i < $cols; $i++ ) {
				echo '<div class="souvik-ws-skeleton-card"><div class="souvik-ws-skel souvik-ws-skel--img"></div><div class="souvik-ws-skel souvik-ws-skel--line"></div><div class="souvik-ws-skel souvik-ws-skel--line souvik-ws-skel--short"></div></div>';
			}
			echo '</div>';
		}

		// 10. Grid.
		echo '<div class="souvik-ws-team-grid" style="--souvik-ws-cols:' . esc_attr( (string) $cols ) . ';" data-total="' . esc_attr( (string) count( $members ) ) . '">';

		foreach ( $members as $idx => $m ) {
			$photo_url   = $m['photo_url'] ?? ( $m['photo']['url'] ?? '' );
			$name        = sanitize_text_field( $m['name'] ?? '' );
			$designation = sanitize_text_field( $m['designation'] ?? '' );
			$department  = sanitize_text_field( $m['department'] ?? '' );
			$bio         = wp_kses_post( $m['bio'] ?? '' );
			$btn_label   = sanitize_text_field( $m['button_label'] ?? '' );
			$btn_url_raw = $m['button_url'] ?? '';
			$btn_url     = is_array( $btn_url_raw ) ? ( $btn_url_raw['url'] ?? '#' ) : (string) $btn_url_raw;
			$socials     = (array) ( $m['socials'] ?? [] );
			$post_id     = isset( $m['post_id'] ) ? (int) $m['post_id'] : 0;

			$is_hidden = ( $pag_type !== 'none' && $idx >= $items_per_page ) ? ' souvik-ws-team-card--hidden' : '';
			$card_class = 'souvik-ws-team-card' . $is_hidden;
			if ( 'none' !== $hover_tr ) {
				$card_class .= ' souvik-ws-hover--' . $hover_tr;
			}
			$open_trigger = $popup_enable && 'card_click' === $popup_trigger ? ' role="button" tabindex="0" data-souvik-ws-popup-open="true"' : '';

			echo '<article class="' . esc_attr( $card_class ) . '" data-dept="' . esc_attr( $department ) . '" data-index="' . esc_attr( (string) $idx ) . '"' . $open_trigger . '>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

			// Image.
			if ( $photo_url ) {
				$img_attrs = $lazy_load ? ' loading="lazy"' : '';
				
				$photo_link_to = sanitize_key( $s['photo_link_to'] ?? 'none' );
				$photo_href = '';
				$photo_link_attrs = '';
				
				if ( 'member_url' === $photo_link_to ) {
					$photo_href = $post_id ? get_permalink( $post_id ) : $btn_url;
				} elseif ( 'custom' === $photo_link_to ) {
					$photo_href = is_array( $s['photo_custom_url'] ?? '' ) ? ( $s['photo_custom_url']['url'] ?? '' ) : (string) ( $s['photo_custom_url'] ?? '' );
					if ( is_array( $s['photo_custom_url'] ?? '' ) && ! empty( $s['photo_custom_url']['is_external'] ) ) {
						$photo_link_attrs .= ' target="_blank" rel="noopener noreferrer"';
					}
				} elseif ( 'popup' === $photo_link_to && $popup_enable ) {
					$photo_href = '#';
					$photo_link_attrs .= ' data-souvik-ws-popup-open="true"';
				}

				$image_html = '<img src="' . esc_url( $photo_url ) . '" class="souvik-ws-team-card__img" alt="' . esc_attr( $name ) . '"' . $img_attrs . '>';
				if ( $photo_href ) {
					$image_html = '<a href="' . esc_url( $photo_href ) . '" class="souvik-ws-team-card__img-link"' . $photo_link_attrs . '>' . $image_html . '</a>';
				}

				echo '<div class="souvik-ws-team-card__image">' . $image_html . '</div>';
			}

			// Body.
			echo '<div class="souvik-ws-team-card__body">';

			// Dynamic Element Render Closures
			$render_element = function( $key, $value, $default_tag, $class_name, $post_id_val = 0 ) use ( $s, $btn_url, $popup_enable ) {
				if ( ! $value ) {
					return;
				}

				$tag = sanitize_key( $s[ $key . '_html_tag' ] ?? $default_tag );
				$link_to = sanitize_key( $s[ $key . '_link_to' ] ?? 'none' );
				$href = '';
				$link_attrs = '';

				if ( 'member_url' === $link_to ) {
					$href = $post_id_val ? get_permalink( $post_id_val ) : $btn_url;
				} elseif ( 'custom' === $link_to ) {
					$custom_url_raw = $s[ $key . '_custom_url' ] ?? '';
					$href = is_array( $custom_url_raw ) ? ( $custom_url_raw['url'] ?? '' ) : (string) $custom_url_raw;
					if ( is_array( $custom_url_raw ) && ! empty( $custom_url_raw['is_external'] ) ) {
						$link_attrs .= ' target="_blank" rel="noopener noreferrer"';
					}
				} elseif ( 'popup' === $link_to && $popup_enable ) {
					$href = '#';
					$link_attrs .= ' data-souvik-ws-popup-open="true"';
				}

				$content_html = esc_html( $value );
				if ( str_contains( $key, 'bio' ) ) {
					$content_html = wp_kses_post( $value );
				}

				if ( $href ) {
					$content_html = '<a href="' . esc_url( $href ) . '" class="' . esc_attr( $class_name ) . '-link"' . $link_attrs . '>' . $content_html . '</a>';
				}

				echo '<' . esc_attr( $tag ) . ' class="' . esc_attr( $class_name ) . '">' . $content_html . '</' . esc_attr( $tag ) . '>';
			};

			// Render standard fields
			$render_element( 'name', $name, 'h3', 'souvik-ws-team-card__name', $post_id );
			$render_element( 'designation', $designation, 'p', 'souvik-ws-team-card__role', $post_id );
			$render_element( 'department', $department, 'span', 'souvik-ws-team-dept-badge', $post_id );
			$render_element( 'bio', $bio, 'p', 'souvik-ws-team-card__bio', $post_id );

			// Render Custom / Extra Fields
			$extras = (array) ( $m['extras'] ?? [] );
			if ( ! empty( $extras ) ) {
				foreach ( $extras as $extra ) {
					$ex_label = sanitize_text_field( $extra['label'] ?? '' );
					$ex_value = wp_kses_post( $extra['value'] ?? '' );
					if ( ! $ex_value ) {
						continue;
					}

					$ex_tag = sanitize_key( $extra['html_tag'] ?? 'p' );
					$ex_link_to = sanitize_key( $extra['link_to'] ?? 'none' );
					$ex_style = sanitize_key( $extra['display_style'] ?? 'text' );
					$ex_icon = $extra['icon'] ?? [];
					
					$ex_href = '';
					$ex_link_attrs = '';
					
					if ( 'member_url' === $ex_link_to ) {
						$ex_href = $post_id ? get_permalink( $post_id ) : $btn_url;
					} elseif ( 'custom' === $ex_link_to ) {
						$ex_url_raw = $extra['custom_url'] ?? '';
						$ex_href = is_array( $ex_url_raw ) ? ( $ex_url_raw['url'] ?? '' ) : (string) $ex_url_raw;
						if ( is_array( $ex_url_raw ) && ! empty( $ex_url_raw['is_external'] ) ) {
							$ex_link_attrs .= ' target="_blank" rel="noopener noreferrer"';
						}
					} elseif ( 'popup' === $ex_link_to && $popup_enable ) {
						$ex_href = '#';
						$ex_link_attrs .= ' data-souvik-ws-popup-open="true"';
					}

					$icon_html = '';
					if ( ! empty( $ex_icon['value'] ) ) {
						ob_start();
						\Elementor\Icons_Manager::render_icon( $ex_icon, [ 'aria-hidden' => 'true' ] );
						$icon_html = ob_get_clean();
					}

					$content_html = $ex_value;

					// Prepend label if show_label is enabled
					$ex_show_label = $extra['show_label'] ?? 'yes';
					if ( 'yes' === $ex_show_label && '' !== $ex_label ) {
						$content_html = '<strong class="souvik-ws-extra-label">' . esc_html( $ex_label ) . ': </strong>' . $content_html;
					}

					if ( $icon_html ) {
						$content_html = '<span class="souvik-ws-extra-icon">' . $icon_html . '</span> ' . $content_html;
					}

					if ( $ex_href ) {
						$content_html = '<a href="' . esc_url( $ex_href ) . '" class="souvik-ws-extra-link"' . $ex_link_attrs . '>' . $content_html . '</a>';
					}

					$ex_class = 'souvik-ws-extra-field';
					if ( 'badge' === $ex_style ) {
						$ex_class = 'souvik-ws-team-dept-badge souvik-ws-extra-badge';
					} elseif ( 'btn' === $ex_style ) {
						$ex_class = 'souvik-ws-team-card__btn souvik-ws-extra-btn';
					}

					if ( $ex_label ) {
						$ex_class .= ' souvik-ws-custom-field--' . sanitize_title( $ex_label );
					}

					$field_id = $extra['_id'] ?? '';
					if ( $field_id ) {
						$ex_class .= ' elementor-repeater-item-' . $field_id;
					}

					echo '<' . esc_attr( $ex_tag ) . ' class="' . esc_attr( $ex_class ) . '">' . $content_html . '</' . esc_attr( $ex_tag ) . '>';
				}
			}

			// Card Bottom section (socials & CTA button)
			$has_socials = ! empty( $socials );
			$has_button  = ! empty( $btn_label );

			if ( $has_socials || $has_button ) {
				echo '<div class="souvik-ws-team-card__bottom">';

				// Socials.
				if ( $has_socials ) {
					$social_icons = [
						'facebook'  => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512"><path d="M80 299.3V256H12v-54.7h68v-43.2c0-67 41-103.4 100.4-103.4 28.5 0 53 2.1 60.1 3v69.9h-41.3c-32.5 0-38.9 15.4-38.9 38.1v49.9h77.4L228 256h-69.3v242.3H80V299.3z"/></svg>',
						'twitter'   => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path d="M389.2 48h70.6L305.6 224.2 487 464H345L233.7 318.6 106.5 464H35.8L200.7 275.5 26.8 48H172.4L272.9 180.9 389.2 48zM364.4 421.8h39.1L151.1 88h-42L364.4 421.8z"/></svg>',
						'linkedin'  => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><path d="M100.28 448H7.4V148.9h92.88zM53.79 108.1C24.09 108.1 0 83.5 0 53.8a53.79 53.79 0 0 1 107.58 0c0 29.7-24.1 54.3-53.79 54.3zM447.9 448h-92.68V302.4c0-34.7-.7-79.2-48.29-79.2-48.29 0-55.69 37.7-55.69 76.7V448h-92.78V148.9h89.08v40.8h1.3c12.4-23.5 42.69-48.3 87.88-48.3 94 0 111.28 61.9 111.28 142.3V448z"/></svg>',
						'instagram' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><path d="M224.1 141c-63.6 0-114.9 51.3-114.9 114.9s51.3 114.9 114.9 114.9S339 319.5 339 255.9 287.7 141 224.1 141zm0 189.6c-41.1 0-74.7-33.5-74.7-74.7s33.5-74.7 74.7-74.7 74.7 33.5 74.7 74.7-33.6 74.7-74.7 74.7zm146.4-194.3c0 14.9-12 26.8-26.8 26.8-14.9 0-26.8-12-26.8-26.8s12-26.8 26.8-26.8c14.8 0 26.8 12 26.8 26.8zm76.1 27.2c-1.7-35.9-9.9-67.7-36.2-93.9-26.2-26.2-58-34.4-93.9-36.2-37-2.1-147.9-2.1-184.9 0-35.8 1.7-67.6 9.9-93.9 36.1s-34.4 58-36.2 93.9c-2.1 37-2.1 147.9 0 184.9 1.7 35.9 9.9 67.7 36.2 93.9s58 34.4 93.9 36.2c37 2.1 147.9 2.1 184.9 0 35.9-1.7 67.7-9.9 93.9-36.2 26.2-26.2 34.4-58 36.2-93.9 2.1-37 2.1-147.8 0-184.8zM398.8 388a75.63 75.63 0 0 1-42.6 42.6c-29.5 11.7-99.5 9-132.1 9s-102.7 2.6-132.1-9A75.63 75.63 0 0 1 69.4 388c-11.7-29.5-9-99.5-9-132.1s-2.6-102.7 9-132.1A75.63 75.63 0 0 1 112 81.2c29.5-11.7 99.5-9 132.1-9s102.7-2.6 132.1 9a75.63 75.63 0 0 1 42.6 42.6c11.7 29.5 9 99.5 9 132.1s2.7 102.7-9 132.1z"/></svg>',
						'youtube'   => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><path d="M549.655 124.083c-6.281-23.65-24.787-42.156-48.437-48.437C458.517 64 288 64 288 64S117.483 64 74.782 75.646c-23.65 6.281-42.156 24.787-48.437 48.437C14.7 166.782 14.7 256 14.7 256s0 89.218 11.646 131.917c6.281 23.65 24.787 42.156 48.437 48.437C117.483 448 288 448 288 448s170.517 0 213.218-11.646c23.65-6.281 42.156-24.787 48.437-48.437C561.3 345.218 561.3 256 561.3 256s0-89.218-11.645-131.917zM219.782 328.718V183.282L346.882 256l-127.1 72.718z"/></svg>',
						'github'    => '<svg width="800" height="800" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><title>github [#142]</title><path d="M10 0c5.523 0 10 4.59 10 10.253 0 4.529-2.862 8.371-6.833 9.728-.507.101-.687-.219-.687-.492 0-.338.012-1.442.012-2.814 0-.956-.32-1.58-.679-1.898 2.227-.254 4.567-1.121 4.567-5.059 0-1.12-.388-2.034-1.03-2.752.104-.259.447-1.302-.098-2.714 0 0-.838-.275-2.747 1.051A9.4 9.4 0 0 0 10 4.958a9.4 9.4 0 0 0-2.503.345C5.586 3.977 4.746 4.252 4.746 4.252c-.543 1.412-.2 2.455-.097 2.714-.639.718-1.03 1.632-1.03 2.752 0 3.928 2.335 4.808 4.556 5.067-.286.256-.545.708-.635 1.371-.57.262-2.018.715-2.91-.852 0 0-.529-.985-1.533-1.057 0 0-.975-.013-.068.623 0 0 .655.315 1.11 1.5 0 0 .587 1.83 3.369 1.21.005.857.014 1.665.014 1.909 0 .271-.184.588-.683.493C2.865 18.627 0 14.783 0 10.253 0 4.59 4.478 0 10 0" fill-rule="evenodd"/></svg>',
						'dribbble'  => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path d="M256 8C119.25 8 8 119.25 8 256s111.25 248 248 248 248-111.25 248-248S392.75 8 256 8zm157.12 96.67c17.51 22.91 29.35 49.52 34.18 78.43-16.71-3.69-58.83-9.56-99.76-4.59-8.49-19.46-17.75-38.64-27.75-57.06 43.23-14.88 81.33-16.31 93.33-16.78zm-225.13 23c10.4 17.95 20 36.6 28.79 55.44-53.77 15.11-118.84 14.84-142.34 14.5-2-12.72-2.58-25.79-2.58-39 0-48.64 19.34-92.79 50.84-125.13 6.36 10.33 34.62 48.06 65.29 94.19zm-72.34 107.5c24.23.3 90.72-.08 147.24-17.13 10.1 20 19.35 40.23 27.52 60.1-59.5 22.18-109 77.27-123.63 94.75-23.77-31.57-38.8-69.83-42.53-111.45-6.73-1.87-8.6-4.63-8.6-6.27zm181 184.28c11.66-16.14 55.67-71.18 113.1-91.88 12.38 29.07 22.12 56.63 29.21 82.26-36.5 21-78.6 33.28-123.68 33.28-6.4 0-12.72-.25-19-.77 6.43-6.9 14.18-15 20.37-22.89zm135.69-35.34c-6.84-24.3-16-50.59-27.88-78.11 39.5-3.5 81.82 2.21 95.82 4.41-5.69 30.68-20.19 58.12-40.85 80.97-15.03-1.39-21.67-4.47-27.09-7.27zm-60-129.56c-8.61-20.48-18.43-41.22-29.35-61.64 42-4.14 85 .87 101.37 3.32-3.8 23.3-13.62 44.59-27.67 62.33-14.73-2.14-27.18-3.07-44.35-4.01z"/></svg>',
						'behance'   => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><path d="M232 235.1c0 18.4-15.3 30.9-34.9 30.9h-69.1v-62.9h69.1c19.6 0 34.9 13.6 34.9 32zm-38.3-95c16.2 0 30.3 11 30.3 26.6 0 16.5-14.1 25.5-30.3 25.5h-65.7v-52.1h65.7zm198 102.3c0 14.9-10.4 26.3-26.6 26.3-15.6 0-25.7-11.4-25.7-26.3s10.1-26.6 25.7-26.6c16.2 0 26.6 11.7 26.6 26.6zm23.6-28.8c-1.2-46.7-34.9-80.9-83.3-80.9-48.6 0-82.6 34.3-82.6 83.3 0 50.5 33.7 83.6 84.8 83.6 40.4 0 71.9-23 80.9-59.4h-35.8c-7 17.1-23 27.5-44.1 27.5-25.7 0-41.9-15.3-43.2-40.7h124.3v-4.4zM0 80v352c0 26.5 21.5 48 48 48h480c26.5 0 48-21.5 48-48V80c0-26.5-21.5-48-48-48H48C21.5 32 0 53.5 0 80zm237 281.3c0 24.5-12.2 44.4-35.8 49.3-10.4 2.1-29.4 2.1-47.1 2.1H48V103.7h92.2c28.1 0 47.7 2.1 57.8 7.3 17.7 9.2 24.8 28.1 24.8 47.7 0 20.2-11 36.1-27.5 44 26 8.3 41.7 28.8 41.7 58.7v19.9zm271.7-227h-119V111h119v23.3z"/></svg>',
					];

					// Merge global database overrides if defined
					$custom_global_icons = get_option( 'souvik_ws_team_custom_icons', [] );
					if ( is_array( $custom_global_icons ) ) {
						foreach ( $custom_global_icons as $plat => $svg_markup ) {
							if ( ! empty( trim( $svg_markup ) ) ) {
								$social_icons[ $plat ] = trim( $svg_markup );
							}
						}
					}

					$soc_hover_style = sanitize_key( $s['social_hover_style'] ?? 'brand' );
					$soc_class       = 'souvik-ws-team-card__social';
					if ( 'uniform' === $soc_hover_style ) {
						$soc_class .= ' souvik-ws-socials--uniform';
					}

					echo '<ul class="' . esc_attr( $soc_class ) . '" aria-label="' . esc_attr__( 'Social links', 'souvik-ws-team-showcase' ) . '">';
					foreach ( $socials as $social ) {
						$platform = sanitize_key( $social['platform'] ?? '' );
						$soc_url  = is_array( $social['url'] ?? '' ) ? ( $social['url']['url'] ?? '' ) : (string) ( $social['url'] ?? '' );
						if ( ! $soc_url ) {
							continue;
						}

						$icon_src = $social['icon_source'] ?? 'default';
						$custom_icon = $social['custom_icon'] ?? [];
						$custom_image = $social['custom_image'] ?? [];
						$icon_html = '';

						if ( ( 'image' === $icon_src || 'custom' === $icon_src ) && ! empty( $custom_image['url'] ) ) {
							$icon_html = '<img src="' . esc_url( $custom_image['url'] ) . '" alt="' . esc_attr( $platform ) . '" class="souvik-ws-social-custom-img" aria-hidden="true">';
						} elseif ( ( 'icon' === $icon_src || 'custom' === $icon_src ) && ! empty( $custom_icon['value'] ) ) {
							ob_start();
							\Elementor\Icons_Manager::render_icon( $custom_icon, [ 'aria-hidden' => 'true' ] );
							$icon_html = ob_get_clean();
						} else {
							$icon_html = $social_icons[ $platform ] ?? '<span aria-hidden="true">?</span>';
						}

						echo '<li>'
							. '<a href="' . esc_url( $soc_url ) . '" class="souvik-ws-social-icon souvik-ws-social-icon--' . esc_attr( $platform ) . '" target="_blank" rel="noopener noreferrer" aria-label="' . esc_attr( ucfirst( $platform ) ) . '">'
							. $icon_html
							. '</a>'
							. '</li>';
					}
					echo '</ul>';
				}

				// Button.
				if ( $has_button ) {
					$btn_target    = ( is_array( $m['button_url'] ?? '' ) && ! empty( $m['button_url']['is_external'] ) ) ? ' target="_blank" rel="noopener noreferrer"' : '';
					$popup_btn_attr = $popup_enable && 'button' === $popup_trigger ? ' data-souvik-ws-popup-open="true"' : '';
					echo '<div class="souvik-ws-team-card__btn-wrap">'
						. '<a class="souvik-ws-team-card__btn" href="' . esc_url( $btn_url ) . '"' . $btn_target . $popup_btn_attr . '>' // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						. esc_html( $btn_label )
						. '</a>'
						. '</div>';
				}

				echo '</div>'; // .souvik-ws-team-card__bottom
			}

			echo '</div>'; // .body
			echo '</article>';
		}

		echo '</div>'; // .grid

		// 11. Load more / numbers.
		if ( 'load_more' === $pag_type && count( $members ) > $items_per_page ) {
			echo '<div class="souvik-ws-load-more-wrap">'
				. '<button class="souvik-ws-load-more-btn" data-loaded="' . esc_attr( (string) $items_per_page ) . '">' . $load_more_lbl . '</button>'
				. '</div>';
		}

		if ( 'numbers' === $pag_type && count( $members ) > $items_per_page ) {
			$total_pages = (int) ceil( count( $members ) / $items_per_page );
			echo '<nav class="souvik-ws-pagination" aria-label="' . esc_attr__( 'Team pagination', 'souvik-ws-team-showcase' ) . '">';
			for ( $pg = 1; $pg <= $total_pages; $pg++ ) {
				$active_class = 1 === $pg ? ' is-active' : '';
				echo '<button class="souvik-ws-page-btn' . esc_attr( $active_class ) . '" data-page="' . esc_attr( (string) $pg ) . '">' . esc_html( (string) $pg ) . '</button>';
			}
			echo '</nav>';
		}

		// 12. Popup container (outside grid).
		if ( $popup_enable ) {
			echo '<div class="souvik-ws-popup-overlay" aria-hidden="true" role="presentation"></div>';
			echo '<div class="souvik-ws-popup-modal souvik-ws-popup-modal--' . esc_attr( $popup_size ) . '" role="dialog" aria-modal="true" aria-hidden="true">'
				. '<button class="souvik-ws-popup-close" aria-label="' . esc_attr__( 'Close modal', 'souvik-ws-team-showcase' ) . '">&times;</button>'
				. '<div class="souvik-ws-popup-modal__content" data-popup-fields="' . esc_attr( wp_json_encode( $popup_fields ) ) . '"></div>'
				. '</div>';
		}

		echo '</div>'; // .souvik-ws-team-wrapper
	}
}

