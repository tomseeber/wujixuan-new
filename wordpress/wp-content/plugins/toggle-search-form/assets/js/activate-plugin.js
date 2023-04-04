function ActivateTSFPlugin( id, action, nonce ) {
  var key = jQuery( '#' + id).val();   
  var data = {
    "action"      : "tsf_" + action + "_plugin",
    "license_key" : key,
    "security"    : nonce
 };
 
 jQuery('#actplug').css('visibility', 'inherit');
 
 jQuery.post(ajaxurl, data, function( response ) {
    jQuery('#actplug').removeAttr('style');
    if( response != '200' ) {
      jQuery('.tsf-response').addClass('error').text(response);
    }else {
      jQuery('#btn-' + action + '-license').hide();
      jQuery('.tsf-response').text('');
      jQuery('.tsf-response').removeClass('error');
      if( action == 'reactivate' ) {
        jQuery('td .update-nag').hide();
      }
    }   
    
    jQuery('.tsf-response').show();
 });
}