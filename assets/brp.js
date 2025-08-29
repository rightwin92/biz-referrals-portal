function brpKey(postId, type){ return 'brp_'+type+'_p'+postId; }
function brpMark(postId, type){ try{ localStorage.setItem(brpKey(postId,type), '1'); }catch(e){} }
function brpSeen(postId, type){ try{ return localStorage.getItem(brpKey(postId,type))==='1'; }catch(e){ return false; } }
function brpUpdateCount($el, type, val){
  if(type==='like'){ $el.closest('.brp-cta').find('.brp-like-count').text(val); }
  if(type==='enquiry'){ $el.closest('.brp-cta').find('.brp-enq-count').text(val); }
}
function brpSendCount(postId, type, $el){
  if(brpSeen(postId, type)) return; // already counted
  var data = new FormData();
  data.append('action','brp_track_interest');
  data.append('nonce', BRP_Ajax.nonce);
  data.append('post_id', postId);
  data.append('type', type);

  // Try sendBeacon first (won't block navigation)
  if (navigator.sendBeacon) {
    var params = new URLSearchParams();
    params.append('action','brp_track_interest');
    params.append('nonce', BRP_Ajax.nonce);
    params.append('post_id', postId);
    params.append('type', type);
    var ok = navigator.sendBeacon(BRP_Ajax.ajax_url, params);
    if(ok){ brpMark(postId,type); }
    return;
  }

  // Fallback AJAX (non-blocking enough)
  $.ajax({
    url: BRP_Ajax.ajax_url, method:'POST', data: data, processData:false, contentType:false
  }).always(function(res){
    if(res && res.success && res.data && typeof res.data.count !== 'undefined'){
      brpMark(postId, type);
      if($el) brpUpdateCount($el, type, res.data.count);
    }
  });
}
jQuery(function($){
  // Tabs (login/register/forgot)
  $('.brp-tab').on('click', function(){
    var tab=$(this).data('tab');
    $('.brp-panel').hide();
    $('#brp-'+tab).show();
  });

  // Register AJAX
  $('#brp-register-form').on('submit', function(e){
    e.preventDefault();
    var data=$(this).serialize();
    $('#brp-register-msg').text('Creating account...');
    $.post(BRP_Ajax.ajax_url, data, function(res){
      if(res && res.success){
        $('#brp-register-msg').css('color','#16a34a').text(res.data || 'Account created.');
      } else {
        $('#brp-register-msg').css('color','#dc2626').text((res && res.data) ? res.data : 'Registration failed.');
      }
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

  // Copy link (single + cards)
  $(document).on('click','.brp-copy', function(){
    var link=$(this).data('link');
    navigator.clipboard.writeText(link).then(()=>{
      $(this).text('Copied');
      setTimeout(()=>$(this).text('Copy Link'),1500);
    });
  });

  // Inject compact share buttons on cards (theme list/grid)
  // Adds WA, TG, FB, IN, X, Email, Copy
  var $cards = $('.brpt-card');
  if ($cards.length){
    $cards.each(function(){
      var $card = $(this);
      if ($card.find('.brp-card-share').length) return;

      var link = $card.find('.brpt-card-link').attr('href');
      if (!link) return;

      var title = $card.find('.brpt-title').text();
      var url   = encodeURIComponent(link);
      var txt   = encodeURIComponent(title);

      var html = '<div class="brp-card-share" style="margin-top:8px;display:flex;gap:6px;flex-wrap:wrap;">'
        + '<a class="brp-sh" href="https://wa.me/?text='+txt+'%20'+url+'" target="_blank" rel="noopener">WA</a>'
        + '<a class="brp-sh" href="https://t.me/share/url?url='+url+'&text='+txt+'" target="_blank" rel="noopener">TG</a>'
        + '<a class="brp-sh" href="https://www.facebook.com/sharer/sharer.php?u='+url+'" target="_blank" rel="noopener">FB</a>'
        + '<a class="brp-sh" href="https://www.linkedin.com/sharing/share-offsite/?url='+url+'" target="_blank" rel="noopener">IN</a>'
        + '<a class="brp-sh" href="https://twitter.com/intent/tweet?url='+url+'&text='+txt+'" target="_blank" rel="noopener">X</a>'
        + '<a class="brp-sh" href="mailto:?subject='+txt+'&body='+url+'" target="_blank" rel="noopener">Email</a>'
        + '<button class="brp-copy" data-link="'+link+'">Copy</button>'
        + '</div>';

      // Append into the card footer area (theme has .brpt-card-footer)
      $card.find('.brpt-card-footer').append(html);
    });
  }
});
