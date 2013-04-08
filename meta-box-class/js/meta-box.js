/**
 * All Types Meta Box Class JS
 *
 * JS used for the custom metaboxes and other form items.
 *
 * Copyright 2011 - 2013 Ohad Raz (admin@bainternet.info)
 * @since 1.0
 */

var $ =jQuery.noConflict();
function update_repeater_fields(){
    /**
     * Datepicker Field.
     *
     * @since 1.0
     */
    load_date_picker();
  
    /**
     * Timepicker Field.
     *
     * @since 1.0
     */
    load_time_picker();
  
    /**
     * Colorpicker Field.
     *
     * @since 1.0
     */
    load_color_picker();
  
    /**
     * Add Files.
     *
     * @since 1.0
     */
    load_file_upload();
    
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
          data   = order + '|' + $this.siblings('.at-images-data').val();
  
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
      
      var data       = $(this).attr('rel').split('|'),
          post_id   = data[0],
          field_id   = data[1],
          backup     = window.send_to_editor; // backup the original 'send_to_editor' function which adds images to the editor
          
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
     * repeater sortable
     * @since 2.1
     */
    $('.repeater-sortable').sortable();
    /**
     * enable select2
     */
    fancySelect();
  
  }
var Ed_array = Array;
jQuery(document).ready(function($) {

  /**
   *  conditinal fields
   *  @since 2.9.9
   */
  load_conditinal();


  /**
   * enable select2
   * @since 2.9.8
   */
  fancySelect();

  /**
   * repeater sortable
   * @since 2.1
   */
  
  $('.repeater-sortable').sortable();

  /**
   * Code Editor Field
   * @since 2.1
   */
  load_code_editor();
  
  
  /**
   * repater Field
   * @since 1.1
   */  
  $(".at-re-toggle").live('click', function() {
    $(this).prev().toggle('slow');
  });
  
  
  /**
   * Datepicker Field.
   *
   * @since 1.0
   */
  load_date_picker();

  /**
   * Timepicker Field.
   *
   * @since 1.0
   */
  load_time_picker();

  /**
   * Colorpicker Field.
   *
   * @since 1.0
   * better handler for color picker with repeater fields support
   * which now works both when button is clicked and when field gains focus.
   */
  load_color_picker();

  /**
   * Add Files.
   *
   * @since 1.0
   */
  load_file_upload();
   
  /**
   * Thickbox Upload
   *
   * @since 1.0
   */
  $('.at-upload-button').click( function() {
    
    var data       = $(this).attr('rel').split('|'),
        post_id   = data[0],
        field_id   = data[1],
        backup     = window.send_to_editor; // backup the original 'send_to_editor' function which adds images to the editor
        
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
  
  //new image upload field
  function load_images_muploader(){
    jQuery(".mupload_img_holder").each(function(i,v){
      if (jQuery(this).next().next().val() != ''){
        if (!jQuery(this).children().size() > 0){
          jQuery(this).append('<img src="' + jQuery(this).next().next().val() + '" style="height: 150px;width: 150px;" />');
          jQuery(this).next().next().next().val("Delete");
          jQuery(this).next().next().next().removeClass('at-upload_image_button').addClass('at-delete_image_button');
        }
      }
    });
  }
  
  load_images_muploader();
  //delete img button
  jQuery('.at-delete_image_button').live('click', function(e){
    var field_id = jQuery(this).attr("rel");
    var at_id = jQuery(this).prev().prev();
    var at_src = jQuery(this).prev();
    var t_button = jQuery(this);
    data = {
        action: 'at_delete_mupload',
        _wpnonce: $('#nonce-delete-mupload_' + field_id).val(),
        post_id: jQuery('#post_ID').val(),
        field_id: field_id,
        attachment_id: jQuery(at_id).val()
    };
  
    $.getJSON(ajaxurl, data, function(response) {
      if ('success' == response.status){
        jQuery(t_button).val("Upload Image");
        jQuery(t_button).removeClass('at-delete_image_button').addClass('at-upload_image_button');
        //clear html values
        jQuery(at_id).val('');
        jQuery(at_src).val('');
        jQuery(at_id).prev().html('');
        load_images_muploader();
      }else{
        alert(response.message);
      }
    });
  
    return false;
  });
  
  

  //upload button
    var formfield1;
    var formfield2;
    jQuery('.at-upload_image_button').live('click',function(e){
      formfield1 = jQuery(this).prev();
      formfield2 = jQuery(this).prev().prev();      
      tb_show('', 'media-upload.php?post_id='+ jQuery('#post_ID').val() + '&type=image&amp;TB_iframe=true');
      //store old send to editor function
      window.restore_send_to_editor = window.send_to_editor;
      //overwrite send to editor function
      window.send_to_editor = function(html) {
        imgurl = jQuery('img',html).attr('src');
        img_calsses = jQuery('img',html).attr('class').split(" ");
        att_id = '';
        jQuery.each(img_calsses,function(i,val){
          if (val.indexOf("wp-image") != -1){
            att_id = val.replace('wp-image-', "");
          }
        });

        jQuery(formfield2).val(att_id);
        jQuery(formfield1).val(imgurl);
        load_images_muploader();
        tb_remove();
        //restore old send to editor function
        window.send_to_editor = window.restore_send_to_editor;
      }
      return false;
    });
    
  //editor rezise fix
  $(window).resize(function() {
    $.each(Ed_array, function() {
      var ee = this;
      $(ee.getScrollerElement()).width(100); // set this low enough
      width = $(ee.getScrollerElement()).parent().width();
      $(ee.getScrollerElement()).width(width); // set it to
      ee.refresh();
    });
  });
});

/**
 * Select 2 enable function
 * @since 2.9.8
 */
function fancySelect(){
  if ($().select2){
    $(".at-select").each(function (){
      if(! $(this).hasClass('no-fancy'))
        $(this).select2();
    });
  }
}

/**
 * Loads Codemirror code editor 
 * @since 3.0.2
 */
var e_d_count = 0;
function load_code_editor(){
  $(".code_text").each(function() {
    var lang = $(this).attr("data-lang");
    //php application/x-httpd-php
    //css text/css
    //html text/html
    //javascript text/javascript
    switch(lang){
      case 'php':
        lang = 'application/x-httpd-php';
        break;
      case 'css':
        lang = 'text/css';
        break;
      case 'html':
        lang = 'text/html';
        break;
      case 'javascript':
        lang = 'text/javascript';
        break;
      default:
        lang = 'application/x-httpd-php';
    }
    var theme  = $(this).attr("data-theme");
    switch(theme){
      case 'default':
        theme = 'default';
        break;
      case 'light':
        theme = 'solarizedLight';
        break;
      case 'dark':
        theme = 'solarizedDark';;
        break;
      default:
        theme = 'default';
    }
    
    var editor = CodeMirror.fromTextArea(document.getElementById($(this).attr('id')), {
      lineNumbers: true,
      matchBrackets: true,
      mode: lang,
      indentUnit: 4,
      indentWithTabs: true,
      enterMode: "keep",
      tabMode: "shift"
    });
    editor.setOption("theme", theme);
    $(editor.getScrollerElement()).width(100); // set this low enough
    width = $(editor.getScrollerElement()).parent().width();
    $(editor.getScrollerElement()).width(width); // set it to
    editor.refresh();
    Ed_array[e_d_count] = editor;
    e_d_count++;
  });
}

/**
 * loads color picker 
 * @since 3.0.2
 */
function load_color_picker(){
  if ($.farbtastic){//since WordPress 3.5
    $('.at-color').live('focus', function() {
      load_colorPicker_enable($(this).next());
    });

    $('.at-color').live('focusout', function() {
      hide_colorPicker($(this).next());
    });

    /**
     * Select Color Field.
     *
     * @since 1.0
     */
    $('.at-color-select').live('click', function(){
      if ($(this).next('div').css('display') == 'none')
        load_colorPicker_enable($(this));
      else
        hide_colorPicker($(this));
    });

    function load_colorPicker_enable(ele){
      colorPicker = $(ele).next('div');
      input = $(ele).prev('input');

      $.farbtastic($(colorPicker), function(a) { $(input).val(a).css('background', a); });

      colorPicker.show();
    }

    function hide_colorPicker(ele){
      colorPicker = $(ele).next('div');
      $(colorPicker).hide();
    }
    //issue #15
    $('.at-color').each(function(){
      var colo = $(this).val();
      if (colo.length == 7)
        $(this).css('background',colo);
    });
  }else{
    if ($('.at-color-iris').length>0){
      $('.at-color-iris').wpColorPicker(); 
    }
  }
}

/**
 * loadS conditinal field
 * @since 3.0.2
 */
function load_conditinal(){
  $(".conditinal_control").click(function(){
    if($(this).is(':checked')){
      $(this).next().show('fast');    
    }else{
      $(this).next().hide('fast');    
    }
  });
}

/**
 * loads time picker
 * @since 3.0.2
 */
function load_time_picker(){  
  $('.at-time').each( function() {
    
    var $this   = $(this),
          format   = $this.attr('rel'),
          aampm    = $this.attr('data-ampm');
      if ('true' == aampm)
        aampm = true;
      else
        aampm = false;

      $this.timepicker( { showSecond: true, timeFormat: format, ampm: aampm } );
    
  });
}

/**
 * loads date picker 
 * @since 3.0.2
 */
function load_date_picker() {
  $('.at-date').each( function() {
    
    var $this  = $(this),
        format = $this.attr('rel');

    $this.datepicker( { showButtonPanel: true, dateFormat: format } );
    
  });
}

/**
 * loads file upload 
 * @since 3.0.2
 */
function load_file_upload(){
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
    
    var $this   = $(this),
        $parent = $this.parent(),
        data = $this.attr('rel');
    
    var ind = $(this).index()
    $.post( ajaxurl, { action: 'atm_delete_file', data: data, tag_id: $('#post_ID').val() }, function(response) {
      response == '0' ? ( alert( 'File has been successfully deleted.' ), $parent.remove() ) : alert( 'You do NOT have permission to delete this file.' );
    });
    
    return false;
  });
}
