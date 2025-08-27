jQuery(function($){
  // Tabs
  $('.brp-tab').on('click', function(){
    var tab=$(this).data('tab');
    $('.brp-panel').hide(); $('#brp-'+tab).show();
  });

  // Register AJAX
  $('#brp-register-form').on('submit', function(e){
    e.preventDefault();
    $('#brp-register-msg').text('Creating account...');
    $.post(BRP_Ajax.ajax_url, $(this).serialize(), function(res){
      if(res.success) $('#brp-register-msg').css('color','#16a34a').text(res.data);
      else $('#brp-register-msg').css('color','#dc2626').text(res.data);
    });
  });

  // Submit AJAX with progress + friendly errors
$('#brp-submit-form').on('submit', function(e){
  e.preventDefault();

  var formData = new FormData(this);
  formData.append('nonce', BRP_Ajax.nonce);

  var $msg = $('#brp-submit-msg');
  var $btn = $('#brp-submit-form button[type=submit]');
  $btn.prop('disabled', true);
  $msg.css('color','').text('Uploading… 0%');

  $.ajax({
    url: BRP_Ajax.ajax_url,
    method: 'POST',
    data: formData,
    processData: false,
    contentType: false,
    xhr: function(){
      var xhr = new window.XMLHttpRequest();
      xhr.upload.addEventListener('progress', function(evt){
        if (evt.lengthComputable) {
          var p = Math.round((evt.loaded / evt.total) * 100);
          $msg.text('Uploading… ' + p + '%');
        }
      }, false);
      return xhr;
    },
    success: function(res){
      if (res && res.success){
        $msg.css('color','#16a34a').text(res.data || 'Submitted!');
        $('#brp-submit-form')[0].reset();
      } else {
        $msg.css('color','#dc2626').text((res && res.data) ? res.data : 'Upload failed. Please try a smaller file (JPG/PNG/GIF/WEBP/PDF/MP4, max 10MB).');
      }
    },
    error: function(xhr){
      // Friendly hints for common server limits:
      if (xhr && xhr.status === 413){
        $msg.css('color','#dc2626').text('Upload too large for the server. Please resize the file under 10MB or increase server PHP limits.');
      } else {
        $msg.css('color','#dc2626').text('Network/server error. Please try again or use a smaller file.');
      }
    },
    complete: function(){
      $btn.prop('disabled', false);
    }
  });
});


  // Copy link
  $(document).on('click','.brp-copy', function(){
    var link=$(this).data('link');
    navigator.clipboard.writeText(link).then(()=>{
      $(this).text('Copied'); setTimeout(()=>$(this).text('Copy Link'),1500);
    });
  });

  // Inject compact share buttons in theme cards (if present)
  var $cards=$('.brpt-card');
  if($cards.length){
    $cards.each(function(){
      var $card=$(this);
      if($card.find('.brp-card-share').length) return;
      var link=$card.find('.brpt-card-link').attr('href');
      var title=$card.find('.brpt-title').text();
      var url=encodeURIComponent(link), txt=encodeURIComponent(title);
      var html='<div class="brp-card-share" style="margin-top:8px;display:flex;gap:6px;flex-wrap:wrap;">'
        +'<a class="brp-sh" href="https://wa.me/?text='+txt+'%20'+url+'" target="_blank" rel="noopener">WA</a>'
        +'<a class="brp-sh" href="https://t.me/share/url?url='+url+'&text='+txt+'" target="_blank" rel="noopener">TG</a>'
        +'<a class="brp-sh" href="https://twitter.com/intent/tweet?url='+url+'&text='+txt+'" target="_blank" rel="noopener">X</a>'
        +'<button class="brp-copy" data-link="'+link+'">Copy</button>'
        +'</div>';
      $card.find('.brpt-card-footer').append(html);
    });
  }
});
