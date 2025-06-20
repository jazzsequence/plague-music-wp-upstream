<?php
/**
 * Plugin Name: FAIR Default Gravatar
 * Description: Sets Gravatar as the default avatar source for the FAIR plugin.
 * Author: Chris Reynolds
 * Version: 1.0
 */

add_action( 'admin_init', function() {
    // Only run in the admin area.
    if ( ! is_admin() ) {
        return;
    }

    $settings = get_option( 'fair_settings', [] );

    // If not set or not already gravatar, set to gravatar.
    if ( ! isset( $settings['avatar_source'] ) || $settings['avatar_source'] !== 'gravatar' ) {
        $settings['avatar_source'] = 'gravatar';
        update_option( 'fair_settings', $settings, false );
    }
});
