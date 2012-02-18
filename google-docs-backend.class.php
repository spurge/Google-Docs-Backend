<?php

/**
 * The global plugin class
 */
class Google_Docs_Backend {

	const POST_TYPE = 'google-docs';

	var $user = '';
	var $pass = '';
	var $doc_client;
	var $spread_client;
	var $docs;
	var $spreads;
	var $feed;

	function __construct() {
		$this->auth();
		$this->get_feed();
		add_action( 'init', array( $this, 'register_post_type' ) );
		//add_action( 'init', array( $this, 'register_taxonomy' ) );
	}

	function auth() {
		$this->doc_client = Zend_Gdata_ClientLogin::getHttpClient( $this->user, $this->pass, Zend_Gdata_Docs::AUTH_SERVICE_NAME );
		$this->spread_client = Zend_Gdata_ClientLogin::getHttpClient( $this->user, $this->pass, Zend_Gdata_Spreadsheets::AUTH_SERVICE_NAME );
		$this->docs = new Zend_Gdata_Docs( $this->doc_client );
		$this->spreads = new Zend_Gdata_Spreadsheets( $this->spread_client );
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

		foreach( $this->feed as $entry ) {
			//echo $entry->getTitle() . '<br/>' . $entry->content->getSrc() . '<br/><br/>';
			if ( $entry->content->type == 'text/html' ) {
				$query = new WP_Query( array(
					'meta_value' => ( string ) $entry->getId(),
					'post_type' => self::POST_TYPE
				) );

				if ( $query->have_posts() ) {
					$ids[] = $query->post->ID;
					if ( strtotime( $query->post->post_date ) != strtotime( $entry->getUpdated() ) ) {
						wp_update_post( $this->create_post_by_entry( $entry, array( 'ID' => $query->post->ID ) ) );
					}
				} else {
					$id = wp_insert_post( $this->create_post_by_entry( $entry ) );
					add_post_meta( $id, 'gdoc_id', ( string ) $entry->getId(), true );
					$ids[] = $id;
				}
			}
		}

		$query = new WP_Query( array(
			'post__not_in' => $ids,
			'post_type' => self::POST_TYPE
		) );
		foreach( $query->posts as $post ) {
			wp_delete_post( $post->ID, true );
		}
	}

	function create_post_by_entry( $entry, $fields = array() ) {
		$url = $entry->content->getSrc();

		if ( strpos( $url, 'download/spreadsheets' ) ) {
			$url = str_replace( 'docs.google.com', 'spreadsheets.google.com', $url );
			$url .= '&exportFormat=html';
			$response = $this->spreads->get( $url );
		} else {
			$response = $this->docs->get( $url );
		}

		$body = wpautop( $response->getBody() );
		//$body = preg_replace( '/<style [^>]*>.*?<\/style>/i', '', $body );

		return array_merge( $fields, array(
			'post_type' => self::POST_TYPE,
			'post_title' => ( string ) $entry->getTitle(),
			'post_status' => 'publish',
			'post_date' => date( 'Y-m-d H:i:s', strtotime( $entry->getUpdated() ) ),
			'post_content' => $body
		) );
	}

}
