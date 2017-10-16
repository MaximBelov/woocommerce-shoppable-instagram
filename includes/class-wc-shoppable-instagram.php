<?php

use InstagramScraper\Instagram;

class WC_Shoppable_Instagram {

	protected $_instagram;


	private $_api_key = null;


	private $_api_secret = null;


	private $token = null;


	private $_errors = array();


	const API_URL = 'https://api.instagram.com/v1/';


	public function __construct() {
		$this->init();
		$this->includes();
	}


	private function init() {
		$settings_value = get_option( 'icy_instagram_feed' );
	}


	public function admin_init() {
		global $wcsi_admin, $wcsi_settings;

		$page = isset( $_GET['page'] ) ? esc_attr( $_GET['page'] ) : '';


		$wcsi_admin->admin_init();
		//$wcsi_admin->admin_menu();
		//$wcsi_settings->init();

		$wcsi_settings->init();

		add_action( 'admin_enqueue_scripts', array( $wcsi_admin, 'admin_scripts' ), 99 );

		add_action( 'wp_ajax_icyig_update', array( $wcsi_admin, 'update_database' ) );
		add_action( 'wp_ajax_icyig_get_hotspots', array( $wcsi_admin, 'get_hotspots' ) );

		if ( $page == 'instagram-feed' ) {
			add_action( 'admin_notices', array( $wcsi_admin, 'auth_check_notice' ) );

			if ( isset( $_GET['token'] ) && $this->getToken() == 0 ) {
				$wcsi_admin->updateAuth();
			}

			if ( isset( $_GET['disconnect'] ) && $_GET['disconnect'] == 'true' ) {
				$wcsi_admin->disconnectAuth();
			}
		}
	}


	public function ig() {
		return $this->_instagram;
	}


	public function getToken() {
		$token = get_option( 'icy_instagram_feed_token' );

		return $token;
	}


	public function setToken( $token ) {
		$this->token = ! empty( $token ) ? $token : 0;
	}

	public function getUserID() {
		$user_id = get_option( 'wcsi_user_id' );

		if ( $user_id ) {
			return absint( $user_id );
		}

		return 'self';
	}


	public function getCached() {

		$json_data = file_get_contents(ABSPATH.'results.json');

		return maybe_unserialize($json_data);

	}


	public function setCache( $media ) {

		$fp = fopen(ABSPATH.'results.json', 'w');
		$test = fwrite($fp, maybe_serialize($media));

		fclose($fp);

		set_transient( 'wcsi_stored_media', 'stored to results.json', apply_filters( 'wcsi_stored_media_duration', 60 * 60 * 24 ) );
	}


	public function deleteCache() {
		wp_cache_flush();

		delete_transient( 'wcsi_stored_media' );
	}

	function getHashtags( $string ) {
		$hashtags = false;
		preg_match_all( "/(#\w+)/u", $string, $matches );
		if ( $matches ) {
			$hashtagsArray = array_count_values( $matches[0] );
			$hashtags      = array_keys( $hashtagsArray );
		}

		return $hashtags;
	}

	public function getMedia( $mediasP= array(), $maxId = '' ) {
//		$medias = Instagram::getMedias( 'zhilyova_lingerie', $num );
		$settings_value = get_option( 'icy_instagram_config_settings' );

		if(!isset($settings_value['tags'])){
			return false;
		}

		$inst = new Instagram;
		$result = $inst->getPaginateMediasByTag($settings_value['tags'], $maxId);

		$medias = $result['medias'];

//		$this->setCache( $medias );
//		$this->getCached( );

//		die();

		$medias = array_merge($medias, $mediasP);


//		 See if we have media that is cached already
		if ( false === $this->getCached() ) {


			/*
			Available properties:
				$id;
				$createdTime;
				$type;
				$link;
				$imageLowResolutionUrl;
				$imageThumbnailUrl;
				$imageStandardResolutionUrl;
				$imageHighResolutionUrl;
				$caption;
				$captionIsEdited;
				$isAd;
				$videoLowResolutionUrl;
				$videoStandardResolutionUrl;
				$videoLowBandwidthUrl;
				$videoViews;
				$code;
				$owner;
				$ownerId;
				$likesCount;
				$locationId;
				$locationName;
				$commentsCount;

			*/

			if(isset($settings_value['display_num'])){

				if(
					$result['hasNextPage'] === true
				   && count($medias) < (int)$settings_value['display_num']
				) {
					return $this->getMedia($medias, $result['maxId']);
				}
			}

			if ( is_array( $medias ) ) {
				foreach ( $medias as $key => $media ) {
					$media->tags    = $this->getHashtags( $media->caption );
					$medias[ $key ] = $media;
					if(isset($settings_value['author'])){
						if($media->ownerId != $settings_value['author']){
							unset($medias[$key]);
						}
					}
				}
			}
			// Cache the results
			$this->setCache( $medias );
			return $medias;
		}

		return $this->getCached();
	}


	public function has_errors( $media ) {
		if ( isset( $media->meta->error_type ) ) {
			return true;
		}

		return false;
	}


	public function log_error( $media ) {
		$this->_errors[] = $media->meta->error_message;
	}


	public function show_errors() {
		if ( ! empty( $this->_errors ) ) {
			foreach ( $this->_errors as $error ) {
				print '<p class="ig-error">' . $error . '</p>';
			}
		}
	}


	public function includes() {

	}


	public function plugin_url() {
		return untrailingslashit( plugin_dir_url( __FILE__ ) );
	}


	public function plugin_path() {
		return untrailingslashit( plugin_dir_path( __FILE__ ) );
	}


	public function template_path() {
		return untrailingslashit( plugin_dir_path( __FILE__ ) ) . '/templates';
	}

}