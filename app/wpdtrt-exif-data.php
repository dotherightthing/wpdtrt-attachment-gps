<?php
/**
 * Get EXIF (meta)data from image
 *
 * This file contains PHP.
 *
 * @since       0.1.0
 *
 * @package     WPDTRT_EXIF
 * @subpackage  WPDTRT_EXIF/app
 */

include_once( ABSPATH . 'wp-admin/includes/image.php' ); // access wp_read_image_metadata
add_filter('wp_read_image_metadata', 'wpdtrt_exif_read_image_geodata','',3);

/**
 * Read metadata from image
 *
 * Supplement the core function wp_read_image_metadata
 * to also return the GPS location data which WP usually ignores
 *
 * Added false values to prevent this function running over and over
 * if the image was taken with a non-geotagging camera
 *
 * @todo Pull geotag from wpdtrt_exif_attachment_geotag if it is not available in the image
 *
 * @example
 *  include_once( ABSPATH . 'wp-admin/includes/image.php' ); // access wp_read_image_metadata
 *  add_filter('wp_read_image_metadata', 'wpdtrt_exif_read_image_geodata','',3);
 *
 * @see http://kristarella.blog/2009/04/add-image-exif-metadata-to-wordpress/
 * @uses wp-admin/includes/image.php
 */
function wpdtrt_exif_read_image_geodata( $meta, $file, $sourceImageType ) {

  // the filtered function also runs exif_read_data
  // but the value is not accessible to the function.
  // note: @ suppresses any error messages that might be generated by the prefixed expression
  $exif = @exif_read_data( $file );

  if (!empty($exif['GPSLatitude'])) {
    $meta['latitude'] = $exif['GPSLatitude'] ;
  }
  else {
    $meta['latitude'] = false;
  }

  if (!empty($exif['GPSLatitudeRef'])) {
    $meta['latitude_ref'] = trim( $exif['GPSLatitudeRef'] );
  }
  else {
    $meta['latitude_ref'] = false;
  }

  if (!empty($exif['GPSLongitude'])) {
    $meta['longitude'] = $exif['GPSLongitude'] ;
  }
  else {
    $meta['longitude'] = false;
  }

  if (!empty($exif['GPSLongitudeRef'])) {
    $meta['longitude_ref'] = trim( $exif['GPSLongitudeRef'] );
  }
  else {
    $meta['longitude_ref'] = false;
  }

  return $meta;
}

?>