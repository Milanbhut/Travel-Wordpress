<?php
/**
 * Author system: real-photo avatars + editorial profile fields.
 *
 * @package Overlaytop
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Use a uploaded portrait (user meta 'overlaytop_avatar' = attachment ID) instead of Gravatar.
 */
function overlaytop_custom_avatar_url( $url, $id_or_email, $args ) {
	$user = false;
	if ( is_numeric( $id_or_email ) ) {
		$user = get_user_by( 'id', (int) $id_or_email );
	} elseif ( is_object( $id_or_email ) && ! empty( $id_or_email->user_id ) ) {
		$user = get_user_by( 'id', (int) $id_or_email->user_id );
	} elseif ( is_string( $id_or_email ) ) {
		$user = get_user_by( 'email', $id_or_email );
	}
	if ( $user ) {
		$att = get_user_meta( $user->ID, 'overlaytop_avatar', true );
		if ( $att ) {
			$size = isset( $args['size'] ) ? (int) $args['size'] : 96;
			$src  = wp_get_attachment_image_url( $att, $size > 150 ? 'medium' : 'thumbnail' );
			if ( $src ) {
				return $src;
			}
		}
	}
	return $url;
}
add_filter( 'get_avatar_url', 'overlaytop_custom_avatar_url', 10, 3 );

/**
 * Extra profile fields shown in wp-admin (role, socials, avatar attachment id).
 */
function overlaytop_user_profile_fields( $user ) {
	$fields = array(
		'overlaytop_role'      => __( 'Editorial role / title', 'overlaytop' ),
		'overlaytop_avatar'    => __( 'Avatar attachment ID', 'overlaytop' ),
		'overlaytop_twitter'   => __( 'Twitter/X URL', 'overlaytop' ),
		'overlaytop_instagram' => __( 'Instagram URL', 'overlaytop' ),
	);
	echo '<h2>' . esc_html__( 'Overlaytop author profile', 'overlaytop' ) . '</h2><table class="form-table">';
	foreach ( $fields as $key => $label ) {
		$val = esc_attr( get_user_meta( $user->ID, $key, true ) );
		echo '<tr><th><label for="' . esc_attr( $key ) . '">' . esc_html( $label ) . '</label></th>';
		echo '<td><input type="text" name="' . esc_attr( $key ) . '" id="' . esc_attr( $key ) . '" value="' . $val . '" class="regular-text"></td></tr>';
	}
	echo '</table>';
}
add_action( 'show_user_profile', 'overlaytop_user_profile_fields' );
add_action( 'edit_user_profile', 'overlaytop_user_profile_fields' );

function overlaytop_save_user_profile_fields( $user_id ) {
	if ( ! current_user_can( 'edit_user', $user_id ) ) {
		return;
	}
	foreach ( array( 'overlaytop_role', 'overlaytop_avatar', 'overlaytop_twitter', 'overlaytop_instagram' ) as $key ) {
		if ( isset( $_POST[ $key ] ) ) {
			update_user_meta( $user_id, $key, sanitize_text_field( wp_unslash( $_POST[ $key ] ) ) );
		}
	}
}
add_action( 'personal_options_update', 'overlaytop_save_user_profile_fields' );
add_action( 'edit_user_profile_update', 'overlaytop_save_user_profile_fields' );
