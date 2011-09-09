<?php 
/**
 * Custom Meta Box Class
 *
 * The Meta Box Class is used by including it in your plugin files and using its methods to 
 * create custom meta boxes for custom post types. It is meant to be very simple and 
 * straightforward. For name spacing purposes, All Types metabox ( meaning you can do anything with it )
 * is used. 
 *
 * This class is derived from Meta Box script by Rilwis<rilwis@gmail.com> version 3.2. which later was forked 
 * by Cory Crowley (email: cory.ivan@gmail.com) The purpose of this class is not to rewrite the script but to 
 * modify and change small things and adding a few field types that i needed to my personal preference. 
 * The original author did a great job in writing this class, so all props goes to him.
 * 
 * @version 0.1.0
 * @copyright 2011 
 * @author Ohad Raz (email: admin@bainternet.info)
 * @link http://en.bainternet.info
 * 
 * @license GNU General Public LIcense v3.0 - license.txt
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NON-INFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 * @package All Types Meta Box
 */

if ( ! class_exists( 'AT_Meta_Box') ) :

/**
 * All Types Meta Box class.
 *
 * @package All Types Meta Box
 * @since 1.0
 *
 * @todo Nothing.
 */
class AT_Meta_Box {
	
	/**
	 * Holds meta box object
	 *
	 * @var object
	 * @access protected
	 */
	protected $_meta_box;
	
	/**
	 * Holds meta box fields.
	 *
	 * @var array
	 * @access protected
	 */
	protected $_prefix;
	
	/**
	 * Holds Prefix for meta box fields.
	 *
	 * @var array
	 * @access protected
	 */
	protected $_fields;
	
	/**
	 * Constructor
	 *
	 * @since 1.0
	 * @access public
	 *
	 * @param array $meta_box 
	 */
	public function __construct ( $meta_box ) {
		
		// If we are not in admin area exit.
		if ( ! is_admin() )
			return;
			
		// Assign meta box values to local variables and add it's missed values.
		$this->_meta_box = $meta_box;
		$this->_prefix = (isset($meta_box['prefix'])) ? $meta_box['prefix'] : ''; 
		$this->_fields = &$this->_meta_box['fields'];
		$this->add_missed_values();
		
		// Add Actions
		add_action( 'add_meta_boxes', array( &$this, 'add' ) );
		//add_action( 'wp_insert_post', array( &$this, 'save' ) );
		add_action( 'save_post', array( &$this, 'save' ) );
		
		// Check for special fields and add needed actions for them.
		$this->check_field_upload();
		$this->check_field_color();
		$this->check_field_date();
		$this->check_field_time();
		
		// Load common js, css files
		// Must enqueue for all pages as we need js for the media upload, too.
		add_action( 'admin_print_styles', array( &$this, 'load_scripts_styles' ) );
		
	}
	
	/**
	 * Load all Javascript and CSS
	 *
	 * @since 1.0
	 * @access public
	 */
	public function load_scripts_styles() {
		
		// Get Plugin Path
		$plugin_path = plugins_url( 'meta-box-class', plugin_basename( dirname( __FILE__ ) ) );
		
		// Enqueue Meta Box Style
		wp_enqueue_style( 'at-meta-box', $plugin_path . '/css/meta-box.css' );
		
		// Enqueue Meta Box Scripts
		wp_enqueue_script( 'at-meta-box', $plugin_path . '/js/meta-box.js', array( 'jquery' ), null, true );
		
	}
	
	/**
	 * Check the Field Upload, Add needed Actions
	 *
	 * @since 1.0
	 * @access public
	 */
	public function check_field_upload() {
		
		// Check if the field is an image or file. If not, return.
		if ( ! $this->has_field( 'image' ) && ! $this->has_field( 'file' ) )
			return;
		
		// Add data encoding type for file uploading.	
		add_action( 'post_edit_form_tag', array( &$this, 'add_enctype' ) );
		
		// Make upload feature work event when custom post type doesn't support 'editor'
		wp_enqueue_script( 'media-upload' );
		add_thickbox();
		wp_enqueue_script( 'jquery-ui-core' );
		wp_enqueue_script( 'jquery-ui-sortable' );
		
		// Add filters for media upload.
		add_filter( 'media_upload_gallery', array( &$this, 'insert_images' ) );
		add_filter( 'media_upload_library', array( &$this, 'insert_images' ) );
		add_filter( 'media_upload_image', 	array( &$this, 'insert_images' ) );
			
		// Delete all attachments when delete custom post type.
		add_action( 'wp_ajax_at_delete_file', 		array( &$this, 'delete_file' ) );
		add_action( 'wp_ajax_at_reorder_images', 	array( &$this, 'reorder_images' ) );
		
	}
	
	/**
	 * Add data encoding type for file uploading
	 *
	 * @since 1.0
	 * @access public
	 */
	public function add_enctype () {
		echo ' enctype="multipart/form-data"';
	}
	
	/**
	 * Process images added to meta field.
	 *
	 * Modified from Faster Image Insert plugin.
	 *
	 * @return void
	 * @author Cory Crowley
	 */
	public function insert_images() {
		
		// If post variables are empty, return.
		if ( ! isset( $_POST['at-insert'] ) || empty( $_POST['attachments'] ) )
			return;
		
		// Security Check
		check_admin_referer( 'media-form' );
		
		// Create Security Nonce
		$nonce = wp_create_nonce( 'at_ajax_delete' );
		
		// Get Post Id and Field Id
		$post_id = $_POST['post_id'];
		$id = $_POST['field_id'];
		
		// Modify the insertion string
		$html = '';
		foreach( $_POST['attachments'] as $attachment_id => $attachment ) {
			
			// Strip Slashes
			$attachment = stripslashes_deep( $attachment );
			
			// If not selected or url is empty, continue in loop.
			if ( empty( $attachment['selected'] ) || empty( $attachment['url'] ) )
				continue;
				
			$li 	 = "<li id='item_{$attachment_id}'>";
			$li 	.= "<img src='{$attachment['url']}' alt='image_{$attachment_id}' />";
			//$li 	.= "<a title='" . __( 'Delete this image' ) . "' class='at-delete-file' href='#' rel='{$nonce}|{$post_id}|{$id}|{$attachment_id}'>" . __( 'Delete' ) . "</a>";
			$li 	.= "<a title='" . __( 'Delete this image' ) . "' class='at-delete-file' href='#' rel='{$nonce}|{$post_id}|{$id}|{$attachment_id}'><img src='" . plugins_url( 'meta-box-class/images/delete-16.png' , dirname( __FILE__ ) ) . "' alt='" . __( 'Delete' ) . "' /></a>";
			$li 	.= "<input type='hidden' name='{$id}[]' value='{$attachment_id}' />";
			$li 	.= "</li>";
			$html .= $li;
			
		} // End For Each
		
		return media_send_to_editor( $html );
		
	}
	
	/**
	 * Delete attachments associated with the post.
	 *
	 * @since 1.0
	 * @access public
	 *
	 * @param string $post_id 
	 */
	public function delete_attachments( $post_id ) {
		
		// Get Attachments
		$attachments = get_posts( array( 'numberposts' => -1, 'post_type' => 'attachment', 'post_parent' => $post_id ) );
		
		// Loop through attachments, if not empty, delete it.
		if ( ! empty( $attachments ) ) {
			foreach ( $attachments as $att ) {
				wp_delete_attachment( $att->ID );
			}
		}
		
	}
	
	/**
	 * Ajax callback for deleting files.
	 * 
	 * Modified from a function used by "Verve Meta Boxes" plugin ( http://goo.gl/aw64H )
	 *
	 * @since 1.0
	 * @access public 
	 */
	public function delete_file() {
		
		// If data is not set, die.
		if ( ! isset( $_POST['data'] ) )
			die();
			
		list($nonce, $post_id, $key, $attach_id) = explode('|', $_POST['data']);
		
		if ( ! wp_verify_nonce( $nonce, 'at_ajax_delete' ) )
			die( '1' );
			
		delete_post_meta( $post_id, $key, $attach_id );
		
		die( '0' );
	
	}
	
	/**
	 * Ajax callback for reordering Images.
	 *
	 * @since 1.0
	 * @access public
	 */
	public function reorder_images() {
		
		if ( ! isset( $_POST['data'] ) )
			die();
			
		list( $order, $post_id, $key, $nonce ) = explode( '|', $_POST['data'] );
		
		if ( ! wp_verify_nonce( $nonce, 'at_ajax_reorder' ) )
			die( '1' );
			
		parse_str( $order, $items );
		$items = $items['item'];
		$order = 1;
		foreach ( $items as $item ) {
			wp_update_post( array( 'ID' => $item, 'post_parent' => $post_id, 'menu_order' => $order ) );
			$order++;
		}
		
		die( '0' );
	
	}
	
	/**
	 * Check Field Color
	 *
	 * @since 1.0
	 * @access public
	 */
	public function check_field_color() {
		
		if ( $this->has_field( 'color' ) && $this->is_edit_page() ) {
			// Enqueu built-in script and style for color picker.
			wp_enqueue_style( 'farbtastic' );
			wp_enqueue_script( 'farbtastic' );
		}
		
	}
	
	/**
	 * Check Field Date
	 *
	 * @since 1.0
	 * @access public 
	 */
	public function check_field_date() {
		
		if ( $this->has_field( 'date' ) && $this->is_edit_page() ) {
			// Enqueu JQuery UI, use proper version.
			wp_enqueue_style( 'at-jquery-ui-css', 'http://ajax.googleapis.com/ajax/libs/jqueryui/' . $this->get_jqueryui_ver() . '/themes/base/jquery-ui.css' );
			wp_enqueue_script( 'at-jquery-ui', 'https://ajax.googleapis.com/ajax/libs/jqueryui/' . $this->get_jqueryui_ver() . '/jquery-ui.min.js', array( 'jquery' ) );
		}
		
	}
	
	/**
	 * Check Field Time
	 *
	 * @since 1.0
	 * @access public
	 */
	public function check_field_time() {
		
		if ( $this->has_field( 'time' ) && $this->is_edit_page() ) {
			
			// Enqueu JQuery UI, use proper version.
			wp_enqueue_style( 'at-jquery-ui-css', 'http://ajax.googleapis.com/ajax/libs/jqueryui/' . $this->get_jqueryui_ver() . '/themes/base/jquery-ui.css' );
			wp_enqueue_script( 'at-jquery-ui', 'https://ajax.googleapis.com/ajax/libs/jqueryui/' . $this->get_jqueryui_ver() . '/jquery-ui.min.js', array( 'jquery' ) );
			wp_enqueue_script( 'at-timepicker', 'https://github.com/trentrichardson/jQuery-Timepicker-Addon/raw/master/jquery-ui-timepicker-addon.js', array( 'at-jquery-ui' ) );
		
		}
		
	}
	
	/**
	 * Add Meta Box for multiple post types.
	 *
	 * @since 1.0
	 * @access public
	 */
	public function add() {
		
		// Loop through array
		foreach ( $this->_meta_box['pages'] as $page ) {
			add_meta_box( $this->_meta_box['id'], $this->_meta_box['title'], array( &$this, 'show' ), $page, $this->_meta_box['context'], $this->_meta_box['priority'] );
		}
		
	}
	
	/**
	 * Callback function to show fields in meta box.
	 *
	 * @since 1.0
	 * @access public 
	 */
	public function show() {
		
		global $post;
		var_dump($this->_fields);
		wp_nonce_field( basename(__FILE__), 'at_meta_box_nonce' );
		echo '<table class="form-table">';
		foreach ( $this->_fields as $field ) {
			$meta = get_post_meta( $post->ID, $field['id'], !$field['multiple'] );
			$meta = ( $meta !== '' ) ? $meta : $field['std'];
			$meta = is_array( $meta ) ? array_map( 'esc_attr', $meta ) : esc_attr( $meta );
			echo '<tr>';
		
			// Call Separated methods for displaying each type of field.
			call_user_func ( array( &$this, 'show_field_' . $field['type'] ), $field, $meta );
			echo '</tr>';
		}
		echo '</table>';
	}
	
	/**
	 * Show Repeater Fields.
	 *
	 * @param string $field 
	 * @param string $meta 
	 * @since 1.0
	 * @access public
	 */
	public function show_field_repeater( $field, $meta ) {
		global $post;	
		// Get Plugin Path
		$plugin_path = plugins_url( 'meta-box-class', plugin_basename( dirname( __FILE__ ) ) );
		$this->show_field_begin( $field, $meta );
		echo "<div class='at-repeat' id='{$field['id']}'>";
		
		$c = 0;
		$meta = get_post_meta($post->ID,$field['id'],true);
		
    	if (count($meta) > 0 && is_array($meta)){
   			foreach ($meta as $me){
   				
   				echo '<div><table class="repeater-table">';
				foreach ($field['fields'] as $f){
					//reset var $id for repeater
					$id = '';
					$id = $field['id'].'['.$c.']['.$f['id'].']';
					$m = $me[$f['id']];
					$m = ( $m !== '' ) ? $m : $f['std'];
					$m = is_array( $m) ? array_map( 'esc_attr', $m ) : esc_attr( $m);
					//set new id for field in array format
					$f['id'] = $id; 
					echo '<tr>';
					call_user_func ( array( &$this, 'show_field_' . $f['type'] ), $f, $m);
					echo '</tr>';	
				}
				echo '</table><img src="'.$plugin_path.'/images/remove.png" alt="'.__('Remove').'" title="'.__('Remove').'" id="remove-'.$field['id'].'"></div>';
				$c = $c + 1;
				//<span id="remove-'.$field['id'].'">Remove</span>
    		}
    	}
		//echo '<span id="add-'.$field['id'].'">'.__('Add').'</span>';
		echo '<img src="'.$plugin_path.'/images/add.png" alt="'.__('Add').'" title="'.__('Add').'" id="add-'.$field['id'].'"><br/></div>';
		
		//create all fields once more for js function and catch with object buffer
		ob_start();
		echo '<div><table class="repeater-table">';
		foreach ($field['fields'] as $f){
			//reset var $id for repeater
			$id = '';
			$id = $field['id'].'[CurrentCounter]['.$f['id'].']';
			$f['id'] = $id; 
			echo '<tr>';
			call_user_func ( array( &$this, 'show_field_' . $f['type'] ), $f, $m);
			echo '</tr>';	
		}
		echo '</table><img src="'.$plugin_path.'/images/remove.png" alt="'.__('Remove').'" id="remove-'.$field['id'].'"></div>';
		$counter = 'countadd_'.$field['id'];
		$js_code = ob_get_clean ();		
		$js_code = str_replace("'","\"",$js_code);
		$js_code = str_replace("CurrentCounter","' + ".$counter." + '",$js_code);
		echo '<script>
				jQuery(document).ready(function() {
					var '.$counter.' = '.$c.';
					jQuery("#add-'.$field['id'].'").live(\'click\', function() {
						'.$counter.' = '.$counter.' + 1;
						//jQuery("#'.$field['id'].'").append(\''.$js_code.'\');
						jQuery(this).before(\''.$js_code.'\');						
						update_repeater_fields();
					});
        			jQuery("#remove-'.$field['id'].'").live(\'click\', function() {
            			jQuery(this).parent().remove();
        			});
    			});
    		</script>';          	

		$this->show_field_end($field, $meta);
	}
	
	/**
	 * Begin Field.
	 *
	 * @param string $field 
	 * @param string $meta 
	 * @since 1.0
	 * @access public
	 */
	public function show_field_begin( $field, $meta ) {
		
		echo "<td class='at-field'>";
		
		if ( $field['name'] != '' || $field['name'] != FALSE ) {
			echo "<div class='at-label'>";
				echo "<label for='{$field['id']}'>{$field['name']}</label>";
			echo "</div>";
		}
		
	}
	
	/**
	 * End Field.
	 *
	 * @param string $field 
	 * @param string $meta 
	 * @since 1.0
	 * @access public 
	 */
	public function show_field_end( $field, $meta ) {
		
		if ( $field['desc'] != '' ) {
			echo "<div class='desc-field'>{$field['desc']}</div></td>";
		} else {
			echo "</td>";
		}
		
	}
	
	/**
	 * Show Field Text.
	 *
	 * @param string $field 
	 * @param string $meta 
	 * @since 1.0
	 * @access public
	 */
	public function show_field_text( $field, $meta) {	
		$this->show_field_begin( $field, $meta );
		echo "<input type='text' class='at-text' name='{$field['id']}' id='{$field['id']}' value='{$meta}' size='30' />";
		$this->show_field_end( $field, $meta );
	}
	
	/**
	 * Show Field Textarea.
	 *
	 * @param string $field 
	 * @param string $meta 
	 * @since 1.0
	 * @access public
	 */
	public function show_field_textarea( $field, $meta ) {
		$this->show_field_begin( $field, $meta );
			echo "<textarea class='at-textarea large-text' name='{$field['id']}' id='{$field['id']}' cols='60' rows='10'>{$meta}</textarea>";
		$this->show_field_end( $field, $meta );
	}
	
	/**
	 * Show Field Select.
	 *
	 * @param string $field 
	 * @param string $meta 
	 * @since 1.0
	 * @access public
	 */
	public function show_field_select( $field, $meta ) {
		
		if ( ! is_array( $meta ) ) 
			$meta = (array) $meta;
			
		$this->show_field_begin( $field, $meta );
			echo "<select class='at-select' name='{$field['id']}" . ( $field['multiple'] ? "[]' id='{$field['id']}' multiple='multiple'" : "'" ) . ">";
			foreach ( $field['options'] as $key => $value ) {
				echo "<option value='{$key}'" . selected( in_array( $key, $meta ), true, false ) . ">{$value}</option>";
			}
			echo "</select>";
		$this->show_field_end( $field, $meta );
		
	}
	
	/**
	 * Show Radio Field.
	 *
	 * @param string $field 
	 * @param string $meta 
	 * @since 1.0
	 * @access public 
	 */
	public function show_field_radio( $field, $meta ) {
		
		if ( ! is_array( $meta ) )
			$meta = (array) $meta;
			
		$this->show_field_begin( $field, $meta );
			foreach ( $field['options'] as $key => $value ) {
				echo "<input type='radio' class='at-radio' name='{$field['id']}' value='{$key}'" . checked( in_array( $key, $meta ), true, false ) . " /> <span class='at-radio-label'>{$value}</span>";
			}
		$this->show_field_end( $field, $meta );
	}
	
	/**
	 * Show Checkbox Field.
	 *
	 * @param string $field 
	 * @param string $meta 
	 * @since 1.0
	 * @access public
	 */
	public function show_field_checkbox( $field, $meta ) {
	
		$this->show_field_begin($field, $meta);
		echo "<input type='checkbox' class='rw-checkbox' name='{$field['id']}' id='{$field['id']}'" . checked(!empty($meta), true, false) . " /> {$field['desc']}</td>";
			
	}
	
	/**
	 * Show Wysiwig Field.
	 *
	 * @param string $field 
	 * @param string $meta 
	 * @since 1.0
	 * @access public
	 */
	public function show_field_wysiwyg( $field, $meta ) {
		$this->show_field_begin( $field, $meta );
			echo "<textarea class='at-wysiwyg theEditor large-text' name='{$field['id']}' id='{$field['id']}' cols='60' rows='10'>{$meta}</textarea>";
		$this->show_field_end( $field, $meta );
	}
	
	/**
	 * Show File Field.
	 *
	 * @param string $field 
	 * @param string $meta 
	 * @since 1.0
	 * @access public
	 */
	public function show_field_file( $field, $meta ) {
		
		global $post;

		if ( ! is_array( $meta ) )
			$meta = (array) $meta;

		$this->show_field_begin( $field, $meta );
			echo "{$field['desc']}<br />";

			if ( ! empty( $meta ) ) {
				$nonce = wp_create_nonce( 'at_ajax_delete' );
				echo '<div style="margin-bottom: 10px"><strong>' . __('Uploaded files') . '</strong></div>';
				echo '<ol class="at-upload">';
				foreach ( $meta as $att ) {
					// if (wp_attachment_is_image($att)) continue; // what's image uploader for?
					echo "<li>" . wp_get_attachment_link( $att, '' , false, false, ' ' ) . " (<a class='at-delete-file' href='#' rel='{$nonce}|{$post->ID}|{$field['id']}|{$att}'>" . __( 'Delete' ) . "</a>)</li>";
				}
				echo '</ol>';
			}

			// show form upload
			echo "<div class='at-file-upload-label'>";
				echo "<strong>" . __( 'Upload new files' ) . "</strong>";
			echo "</div>";
			echo "<div class='new-files'>";
				echo "<div class='file-input'>";
					echo "<input type='file' name='{$field['id']}[]' />";
				echo "</div><!-- End .file-input -->";
				echo "<a class='at-add-file button' href='#'>" . __( 'Add more files' ) . "</a>";
			echo "</div><!-- End .new-files -->";
		echo "</td>";
	}
	
	/**
	 * Show Image Field.
	 *
	 * @param string $field 
	 * @param string $meta 
	 * @since 1.0
	 * @access public
	 */
	public function show_field_image( $field, $meta ) {
		
		global $wpdb, $post;

		if ( ! is_array( $meta ) ) 
			$meta = (array) $meta;

		$this->show_field_begin( $field, $meta );
		
		echo "{$field['desc']}<br />";

		$nonce_delete = wp_create_nonce( 'at_ajax_delete' );
		$nonce_sort = wp_create_nonce( 'at_ajax_reorder' );

		echo "<input type='hidden' class='at-images-data' value='{$post->ID}|{$field['id']}|{$nonce_sort}' />";
		echo "<ul class='at--images at-upload' id='at-images-{$field['id']}'>";

		// re-arrange images with 'menu_order', thanks Onur
		$meta = ($meta) ? implode( ',', $meta ) : 1;
		$images = $wpdb->get_col("
			SELECT ID FROM $wpdb->posts
			WHERE post_type = 'attachment'
			AND post_parent = $post->ID
			AND ID in ($meta)
			ORDER BY menu_order ASC
		");
		
		foreach ( $images as $image ) {
			$src = wp_get_attachment_image_src( $image );
			$src = $src[0];

			echo "<li id='item_{$image}'>";
				echo "<img src='{$src}' alt='image_{$image}' />";
				echo "<a title='" . __( 'Delete this image' ) . "' class='at-delete-file' href='#' rel='{$nonce_delete}|{$post->ID}|{$field['id']}|{$image}'><img src='" . plugins_url( 'meta-box-class/images/delete-16.png' , dirname( __FILE__ ) ) . "' alt='" . __( 'Delete' ) . "' width='16' height='16' /></a>";
				//echo "<a title='" . __( 'Delete this image' ) . "' class='at-delete-file' href='#' rel='{$nonce_delete}|{$post->ID}|{$field['id']}|{$image}'>" . __( 'Delete' ) . "</a>";
				echo "<input type='hidden' name='{$field['id']}[]' value='{$image}' />";
			echo "</li>";
		}
		
		echo '</ul>';

		echo "<a href='#' class='at-upload-button button' rel='{$post->ID}|{$field['id']}'>" . __( 'Add more images' ) . "</a>";
	}
	
	/**
	 * Show Color Field.
	 *
	 * @param string $field 
	 * @param string $meta 
	 * @since 1.0
	 * @access public
	 */
	public function show_field_color( $field, $meta ) {
		
		if ( empty( $meta ) ) 
			$meta = '#';
			
		$this->show_field_begin( $field, $meta );
			echo "<input class='at-color' type='text' name='{$field['id']}' id='{$field['id']}' value='{$meta}' size='8' />";
		//	echo "<a href='#' class='at-color-select button' rel='{$field['id']}'>" . __( 'Select a color' ) . "</a>";
			echo "<input type='button' class='at-color-select button' rel='{$field['id']}' value='" . __( 'Select a color' ) . "'/>";
			echo "<div style='display:none' class='at-color-picker' rel='{$field['id']}'></div>";
		$this->show_field_end($field, $meta);
		
	}

	/**
	 * Show Checkbox List Field
	 *
	 * @param string $field 
	 * @param string $meta 
	 * @since 1.0
	 * @access public
	 */
	public function show_field_checkbox_list( $field, $meta ) {
		
		if ( ! is_array( $meta ) ) 
			$meta = (array) $meta;
			
		$this->show_field_begin($field, $meta);
		
			$html = array();
		
			foreach ($field['options'] as $key => $value) {
				$html[] = "<input type='checkbox' class='at-checkbox_list' name='{$field['id']}[]' value='{$key}'" . checked( in_array( $key, $meta ), true, false ) . " /> {$value}";
			}
		
			echo implode( '<br />' , $html );
			
		$this->show_field_end($field, $meta);
		
	}
	
	/**
	 * Show Date Field.
	 *
	 * @param string $field 
	 * @param string $meta 
	 * @since 1.0
	 * @access public
	 */
	public function show_field_date( $field, $meta ) {
		$this->show_field_begin( $field, $meta );
			echo "<input type='text' class='at-date' name='{$field['id']}' id='{$field['id']}' rel='{$field['format']}' value='{$meta}' size='30' />";
		$this->show_field_end( $field, $meta );
	}
	
	/**
	 * Show time field.
	 *
	 * @param string $field 
	 * @param string $meta 
	 * @since 1.0
	 * @access public 
	 */
	public function show_field_time( $field, $meta ) {
		$this->show_field_begin( $field, $meta );
			echo "<input type='text' class='at-time' name='{$field['id']}' id='{$field['id']}' rel='{$field['format']}' value='{$meta}' size='30' />";
		$this->show_field_end( $field, $meta );
	}
	
	 /**
	 * Show Posts field.
	 * used creating a posts/pages/custom types checkboxlist or a select dropdown
	 * @param string $field 
	 * @param string $meta 
	 * @since 1.0
	 * @access public 
	 */
	public function show_field_posts($field, $meta) {
		global $post;
		
		if (!is_array($meta)) $meta = (array) $meta;
		$this->show_field_begin($field, $meta);
		$options = $field['options'];
		$posts = get_posts($options['args']);
		
		// checkbox_list
		if ('checkbox_list' == $options['type']) {
			foreach ($posts as $p) {
				echo "<input type='checkbox' name='{$field['id']}[]' value='$p->ID'" . checked(in_array($p->ID, $meta), true, false) . " /> $p->post_title<br/>";
			}
		}
		// select
		else {
			echo "<select name='{$field['id']}" . ($field['multiple'] ? "[]' multiple='multiple' style='height:auto'" : "'") . ">";
			foreach ($posts as $p) {
				echo "<option value='$p->ID'" . selected(in_array($p->ID, $meta), true, false) . ">$p->post_title</option>";
			}
			echo "</select>";
		}
		
		$this->show_field_end($field, $meta);
	}
	
	/**
	 * Show Taxonomy field.
	 * used creating a category/tags/custom taxonomy checkboxlist or a select dropdown
	 * @param string $field 
	 * @param string $meta 
	 * @since 1.0
	 * @access public 
	 * 
	 * @uses get_terms()
	 */
	public function show_field_taxonomy($field, $meta) {
		global $post;
		
		if (!is_array($meta)) $meta = (array) $meta;
		$this->show_field_begin($field, $meta);
		$options = $field['options'];
		$terms = get_terms($options['taxonomy'], $options['args']);
		
		// checkbox_list
		if ('checkbox_list' == $options['type']) {
			foreach ($terms as $term) {
				echo "<input type='checkbox' name='{$field['id']}[]' value='$term->slug'" . checked(in_array($term->slug, $meta), true, false) . " /> $term->name<br/>";
			}
		}
		// select
		else {
			echo "<select name='{$field['id']}" . ($field['multiple'] ? "[]' multiple='multiple' style='height:auto'" : "'") . ">";
			foreach ($terms as $term) {
				echo "<option value='$term->slug'" . selected(in_array($term->slug, $meta), true, false) . ">$term->name</option>";
			}
			echo "</select>";
		}
		
		$this->show_field_end($field, $meta);
	}
	
	/**
	 * Save Data from Metabox
	 *
	 * @param string $post_id 
	 * @since 1.0
	 * @access public 
	 */
	public function save( $post_id ) {
		
		global $post_type;
		
		$post_type_object = get_post_type_object( $post_type );

		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )											// Check Autosave
		|| ( ! isset( $_POST['post_ID'] ) || $post_id != $_POST['post_ID'] )				// Check Revision
		|| ( ! in_array( $post_type, $this->_meta_box['pages'] ) )									// Check if current post type is supported.
		|| ( ! check_admin_referer( basename( __FILE__ ), 'at_meta_box_nonce') )		// Check nonce - Security
		|| ( ! current_user_can( $post_type_object->cap->edit_post, $post_id ) ) ) 	// Check permission
		{
			return $post_id;
		}
		
		foreach ( $this->_fields as $field ) {
			
			$name = $field['id'];
			$type = $field['type'];
			$old = get_post_meta( $post_id, $name, ! $field['multiple'] );
			$new = ( isset( $_POST[$name] ) ) ? $_POST[$name] : ( ( $field['multiple'] ) ? array() : '' );
						

			// Validate meta value
			if ( class_exists( 'at_Meta_Box_Validate' ) && method_exists( 'at_Meta_Box_Validate', $field['validate_func'] ) ) {
				$new = call_user_func( array( 'at_Meta_Box_Validate', $field['validate_func'] ), $new );
			}

			// Call defined method to save meta value, if there's no methods, call common one.
			$save_func = 'save_field_' . $type;
			if ( method_exists( $this, $save_func ) ) {
				call_user_func( array( &$this, 'save_field_' . $type ), $post_id, $field, $old, $new );
			} else {
				$this->save_field( $post_id, $field, $old, $new );
			}
			
		} // End foreach
		
	}
	
	/**
	 * Common function for saving fields.
	 *
	 * @param string $post_id 
	 * @param string $field 
	 * @param string $old 
	 * @param string|mixed $new 
	 * @since 1.0
	 * @access public
	 */
	public function save_field( $post_id, $field, $old, $new ) {
		
		$name = $field['id'];
		delete_post_meta( $post_id, $name );
		if ( $new === '' || $new === array() ) 
			return;
		if ( $field['multiple'] ) {
			foreach ( $new as $add_new ) {
				add_post_meta( $post_id, $name, $add_new, false );
			}
		} else {
			update_post_meta( $post_id, $name, $new );
		}
	}
	
	/**
	 * Save Wysiwyg Field.
	 *
	 * @param string $post_id 
	 * @param string $field 
	 * @param string $old 
	 * @param string $new 
	 * @since 1.0
	 * @access public 
	 */
	public function save_field_wysiwyg( $post_id, $field, $old, $new ) {
		$new = wpautop( $new );
		$this->save_field( $post_id, $field, $old, $new );
	}
	
	/**
	 * Save repeater Fields.
	 *
	 * @param string $post_id 
	 * @param string $field 
	 * @param string|mixed $old 
	 * @param string|mixed $new 
	 * @since 1.0
	 * @access public 
	 */
	public function save_field_repeater( $post_id, $field, $old, $new ) {
		
		foreach ($new as $n){
			foreach ( $field['fields'] as $f ) {
				$type = $f['type'];
				switch($type) {
					case 'wysiwyg':
				    	$n[$f['id']] = wpautop( $n[$f['id']] ); 
				    	break;
				    case 'file':
				    	$n[$f['id']] = $this->save_field_file_repeater($post_id,$f,'',$n[$f['id']]);
				    	break;
				    default:
				       	break;
				}
			}
			$temp[] = $n;
		}
		update_post_meta($post_id,$field['id'],$temp);
	}
	
	/**
	 * Save File Field.
	 *
	 * @param string $post_id 
	 * @param string $field 
	 * @param string $old 
	 * @param string $new 
	 * @since 1.0
	 * @access public
	 */
	public function save_field_file( $post_id, $field, $old, $new ) {
	
		$name = $field['id'];
		if ( empty( $_FILES[$name] ) ) 
			return;
		$this->fix_file_array( $_FILES[$name] );
		foreach ( $_FILES[$name] as $position => $fileitem ) {
			
			$file = wp_handle_upload( $fileitem, array( 'test_form' => false ) );
			if ( empty( $file['file'] ) ) 
				continue;
			$filename = $file['file'];

			$attachment = array(
				'post_mime_type' => $file['type'],
				'guid' => $file['url'],
				'post_parent' => $post_id,
				'post_title' => preg_replace('/\.[^.]+$/', '', basename( $filename ) ),
				'post_content' => ''
			);
			
			$id = wp_insert_attachment( $attachment, $filename, $post_id );
			
			if ( ! is_wp_error( $id ) ) {
				
				wp_update_attachment_metadata( $id, wp_generate_attachment_metadata( $id, $filename ) );
				add_post_meta( $post_id, $name, $id, false );	// save file's url in meta fields
			
			} // End if
			
		} // End foreach
		
	}
	
	/**
	 * Save repeater File Field.
	 *
	 * @param string $post_id 
	 * @param string $field 
	 * @param string $old 
	 * @param string $new 
	 * @since 1.0
	 * @access public
	 */
	public function save_field_file_repeater( $post_id, $field, $old, $new ) {
	
		$name = $field['id'];
		if ( empty( $_FILES[$name] ) ) 
			return;
		$this->fix_file_array( $_FILES[$name] );
		foreach ( $_FILES[$name] as $position => $fileitem ) {
			
			$file = wp_handle_upload( $fileitem, array( 'test_form' => false ) );
			if ( empty( $file['file'] ) ) 
				continue;
			$filename = $file['file'];

			$attachment = array(
				'post_mime_type' => $file['type'],
				'guid' => $file['url'],
				'post_parent' => $post_id,
				'post_title' => preg_replace('/\.[^.]+$/', '', basename( $filename ) ),
				'post_content' => ''
			);
			
			$id = wp_insert_attachment( $attachment, $filename, $post_id );
			
			if ( ! is_wp_error( $id ) ) {
				
				wp_update_attachment_metadata( $id, wp_generate_attachment_metadata( $id, $filename ) );
				return $id;	// return file's url in meta fields
			} // End if
		} // End foreach
	}
	
	/**
	 * Add missed values for meta box.
	 *
	 * @since 1.0
	 * @access public
	 */
	public function add_missed_values() {
		
		// Default values for meta box
		$this->_meta_box = array_merge( array( 'context' => 'normal', 'priority' => 'high', 'pages' => array( 'post' ) ), $this->_meta_box );

		// Default values for fields
		foreach ( $this->_fields as &$field ) {
			
			$multiple = in_array( $field['type'], array( 'checkbox_list', 'file', 'image' ) );
			$std = $multiple ? array() : '';
			$format = 'date' == $field['type'] ? 'yy-mm-dd' : ( 'time' == $field['type'] ? 'hh:mm' : '' );

			$field = array_merge( array( 'multiple' => $multiple, 'std' => $std, 'desc' => '', 'format' => $format, 'validate_func' => '' ), $field );
		
		} // End foreach
		
	}

	/**
	 * Check if field with $type exists.
	 *
	 * @param string $type 
	 * @since 1.0
	 * @access public
	 */
	public function has_field( $type ) {
		foreach ( $this->_fields as $field ) {
			if ( $type == $field['type'] ) 
				return true;
		}
		return false;
	}

	/**
	 * Check if current page is edit page.
	 *
	 * @since 1.0
	 * @access public
	 */
	public function is_edit_page() {
		global $pagenow;
		return in_array( $pagenow, array( 'post.php', 'post-new.php' ) );
	}
	
	/**
	 * Fixes the odd indexing of multiple file uploads.
	 *
	 * Goes from the format: 
	 * $_FILES['field']['key']['index']
	 * to
	 * The More standard and appropriate:
	 * $_FILES['field']['index']['key']
	 *
	 * @param string $files 
	 * @since 1.0
	 * @access public
	 */
	public function fix_file_array( &$files ) {
		
		$output = array();
		
		foreach ( $files as $key => $list ) {
			foreach ( $list as $index => $value ) {
				$output[$index][$key] = $value;
			}
		}
		
		return $files = $output;
	
	}

	/**
	 * Get proper JQuery UI version.
	 *
	 * Used in order to not conflict with WP Admin Scripts.
	 *
	 * @since 1.0
	 * @access public
	 */
	public function get_jqueryui_ver() {
		
		global $wp_version;
		
		if ( version_compare( $wp_version, '3.1', '>=') ) {
			return '1.8.10';
		}
		
		return '1.7.3';
	
	}
	
	/**
	 *  Add Text Field to meta box
	 *  @author Ohad Raz
	 *  @since 1.0
	 *  @access public
	 *  @param $id string  field id, i.e. the meta key
	 *  @param $args mixed|array
	 *  	'name' => // field name/label string optional
	 *  	'desc' => // field description, string optional
	 *  	'std' => // default value, string optional
	 *  	'style' => 	// custom style for field, string optional
	 *  	'validate_func' => // validate function, string optional 
	 */
	public function addTextField($id,$args){
		$new_field = array('type' => 'text','id'=> $id,'std' => '','desc' => '','style' =>'','name' => 'Text Field');
		$new_field = array_merge($new_field, $args);
		$this->_fields[] = $new_field;
	}

	/**
	 *  Add Checkbox Field to meta box
	 *  @author Ohad Raz
	 *  @since 1.0
	 *  @access public
	 *  @param $id string  field id, i.e. the meta key
	 *  @param $args mixed|array
	 *  	'name' => // field name/label string optional
	 *  	'desc' => // field description, string optional
	 *  	'std' => // default value, string optional
	 *  	'validate_func' => // validate function, string optional 
	 */
	public function addCheckboxField($id,$args){
		$new_field = array('type' => 'checkbox','id'=> $id,'std' => '','desc' => '','style' =>'','name' => 'Checkbox Field');
		$new_field = array_merge($new_field, $args);
		$this->_fields[] = $new_field;
	}

	/**
	 *  Add CheckboxList Field to meta box
	 *  @author Ohad Raz
	 *  @since 1.0
	 *  @access public
	 *  @param $id string  field id, i.e. the meta key
	 *  @param $options (array)  array of key => value pairs for select options
	 *  @param $args mixed|array
	 *  	'name' => // field name/label string optional
	 *  	'desc' => // field description, string optional
	 *  	'std' => // default value, string optional
	 *  	'validate_func' => // validate function, string optional
	 *  
	 *   @return : remember to call: $checkbox_list = get_post_meta(get_the_ID(), 'meta_name', false); 
	 *   which means the last param as false to get the values in an array
	 */
	public function addCheckboxListField($id,$options,$args){
		$new_field = array('type' => 'checkbox_list','id'=> $id,'std' => '','desc' => '','style' =>'','name' => 'Checkbox List Field');
		$new_field = array_merge($new_field, $args);
		$this->_fields[] = $new_field;
	}
	
	/**
	 *  Add Textarea Field to meta box
	 *  @author Ohad Raz
	 *  @since 1.0
	 *  @access public
	 *  @param $id string  field id, i.e. the meta key
	 *  @param $args mixed|array
	 *  	'name' => // field name/label string optional
	 *  	'desc' => // field description, string optional
	 *  	'std' => // default value, string optional
	 *  	'style' => 	// custom style for field, string optional
	 *  	'validate_func' => // validate function, string optional 
	 */
	public function addTextareaField($id,$args){
		$new_field = array('type' => 'textarea','id'=> $id,'std' => '','desc' => '','style' =>'','name' => 'Textarea Field');
		$new_field = array_merge($new_field, $args);
		$this->_fields[] = $new_field;
	}
	
	/**
	 *  Add Select Field to meta box
	 *  @author Ohad Raz
	 *  @since 1.0
	 *  @access public
	 *  @param $id string field id, i.e. the meta key
	 *  @param $options (array)  array of key => value pairs for select options  
	 *  @param $args mixed|array
	 *  	'name' => // field name/label string optional
	 *  	'desc' => // field description, string optional
	 *  	'std' => // default value, (array) optional
	 *  	'multiple' => // select multiple values, optional. Default is false.
	 *  	'validate_func' => // validate function, string optional 
	 */
	public function addSelectField($id,$options,$args){
		$new_field = array('type' => 'select','id'=> $id,'std' => array(),'desc' => '','style' =>'','name' => 'Select Field','multiple' => false,'options' => $options);
		$new_field = array_merge($new_field, $args);
		$this->_fields[] = $new_field;
	}
	
	
	/**
	 *  Add Radio Field to meta box
	 *  @author Ohad Raz
	 *  @since 1.0
	 *  @access public
	 *  @param $id string field id, i.e. the meta key
	 *  @param $options (array)  array of key => value pairs for radio options
	 *  @param $args mixed|array
	 *  	'name' => // field name/label string optional
	 *  	'desc' => // field description, string optional
	 *  	'std' => // default value, string optional
	 *  	'validate_func' => // validate function, string optional 
	 */
	public function addRadioField($id,$options,$args){
		$new_field = array('type' => 'radio','id'=> $id,'std' => array(),'desc' => '','style' =>'','name' => 'Radio Field','options' => $options);
		$new_field = array_merge($new_field, $args);
		$this->_fields[] = $new_field;
	}

	/**
	 *  Add Date Field to meta box
	 *  @author Ohad Raz
	 *  @since 1.0
	 *  @access public
	 *  @param $id string  field id, i.e. the meta key
	 *  @param $args mixed|array
	 *  	'name' => // field name/label string optional
	 *  	'desc' => // field description, string optional
	 *  	'std' => // default value, string optional
	 *  	'validate_func' => // validate function, string optional
	 *  	'format' => // date format, default yy-mm-dd. Optional. Default "'d MM, yy'"  See more formats here: http://goo.gl/Wcwxn 
	 */
	public function addDateField($id,$args){
		$new_field = array('type' => 'date','id'=> $id,'std' => '','desc' => '','format'=>'d MM, yy','name' => 'Date Field');
		$new_field = array_merge($new_field, $args);
		$this->_fields[] = $new_field;
	}

	/**
	 *  Add Time Field to meta box
	 *  @author Ohad Raz
	 *  @since 1.0
	 *  @access public
	 *  @param $id string- field id, i.e. the meta key
	 *  @param $args mixed|array
	 *  	'name' => // field name/label string optional
	 *  	'desc' => // field description, string optional
	 *  	'std' => // default value, string optional
	 *  	'validate_func' => // validate function, string optional
	 *  	'format' => // time format, default hh:mm. Optional. See more formats here: http://goo.gl/83woX 
	 */
	public function addTimeField($id,$args){
		$new_field = array('type' => 'time','id'=> $id,'std' => '','desc' => '','format'=>'hh:mm','name' => 'Time Field');
		$new_field = array_merge($new_field, $args);
		$this->_fields[] = $new_field;
	}
	
	/**
	 *  Add Color Field to meta box
	 *  @author Ohad Raz
	 *  @since 1.0
	 *  @access public
	 *  @param $id string  field id, i.e. the meta key
	 *  @param $args mixed|array
	 *  	'name' => // field name/label string optional
	 *  	'desc' => // field description, string optional
	 *  	'std' => // default value, string optional
	 *  	'validate_func' => // validate function, string optional 
	 */
	public function addColorField($id,$args){
		$new_field = array('type' => 'color','id'=> $id,'std' => '','desc' => '','name' => 'ColorPicker Field');
		$new_field = array_merge($new_field, $args);
		$this->_fields[] = $new_field;
	}
	
	/**
	 *  Add Image Field to meta box
	 *  @author Ohad Raz
	 *  @since 1.0
	 *  @access public
	 *  @param $id string  field id, i.e. the meta key
	 *  @param $args mixed|array
	 *  	'name' => // field name/label string optional
	 *  	'desc' => // field description, string optional
	 *  	'validate_func' => // validate function, string optional 
	 */
	public function addImageField($id,$args){
		$new_field = array('type' => 'image','id'=> $id,'desc' => '','name' => 'Image Field');
		$new_field = array_merge($new_field, $args);
		$this->_fields[] = $new_field;
	}
	
	/**
	 *  Add File Field to meta box
	 *  @author Ohad Raz
	 *  @since 1.0
	 *  @access public
	 *  @param $id string  field id, i.e. the meta key
	 *  @param $args mixed|array
	 *  	'name' => // field name/label string optional
	 *  	'desc' => // field description, string optional
	 *  	'validate_func' => // validate function, string optional 
	 */
	public function addFileField($id,$args){
		$new_field = array('type' => 'file','id'=> $id,'desc' => '','name' => 'File Field');
		$new_field = array_merge($new_field, $args);
		$this->_fields[] = $new_field;
	}

	/**
	 *  Add WYSIWYG Field to meta box
	 *  @author Ohad Raz
	 *  @since 1.0
	 *  @access public
	 *  @param $id string  field id, i.e. the meta key
	 *  @param $args mixed|array
	 *  	'name' => // field name/label string optional
	 *  	'desc' => // field description, string optional
	 *  	'std' => // default value, string optional
	 *  	'style' => 	// custom style for field, string optional Default 'width: 300px; height: 400px'
	 *  	'validate_func' => // validate function, string optional 
	 */
	public function addWysiwygField($id,$args){
		$new_field = array('type' => 'wysiwyg','id'=> $id,'std' => '','desc' => '','style' =>'width: 300px; height: 400px','name' => 'WYSIWYG Editor Field');
		$new_field = array_merge($new_field, $args);
		$this->_fields[] = $new_field;
	}
	
	/**
	 *  Add Taxonomy Field to meta box
	 *  @author Ohad Raz
	 *  @since 1.0
	 *  @access public
	 *  @param $id string  field id, i.e. the meta key
	 *  @param $options mixed|array options of taxonomy field
	 *  	'taxonomy' =>    // taxonomy name can be category,post_tag or any custom taxonomy default is category
			'type' =>  // how to show taxonomy? 'select' (default) or 'checkbox_list'
			'args' =>  // arguments to query taxonomy, see http://goo.gl/uAANN default ('hide_empty' => false)  
	 *  @param $args mixed|array
	 *  	'name' => // field name/label string optional
	 *  	'desc' => // field description, string optional
	 *  	'std' => // default value, string optional
	 *  	'validate_func' => // validate function, string optional 
	 */
	public function addTaxonomyField($id,$options,$args){
		$q = array('hide_empty' => 0);
		$tax = 'category';
		$type = 'select';
		$temp = array($tax,$type,$q);
		$options = array_merge($temp,$options);
		$new_field = array('type' => 'taxonomy','id'=> $id,'desc' => '','name' => 'Taxonomy Field','options'=> $options);
		$new_field = array_merge($new_field, $args);
		$this->_fields[] = $new_field;
	}

	/**
	 *  Add posts Field to meta box
	 *  @author Ohad Raz
	 *  @since 1.0
	 *  @access public
	 *  @param $id string  field id, i.e. the meta key
	 *  @param $options mixed|array options of taxonomy field
	 *  	'post_type' =>    // post type name, 'post' (default) 'page' or any custom post type
			'type' =>  // how to show posts? 'select' (default) or 'checkbox_list'
			'args' =>  // arguments to query posts, see http://goo.gl/is0yK default ('posts_per_page' => -1)  
	 *  @param $args mixed|array
	 *  	'name' => // field name/label string optional
	 *  	'desc' => // field description, string optional
	 *  	'std' => // default value, string optional
	 *  	'validate_func' => // validate function, string optional 
	 */
	public function addPostsField($id,$options,$args){
		$q = array('posts_per_page' => -1);
		$temp = array('post_type' =>'post','type'=>'select','args'=>$q);
		$options = array_merge($temp,$options);
		$new_field = array('type' => 'posts','id'=> $id,'desc' => '','name' => 'Posts Field','options'=> $options);
		$new_field = array_merge($new_field, $args);
		$this->_fields[] = $new_field;
	}
	
	
	/**
	 * Finish Declaration of Meta Box
	 * @author Ohad Raz
	 * @since 1.0
	 * @access public
	 */
	public function Finish() {
		$this->add_missed_values();
		$this->check_field_upload();
		$this->check_field_color();
		$this->check_field_date();
		$this->check_field_time();
	}
	
} // End Class

endif; // End Check Class Exists