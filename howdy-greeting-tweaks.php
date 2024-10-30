<?php
/*
Plugin Name: Howdy Tweaks
Plugin URI: http://trepmal.com/plugins/howdy-tweaks/
Description: Tweaks to the Howdy greeting and Favorites menu
Author: Kailey Lampert
Version: 2.3
Author URI: http://kaileylampert.com/

Copyright (C) 2012  Kailey Lampert

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

load_plugin_textdomain( 'howdy-tweaks', false, dirname( plugin_basename( __FILE__ ) ) .  '/lang' );

$howdy_tweaks = new howdy_tweaks();

class howdy_tweaks {

	/**
	 * Get hooked in
	 *
	 * @return void
	 */
	function __construct() {
		add_action( 'admin_init',            array( $this, 'register' ) );
		add_action( 'admin_menu',            array( $this, 'menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'scripts' ) );
		add_action( 'admin_bar_menu',        array( $this, 'the_info_tweaks' ) );
		add_action( 'admin_bar_menu',        array( $this, 'the_favs_tweaks' ), 100 );
		add_action( 'admin_bar_menu',        array( $this, 'change_howdy' ) );
	}

	/**
	 * Register and save options
	 *
	 * @return void
	 */
	function register() {
		// intercept user favorites
		if ( isset( $_POST['ht_options_user'] ) ) {
			$input = $this->sanitize( $_POST['ht_options_user'] );
			update_user_meta( get_current_user_id(), 'ht_options', $input );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		register_setting( 'howdy-tweaks_options', 'ht_options', array ( $this, 'sanitize' ) );
		register_setting( 'howdy-tweaks_options', 'ht_greeting', 'esc_attr' );
	}

	/**
	 * Sanitize options for saving
	 *
	 * @param array $input Options array
	 * @return array
	 */
	function sanitize( $input ) {

		foreach ( $input as $k => $opts ) {
			$input[ $k ]['label'] = esc_attr( $opts['label'] );
			$input[ $k ]['favs']  = isset( $opts['favs'] ) ? 1 : '';
			$input[ $k ]['info']  = isset( $opts['info'] ) ? 1 : '';
			$input[ $k ]['url']   = empty( $opts['url'] ) ? '' : esc_url( $opts['url'] );
			if ( empty( $input[ $k ]['label'] ) ) {
				unset( $input[ $k ] );
			}
		}

		return $input;
	}

	/**
	 * Create menu page
	 * Set up corresponding help tab
	 *
	 * @return void
	 */
	function menu() {
		global $howdy_tweaks_page;
		$howdy_tweaks_page = add_options_page( __( 'Howdy Tweaks', 'howdy-tweaks' ),  __( 'Howdy Tweaks', 'howdy-tweaks' ), 'edit_posts', 'howdy-tweaks', array( $this, 'page' ) );
		add_action( "load-$howdy_tweaks_page", array( $this, 'help_tab' ) );
	}

	/**
	 * Enqueue scripts
	 *
	 * @param string $hook Page hook
	 * @return void
	 */
	function scripts( $hook ) {
		if ( $hook != 'settings_page_howdy-tweaks' ) {
			return;
		}
		wp_enqueue_script( 'jquery' );
		wp_enqueue_script( 'jquery-ui' );
		wp_enqueue_script( 'jquery-ui-sortable' );
		wp_enqueue_script( 'howdy-tweaks', plugins_url( 'howdy.js', __FILE__ ), array( 'jquery-ui-sortable' ) );
	}

	/**
	 * Output page
	 *
	 * @return void
	 */
	function page() {

		echo '<div class="wrap">';
		echo '<h2>' . __( 'Howdy Tweaks', 'howdy-tweaks' ) . '</h2>';

		echo '<form method="post" action="' . ( current_user_can( 'manage_options' ) ? 'options.php' : '' ) . '">';
		settings_fields( 'howdy-tweaks_options' );

		if ( current_user_can( 'manage_options' ) ) {
			$greeting = get_option( 'ht_greeting', 'Howdy,' );

			echo '<p><label for="greeting">' . __( 'Greeting', 'howdy-tweaks' ) . ': <input type="text" name="ht_greeting" id="greeting" value="' . $greeting . '" size="50" /></label></p>';
			echo '<p>' . sprintf( __( 'Available placeholders: %1$s', 'howdy-tweaks' ) , '<code>%name%</code>' ) . '<br />' .
			__( 'If not specified, %name% will be added to the end. ', 'howdy-tweaks' ) . '</p>';
		}

		$this->options_table( 'ht_options' );
		$this->options_table( 'ht_options_user' );

		submit_button( __( 'Save', 'howdy-tweaks' ), 'primary' );
		echo '</form>';

		echo '</div>';

	} // end page()

	/**
	 * Print html tables for options fields
	 *
	 * @param string $name Which set of options to print
	 * @return void
	 */
	function options_table( $name ) {

		$heading = '';
		if ( 'ht_options' == $name ) {
			if ( ! current_user_can( 'manage_options' ) ) return;
			$values = get_option( $name, array() );
			$heading = '<h3>Global Options</h3>';
		}
		if ( 'ht_options_user' == $name ) {
			$values = get_user_meta( get_current_user_id(), 'ht_options', true );
			$values = empty( $values ) ? array() : $values;
			$heading = '<h3>User Options</h3>';
		}

		echo '<div class="ht_options_group">';
		echo $heading;
		$garbage = uniqid();
		echo "<input type='hidden' class='ht_garbage' value='{$garbage}' />";

		//echo '<pre>' . print_r( $values, true ) . '</pre>';

		?>
		<table class="widefat ht_table">
		<thead>
			<tr>
				<th><?php _e( 'Label', 'howdy-tweaks' ); ?></th>
				<th><?php _e( 'Favorites', 'howdy-tweaks' ); ?></th>
				<th><?php _e( 'Info Links', 'howdy-tweaks' ); ?></th>
				<th><?php _e( 'Link', 'howdy-tweaks' ); ?></th>
			</tr>
		</thead>
		<tfoot>
			<tr>
				<th><?php _e( 'Label', 'howdy-tweaks' ); ?></th>
				<th><?php _e( 'Favorites', 'howdy-tweaks' ); ?></th>
				<th><?php _e( 'Info Links', 'howdy-tweaks' ); ?></th>
				<th><?php _e( 'Link', 'howdy-tweaks' ); ?></th>
			</tr>
		</tfoot>
		<tbody>
			<?php foreach ( $values as $id => $opt ) { ?>
			<tr>
				<td><input type="text"     name="<?php echo $name; ?>[<?php echo $id; ?>][label]" value="<?php    echo $opt['label']; ?>" size="40" /></td>
				<td><input type="checkbox" name="<?php echo $name; ?>[<?php echo $id; ?>][favs]"  value="1" <?php checked( $opt['favs'] ); ?> /></td>
				<td><input type="checkbox" name="<?php echo $name; ?>[<?php echo $id; ?>][info]"  value="1" <?php checked( $opt['info'] ); ?> /></td>
				<td><input type="text"     name="<?php echo $name; ?>[<?php echo $id; ?>][url]"   value="<?php    echo $opt['url']; ?>" size="40" /></td>
			</tr>
			<?php } ?>
			<tr class="ht_new_row">
				<td><input type="text"     name="<?php echo $name; ?>[<?php echo $garbage; ?>][label]" value="" size="40" /></td>
				<td><input type="checkbox" name="<?php echo $name; ?>[<?php echo $garbage; ?>][favs]"  value="" /></td>
				<td><input type="checkbox" name="<?php echo $name; ?>[<?php echo $garbage; ?>][info]"  value="" /></td>
				<td><input type="text"     name="<?php echo $name; ?>[<?php echo $garbage; ?>][url]"   value="" size="40" /></td>
			</tr>
		</tbody>
		</table>
		<?php

		echo '<p><a href="#" class="ht_add_new">' . __( 'Add another row', 'howdy-tweaks' ) . '</a></p>';
		echo '</div>';
	}

	/**
	 * Set up help tab
	 *
	 * @return void
	 */
	function help_tab() {
		global $howdy_tweaks_page;
		$screen = get_current_screen();
		if ( $screen->id != $howdy_tweaks_page ) {
			return;
		}

		$screen->add_help_tab( array(
			'id'      => 'howdy-tweaks',
			'title'   => __( 'Howdy Tweaks', 'howdy-tweaks' ),
			'content' => $this->help_text(),
		) );
	}

	/**
	 * Return help tab content
	 *
	 * @return string
	 */
	function help_text() {
		$help = '';
		$help .= '<p>' . __( 'Items checked "Favorites" will appear in a new Favorites menu on the right side of the Toolbar.', 'howdy-tweaks' ) . ' ';
		$help .= __( 'If there are no "favorites," the menu will not be created.', 'howdy-tweaks' ) . '</p>';
		$help .= '<p>' . __( "You can drag-n-drop each row so the the items will appear in the order you'd prefer", 'howdy-tweaks' ) . '</p>';
		$help .= '<p>' . __( 'Click "Add another row" to quickly add more items.', 'howdy-tweaks' ) . '</p>';
		$help .= '<p>' . __( 'To remove an item, simply delete its label.', 'howdy-tweaks' ) . '</p>';
		$help .= '<p>' . sprintf( __( "If you are using WordPress Multisite, you can use %s as a placeholder for the current site's ID.", 'howdy-tweaks' ), '<code>%ID%</code>' ) . '</p>';
		return $help;
	}

	/**
	 * Insert custom 'user info' links
	 *
	 * @param WP_Admin_Bar $wp_admin_bar
	 * @return void
	 */
	function the_info_tweaks( $wp_admin_bar ) {

		global $blog_id;
		$opts = get_option( 'ht_options', array() );
		$opts = array_merge( $opts, (array) get_user_meta( get_current_user_id(), 'ht_options', true ) );
		$opts = array_filter( $opts ); //clean up empties

		foreach ( $opts as $k => $vals ) {
			if ( $vals['info'] ) {

				$label = str_replace( '%ID%', $blog_id, $vals['label'] );
				$node = array (
					'parent' => 'my-account',
					'id'     => 'ht-info-' . sanitize_title( $label ),
					'title'  => $label,
					'href'   => $vals['url']
				);

				$wp_admin_bar->add_menu( $node );

			}
		}

	}

	/**
	 * Insert custom 'favorite' links
	 *
	 * @param WP_Admin_Bar $wp_admin_bar
	 * @return void
	 */
	function the_favs_tweaks( $wp_admin_bar ) {
		global $blog_id;
		$opts = get_option( 'ht_options', array() );
		$opts = array_merge( $opts, (array) get_user_meta( get_current_user_id(), 'ht_options', true ) );
		$opts = array_filter( $opts ); //clean up empties

		$wp_admin_bar->add_menu( array(
			'id'     => 'favorites',
			'parent' => 'top-secondary',
			'title'  => __( 'Favorites', 'howdy-tweaks' ),
			'meta'   => array (
				'class' => 'opposite'
			)
		) );
		$i = 0;
		foreach ( $opts as $k => $vals ) {
			if ( $vals['favs'] != 0 ) {
				$label = str_replace( '%ID%', $blog_id, $vals['label'] );
				$node = array (
					'parent' => 'favorites',
					'id'     => 'ht-fave-' . sanitize_title( $label ),
					'title'  => $label,
					'href'   => $vals['url']
				);

				$wp_admin_bar->add_menu( $node );
				++$i;
			}
		}
		//if there are no 'favorites' items, remove the menu
		if ( $i < 1 ) {
			$wp_admin_bar->remove_menu( 'favorites' );
		}

	}

	/**
	 * Change the "howdy"
	 *
	 * @param WP_Admin_Bar $wp_admin_bar
	 * @return void
	 */
	function change_howdy( $wp_admin_bar ) {

		$greeting = get_option( 'ht_greeting', 'Howdy,' );

		//get the node that contains "howdy"
		$my_account = $wp_admin_bar->get_node( 'my-account' );
		//change the "howdy"

		// fetch user data
		// $user_id      = get_current_user_id();
		$current_user = wp_get_current_user();
		$avatar       = get_avatar( $current_user->ID, 16 );

		if ( strpos( $greeting, '%name%' ) !== false ) {
			$howdy = str_replace( '%name%', $current_user->display_name, $greeting );
		} else {
			$howdy = $greeting . ' ' . $current_user->display_name;
		}

		$my_account->title = $howdy . $avatar;
		//remove the original node
		$wp_admin_bar->remove_node( 'my-account' );
		//add back our modified version
		$wp_admin_bar->add_node( $my_account );

	}

} //end class howdy-tweaks
