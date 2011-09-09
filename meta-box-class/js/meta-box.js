/**
 * All Types Meta Box Class JS
 *
 * JS used for the custom metaboxes and other form items.
 *
 * Copyright 2011 Ohad Raz (admin@bainternet.info)
 * @since 1.0
 */

var $ =jQuery.noConflict();
function update_repeater_fields(){
		
			
		/**
		 * Datepicker Field.
		 *
		 * @since 1.0
		 */
		$('.at-date').each( function() {
			
			var $this  = $(this),
				  format = $this.attr('rel');
	
			$this.datepicker( { showButtonPanel: true, dateFormat: format } );
			
		});
	
		/**
		 * Timepicker Field.
		 *
		 * @since 1.0
		 */
		$('.at-time').each( function() {
			
			var $this 	= $(this),
				  format 	= $this.attr('rel');
	
			$this.timepicker( { showSecond: true, timeFormat: format } );
			
		});
	
		/**
		 * Colorpicker Field.
		 *
		 * @since 1.0
		 */
		/*
		$('.at-color-picker').each( function() {
			
			var $this = $(this);
			var id = $this.attr('rel');
	
			$this.farbtastic( '#' + id );
			//$(this).focus.siblings('.at-color-picker').toggle();
		});
		*/
		
		
		/**
		 * Select Color Field.
		 *
		 * @since 1.0
		 */
		$('.at-color-select').click( function(){
			var $this = $(this);
			var id = $this.attr('rel');
			$(this).siblings('.at-color-picker').farbtastic("#" + id).toggle();
			return false;
		});
	
		/**
		 * Add Files.
		 *
		 * @since 1.0
		 */
		$('.at-add-file').click( function() {
			var $first = $(this).parent().find('.file-input:first');
			$first.clone().insertAfter($first).show();
			return false;
		});
	
		/**
		 * Delete File.
		 *
		 * @since 1.0
		 */
		$('.at-upload').delegate( '.at-delete-file', 'click' , function() {
			
			var $this 	= $(this),
					$parent = $this.parent(),
					data 		= $this.attr('rel');
					
			$.post( ajaxurl, { action: 'at_delete_file', data: data }, function(response) {
				response == '0' ? ( alert( 'File has been successfully deleted.' ), $parent.remove() ) : alert( 'You do NOT have permission to delete this file.' );
			});
			
			return false;
		
		});
	
		/**
		 * Reorder Images.
		 *
		 * @since 1.0
		 */
		$('.at-images').each( function() {
			
			var $this = $(this), order, data;
			
			$this.sortable( {
				placeholder: 'ui-state-highlight',
				update: function (){
					order = $this.sortable('serialize');
					data 	= order + '|' + $this.siblings('.at-images-data').val();
	
					$.post(ajaxurl, {action: 'at_reorder_images', data: data}, function(response){
						response == '0' ? alert( 'Order saved!' ) : alert( "You don't have permission to reorder images." );
					});
				}
			});
			
		});
		
		/**
		 * Thickbox Upload
		 *
		 * @since 1.0
		 */
		$('.at-upload-button').click( function() {
			
			var data 			= $(this).attr('rel').split('|'),
					post_id 	= data[0],
					field_id 	= data[1],
					backup 		= window.send_to_editor; // backup the original 'send_to_editor' function which adds images to the editor
					
			// change the function to make it adds images to our section of uploaded images
			window.send_to_editor = function(html) {
				
				$('#at-images-' + field_id).append( $(html) );
	
				tb_remove();
				
				window.send_to_editor = backup;
			
			};
	
			// note that we pass the field_id and post_id here
			tb_show('', 'media-upload.php?post_id=' + post_id + '&field_id=' + field_id + '&type=image&TB_iframe=true');
	
			return false;
		});
	
		/**
		 * Add checkboxes to select images to add.
		 *
		 * @since 1.0
		 */
		$('#media-items .new').each( function() {
			var id = $(this).parent().attr('id').split('-')[2];
			$(this).prepend('<input type="checkbox" class="item_selection" id="attachments[' + id + '][selected]" name="attachments[' + id + '][selected]" value="selected" /> ');
		});
	
		/**
		 * Add checkboxes to select images to add.
		 *
		 * @since 1.0
		 */
		$('.ml-submit').live('mouseenter',function() {
			
			$('#media-items .new').each(function() {
				var id = $(this).parent().children('input[value="image"]').attr('id');
				if (!id) return;
				id = id.split('-')[2];
				$(this).not(':has("input")').prepend('<input type="checkbox" class="item_selection" id="attachments[' + id + '][selected]" name="attachments[' + id + '][selected]" value="selected" /> ');
			});
			
		});
	
		/**
		 * Add 'Insert selected Images' Button
		 *
		 * We need to pull out the 'field_id' from the url as the
		 * media uploader is an iframe.
		 *
		 * @since 1.0
		 */
		/*
		var field_id = get_query_var( 'field_id' );
		$('.ml-submit:first').append('<input type="hidden" name="field_id" value="' + field_id + '" /> <input type="submit" class="button" name="at-insert" value="Insert selected images" />');
		*/
	}
jQuery(document).ready(function($) {

	/**
	 * repater Field
	 * @since 1.1
	 */
	$( ".at-repeater-item" ).live('click', function() {
		var $this  = $(this);
		$this.siblings().toggle();
	});
	
	/**
	 * Datepicker Field.
	 *
	 * @since 1.0
	 */
	$('.at-date').each( function() {
		
		var $this  = $(this),
			  format = $this.attr('rel');

		$this.datepicker( { showButtonPanel: true, dateFormat: format } );
		
	});

	/**
	 * Timepicker Field.
	 *
	 * @since 1.0
	 */
	$('.at-time').each( function() {
		
		var $this 	= $(this),
			  format 	= $this.attr('rel');

		$this.timepicker( { showSecond: true, timeFormat: format } );
		
	});

	/**
	 * Colorpicker Field.
	 *
	 * @since 1.0
	 * better handler for color picker with repeater fields support
	 * which now works both when button is clicked and when field gains focus.
	 */
	$('.at-color').live('focus', function() {
		var $this = $(this);
		$(this).siblings('.at-color-picker').farbtastic($this).toggle();
	});

	$('.at-color').live('focusout', function() {
		var $this = $(this);
		$(this).siblings('.at-color-picker').farbtastic($this).toggle();
	});
	
	/**
	 * Add Files.
	 *
	 * @since 1.0
	 */
	$('.at-add-file').click( function() {
		var $first = $(this).parent().find('.file-input:first');
		$first.clone().insertAfter($first).show();
		return false;
	});

	/**
	 * Delete File.
	 *
	 * @since 1.0
	 */
	$('.at-upload').delegate( '.at-delete-file', 'click' , function() {
		
		var $this 	= $(this),
				$parent = $this.parent(),
				data 		= $this.attr('rel');
				
		$.post( ajaxurl, { action: 'at_delete_file', data: data }, function(response) {
			response == '0' ? ( alert( 'File has been successfully deleted.' ), $parent.remove() ) : alert( 'You do NOT have permission to delete this file.' );
		});
		
		return false;
	
	});

	/**
	 * Reorder Images.
	 *
	 * @since 1.0
	 */
	$('.at-images').each( function() {
		
		var $this = $(this), order, data;
		
		$this.sortable( {
			placeholder: 'ui-state-highlight',
			update: function (){
				order = $this.sortable('serialize');
				data 	= order + '|' + $this.siblings('.at-images-data').val();

				$.post(ajaxurl, {action: 'at_reorder_images', data: data}, function(response){
					response == '0' ? alert( 'Order saved!' ) : alert( "You don't have permission to reorder images." );
				});
			}
		});
		
	});
	
	/**
	 * Thickbox Upload
	 *
	 * @since 1.0
	 */
	$('.at-upload-button').click( function() {
		
		var data 			= $(this).attr('rel').split('|'),
				post_id 	= data[0],
				field_id 	= data[1],
				backup 		= window.send_to_editor; // backup the original 'send_to_editor' function which adds images to the editor
				
		// change the function to make it adds images to our section of uploaded images
		window.send_to_editor = function(html) {
			
			$('#at-images-' + field_id).append( $(html) );

			tb_remove();
			
			window.send_to_editor = backup;
		
		};

		// note that we pass the field_id and post_id here
		tb_show('', 'media-upload.php?post_id=' + post_id + '&field_id=' + field_id + '&type=image&TB_iframe=true');

		return false;
	});

	/**
	 * Add checkboxes to select images to add.
	 *
	 * @since 1.0
	 */
	$('#media-items .new').each( function() {
		var id = $(this).parent().attr('id').split('-')[2];
		$(this).prepend('<input type="checkbox" class="item_selection" id="attachments[' + id + '][selected]" name="attachments[' + id + '][selected]" value="selected" /> ');
	});

	/**
	 * Add checkboxes to select images to add.
	 *
	 * @since 1.0
	 */
	$('.ml-submit').live('mouseenter',function() {
		
		$('#media-items .new').each(function() {
			var id = $(this).parent().children('input[value="image"]').attr('id');
			if (!id) return;
			id = id.split('-')[2];
			$(this).not(':has("input")').prepend('<input type="checkbox" class="item_selection" id="attachments[' + id + '][selected]" name="attachments[' + id + '][selected]" value="selected" /> ');
		});
		
	});

	/**
	 * Add 'Insert selected Images' Button
	 *
	 * We need to pull out the 'field_id' from the url as the
	 * media uploader is an iframe.
	 *
	 * @since 1.0
	 */
	var field_id = get_query_var( 'field_id' );
	$('.ml-submit:first').append('<input type="hidden" name="field_id" value="' + field_id + '" /> <input type="submit" class="button" name="at-insert" value="Insert selected images" />');
	
	/**
	 * Helper Function
	 *
	 * Get Query string value by name.
	 *
	 * @since 1.0
	 */
	function get_query_var( name ) {

		var match = RegExp('[?&]' + name + '=([^&#]*)').exec(location.href);
		return match && decodeURIComponent(match[1].replace(/\+/g, ' '));
			
	}

});