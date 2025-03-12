<?php
if ( !function_exists( 'get_the_labels' ) ) {
	function get_the_labels($before = null, $after = ', ', $forced = false) {
		global $post;
		$labels = get_the_terms( $post->ID, 'label' );
		$label_list = null;
		if ( $labels && !is_wp_error( $labels ) ) {
			$label_out = array();
			foreach ( $labels as $label ) {
				$label_out[] = sprintf( '<a href="%s">%s</a>',
					home_url() . '/?label=' . $label->slug,
					$label->name);
			}
			$count = 0;
			foreach ( $label_out as $out ) {
				$label_list .= $before . $out;
				$count++;
				if ( ( count($label_out) > 1 ) && ( $after == ', ' ) && ( count($label_out) != $count ) || $forced ) {
					$label_list .= $after;
				}
			}
		}
		if ( $label_list )
			return wp_kses_post($label_list);
	}
}