<?php

/**
 * The global plugin class
 */
class Google_Docs_Backend {

	const POST_TYPE = 'google-docs';

	var $user = '';
	var $pass = '';
	var $client;
	var $docs;
	var $feed;

	function __construct() {
		$this->auth();
		$this->get_feed();
		add_action( 'init', array( $this, 'register_post_type' ) );
		//add_action( 'init', array( $this, 'register_taxonomy' ) );
	}

	function auth() {
		$service = Zend_Gdata_Docs::AUTH_SERVICE_NAME;
		$this->client = Zend_Gdata_ClientLogin::getHttpClient( $this->user, $this->pass, $service );
		$this->docs = new Zend_Gdata_Docs( $this->client );
	}

	function get_feed() {
		$this->feed = $this->docs->getDocumentListFeed();
	}

	function register_post_type() {
		register_post_type( self::POST_TYPE,
			array(
				'labels' => array(
					'name' => __( 'Google Docs' ),
					'singular_name' => __( 'Google Doc' )
				),
				'public' => true,
				'show_ui' => false,
				'capabilities' => array( 'read_post' ),
				'has_archive' => true,
				'rewrite' => array( 'slug' => 'google-docs' )
			)
		);

		$this->sync_with_google();
	}

	function sync_with_google() {
		$ids = array();

		/*$query = new WP_Query( array( 'post_type' => self::POST_TYPE ) );
		foreach( $query->posts as $post ) {
			wp_delete_post( $post->ID );
		}*/

		foreach( $this->feed as $entry ) {
			if ( $entry->content->type == 'text/html' ) {
				$query = new WP_Query( array(
					'meta_value' => ( string ) $entry->getId(),
					'post_type' => self::POST_TYPE
				) );

				if ( $query->have_posts() ) {
					if ( strtotime( $query->post->post_date ) != strtotime( $entry->getUpdated() ) ) {
						$ids[] = $query->post->ID;
						wp_update_post( $this->create_post_by_entry( $entry, array( 'ID' => $query->post->ID ) ) );
					}
				} else {
					$id = wp_insert_post( $this->create_post_by_entry( $entry ) );
					add_post_meta( $id, 'gdoc_id', ( string ) $entry->getId(), true );
					$ids[] = $id;
				}
			}
		}

		/*$query = new WP_Query( array(
			'post__not_in' => $ids,
			'post_type' => self::POST_TYPE
		) );
		foreach( $query->posts as $post ) {
			wp_delete_post( $post->ID, true );
		}*/
	}

	function create_post_by_entry( $entry, $fields = array() ) {
		$this->client->setUri( $entry->content->getSrc() );
		$response = $this->client->request();
		return array_merge( $fields, array(
			'post_type' => self::POST_TYPE,
			'post_title' => ( string ) $entry->getTitle(),
			'post_status' => 'publish',
			'post_date' => date( 'Y-m-d H:i:s', strtotime( $entry->getUpdated() ) ),
			'post_content' => ( string ) $response->getBody()
		) );
	}

}
