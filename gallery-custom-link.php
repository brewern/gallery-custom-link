<?php
/*
Plugin Name: Gallery Custom Link
Plugin URI: http://nick-brewer.com
Description: Add an extra field to your image for a custom link to be used with the gallery
Version: 0.1
Author: Nick Brewer
Author URI: http://nick-brewer.com
License: GPL2

	  Copyright 2012  Nick Brewer  (email : brewer.nick@gmail.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

class GalleryCustomLink {
	public function __construct()
	{
		add_filter("attachment_fields_to_edit", array($this, 'rt_image_attachment_fields_to_edit'), null, 2);
		add_filter("attachment_fields_to_save", array($this, 'rt_image_attachment_fields_to_save'), null , 2);
		add_filter('post_gallery', array($this, 'shortcode'), 10, 2);
	}

	/**
	* The Gallery shortcode.
	*
	* This implements the functionality of the Gallery Shortcode for displaying
	* WordPress images on a post.
	*
	* @since 2.5.0
	*
	* @param array $attr Attributes attributed to the shortcode.
	* @return string HTML content to display gallery.
	*/
	public function shortcode($output, $attr)
	{
		/* -------------------------------
		MODIFIED CORE FUNCTION
		------------------------------- */

		global $post, $wp_locale;

	  static $instance = 0;
	  $instance++;

	  // We're trusting author input, so let's at least make sure it looks like a valid orderby statement
	  if ( isset( $attr['orderby'] ) ) {
	    $attr['orderby'] = sanitize_sql_orderby( $attr['orderby'] );
	    if ( !$attr['orderby'] )
	      unset( $attr['orderby'] );
	  }

	  extract(shortcode_atts(array(
	    'order'      => 'ASC',
	    'orderby'    => 'menu_order ID',
	    'id'         => $post->ID,
	    'itemtag'    => 'dl',
	    'icontag'    => 'dt',
	    'captiontag' => 'dd',
	    'columns'    => 3,
	    'size'       => 'thumbnail',
	    'include'    => '',
	    'exclude'    => ''
	  ), $attr));

	  $id = intval($id);
	  if ( 'RAND' == $order )
	    $orderby = 'none';

	  if ( !empty($include) ) {
	    $include = preg_replace( '/[^0-9,]+/', '', $include );
	    $_attachments = get_posts( array('include' => $include, 'post_status' => 'inherit', 'post_type' => 'attachment', 'post_mime_type' => 'image', 'order' => $order, 'orderby' => $orderby) );

	    $attachments = array();
	    foreach ( $_attachments as $key => $val ) {
	      $attachments[$val->ID] = $_attachments[$key];
	    }
	  } elseif ( !empty($exclude) ) {
	    $exclude = preg_replace( '/[^0-9,]+/', '', $exclude );
	    $attachments = get_children( array('post_parent' => $id, 'exclude' => $exclude, 'post_status' => 'inherit', 'post_type' => 'attachment', 'post_mime_type' => 'image', 'order' => $order, 'orderby' => $orderby) );
	  } else {
	    $attachments = get_children( array('post_parent' => $id, 'post_status' => 'inherit', 'post_type' => 'attachment', 'post_mime_type' => 'image', 'order' => $order, 'orderby' => $orderby) );
	  }

	  if ( empty($attachments) )
	    return '';

	  if ( is_feed() ) {
	    $output = "\n";
	    foreach ( $attachments as $att_id => $attachment )
	      $output .= wp_get_attachment_link($att_id, $size, true) . "\n";
	    return $output;
	  }

	  $itemtag = tag_escape($itemtag);
	  $captiontag = tag_escape($captiontag);
	  $columns = intval($columns);
	  $itemwidth = $columns > 0 ? floor(100/$columns) : 100;
	  $float = is_rtl() ? 'right' : 'left';

	  $selector = "gallery-{$instance}";

		$gallery_style = $gallery_div = '';
		if ( apply_filters( 'use_default_gallery_style', true ) )
			$gallery_style = "
			<style type='text/css'>
				#{$selector} {
					margin: auto;
				}
				#{$selector} .gallery-item {
					float: {$float};
					margin-top: 10px;
					text-align: center;
					width: {$itemwidth}%;
				}
				#{$selector} img {
					border: 2px solid #cfcfcf;
				}
				#{$selector} .gallery-caption {
					margin-left: 0;
				}
			</style>
			<!-- see gallery_shortcode() in wp-includes/media.php -->";
		$size_class = sanitize_html_class( $size );
		$gallery_div = "<div id='$selector' class='gallery galleryid-{$id} gallery-columns-{$columns} gallery-size-{$size_class}'>";
		$output = apply_filters( 'gallery_style', $gallery_style . "\n\t\t" . $gallery_div );

	  $i = 0;
	  foreach ( $attachments as $id => $attachment ) {

	  /* -------------------------------
	  CORE MODIFICATION ################
	  ------------------------------- */
      if( 'custom' == $attr['link']){
          $image = wp_get_attachment_image($id, $size, false);
          $attachment_meta = get_post_meta($id, '_rt-image-link', true);
          if($attachment_meta){
    	      $link = "<a href='$attachment_meta'>$image</a>";
	      }
	  } else {
          $link = isset($attr['link']) && 'file' == $attr['link'] ? wp_get_attachment_link($id, $size, false, false) : wp_get_attachment_link($id, $size, true, false);
      }

          $output .= "<{$itemtag} class='gallery-item'>";
        		$output .= "
        			<{$icontag} class='gallery-icon'>
        				$link
        			</{$icontag}>";
        		if ( $captiontag && trim($attachment->post_excerpt) ) {
        			$output .= "
        				<{$captiontag} class='wp-caption-text gallery-caption'>
        				" . wptexturize($attachment->post_excerpt) . "
        				</{$captiontag}>";
        		}
        		$output .= "</{$itemtag}>";
        		if ( $columns > 0 && ++$i % $columns == 0 )
        			$output .= '<br style="clear: both" />';

	  }
	 
	  $output .= "<br style='clear: both;' /></div>\n";
	 
	  return $output;
	}

	public function rt_image_attachment_fields_to_edit($form_fields, $post){
		$form_fields["rt-image-link"] = array(
	    "label" => __("Custom Link"),
	    "input" => "text", // default
	    "value" => get_post_meta($post->ID, "_rt-image-link", true),
	    "helps" => __("http://"),
	  );
	  return $form_fields;
	}

	public function rt_image_attachment_fields_to_save($post, $attachment) {
	  if( isset($attachment['rt-image-link']) ){
	    update_post_meta($post['ID'], '_rt-image-link', $attachment['rt-image-link']);
	  }
	  return $post;
	}
}

$galleryCustomLink = new GalleryCustomLink();
