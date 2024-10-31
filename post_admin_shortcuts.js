jQuery(document).ready(function() { 
	var $ = jQuery;
	$.post_admin_shortcuts = { 
		init: 	function() {  
					$("a.pin_link").click( function(e) {
						e.preventDefault();
						
						$this = $(this);
						
						$this.addClass('loading');
						
						$post_id = $this.attr('post:id');
						
						$post_type = $this.attr('post:type');
						
						if ($post_type == "post") { $post_type = "posts"; }
						else if ($post_type == "page") { $post_type = "pages"; }
						else { $post_type = "posts-"+$post_type; }
						
						//alert($post_id + '::' + $post_type);
						
						var data = {
							action: 'toggle_shortcut',
							post_id: $post_id,
							pinned: $this.hasClass('pinned')
						};

						jQuery.post(ajaxurl, data, function(response) {
							$this.removeClass('loading');
							if (response=='UNPIN') {
								$this.removeClass('pinned');
								var $img = jQuery("#pin_"+$post_id);
								$img.parents("li:first").css({backgroundColor: '#fdd'}).slideUp(function() { jQuery(this).remove(); });
							} else {
								$this.addClass('pinned');
								var $li = jQuery(response);
								$li.hide().appendTo("#menu-"+$post_type+" ul").slideDown();
								var origColor = $li.css("backgroundColor"); 
								$li.css({backgroundColor: '#ffa'}); 
								setTimeout(function() { 
									$li.animate({backgroundColor: origColor}, 1000); 
								}, 1000); 
							}
						});						
					});
					return false; 
				}
	}; //End of post_admin_shortcuts 
	
	$.post_admin_shortcuts.init(); 
});