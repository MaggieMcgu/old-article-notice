<?php
/**
 * Plugin Name: Old Article Notice
 * Plugin URI: https://maggie-mcguire.com/old-article-notice
 * Description: Displays a configurable notice on old articles so readers know when content may be outdated. Built for news sites, blogs, and documentation.
 * Version: 1.2.0
 * Author: Maggie McGuire
 * Author URI: https://maggie-mcguire.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: old-article-notice
 * Requires at least: 5.0
 * Tested up to: 6.7
 * Requires PHP: 7.4
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Old_Article_Notice {

	const OPTION_KEY = 'opn_settings';

	private static $instance = null;
	private $settings;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		// Settings loaded once per request — safe for a singleton since
		// nothing else modifies this option mid-request.
		$this->settings = $this->get_settings();

		// Frontend
		add_filter( 'the_content', array( $this, 'maybe_show_notice' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_css' ) );

		// Admin
		add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );

		// Per-post meta box
		add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );
		add_action( 'save_post', array( $this, 'save_meta_box' ) );
	}

	/**
	 * Default settings.
	 */
	private function defaults() {
		return array(
			'threshold_value'      => 12,
			'threshold_unit'       => 'months',    // days, months, years
			'message'              => 'This article was published {time_ago} and is kept for archival purposes. Some information may be outdated.',
			'position'             => 'before',    // before, after
			'border_color'         => '#d63638',
			'text_color'           => '#d63638',
			'background_color'     => '#fef0f0',
			'border_width'         => 2,
			'border_radius'        => 4,
			'post_types'           => array( 'post' ),
			'excluded_categories'  => array(),
			'enabled'              => true,
			'use_modified_date'    => false,
			'coverage_link'        => false,
			'coverage_taxonomy'    => 'category',
			'coverage_link_text'   => 'See our latest {term_name} coverage &rarr;',
		);
	}

	/**
	 * Get merged settings.
	 */
	private function get_settings() {
		$saved = get_option( self::OPTION_KEY, array() );
		return wp_parse_args( $saved, $this->defaults() );
	}

	/**
	 * Convert threshold to seconds.
	 */
	private function threshold_seconds() {
		$val  = absint( $this->settings['threshold_value'] );
		$unit = $this->settings['threshold_unit'];

		switch ( $unit ) {
			case 'days':
				return $val * DAY_IN_SECONDS;
			case 'years':
				return $val * YEAR_IN_SECONDS;
			case 'months':
			default:
				return $val * 30 * DAY_IN_SECONDS;
		}
	}

	/**
	 * Replace template tags in a message string.
	 *
	 * @param string   $message        The message with template tags.
	 * @param int|null $post_timestamp Unix timestamp of the post, or null for sample data.
	 * @return string
	 */
	private function replace_tags( $message, $post_timestamp = null ) {
		if ( null === $post_timestamp ) {
			// Sample data for the settings page preview
			$replacements = array(
				'{time_ago}'      => '2 years ago',
				'{years}'         => '2',
				'{months}'        => '25',
				'{days}'          => '760',
				'{date}'          => 'March 15, 2024',
				'{updated_date}'  => 'January 10, 2026',
			);

			// Sample coverage link for preview
			if ( $this->settings['coverage_link'] ) {
				$sample_link_text = str_replace( '{term_name}', 'Local Government', $this->settings['coverage_link_text'] );
				$replacements['{coverage_link}'] = '<a href="#">' . esc_html( $sample_link_text ) . '</a>';
				$replacements['{term_name}']     = 'Local Government';
			} else {
				$replacements['{coverage_link}'] = '';
				$replacements['{term_name}']     = '';
			}
		} else {
			$age_seconds = time() - $post_timestamp;
			$modified_date = get_the_modified_date( '', get_the_ID() );
			$replacements = array(
				'{time_ago}'      => human_time_diff( $post_timestamp, time() ) . ' ago',
				'{years}'         => (string) floor( $age_seconds / YEAR_IN_SECONDS ),
				'{months}'        => (string) floor( $age_seconds / ( 30 * DAY_IN_SECONDS ) ),
				'{days}'          => (string) floor( $age_seconds / DAY_IN_SECONDS ),
				'{date}'          => get_the_date( '', get_the_ID() ),
				'{updated_date}'  => $modified_date ?: get_the_date( '', get_the_ID() ),
			);

			// Coverage link
			$replacements['{coverage_link}'] = '';
			$replacements['{term_name}']     = '';

			if ( $this->settings['coverage_link'] ) {
				$term = $this->get_primary_term( get_the_ID() );

				/**
				 * Filter the primary term used for the coverage link.
				 *
				 * @param WP_Term|null $term     The selected primary term.
				 * @param int          $post_id  The current post ID.
				 * @param string       $taxonomy The configured taxonomy.
				 */
				$term = apply_filters( 'opn_primary_term', $term, get_the_ID(), $this->settings['coverage_taxonomy'] );

				if ( $term ) {
					$replacements['{term_name}'] = $term->name;
					$link_text = str_replace( '{term_name}', esc_html( $term->name ), $this->settings['coverage_link_text'] );
					$term_url  = get_term_link( $term );
					if ( ! is_wp_error( $term_url ) ) {
						$replacements['{coverage_link}'] = '<a href="' . esc_url( $term_url ) . '">' . wp_kses_post( $link_text ) . '</a>';
					}
				}
			}
		}

		return str_replace( array_keys( $replacements ), array_values( $replacements ), $message );
	}

	/**
	 * Get the primary term for coverage link.
	 *
	 * Returns the first term from the configured taxonomy. If Yoast or
	 * Rank Math is active and has set a primary term, that's used instead.
	 */
	private function get_primary_term( $post_id ) {
		$taxonomy = $this->settings['coverage_taxonomy'];

		if ( ! taxonomy_exists( $taxonomy ) ) {
			return null;
		}

		// Check for Yoast primary term
		$primary_term_id = get_post_meta( $post_id, '_yoast_wpseo_primary_' . $taxonomy, true );
		if ( $primary_term_id ) {
			$term = get_term( (int) $primary_term_id, $taxonomy );
			if ( $term && ! is_wp_error( $term ) ) {
				return $term;
			}
		}

		// Check for Rank Math primary term
		$rm_primary = get_post_meta( $post_id, 'rank_math_primary_' . $taxonomy, true );
		if ( $rm_primary ) {
			$term = get_term( (int) $rm_primary, $taxonomy );
			if ( $term && ! is_wp_error( $term ) ) {
				return $term;
			}
		}

		// Fall back to first assigned term
		$terms = get_the_terms( $post_id, $taxonomy );
		if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
			return $terms[0];
		}

		return null;
	}

	/**
	 * Maybe show the notice on the_content.
	 */
	public function maybe_show_notice( $content ) {
		if ( ! is_singular() || ! in_the_loop() || ! is_main_query() ) {
			return $content;
		}

		if ( ! $this->settings['enabled'] ) {
			return $content;
		}

		// Check post type
		$post_type = get_post_type();
		if ( ! in_array( $post_type, (array) $this->settings['post_types'], true ) ) {
			return $content;
		}

		// Per-post disable
		if ( get_post_meta( get_the_ID(), '_opn_disable', true ) ) {
			return $content;
		}

		// Category exclusions (uses the built-in 'category' taxonomy —
		// custom post types with different taxonomies should use the
		// per-post disable checkbox instead)
		$excluded = (array) $this->settings['excluded_categories'];
		if ( ! empty( $excluded ) && has_category( $excluded ) ) {
			return $content;
		}

		// Check age — use modified date if enabled, otherwise published date
		$post_time = get_the_time( 'U' );
		$check_time = $post_time;
		if ( $this->settings['use_modified_date'] ) {
			$modified_time = get_the_modified_time( 'U' );
			if ( $modified_time && $modified_time > $post_time ) {
				$check_time = $modified_time;
			}
		}
		$age = time() - $check_time;

		if ( $age < $this->threshold_seconds() ) {
			return $content;
		}

		/**
		 * Filter whether the notice should be shown on this post.
		 *
		 * @param bool $show    Whether to show the notice. Default true.
		 * @param int  $post_id The current post ID.
		 * @param int  $age     Age in seconds (from published or modified date).
		 */
		$show = apply_filters( 'opn_should_show_notice', true, get_the_ID(), $age );
		if ( ! $show ) {
			return $content;
		}

		// Build notice
		$message = $this->replace_tags( $this->settings['message'], $post_time );
		$notice  = '<div class="opn-notice" role="note" aria-label="' . esc_attr__( 'Old article notice', 'old-article-notice' ) . '">'
		         . wp_kses_post( $message )
		         . '</div>';

		/**
		 * Filter the full notice HTML before it's added to the content.
		 *
		 * @param string $notice  The notice HTML.
		 * @param int    $post_id The current post ID.
		 * @param array  $settings The plugin settings.
		 */
		$notice = apply_filters( 'opn_notice_html', $notice, get_the_ID(), $this->settings );

		if ( 'after' === $this->settings['position'] ) {
			return $content . $notice;
		}

		return $notice . $content;
	}

	/**
	 * Enqueue frontend CSS via WordPress dependency system.
	 */
	public function enqueue_frontend_css() {
		if ( ! is_singular() ) {
			return;
		}

		$s = $this->settings;
		$border_color  = sanitize_hex_color( $s['border_color'] ) ?: '#d63638';
		$text_color    = sanitize_hex_color( $s['text_color'] ) ?: '#d63638';
		$bg_color      = sanitize_hex_color( $s['background_color'] ) ?: '#fef0f0';
		$border_width  = absint( $s['border_width'] );
		$border_radius = absint( $s['border_radius'] );

		$css = '.opn-notice {'
		     . 'border:' . $border_width . 'px solid ' . $border_color . ';'
		     . 'padding:12px 16px;'
		     . 'color:' . $text_color . ';'
		     . 'background:' . $bg_color . ';'
		     . 'font-weight:600;'
		     . 'text-align:center;'
		     . 'margin-bottom:1.5em;'
		     . 'border-radius:' . $border_radius . 'px;'
		     . 'font-size:0.9em;'
		     . 'line-height:1.5;'
		     . '}'
		     . '.opn-notice a{color:' . $text_color . ';text-decoration:underline;}';

		wp_register_style( 'opn-notice', false );
		wp_enqueue_style( 'opn-notice' );
		wp_add_inline_style( 'opn-notice', $css );
	}

	// =========================================================================
	// Admin Settings
	// =========================================================================

	public function add_settings_page() {
		add_options_page(
			__( 'Old Article Notice', 'old-article-notice' ),
			__( 'Old Article Notice', 'old-article-notice' ),
			'manage_options',
			'old-article-notice',
			array( $this, 'render_settings_page' )
		);
	}

	public function register_settings() {
		register_setting( 'opn_settings_group', self::OPTION_KEY, array(
			'type'              => 'array',
			'sanitize_callback' => array( $this, 'sanitize_settings' ),
			'default'           => $this->defaults(),
		) );
	}

	public function sanitize_settings( $input ) {
		$clean = array();

		$clean['enabled']          = ! empty( $input['enabled'] );
		$clean['threshold_value']  = max( 1, absint( $input['threshold_value'] ?? 12 ) );
		$clean['threshold_unit']   = in_array( $input['threshold_unit'] ?? '', array( 'days', 'months', 'years' ), true )
		                             ? $input['threshold_unit'] : 'months';
		$clean['message']          = wp_kses_post( $input['message'] ?? '' );
		$clean['position']         = in_array( $input['position'] ?? '', array( 'before', 'after' ), true )
		                             ? $input['position'] : 'before';
		$clean['border_color']     = sanitize_hex_color( $input['border_color'] ?? '' ) ?: '#d63638';
		$clean['text_color']       = sanitize_hex_color( $input['text_color'] ?? '' ) ?: '#d63638';
		$clean['background_color'] = sanitize_hex_color( $input['background_color'] ?? '' ) ?: '#fef0f0';
		$clean['border_width']     = min( 10, max( 0, absint( $input['border_width'] ?? 2 ) ) );
		$clean['border_radius']    = min( 20, max( 0, absint( $input['border_radius'] ?? 4 ) ) );

		// Post types — only allow public post types
		$valid_types = get_post_types( array( 'public' => true ), 'names' );
		$clean['post_types'] = array();
		if ( ! empty( $input['post_types'] ) && is_array( $input['post_types'] ) ) {
			foreach ( $input['post_types'] as $pt ) {
				if ( isset( $valid_types[ $pt ] ) ) {
					$clean['post_types'][] = $pt;
				}
			}
		}
		if ( empty( $clean['post_types'] ) ) {
			$clean['post_types'] = array( 'post' );
		}

		// Excluded categories
		$clean['excluded_categories'] = array();
		if ( ! empty( $input['excluded_categories'] ) && is_array( $input['excluded_categories'] ) ) {
			$clean['excluded_categories'] = array_map( 'absint', $input['excluded_categories'] );
		}

		// Modified date setting
		$clean['use_modified_date']  = ! empty( $input['use_modified_date'] );

		// Coverage link settings
		$clean['coverage_link']      = ! empty( $input['coverage_link'] );
		$clean['coverage_taxonomy']  = sanitize_key( $input['coverage_taxonomy'] ?? 'category' );
		if ( ! taxonomy_exists( $clean['coverage_taxonomy'] ) ) {
			$clean['coverage_taxonomy'] = 'category';
		}
		$clean['coverage_link_text'] = wp_kses_post( $input['coverage_link_text'] ?? 'See our latest {term_name} coverage &rarr;' );

		return $clean;
	}

	public function enqueue_admin_assets( $hook ) {
		if ( 'settings_page_old-article-notice' !== $hook ) {
			return;
		}
		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_script( 'wp-color-picker' );
	}

	public function render_settings_page() {
		$s = $this->get_settings();
		$post_types = get_post_types( array( 'public' => true ), 'objects' );
		$categories = get_categories( array( 'hide_empty' => false ) );
		?>
		<style>
			#opn-admin-wrap { display:flex; gap:30px; align-items:flex-start; }
			#opn-settings-col { flex:1; max-width:700px; }
			#opn-preview-col { flex:0 0 380px; position:sticky; top:40px; }
			#opn-preview-wrap { background:#fff; padding:20px; border:1px solid #ddd; border-radius:6px; }
			.opn-color-group { display:flex; gap:24px; flex-wrap:wrap; margin-bottom:12px; }
			.opn-color-group label,
			.opn-number-group label { display:block; margin-bottom:4px; font-weight:600; font-size:12px; }
			.opn-number-group { display:flex; gap:24px; flex-wrap:wrap; }
			.opn-sample-content { color:#666; font-size:13px; line-height:1.7; }
			.opn-sample-label { font-size:11px; text-transform:uppercase; letter-spacing:0.5px; color:#999; margin-bottom:8px; }
		</style>

		<div class="wrap">
			<h1><?php esc_html_e( 'Old Article Notice', 'old-article-notice' ); ?></h1>

			<form method="post" action="options.php" id="opn-settings-form">
				<?php settings_fields( 'opn_settings_group' ); ?>

				<div id="opn-admin-wrap">

				<!-- Left column: settings -->
				<div id="opn-settings-col">

				<table class="form-table" role="presentation">

					<!-- Enable -->
					<tr>
						<th scope="row"><?php esc_html_e( 'Enable', 'old-article-notice' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="<?php echo self::OPTION_KEY; ?>[enabled]" value="1" <?php checked( $s['enabled'] ); ?> id="opn-enabled" />
								<?php esc_html_e( 'Show notices on old articles', 'old-article-notice' ); ?>
							</label>
						</td>
					</tr>

					<!-- Threshold -->
					<tr>
						<th scope="row">
							<label for="opn-threshold-value"><?php esc_html_e( 'Age Threshold', 'old-article-notice' ); ?></label>
						</th>
						<td>
							<input type="number" id="opn-threshold-value" name="<?php echo self::OPTION_KEY; ?>[threshold_value]"
							       value="<?php echo esc_attr( $s['threshold_value'] ); ?>" min="1" max="999" style="width:70px;" />
							<select name="<?php echo self::OPTION_KEY; ?>[threshold_unit]" id="opn-threshold-unit">
								<option value="days" <?php selected( $s['threshold_unit'], 'days' ); ?>><?php esc_html_e( 'days', 'old-article-notice' ); ?></option>
								<option value="months" <?php selected( $s['threshold_unit'], 'months' ); ?>><?php esc_html_e( 'months', 'old-article-notice' ); ?></option>
								<option value="years" <?php selected( $s['threshold_unit'], 'years' ); ?>><?php esc_html_e( 'years', 'old-article-notice' ); ?></option>
							</select>
							<p class="description"><?php esc_html_e( 'Articles older than this will show the notice.', 'old-article-notice' ); ?></p>
						</td>
					</tr>

					<!-- Use Modified Date -->
					<tr>
						<th scope="row"><?php esc_html_e( 'Modified Date', 'old-article-notice' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="<?php echo self::OPTION_KEY; ?>[use_modified_date]" value="1"
								       <?php checked( $s['use_modified_date'] ); ?> id="opn-use-modified" />
								<?php esc_html_e( 'Use last modified date instead of published date', 'old-article-notice' ); ?>
							</label>
							<p class="description">
								<?php esc_html_e( 'When enabled, articles that have been recently updated won\'t show the notice even if they were originally published long ago. Useful for evergreen content that gets periodic updates.', 'old-article-notice' ); ?>
							</p>
						</td>
					</tr>

					<!-- Message -->
					<tr>
						<th scope="row">
							<label for="opn-message"><?php esc_html_e( 'Notice Message', 'old-article-notice' ); ?></label>
						</th>
						<td>
							<textarea id="opn-message" name="<?php echo self::OPTION_KEY; ?>[message]"
							          rows="3" class="large-text" style="max-width:500px;"><?php echo esc_textarea( $s['message'] ); ?></textarea>
							<p class="description">
								<?php esc_html_e( 'Available tags:', 'old-article-notice' ); ?>
								<code>{time_ago}</code> <code>{years}</code> <code>{months}</code> <code>{days}</code> <code>{date}</code>
								<code>{updated_date}</code> <code>{coverage_link}</code> <code>{term_name}</code>
							</p>
						</td>
					</tr>

					<!-- Position -->
					<tr>
						<th scope="row"><?php esc_html_e( 'Position', 'old-article-notice' ); ?></th>
						<td>
							<label style="margin-right:20px;">
								<input type="radio" name="<?php echo self::OPTION_KEY; ?>[position]" value="before" <?php checked( $s['position'], 'before' ); ?> />
								<?php esc_html_e( 'Before content', 'old-article-notice' ); ?>
							</label>
							<label>
								<input type="radio" name="<?php echo self::OPTION_KEY; ?>[position]" value="after" <?php checked( $s['position'], 'after' ); ?> />
								<?php esc_html_e( 'After content', 'old-article-notice' ); ?>
							</label>
						</td>
					</tr>

					<!-- Colors -->
					<tr>
						<th scope="row"><?php esc_html_e( 'Appearance', 'old-article-notice' ); ?></th>
						<td>
							<fieldset>
								<div class="opn-color-group">
									<div>
										<label for="opn-border-color"><?php esc_html_e( 'Border', 'old-article-notice' ); ?></label>
										<input type="text" id="opn-border-color" name="<?php echo self::OPTION_KEY; ?>[border_color]"
										       value="<?php echo esc_attr( $s['border_color'] ); ?>" class="opn-color-picker" data-default-color="#d63638" />
									</div>
									<div>
										<label for="opn-text-color"><?php esc_html_e( 'Text', 'old-article-notice' ); ?></label>
										<input type="text" id="opn-text-color" name="<?php echo self::OPTION_KEY; ?>[text_color]"
										       value="<?php echo esc_attr( $s['text_color'] ); ?>" class="opn-color-picker" data-default-color="#d63638" />
									</div>
									<div>
										<label for="opn-bg-color"><?php esc_html_e( 'Background', 'old-article-notice' ); ?></label>
										<input type="text" id="opn-bg-color" name="<?php echo self::OPTION_KEY; ?>[background_color]"
										       value="<?php echo esc_attr( $s['background_color'] ); ?>" class="opn-color-picker" data-default-color="#fef0f0" />
									</div>
								</div>
								<div class="opn-number-group">
									<div>
										<label for="opn-border-width"><?php esc_html_e( 'Border width (px)', 'old-article-notice' ); ?></label>
										<input type="number" id="opn-border-width" name="<?php echo self::OPTION_KEY; ?>[border_width]"
										       value="<?php echo esc_attr( $s['border_width'] ); ?>" min="0" max="10" style="width:60px;" />
									</div>
									<div>
										<label for="opn-border-radius"><?php esc_html_e( 'Corner radius (px)', 'old-article-notice' ); ?></label>
										<input type="number" id="opn-border-radius" name="<?php echo self::OPTION_KEY; ?>[border_radius]"
										       value="<?php echo esc_attr( $s['border_radius'] ); ?>" min="0" max="20" style="width:60px;" />
									</div>
								</div>
							</fieldset>
						</td>
					</tr>

					<!-- Post Types -->
					<tr>
						<th scope="row"><?php esc_html_e( 'Post Types', 'old-article-notice' ); ?></th>
						<td>
							<fieldset>
								<?php foreach ( $post_types as $pt ) :
									if ( 'attachment' === $pt->name ) continue;
								?>
									<label style="display:block; margin-bottom:4px;">
										<input type="checkbox" name="<?php echo self::OPTION_KEY; ?>[post_types][]"
										       value="<?php echo esc_attr( $pt->name ); ?>"
										       <?php checked( in_array( $pt->name, (array) $s['post_types'], true ) ); ?> />
										<?php echo esc_html( $pt->labels->singular_name ); ?>
									</label>
								<?php endforeach; ?>
							</fieldset>
						</td>
					</tr>

					<!-- Excluded Categories -->
					<tr>
						<th scope="row">
							<label for="opn-excluded-cats"><?php esc_html_e( 'Exclude Categories', 'old-article-notice' ); ?></label>
						</th>
						<td>
							<select id="opn-excluded-cats" name="<?php echo self::OPTION_KEY; ?>[excluded_categories][]"
							        multiple="multiple" style="min-width:300px; min-height:120px;">
								<?php foreach ( $categories as $cat ) : ?>
									<option value="<?php echo esc_attr( $cat->term_id ); ?>"
									        <?php echo in_array( $cat->term_id, (array) $s['excluded_categories'], true ) ? 'selected' : ''; ?>>
										<?php echo esc_html( $cat->name ); ?> (<?php echo esc_html( $cat->count ); ?>)
									</option>
								<?php endforeach; ?>
							</select>
							<p class="description"><?php esc_html_e( 'Hold Ctrl/Cmd to select multiple. Articles in these categories will never show the notice.', 'old-article-notice' ); ?></p>
						</td>
					</tr>

				</table>

				<h2 style="margin-top:2em;"><?php esc_html_e( 'Coverage Link', 'old-article-notice' ); ?></h2>
				<p class="description" style="margin-bottom:1em;">
					<?php esc_html_e( 'Automatically link readers to newer coverage by pointing them to the article\'s category or tag archive.', 'old-article-notice' ); ?>
				</p>

				<table class="form-table" role="presentation">

					<!-- Enable Coverage Link -->
					<tr>
						<th scope="row"><?php esc_html_e( 'Enable', 'old-article-notice' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="<?php echo self::OPTION_KEY; ?>[coverage_link]" value="1"
								       <?php checked( $s['coverage_link'] ); ?> id="opn-coverage-link" />
								<?php esc_html_e( 'Add a link to newer coverage in the notice', 'old-article-notice' ); ?>
							</label>
						</td>
					</tr>

					<!-- Taxonomy -->
					<tr class="opn-coverage-row">
						<th scope="row">
							<label for="opn-coverage-taxonomy"><?php esc_html_e( 'Link to', 'old-article-notice' ); ?></label>
						</th>
						<td>
							<?php
							$taxonomies = get_taxonomies( array( 'public' => true, 'show_ui' => true ), 'objects' );
							?>
							<select id="opn-coverage-taxonomy" name="<?php echo self::OPTION_KEY; ?>[coverage_taxonomy]">
								<?php foreach ( $taxonomies as $tax ) : ?>
									<option value="<?php echo esc_attr( $tax->name ); ?>"
									        <?php selected( $s['coverage_taxonomy'], $tax->name ); ?>>
										<?php echo esc_html( $tax->labels->singular_name ); ?>
										(<?php echo esc_html( $tax->name ); ?>)
									</option>
								<?php endforeach; ?>
							</select>
							<p class="description">
								<?php esc_html_e( 'The article\'s primary term from this taxonomy will be used for the link.', 'old-article-notice' ); ?>
							</p>
						</td>
					</tr>

					<!-- Link Text -->
					<tr class="opn-coverage-row">
						<th scope="row">
							<label for="opn-coverage-link-text"><?php esc_html_e( 'Link Text', 'old-article-notice' ); ?></label>
						</th>
						<td>
							<input type="text" id="opn-coverage-link-text"
							       name="<?php echo self::OPTION_KEY; ?>[coverage_link_text]"
							       value="<?php echo esc_attr( $s['coverage_link_text'] ); ?>"
							       class="large-text" style="max-width:500px;" />
							<p class="description">
								<?php esc_html_e( 'Use {term_name} for the category/tag name.', 'old-article-notice' ); ?>
							</p>
						</td>
					</tr>

					<!-- Usage hint -->
					<tr class="opn-coverage-row">
						<th scope="row"><?php esc_html_e( 'How to use', 'old-article-notice' ); ?></th>
						<td>
							<p class="description">
								<?php esc_html_e( 'Add {coverage_link} to your notice message above. It will be replaced with the linked text.', 'old-article-notice' ); ?><br />
								<?php esc_html_e( 'You can also use {term_name} directly in the notice message for just the name without a link.', 'old-article-notice' ); ?>
							</p>
							<p class="description" style="margin-top:8px;">
								<strong><?php esc_html_e( 'Example message:', 'old-article-notice' ); ?></strong><br />
								<code>This article was published {time_ago}. {coverage_link}</code>
							</p>
						</td>
					</tr>

				</table>

				<?php submit_button(); ?>

				</div><!-- /settings col -->

				<!-- Right column: live preview -->
				<div id="opn-preview-col">
					<h3 style="margin-top:30px;"><?php esc_html_e( 'Preview', 'old-article-notice' ); ?></h3>
					<div id="opn-preview-wrap">
						<div id="opn-preview-notice" style="
							border:<?php echo esc_attr( $s['border_width'] ); ?>px solid <?php echo esc_attr( $s['border_color'] ); ?>;
							padding:12px 16px;
							color:<?php echo esc_attr( $s['text_color'] ); ?>;
							background:<?php echo esc_attr( $s['background_color'] ); ?>;
							font-weight:600;
							text-align:center;
							margin-bottom:1em;
							border-radius:<?php echo esc_attr( $s['border_radius'] ); ?>px;
							font-size:0.9em;
							line-height:1.5;
						">
							<?php echo wp_kses_post( $this->replace_tags( $s['message'] ) ); ?>
						</div>
						<div class="opn-sample-content">
							<div class="opn-sample-label"><?php esc_html_e( 'Sample article content', 'old-article-notice' ); ?></div>
							<p style="margin:0 0 0.8em;">Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.</p>
							<p style="margin:0;">Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.</p>
						</div>
					</div>
				</div>

				</div><!-- /opn-admin-wrap -->

			</form>
		</div>

		<script>
		jQuery(function($) {
			$('.opn-color-picker').wpColorPicker({
				change: function() { setTimeout(updatePreview, 50); },
				clear: function() { setTimeout(updatePreview, 50); }
			});

			$('#opn-message, #opn-border-width, #opn-border-radius, #opn-threshold-value, #opn-threshold-unit, #opn-coverage-link, #opn-coverage-link-text').on('input change', updatePreview);

			// Show/hide coverage settings
			function toggleCoverageRows() {
				var enabled = $('#opn-coverage-link').is(':checked');
				$('.opn-coverage-row').toggle(enabled);
			}
			$('#opn-coverage-link').on('change', toggleCoverageRows);
			toggleCoverageRows();

			function updatePreview() {
				var $notice = $('#opn-preview-notice');
				var borderColor = $('#opn-border-color').val() || '#d63638';
				var textColor = $('#opn-text-color').val() || '#d63638';
				var bgColor = $('#opn-bg-color').val() || '#fef0f0';
				var borderWidth = $('#opn-border-width').val() || 2;
				var borderRadius = $('#opn-border-radius').val() || 4;

				$notice.css({
					'border': borderWidth + 'px solid ' + borderColor,
					'color': textColor,
					'background': bgColor,
					'border-radius': borderRadius + 'px'
				});

				var msg = $('#opn-message').val();
				msg = msg.replace('{time_ago}', '2 years ago');
				msg = msg.replace('{years}', '2');
				msg = msg.replace('{months}', '25');
				msg = msg.replace('{days}', '760');
				msg = msg.replace('{date}', 'March 15, 2024');
				msg = msg.replace('{updated_date}', 'January 10, 2026');

				// Coverage link preview
				if ($('#opn-coverage-link').is(':checked')) {
					var linkText = $('#opn-coverage-link-text').val() || 'See our latest {term_name} coverage &rarr;';
					linkText = linkText.replace('{term_name}', 'Local Government');
					msg = msg.replace('{coverage_link}', '<a href="#" style="color:' + textColor + '">' + linkText + '</a>');
					msg = msg.replace('{term_name}', 'Local Government');
				} else {
					msg = msg.replace('{coverage_link}', '');
					msg = msg.replace('{term_name}', '');
				}

				$notice.html(msg);
			}
		});
		</script>
		<?php
	}

	// =========================================================================
	// Per-post meta box
	// =========================================================================

	public function add_meta_box() {
		$post_types = (array) $this->settings['post_types'];
		foreach ( $post_types as $pt ) {
			add_meta_box(
				'opn_disable_notice',
				__( 'Old Article Notice', 'old-article-notice' ),
				array( $this, 'render_meta_box' ),
				$pt,
				'side',
				'low'
			);
		}
	}

	public function render_meta_box( $post ) {
		wp_nonce_field( 'opn_meta_box', 'opn_meta_nonce' );
		$disabled = get_post_meta( $post->ID, '_opn_disable', true );
		?>
		<label>
			<input type="checkbox" name="opn_disable" value="1" <?php checked( $disabled ); ?> />
			<?php esc_html_e( 'Disable notice on this article', 'old-article-notice' ); ?>
		</label>
		<p class="description" style="margin-top:6px;">
			<?php esc_html_e( 'Check this for evergreen content that stays relevant regardless of age.', 'old-article-notice' ); ?>
		</p>
		<?php
	}

	public function save_meta_box( $post_id ) {
		if ( ! isset( $_POST['opn_meta_nonce'] ) || ! wp_verify_nonce( $_POST['opn_meta_nonce'], 'opn_meta_box' ) ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		if ( ! empty( $_POST['opn_disable'] ) ) {
			update_post_meta( $post_id, '_opn_disable', 1 );
		} else {
			delete_post_meta( $post_id, '_opn_disable' );
		}
	}

	// =========================================================================
	// Activation / Uninstall
	// =========================================================================

	public static function activate() {
		if ( false === get_option( self::OPTION_KEY ) ) {
			add_option( self::OPTION_KEY, ( new self() )->defaults() );
		}
	}

	public static function uninstall() {
		delete_option( self::OPTION_KEY );
		delete_metadata( 'post', 0, '_opn_disable', '', true );
	}
}

// Initialize
Old_Article_Notice::instance();

// Lifecycle hooks
register_activation_hook( __FILE__, array( 'Old_Article_Notice', 'activate' ) );
register_uninstall_hook( __FILE__, array( 'Old_Article_Notice', 'uninstall' ) );
