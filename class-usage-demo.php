<?php
/*
Plugin Name: Demo MetaBox
Plugin URI: http://en.bainternet.info
Description: My Meta Box Class usage demo
Version: 1.0
Author: Bainternet, Ohad Raz
Author URI: http://en.bainternet.info
*/

require_once("meta-box-class/my-meta-box-class.php");
if (is_admin()){
	/* 
	 * prefix of meta keys, optional
	 * use underscore (_) at the beginning to make keys hidden, for example $prefix = '_ba_';
	 *  you also can make prefix empty to disable it
	 * 
	 */
	$prefix = 'ba_';
	/* 
	 * configure your meta box
	 */
	$config = array(
		'id' => 'demo_meta_box',					// meta box id, unique per meta box
		'title' => 'Demo Meta Box',					// meta box title
		'pages' => array('post', 'page'),			// post types, accept custom post types as well, default is array('post'); optional
		'context' => 'normal',						// where the meta box appear: normal (default), advanced, side; optional
		'priority' => 'high',						// order of meta box: high (default), low; optional
		'fields' => array()							// list of meta fields (can be added by field arrays)
	);
	/*
	 * Initiate your meta box
	 */
	$my_meta =  new AT_Meta_Box($config);
	
	/*
	 * Add fields to your meta box
	 */
	
	//text field
	$my_meta->addTextField($prefix.'text_field_id',array('name'=> 'My Text Field'));
	//textarea field
	$my_meta->addTextareaField($prefix.'textarea_field_id',array('name'=> 'My Textarea Field'));
	//checkbox field
	$my_meta->addCheckboxField($prefix.'checkbox_field_id',array('name'=> 'My Checkbox Field'));
	//select field
	$my_meta->addSelectField($prefix.'select_field_id',array('selectkey1'=>'Select Value1','selectkey2'=>'Select Value2'),array('name'=> 'My select Field', 'std'=> array('selectkey2')));
	//radio field
	$my_meta->addRadioField($prefix.'radio_field_id',array('radiokey1'=>'Radio Value1','radiokey2'=>'Radio Value2'),array('name'=> 'My Radio Filed', 'std'=> array('radionkey2')));
	//date field
	$my_meta->addDateField($prefix.'date_field_id',array('name'=> 'My Date Field'));
	//Time field
	$my_meta->addTimeField($prefix.'time_field_id',array('name'=> 'My Time Field'));
	//Color field
	$my_meta->addColorField($prefix.'color_field_id',array('name'=> 'My Color Field'));
	//Image field
	$my_meta->addImageField($prefix.'image_field_id',array('name'=> 'My Image Field'));
	//file upload field
	$my_meta->addFileField($prefix.'file_field_id',array('name'=> 'My File Field'));
	//wysiwyg field
	$my_meta->addWysiwygField($prefix.'wysiwyg_field_id',array('name'=> 'My wysiwyg Editor Field'));
	//taxonomy field
	$my_meta->addTaxonomyField($prefix.'taxonomy_field_id',array('taxonomy' => 'category'),array('name'=> 'My Taxonomy Field'));
	//posts field
	$my_meta->addPostsField($prefix.'posts_field_id',array('post_type' => 'post'),array('name'=> 'My Posts Field'));
	
	/*
	 * To Create a reapeater Block first create an array of fields
	 * use the same functions as above but add true as a last param
	 */
	
	$repeater_fields[] = $my_meta->addTextField($prefix.'text_field_id',array('name'=> 'My Text Field'),true);
	$repeater_fields[] = $my_meta->addTextareaField($prefix.'textarea_field_id',array('name'=> 'My Textarea Field'),true);
	$repeater_fields[] = $my_meta->addCheckboxField($prefix.'checkbox_field_id',array('name'=> 'My Checkbox Field'),true);
	
	/*
	 * Then just add the fields to the repeater block
	 */
	//repeater block
	$my_meta->addRepeaterBlock($prefix.'text_field_id',array('inline' => true, 'name' => 'This is a Repeater Block','fields' => $repeater_fields));
	/*
	 * Don't Forget to Close up the meta box decleration
	 */
	//Finish Meta Box Decleration
	$my_meta->Finish();
}