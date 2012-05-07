<?php
/*
Plugin Name: Simple Top Commenters
Plugin URI: 
Description: Displays a list of top commenters across a site, showing the number of comments for each.
Text Domain: simpleTopCommenters
Domain Path: /languages
Version: 1.2
Author: Mike Eng
Author URI: http://mike-eng.com
License: GPL2
*/

/* Load the translation */
$plugin_dir = basename(dirname(__FILE__)).'/languages';
load_plugin_textdomain( 'simpleTopCommenters', false, $plugin_dir );

/**
 * Add function to widgets_init that'll load our widget.
 * @since 0.1
 */
add_action( 'widgets_init', 'SimpleTopCommentersInit' );

/**
 * Register our widget.
 * 'SimpleTopCommenters' is the widget class used below.
 *
 * @since 0.1
 */
function SimpleTopCommentersInit() {
	register_widget( 'SimpleTopCommenters' );
}

/**
 * SimpleTopCommenters class.
 * This class handles everything that needs to be handled with the widget:
 * the settings, form, display, and update.  Nice!
 *
 * @since 0.1
 */
class SimpleTopCommenters extends WP_Widget {

	/**
	 * Widget setup.
	 */
	function SimpleTopCommenters() {
		/* Widget settings. */
		$widget_ops = array( 'classname' => 'example', 'description' => __('A list of top commenters on your site', 'simpleTopCommenters') );

		/* Widget control settings. */
		$control_ops = array( 'width' => 300, 'height' => 350, 'id_base' => 'simple-top-commenters' );

		/* Create the widget. */
		$this->WP_Widget( 'simple-top-commenters', __('SimpleTopCommenters', 'simpleTopCommenters'), $widget_ops, $control_ops );
	}

	/**
	 * How to display the widget on the screen.
	 */
	function widget( $args, $instance ) {
		extract( $args );

		/* Our variables from the widget settings. */
		$title = apply_filters('widget_title', $instance['title'] );
		$name = $instance['excludeCommenters'];
		$identifier = $instance['identifier'];
		$limit = $instance['limit'];
		$showCommentsLabel = $instance['show_comments_label'];

		/* Before widget (defined by themes). */
		echo $before_widget;

		/* Display the widget title if one was input (before and after defined by themes). */
		if ( $title )
			echo $before_title . $title . $after_title;

		/* Process variables */
		if ($instance['excludeCommenters'] != ""){
			$excludedCommenters = trim($instance['excludeCommenters']);
			$excludedCommenters = explode(",", $excludedCommenters);
		}
		else{
			$excludedCommenters = array('');
		}
		
		$excludedEmailQuery = '';
		for ($l=0; $l<count($excludedCommenters); $l++){
			$excludedEmailQuery .= " AND comment_author_email != '".trim($excludedCommenters[$l])."' \r";
		}
		
		$excludedNameQuery = '';
		for ($m=0; $m<count($excludedCommenters); $m++){
			$excludedNameQuery .= " AND comment_author != '".trim($excludedCommenters[$m])."' \r";
		}
		
		if ($identifier == 'name'){
			$groupByQuery = 'GROUP BY comment_author';
		}
		else{
			$groupByQuery = 'GROUP BY comment_author_email';
		}
		
		settype($limit, 'int');
		
		if (($limit > 0) && (is_int($limit) == true)){
			$limitQuery = 'LIMIT '.$limit;
		}
		else{
			$limitQuery = '';
		}
		//}
		
		/* MySQL query */
		global $wpdb;
		$commenters = $wpdb->get_results("
			SELECT count(*) as qty, comment_author_email, comment_author
			FROM $wpdb->comments
			WHERE comment_type != 'pingback'
			$excludedEmailQuery
			$excludedNameQuery
			AND comment_approved = '1'
			$groupByQuery
			ORDER BY qty DESC
			$limitQuery
		");
		
		/* Display list */
		?>
		<!-- opening ul tag to contain the list -->
		<ul>
		<?php
			if(is_array($commenters)) {
				
				//only shows "comments" if "show comments label" is set to true
				if($showCommentsLabel == true){
					foreach ($commenters as $k) {
						echo ('<li>'.$k->comment_author.': '.$k->qty.' '._n('comment', 'comments', $k->qty, 'simpleTopCommenters').'</li>');
					} //end for loop
				} // end if showCommentsLabel == on
				
				//if "show comments label" is set to false, does not show "comments"
				else{
					foreach ($commenters as $w) {
						echo ('<li>'.$w->comment_author.': '.$w->qty);
					} //end for loop
				}
				
				
			} //end if is array
		?>
		<!-- closing ul tag to contain the list -->
		</ul>
		<?php
		
		/* After widget (defined by themes). */
		echo $after_widget;
	}

	/**
	 * Update the widget settings.
	 */
	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;

		/* Strip tags for title and name to remove HTML (important for text inputs). */
		$instance['title'] = strip_tags( $new_instance['title'] );
		$instance['excludeCommenters'] = strip_tags( $new_instance['excludeCommenters'] );
		$instance['limit'] = strip_tags( $new_instance['limit'] );

		/* No need to strip tags for these inputs */
		$instance['identifier'] = $new_instance['identifier'];
		$instance['show_comments_label'] = isset($new_instance['show_comments_label']);

		return $instance;
	}

	/**
	 * Displays the widget settings controls on the widget panel.
	 * Make use of the get_field_id() and get_field_name() function
	 * when creating your form elements. This handles the confusing stuff.
	 */
	function form( $instance ) {

		/* Set up some default widget settings. */
		$defaults = array( 'title' => __('Top Commenters', 'simpleTopCommenters'), 'excludeCommenters' => __('janez, janez@guest.arnes.si', 'simpleTopCommenters'), 'identifier' => 'email', 'limit' => 5, 'show_comments_label' => true );
		$instance = wp_parse_args( (array) $instance, $defaults ); ?>

		<!-- Widget Title: Text Input -->
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e('Title:', 'simpleTopCommenters'); ?></label>
			</br>
			<input id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" value="<?php echo $instance['title']; ?>" size="40"/>
		</p>

		<!-- Define Commenters Select Box -->
		<p>
			<label for="<?php echo $this->get_field_id( 'identifier' ); ?>"><?php _e('Define Commenters by:', 'simpleTopCommenters'); ?></label>
			</br> 
			<select id="<?php echo $this->get_field_id( 'identifier' ); ?>" name="<?php echo $this->get_field_name( 'identifier' ); ?>">
				<option <?php if ( 'email' == $instance['identifier'] ) echo 'selected="selected"'; ?>><?php _e('email', 'simpleTopCommenters'); ?></option>
				<option <?php if ( 'name' == $instance['identifier'] ) echo 'selected="selected"'; ?>><?php _e('name', 'simpleTopCommenters'); ?></option>
			</select>
		</p>
		
		<!-- Your Name: Text Input -->
		<p>
			<label for="<?php echo $this->get_field_id( 'excludeCommenters' ); ?>"><?php _e('Commenters to Exclude: (separated by comma)', 'simpleTopCommenters'); ?></label>
			</br>
			<input id="<?php echo $this->get_field_id( 'excludeCommenters' ); ?>" name="<?php echo $this->get_field_name( 'excludeCommenters' ); ?>" value="<?php echo $instance['excludeCommenters']; ?>" size="40"/>
		</p>
		
		<!-- Limit -->
		<p>
			<label for="<?php echo $this->get_field_id( 'limit' ); ?>"><?php _e('# of Commenters to List: (leave blank to list all)', 'simpleTopCommenters'); ?></label>
			</br>
			<input id="<?php echo $this->get_field_id( 'limit' ); ?>" name="<?php echo $this->get_field_name( 'limit' ); ?>" value="<?php echo $instance['limit']; ?>" style="width:2em;" />
		</p>
		
		<!-- Show "comments label" Checkbox -->
		<p>
			<input class="checkbox" type="checkbox" <?php checked(isset( $instance['show_comments_label']) ? $instance['show_comments_label'] : 0 ); ?> id="<?php echo $this->get_field_id( 'show_comments_label' ); ?>" name="<?php echo $this->get_field_name( 'show_comments_label' ); ?>" /> 
			<label for="<?php echo $this->get_field_id( 'show_comments_label' ); ?>"><?php _e('Show "comments" Label?', 'simpleTopCommenters'); ?></label>
		</p>

	<?php
	}
}
?>