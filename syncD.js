jQuery(document).ready(function () {
  jQuery('.datetimepicker').datetimepicker({
    timepicker:false,
    format:'d/m/y'
  });
  jQuery('#counter').hide();
  let interval;
  jQuery('#sync_data').click(function(e){
    e.preventDefault();
    var admin_url = jQuery('#admin_url').val();
    var plugin_url = jQuery('#plugin_url').val();
      jQuery.ajax({
        url:  admin_url + 'admin-ajax.php',
        type: 'post',
        cache: false,
        context: this,
        dataType: 'json',
        data: {
          action: 'eventd_sync'
        },
        beforeSend: function () {
          let counter = 1;
         interval = setInterval(() => {
            if (counter === 0) {
              counter = 1;
            } else {
              if(counter > 99){
                jQuery('#counter').text('loading..');
                clearInterval(interval);
              }
              jQuery('#counter').show().text(counter+'%');
              counter++;
            }
          }, 300);
            jQuery('#button-section').append('<img style="position:absolute;" id="loader" width="60" src="'+plugin_url+'/sync/loader.gif"/>');
            jQuery(this).addClass('disabled');
            jQuery('p.btn-para').remove();
        },
        success: function (response) {
          clearInterval(interval);
          jQuery('#counter').hide();
          jQuery(this).removeClass('disabled');
          jQuery('#loader').remove();
          if(response.code == 'empty'){
              jQuery('p.btn-para').remove();
              jQuery('<p class="btn-para">No New Data Found</p>').insertAfter('#button-section');
              return false;
          }
          location.reload();
        },
        error: function (xmlhttprequest, textstatus, message) {
          clearInterval(interval);
          console.log(message+'>>>'+textstatus);
          jQuery(this).removeClass('disabled');
          jQuery('#loader').remove();
        }
      });
  });

});