<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Assembles and prints a single JSON-LD <script> block containing a
 * schema.org @graph of the Organization and WebSite, linked via @id.
 */
class Maota_MM_Schema_JsonLD {

	public function __construct() {
		add_action( 'wp_head', array( $this, 'render' ), 5 );
	}

	public function render() {
		$graph = array(
			$this->build_organization_node(),
			$this->build_website_node(),
		);

		$data = array(
			'@context' => 'https://schema.org',
			'@graph'   => $graph,
		);

		$json = wp_json_encode( $data );
		// Guard against </script> breakout when a text field contains it.
		$json = str_replace( '</', '<\/', $json );

		echo '<script type="application/ld+json">' . $json . '</script>' . "\n";
	}

	private function build_organization_node() {
		$data       = Maota_MM_Data::instance();
		$org_id     = $data->get_home_url() . '#organization';
		$legal_name = $data->get_field( 'organization', 'org_legal_name' );
		$logo_url   = $data->get_effective_logo_url();
		$email      = $data->get_field( 'organization', 'org_email' );
		$phone      = $data->get_field( 'organization', 'org_phone' );

		$node = array(
			'@type'       => $data->get_field( 'organization', 'org_type' ) ?: 'Organization',
			'@id'         => $org_id,
			'name'        => $data->get_effective_org_name(),
			'url'         => $data->get_home_url(),
			'description' => $data->get_effective_org_description(),
		);

		if ( $legal_name ) {
			$node['legalName'] = $legal_name;
		}

		if ( $logo_url ) {
			$node['logo'] = array(
				'@type' => 'ImageObject',
				'url'   => $logo_url,
			);
		}

		if ( $email ) {
			$node['email'] = $email;
		}

		$address = $this->build_address();
		if ( $address ) {
			$node['address'] = $address;
		}

		if ( $email || $phone ) {
			$contact_point = array( '@type' => 'ContactPoint' );
			if ( $phone ) {
				$contact_point['telephone'] = $phone;
			}
			if ( $email ) {
				$contact_point['email'] = $email;
			}
			$contact_point['contactType'] = 'customer support';
			$node['contactPoint']         = $contact_point;
		}

		$same_as = $data->get_sameas_urls();
		if ( $same_as ) {
			$node['sameAs'] = $same_as;
		}

		$founding_date = $data->get_field( 'organization', 'org_founding_date' );
		if ( $founding_date ) {
			$node['foundingDate'] = $founding_date;
		}

		$knows_about = $data->get_knows_about();
		if ( $knows_about ) {
			$node['knowsAbout'] = $knows_about;
		}

		$offerings = $data->get_offerings();
		if ( $offerings ) {
			$node['makesOffer'] = array_map(
				function ( $offering ) {
					return array(
						'@type'        => 'Offer',
						'itemOffered' => array(
							'@type' => 'Service',
							'name'  => $offering,
						),
					);
				},
				$offerings
			);
		}

		return $node;
	}

	private function build_address() {
		$data   = Maota_MM_Data::instance();
		$street = $data->get_field( 'organization', 'org_street_address' );
		$city   = $data->get_field( 'organization', 'org_address_locality' );
		$region = $data->get_field( 'organization', 'org_address_region' );
		$postal = $data->get_field( 'organization', 'org_postal_code' );
		$country = $data->get_field( 'organization', 'org_address_country' );

		if ( ! $street && ! $city && ! $region && ! $postal && ! $country ) {
			return null;
		}

		$address = array( '@type' => 'PostalAddress' );

		if ( $street ) {
			$address['streetAddress'] = $street;
		}
		if ( $city ) {
			$address['addressLocality'] = $city;
		}
		if ( $region ) {
			$address['addressRegion'] = $region;
		}
		if ( $postal ) {
			$address['postalCode'] = $postal;
		}
		if ( $country ) {
			$address['addressCountry'] = $country;
		}

		return $address;
	}

	private function build_website_node() {
		$data        = Maota_MM_Data::instance();
		$description = $data->get_field( 'context', 'context_summary' ) ?: $data->get_tagline();

		$node = array(
			'@type'     => 'WebSite',
			'@id'       => $data->get_home_url() . '#website',
			'name'      => $data->get_site_title(),
			'url'       => $data->get_home_url(),
			'publisher' => array( '@id' => $data->get_home_url() . '#organization' ),
		);

		if ( $description ) {
			$node['description'] = $description;
		}

		$language = $data->get_site_language();
		if ( $language ) {
			$node['inLanguage'] = $language;
		}

		$last_reviewed = $data->get_field( 'context', 'context_last_reviewed' );
		if ( $last_reviewed ) {
			$node['dateModified'] = $last_reviewed;
		}

		return $node;
	}
}
