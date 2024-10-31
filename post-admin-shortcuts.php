<?php
/*
Plugin Name: Post Admin Shortcuts
Plugin URI: http://themergency.com
Description: Add shortcuts to easily edit your posts. Watch a screen cast of it in action : http://screenr.com/hZu
Version: 1.0
Author: Brad Vincent
Author URI: http://themergency.com
License: GPL2
*/

class PostAdminShortcuts {
	
	var $pluginname = "post_admin_shortcuts";
	
	function PostAdminShortcuts(){$this->__construct();}
	
	function __construct() { 
		define($this->pluginname.'_ABSPATH', WP_PLUGIN_DIR.'/'.plugin_basename( dirname(__FILE__) ).'/' );
		define($this->pluginname.'_URLPATH', WP_PLUGIN_URL.'/'.plugin_basename( dirname(__FILE__) ).'/' );

		if (is_admin()) {
			// Dashboard stuff
			add_action("wp_dashboard_setup", array(&$this, "setup_dashboard_widget") );
			
			// Init JS Scripts
			add_action('admin_init', array(&$this, "init_scripts"));
			
			// Create pinned items in the admin menu 
			add_action('admin_menu', array(&$this, "admin_menu"));

			// register for posts
			add_filter( 'manage_posts_columns', array(&$this, "add_shortcut_column") );
			add_action( 'manage_posts_custom_column', array(&$this, "add_shortcut_column_value"), 10, 2 ); 

			// register for pages
			add_filter( 'manage_pages_columns', array(&$this, "add_shortcut_column") );
			add_action( 'manage_pages_custom_column', array(&$this, "add_shortcut_column_value"), 10, 2 ); 
			
			//register for all custom post types
			$post_types = get_post_types( array( 'public' => true, '_builtin' => false ) , 'names', 'and');
			foreach ($post_types  as $post_type ) {
				add_filter( "manage_edit-$post_type_columns", array(&$this, "add_shortcut_column") );
				add_action( "manage_edit-$post_type_custom_column" , array(&$this, "add_shortcut_column_value"), 10, 2 ); 
			}		
			
			//add CSS
			add_action('admin_print_styles', array(&$this, "add_css") );
			
			//add JS
			add_action('admin_print_scripts',  array(&$this, "add_scripts") );
			
			add_action('wp_ajax_toggle_shortcut', array(&$this, "toggle_shortcut_callback") );
		}
	}
	
	function setup_dashboard_widget() {
		// Globalize the metaboxes array, this holds all the widgets for wp-admin
		global $wp_meta_boxes;
		
		//if admin - show more simplified right now widget
		if ( current_user_can('edit_posts') ) {
			wp_add_dashboard_widget('dashboard_pinned_posts', 'Pinned Posts', array(&$this, 'dashboard_pinned_posts_output'));
		}
	}
	
	function dashboard_pinned_posts_output() {

		echo '<div class="table table_content">'; 
		echo '<table width="100%">';
		
		$image = post_admin_shortcuts_URLPATH . 'pin_bullet.png';
		
		//get me all pinned posts
		$pinned_posts = get_posts('meta_key=_pinned&meta_value=true&post_type=any&post_status=any&numberposts=-1&orderby=type');
		
		if (count($pinned_posts) > 0) {
		
			//get all public post types
			$post_types = get_post_types( array( 'public'   => true) , 'objects');
			
			//create a lookup array
			$post_types_lookup = array();
			foreach ($post_types  as $post_type ) {
				$post_types_lookup[$post_type->name] = $post_type->label;
			}
			
			echo '<tr class="first"><th align="left">Title</th><th width="10%" align="left">Type</th></tr>'; 
		
			//add each post to the admin menu
			foreach($pinned_posts as $post) {
				$url = "post.php?post=$post->ID&action=edit";
				$post_type = $post_types_lookup[$post->post_type];
				echo "<tr><td style='padding:5px 0 !important'><a href='$url'><img id='pin_$post->ID' align='center' src='$image' />&nbsp;$post->post_title</a></td><td style='padding:5px 0 !important'>$post_type</td></tr>";
			}
		
		} else {
			echo "<tr><td>There are no pinned posts</td></tr>";
		}
		
		echo "</table></div>";
	}
	
	function admin_menu() {
		global $submenu;
		
		$image = post_admin_shortcuts_URLPATH . 'pin_bullet.png';
		
		//get me all pinned posts
		$pinned_posts = get_posts('meta_key=_pinned&meta_value=true&post_type=any&post_status=any&numberposts=-1');
		
		//add each post to the admin menu
		foreach($pinned_posts as $post) {
			$url = "post.php?post=$post->ID&action=edit";
			$post_type = $post->post_type;
			$handle = 'edit.php';
			if ($post_type != 'post') $handle = "edit.php?post_type=$post_type";
			add_submenu_page($handle, $post->post_title, "<img id='pin_$post->ID' align='center' src='$image' />&nbsp;$post->post_title", 'manage_options', $url);
		}
	}

	function toggle_shortcut_callback() {
		global $wpdb; // this is how you get access to the database

		$post_id = $_POST['post_id'];
		$pinned = $_POST['pinned'];
		
		if ($pinned == 'true') {
			delete_post_meta($post_id, '_pinned');
			echo 'UNPIN';
		} else {
			add_post_meta($post_id, '_pinned', 'true');
			
			$image = post_admin_shortcuts_URLPATH . 'pin_bullet.png';
			$url = admin_url("/post.php?post=$post_id&action=edit");
			$pinned_post = get_post($post_id); 
			
			//return the HTML of the menu item so that updates happen in 'realtime'
			echo "<li><a href='$url'><img id='pin_$post_id' align='center' src='$image' />&nbsp;$pinned_post->post_title</a></li>";
		}

		die();
	}	
	
	function add_css() {
		if ((basename($_SERVER['SCRIPT_FILENAME']))=='edit.php') {
			wp_register_style(
				$handle = $this->pluginname.'-css', 
				$src = post_admin_shortcuts_URLPATH . $this->pluginname . '.css', 
				$deps = array(), $ver = '1.0.0', $media = 'all');
				
			wp_enqueue_style($this->pluginname.'-css');
		}
	}
	
	function init_scripts() {
		wp_register_script(
			$handle = 'post_admin_shortcuts-js', 
			$src = post_admin_shortcuts_URLPATH . 'post_admin_shortcuts.js', 
			$deps = array("jquery", "wp-ajax-response") , $ver = '1.0.0');
	}
	
	function add_scripts() {
		if ((basename($_SERVER['SCRIPT_FILENAME']))=='edit.php') {
			wp_enqueue_script('post_admin_shortcuts-js');
		}
	}
	
	function add_shortcut_column($cols) {
		$image = post_admin_shortcuts_URLPATH . 'pin_grey.png';
	
		$newcols['shortcut'] = "<img title='pin/unpin post' src='$image' />";
		
		return array_slice($cols, 0, 1) + $newcols + array_slice($cols, 1);

		return $cols;
	}
 
	function add_shortcut_column_value($column_name, $post_id) {
		if ($column_name == 'shortcut') {
			$is_pinned = get_post_meta( $post_id, '_pinned', true );
			$post_type = get_post_type( $post_id );
			if ($is_pinned) {
				echo "<a title='unpin this post' href='#unpin' post:id='$post_id' post:type='$post_type' class='pin_link pinned'></a>";	
			} else {
				echo "<a title='pin this post' href='#pin' post:id='$post_id' post:type='$post_type' class='pin_link'></a>";
			}
		}
	}
}

add_action("init", create_function('', 'global $PostAdminShortcuts; $PostAdminShortcuts = new PostAdminShortcuts();'));

?>