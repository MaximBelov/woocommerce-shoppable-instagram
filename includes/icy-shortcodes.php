<?php

add_action( 'after_setup_theme', 'calling_child_theme_setup' );
function calling_child_theme_setup() {
	if ( ! function_exists( 'icyig_display_feed' ) ) :

		function icyig_display_feed( $atts, $content = '' ) {
			global $wcsi;


			/**
			 * Number of images to display
			 */
			$display_num = isset( Icy_Instagram_Feed::$settings['display_num'] ) ? (int) Icy_Instagram_Feed::$settings['display_num'] : 10;
			$tags        = ( isset( Icy_Instagram_Feed::$settings['tags'] )
			                 && ! empty( Icy_Instagram_Feed::$settings['tags'] ) )
				? explode( ',', Icy_Instagram_Feed::$settings['tags'] )
				: array();


//		if ( defined( 'ICL_LANGUAGE_CODE' ) ) {
//
//			if ( ICL_LANGUAGE_CODE == 'en' ) {
//				$tags[] = '#eng';
//			}
//			else{
//				$tags[] = '#'.ICL_LANGUAGE_CODE;
//			}
//
//		}


			/**
			 * Instagram images
			 */
			$media = $wcsi->getMedia(  );
			if ( ! empty( $media ) ) {
				/**
				 * Hotspots data
				 */
				$media_data       = get_option( 'icyig_media_data_' . $wcsi->getToken() );
				$data             = Icy_Instagram_Feed::parse_data( json_decode( json_encode( $media_data ), true ) );
				$social_links     = isset( Icy_Instagram_Feed::$settings['enabled_social'] ) ? Icy_Instagram_Feed::$settings['enabled_social'] : array();
				$hoverstate       = ! empty( Icy_Instagram_Feed::$settings['hoverstate'] ) ? '<div class="thumb-hoverstate"><span class="icyicon-shop"></span><br />' . apply_filters( 'icyig_hoverstate_text', __( 'Shop Now' ) ) . '</div>' : '';
				$nav_menu         = Icy_Instagram_Feed::social_links();
				$icyig_image_meta = array();

				ob_start();

				print '<div id="gallery-popup" class="white-popup mfp-hide">
					<div class="icyig-detail">
				       <div class="icyig-detail-container">
				           <div class="icyig-detail-row">
				               <div class="dc-left">
				                   <div class="icyig-image-container"></div>
				               </div>
				               <div class="dc-right">
				                   <div class="icyig-content-container"></div>
				                   ' . $nav_menu . '         
				               </div>
				           </div>
				       </div>
				   </div>
	            </div>';


				print '<div class="icyig-container">';

				foreach ( $media as $entry ) {

					if ( ! empty( $tags ) ) {
						$diff = array_diff( $tags, $entry->tags );

//					var_dump($tags);
//					var_dump($entry->tags);
//					var_dump($diff);
//					die();

//					if ( count( $diff ) !== 0 ) {
//						continue;
//					}
					}


					printf( '<div class="icyig-thumb-container"><a data-mfp-src="#gallery-popup" href="' . $entry->imageStandardResolutionUrl . '">' . $hoverstate . '<div class="icyig-thumb" style="background-image: url(%1$s);" data-image-src="%1$s" data-image-id="%2$s"></div></a></div>', $entry->imageStandardResolutionUrl, $entry->id );

					/**
					 * Generate an array of media for JS access
					 */
					$icyig_image_meta[ $entry->id ] = apply_filters( 'icyig_image_meta', array(
						'caption'   => isset( $entry->caption ) ? $entry->caption : '',
						'user'      => array(
							'username' => $entry->owner->username,
							'picture'  => $entry->owner->profilePicUrl,
							'id'       => $entry->owner->id,
						),
						'timestamp' => human_time_diff( $entry->createdTime, current_time( 'timestamp' ) ),
						'likes'     => $entry->likesCount,
						'link'      => $entry->link,
						'tags'      => $entry->tags,
						'image'     => $entry->imageStandardResolutionUrl
					) );

				}

				print '<script>var icyig_hs_contents = ' . json_encode( $data ) . '; var icyig_image_meta = ' . json_encode( $icyig_image_meta ) . ';</script>';

				print '</div>';

			} elseif ( isset( $media->meta->error_type ) ) {
				print $media->meta->error_message;
			}

			$content = ob_get_contents();

			ob_end_clean();

			return $content;
		}

	endif;
//	add_shortcode( 'woocommerce_shoppable_instagram', 'icyig_display_feed' );
	add_shortcode( 'woocommerce_instagram_feed', 'icyig_display_feed' );

}