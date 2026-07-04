<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers and renders the Settings > Maota Metamarkup admin page,
 * and owns the single field schema used for defaults, rendering, and
 * sanitization (one table, not three).
 */
class Maota_MM_Settings {

	const OPTION_NAME = 'maota_metamarkup_options';
	const GROUP       = 'maota_metamarkup_group';
	const PAGE_SLUG   = 'maota-metamarkup';

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
	}

	/**
	 * Single source of truth for every field: label, input type, choices,
	 * and default. Used to build defaults, render inputs, and sanitize input.
	 */
	public static function get_field_schema() {
		return array(
			'organization'   => array(
				'title'  => __( 'Organization Identity', 'maota-metamarkup' ),
				'fields' => array(
					'org_legal_name'        => array(
						'label'   => __( 'Legal / Organization Name', 'maota-metamarkup' ),
						'type'    => 'text',
						'default' => '',
					),
					'org_type'              => array(
						'label'   => __( 'Organization Type', 'maota-metamarkup' ),
						'type'    => 'select',
						'choices' => array(
							'Organization'            => __( 'Organization', 'maota-metamarkup' ),
							'Corporation'              => __( 'Corporation', 'maota-metamarkup' ),
							'LocalBusiness'            => __( 'Local Business (generic)', 'maota-metamarkup' ),
							'Hotel'                    => __( 'Hotel', 'maota-metamarkup' ),
							'BedAndBreakfast'          => __( 'Bed and Breakfast', 'maota-metamarkup' ),
							'Restaurant'               => __( 'Restaurant', 'maota-metamarkup' ),
							'TravelAgency'             => __( 'Travel Agency / Tour Operator', 'maota-metamarkup' ),
							'TouristAttraction'        => __( 'Tourist Attraction', 'maota-metamarkup' ),
							'NGO'                      => __( 'NGO', 'maota-metamarkup' ),
							'EducationalOrganization'  => __( 'Educational Organization', 'maota-metamarkup' ),
							'GovernmentOrganization'   => __( 'Government Organization', 'maota-metamarkup' ),
						),
						'default' => 'Organization',
					),
					'org_logo_url'          => array(
						'label'       => __( 'Logo URL', 'maota-metamarkup' ),
						'type'        => 'url',
						'default'     => '',
						'description' => __( 'Falls back to your Site Logo (Customizer) or Site Icon if left blank.', 'maota-metamarkup' ),
					),
					'org_description'       => array(
						'label'   => __( 'Description / Summary', 'maota-metamarkup' ),
						'type'    => 'textarea',
						'default' => '',
					),
					'org_email'             => array(
						'label'   => __( 'Contact Email', 'maota-metamarkup' ),
						'type'    => 'email',
						'default' => '',
					),
					'org_phone'             => array(
						'label'   => __( 'Phone', 'maota-metamarkup' ),
						'type'    => 'text',
						'default' => '',
					),
					'org_street_address'    => array(
						'label'   => __( 'Street Address', 'maota-metamarkup' ),
						'type'    => 'text',
						'default' => '',
					),
					'org_address_locality'  => array(
						'label'   => __( 'City / Locality', 'maota-metamarkup' ),
						'type'    => 'text',
						'default' => '',
					),
					'org_address_region'    => array(
						'label'   => __( 'Region / State', 'maota-metamarkup' ),
						'type'    => 'text',
						'default' => '',
					),
					'org_postal_code'       => array(
						'label'   => __( 'Postal Code', 'maota-metamarkup' ),
						'type'    => 'text',
						'default' => '',
					),
					'org_address_country'   => array(
						'label'   => __( 'Country', 'maota-metamarkup' ),
						'type'    => 'text',
						'default' => '',
					),
					'org_sameas_urls'       => array(
						'label'       => __( 'Social Profile URLs', 'maota-metamarkup' ),
						'type'        => 'url_list',
						'default'     => '',
						'description' => __( 'One URL per line (e.g. LinkedIn, X, Facebook).', 'maota-metamarkup' ),
					),
					'org_founding_date'     => array(
						'label'       => __( 'Founding Date', 'maota-metamarkup' ),
						'type'        => 'date',
						'default'     => '',
						'description' => __( 'Optional — can be left blank. If set, it adds a light trust signal for AI agents and search engines; if empty, it is simply omitted from the structured data.', 'maota-metamarkup' ),
					),
				),
			),
			'agent_controls' => array(
				'title'  => __( 'Agent Behavioral Controls', 'maota-metamarkup' ),
				'fields' => array(
					'robots_index'  => array(
						'label'   => __( 'Indexing', 'maota-metamarkup' ),
						'type'    => 'radio',
						'choices' => array(
							'index'   => __( 'Allow indexing', 'maota-metamarkup' ),
							'noindex' => __( 'Prevent indexing', 'maota-metamarkup' ),
						),
						'default' => 'index',
					),
					'robots_follow' => array(
						'label'   => __( 'Link Following', 'maota-metamarkup' ),
						'type'    => 'radio',
						'choices' => array(
							'follow'   => __( 'Follow links', 'maota-metamarkup' ),
							'nofollow' => __( "Don't follow links", 'maota-metamarkup' ),
						),
						'default' => 'follow',
					),
				),
			),
			'context'        => array(
				'title'  => __( 'Dynamic Context Fields', 'maota-metamarkup' ),
				'fields' => array(
					'context_summary'       => array(
						'label'       => __( 'What This Site Provides', 'maota-metamarkup' ),
						'type'        => 'textarea',
						'default'     => '',
						'description' => __( 'A one- to two-sentence overview of your organization and what it does. Used as the default description across meta tags, Open Graph, and llms.txt whenever a more specific description is not available.', 'maota-metamarkup' ),
						'placeholder' => __( 'Example entry: We help small businesses manage their finances with easy-to-use accounting software.', 'maota-metamarkup' ),
					),
					'context_offerings'     => array(
						'label'       => __( 'Primary Offerings / Services', 'maota-metamarkup' ),
						'type'        => 'text_list',
						'default'     => '',
						'description' => __( 'The main products or services you offer, one per line. Listed as structured offerings so AI agents and search engines know what you sell.', 'maota-metamarkup' ),
						'placeholder' => __( "Example entry:\nAccounting software\nPayroll management\nTax filing assistance", 'maota-metamarkup' ),
					),
					'context_audience'      => array(
						'label'       => __( 'Target Audience', 'maota-metamarkup' ),
						'type'        => 'text',
						'default'     => '',
						'description' => __( 'Who your site or organization primarily serves. Helps AI agents judge whether your content matches what a user is looking for.', 'maota-metamarkup' ),
						'placeholder' => __( 'Example entry: Small business owners and freelancers in Norway', 'maota-metamarkup' ),
					),
					'context_knows_about'   => array(
						'label'       => __( 'Topics / Knowledge Areas', 'maota-metamarkup' ),
						'type'        => 'text_list',
						'default'     => '',
						'description' => __( 'Subjects or areas of expertise your organization is known for, one per line. Maps to structured data that AI agents use to understand your expertise.', 'maota-metamarkup' ),
						'placeholder' => __( "Example entry:\nSmall business accounting\nNorwegian tax law\nInvoicing", 'maota-metamarkup' ),
					),
					'context_last_reviewed' => array(
						'label'       => __( 'Knowledge Cutoff / Last Reviewed', 'maota-metamarkup' ),
						'type'        => 'date',
						'default'     => '',
						'description' => __( 'The date this information was last checked for accuracy. Signals freshness to AI agents and search engines.', 'maota-metamarkup' ),
					),
					'context_additional_ai' => array(
						'label'       => __( 'Additional Context for AI Agents', 'maota-metamarkup' ),
						'type'        => 'textarea',
						'default'     => '',
						'description' => __( "Anything else AI agents should know that doesn't fit elsewhere — policies, tone, disambiguation notes, and so on. Appears only in llms.txt, not in meta tags.", 'maota-metamarkup' ),
						'placeholder' => __( 'Example entry: When asked about pricing, point to the current plans page rather than quoting fixed numbers.', 'maota-metamarkup' ),
					),
				),
			),
		);
	}

	public static function get_defaults() {
		$defaults = array();
		foreach ( self::get_field_schema() as $section_key => $section ) {
			$defaults[ $section_key ] = array();
			foreach ( $section['fields'] as $field_key => $field ) {
				$defaults[ $section_key ][ $field_key ] = $field['default'];
			}
		}
		return $defaults;
	}

	public function register_menu() {
		add_options_page(
			__( 'Maota Metamarkup', 'maota-metamarkup' ),
			__( 'Maota Metamarkup', 'maota-metamarkup' ),
			'manage_options',
			self::PAGE_SLUG,
			array( $this, 'render_page' )
		);
	}

	public function register_settings() {
		register_setting(
			self::GROUP,
			self::OPTION_NAME,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize' ),
				'default'           => self::get_defaults(),
			)
		);

		foreach ( self::get_field_schema() as $section_key => $section ) {
			$section_id = "maota_mm_section_{$section_key}";

			// Note: don't pass an 'id' key in $args here — add_settings_section()
			// merges $args over its own defaults (which include 'id' => $section_id),
			// so an 'id' override here would desync section storage from the
			// field registrations below, and do_settings_fields() would render
			// nothing. render_section_intro() below reads 'section_key' instead.
			add_settings_section(
				$section_id,
				$section['title'],
				array( $this, 'render_section_intro' ),
				self::PAGE_SLUG,
				array( 'section_key' => $section_key )
			);

			foreach ( $section['fields'] as $field_key => $field ) {
				add_settings_field(
					"maota_mm_{$field_key}",
					$field['label'],
					array( $this, 'render_field' ),
					self::PAGE_SLUG,
					$section_id,
					array(
						'section' => $section_key,
						'key'     => $field_key,
						'field'   => $field,
					)
				);
			}
		}
	}

	public function render_section_intro( $args ) {
		$section_key = $args['section_key'];

		if ( 'organization' === $section_key ) {
			/* translators: %s: link to Settings > General */
			printf(
				'<p>' . esc_html__( 'Your Site Title, Tagline, Site Icon, and Site Logo are managed under %s and are used automatically alongside the fields below.', 'maota-metamarkup' ) . '</p>',
				'<a href="' . esc_url( admin_url( 'options-general.php' ) ) . '">' . esc_html__( 'Settings → General and the Customizer', 'maota-metamarkup' ) . '</a>'
			);
		} elseif ( 'agent_controls' === $section_key ) {
			echo '<p>' . esc_html__( 'Controls the site-wide robots meta tag read by search engines and AI crawlers.', 'maota-metamarkup' ) . '</p>';
		} elseif ( 'context' === $section_key ) {
			echo '<p>' . esc_html__( 'Extra context that helps AI agents understand what your organization offers. Feeds structured data and llms.txt.', 'maota-metamarkup' ) . '</p>';
		}
	}

	/**
	 * Which language the settings page is currently editing, driven by the
	 * WPML admin-bar language switch. Returns lang/default codes and whether
	 * we're on a secondary (non-default) language.
	 */
	private function editing_language() {
		$lang = Maota_MM_I18n::current_language();
		if ( ! $lang && isset( $_GET['lang'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- display routing only.
			$lang = sanitize_key( wp_unslash( $_GET['lang'] ) );
		}
		$default   = Maota_MM_I18n::default_language();
		$secondary = ( Maota_MM_I18n::is_active() && $lang && $default && $lang !== $default );

		return array(
			'lang'      => $lang,
			'default'   => $default,
			'secondary' => $secondary,
		);
	}

	/** Human-readable name for a language code (falls back to the code). */
	private function language_label( $code ) {
		$langs = Maota_MM_I18n::active_languages();
		return isset( $langs[ $code ]['native_name'] ) ? $langs[ $code ]['native_name'] : $code;
	}

	public function render_field( $args ) {
		$section = $args['section'];
		$key     = $args['key'];
		$field   = $args['field'];
		$data    = Maota_MM_Data::instance();
		$ctx     = $this->editing_language();

		$translating = ( $ctx['secondary'] && Maota_MM_I18n::is_translatable( $key ) );

		if ( $translating ) {
			$lang        = $ctx['lang'];
			$name        = self::OPTION_NAME . "[i18n][{$lang}][{$key}]";
			$id          = "maota_mm_i18n_{$lang}_{$key}";
			$value       = $data->get_i18n_value( $lang, $key );
			$base        = $data->get_field( $section, $key );
			$placeholder = ( '' !== trim( (string) $base ) ) ? $base : ( isset( $field['placeholder'] ) ? $field['placeholder'] : '' );
		} else {
			$name        = self::OPTION_NAME . "[{$section}][{$key}]";
			$id          = "maota_mm_{$section}_{$key}";
			$value       = $data->get_field( $section, $key );
			$placeholder = isset( $field['placeholder'] ) ? $field['placeholder'] : '';
		}

		switch ( $field['type'] ) {
			case 'textarea':
			case 'text_list':
			case 'url_list':
				printf(
					'<textarea id="%1$s" name="%2$s" rows="4" cols="50" class="large-text" placeholder="%4$s">%3$s</textarea>',
					esc_attr( $id ),
					esc_attr( $name ),
					esc_textarea( $value ),
					esc_attr( $placeholder )
				);
				break;

			case 'select':
				echo '<select id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '">';
				foreach ( $field['choices'] as $choice_key => $choice_label ) {
					printf(
						'<option value="%1$s" %2$s>%3$s</option>',
						esc_attr( $choice_key ),
						selected( $value, $choice_key, false ),
						esc_html( $choice_label )
					);
				}
				echo '</select>';
				break;

			case 'radio':
				foreach ( $field['choices'] as $choice_key => $choice_label ) {
					printf(
						'<label style="margin-right:1em;"><input type="radio" name="%1$s" value="%2$s" %3$s /> %4$s</label>',
						esc_attr( $name ),
						esc_attr( $choice_key ),
						checked( $value, $choice_key, false ),
						esc_html( $choice_label )
					);
				}
				break;

			case 'email':
				printf(
					'<input type="email" id="%1$s" name="%2$s" value="%3$s" placeholder="%4$s" class="regular-text" />',
					esc_attr( $id ),
					esc_attr( $name ),
					esc_attr( $value ),
					esc_attr( $placeholder )
				);
				break;

			case 'url':
				printf(
					'<input type="url" id="%1$s" name="%2$s" value="%3$s" placeholder="%4$s" class="regular-text" />',
					esc_attr( $id ),
					esc_attr( $name ),
					esc_attr( $value ),
					esc_attr( $placeholder )
				);
				break;

			case 'date':
				printf(
					'<input type="date" id="%1$s" name="%2$s" value="%3$s" />',
					esc_attr( $id ),
					esc_attr( $name ),
					esc_attr( $value )
				);
				break;

			case 'text':
			default:
				printf(
					'<input type="text" id="%1$s" name="%2$s" value="%3$s" placeholder="%4$s" class="regular-text" />',
					esc_attr( $id ),
					esc_attr( $name ),
					esc_attr( $value ),
					esc_attr( $placeholder )
				);
				break;
		}

		if ( ! empty( $field['description'] ) ) {
			echo '<p class="description">' . esc_html( $field['description'] ) . '</p>';
		}

		if ( $translating ) {
			echo '<p class="description"><em>' . sprintf(
				/* translators: %s: language name */
				esc_html__( 'Translation for %s — leave blank to use the default language.', 'maota-metamarkup' ),
				esc_html( $this->language_label( $ctx['lang'] ) )
			) . '</em></p>';
		} elseif ( $ctx['secondary'] && ! Maota_MM_I18n::is_translatable( $key ) ) {
			echo '<p class="description"><em>' . esc_html__( 'Shared across all languages.', 'maota-metamarkup' ) . '</em></p>';
		}
	}

	/**
	 * Explains which language is being edited when WPML is active, so users
	 * understand that content fields are language-specific.
	 */
	private function render_language_notice() {
		if ( ! Maota_MM_I18n::is_active() ) {
			return;
		}
		$ctx = $this->editing_language();
		if ( ! $ctx['lang'] ) {
			return;
		}
		$label = '<strong>' . esc_html( $this->language_label( $ctx['lang'] ) ) . '</strong>';

		echo '<div class="notice notice-info inline"><p>';
		if ( $ctx['secondary'] ) {
			printf(
				/* translators: %s: language name */
				wp_kses_post( __( 'Editing content for %s. Content fields are language-specific — switch language in the admin bar to translate them. Other settings are shared across all languages.', 'maota-metamarkup' ) ),
				$label // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above.
			);
		} else {
			printf(
				/* translators: %s: language name */
				wp_kses_post( __( 'Editing content for %s (default language). Switch language in the admin bar to add translations for the content fields.', 'maota-metamarkup' ) ),
				$label // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above.
			);
		}
		echo '</p></div>';
	}

	public function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'maota-metamarkup' ) );
		}
		?>
		<div class="wrap">
			<h1><?php echo esc_html__( 'Maota Metamarkup', 'maota-metamarkup' ); ?></h1>
			<?php $this->render_language_notice(); ?>
			<form method="post" action="options.php">
				<?php
				settings_fields( self::GROUP );
				do_settings_sections( self::PAGE_SLUG );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Whitelist-based sanitizer: only known field keys survive, each
	 * passed through the sanitizer appropriate to its declared type.
	 */
	public function sanitize( $input ) {
		$input    = is_array( $input ) ? $input : array();
		$defaults = self::get_defaults();
		$schema   = self::get_field_schema();

		// Start from the currently stored option so anything not present in
		// this submission is preserved: other languages' translations, and the
		// base content fields that are hidden while editing a secondary
		// language (the form only posts one language's values at a time).
		$existing = get_option( self::OPTION_NAME, array() );
		$clean    = is_array( $existing ) ? $existing : array();

		// Base (default-language) fields: overlay only those actually submitted.
		foreach ( $schema as $section_key => $section ) {
			if ( ! isset( $clean[ $section_key ] ) || ! is_array( $clean[ $section_key ] ) ) {
				$clean[ $section_key ] = array();
			}
			foreach ( $section['fields'] as $field_key => $field ) {
				if ( isset( $input[ $section_key ][ $field_key ] ) ) {
					$clean[ $section_key ][ $field_key ] = $this->sanitize_value( $input[ $section_key ][ $field_key ], $field, $defaults[ $section_key ][ $field_key ] );
				} elseif ( ! isset( $clean[ $section_key ][ $field_key ] ) ) {
					$clean[ $section_key ][ $field_key ] = $defaults[ $section_key ][ $field_key ];
				}
			}
		}

		// Per-language translations: overlay submitted languages/fields only.
		if ( isset( $input['i18n'] ) && is_array( $input['i18n'] ) ) {
			if ( ! isset( $clean['i18n'] ) || ! is_array( $clean['i18n'] ) ) {
				$clean['i18n'] = array();
			}
			foreach ( $input['i18n'] as $lang => $vals ) {
				$lang = sanitize_key( $lang );
				if ( '' === $lang || ! is_array( $vals ) ) {
					continue;
				}
				if ( ! isset( $clean['i18n'][ $lang ] ) || ! is_array( $clean['i18n'][ $lang ] ) ) {
					$clean['i18n'][ $lang ] = array();
				}
				foreach ( Maota_MM_I18n::translatable_fields() as $tf ) {
					list( $tsection, $tkey ) = $tf;
					if ( isset( $vals[ $tkey ] ) && isset( $schema[ $tsection ]['fields'][ $tkey ] ) ) {
						$clean['i18n'][ $lang ][ $tkey ] = $this->sanitize_value( $vals[ $tkey ], $schema[ $tsection ]['fields'][ $tkey ], '' );
					}
				}
			}
		}

		return $clean;
	}

	private function sanitize_value( $raw, $field, $default ) {
		switch ( $field['type'] ) {
			case 'email':
				return sanitize_email( $raw );

			case 'url':
				return esc_url_raw( trim( $raw ) );

			case 'url_list':
				$lines = preg_split( '/\r\n|\r|\n/', (string) $raw );
				$lines = array_filter( array_map( 'esc_url_raw', array_map( 'trim', $lines ) ) );
				return implode( "\n", $lines );

			case 'text_list':
				$lines = preg_split( '/\r\n|\r|\n/', (string) $raw );
				$lines = array_filter( array_map( 'sanitize_text_field', array_map( 'trim', $lines ) ) );
				return implode( "\n", $lines );

			case 'textarea':
				return sanitize_textarea_field( $raw );

			case 'select':
			case 'radio':
				$choices = array_keys( $field['choices'] );
				return in_array( $raw, $choices, true ) ? $raw : $default;

			case 'date':
				$raw = trim( (string) $raw );
				if ( '' === $raw ) {
					return '';
				}
				$parsed = DateTime::createFromFormat( 'Y-m-d', $raw );
				return ( $parsed && $parsed->format( 'Y-m-d' ) === $raw ) ? $raw : $default;

			case 'text':
			default:
				return sanitize_text_field( $raw );
		}
	}
}
