(function($){
  jQuery(function(){
  	
  	/************* Main page *******************/
  	
  	// by default close the cta sections
	$('#ns-cloner-section-copy_tables_cta').toggleClass('closed');
	$('#ns-cloner-section-copy_users_cta').toggleClass('closed');
	$('#ns-cloner-section-copy_files_cta').toggleClass('closed');
	$('#ns-cloner-section-search_replace_cta').toggleClass('closed');
  	
  	// close report when close button is clicked
  	$('input.ns-cloner-close-report').click(function(){
  		$('.ns-cloner-report').fadeOut();
  	});
  	
  	// set up action when clone mode select is changed
  	$('.ns-cloner-select-mode').change(function(){
  		$('.ns-cloner-form').trigger('ns_cloner_form_refresh');
  	});
  	
  	// set up action when source site is changed
  	$('select[name=source_id]').change(function(){
  		$('.ns-cloner-form').trigger('ns_cloner_source_refresh');
  		$(this).prevAll('.ns-cloner-site-search').val('');
  	});
  	
  	// make section slide up / down when section toggle is clicked
  	$('.ns-cloner-section-header').click(function(){
  		$(this).parents('.ns-cloner-section').not('#ns-cloner-section-modes').toggleClass('closed');
  	});
  	
  	// make all sections slide up / down when all toggles is clicked
  	$('.ns-cloner-collapse-all').click(function(){
  		var $sections = $('.ns-cloner-section').not('#ns-cloner-section-modes'); 
  		$sections.addClass('closed');
  	});
  	$('.ns-cloner-expand-all').click(function(){
  		var $sections = $('.ns-cloner-section').not('#ns-cloner-section-modes'); 
  		$sections.removeClass('closed');
  	});
  	
  	// show copy logs box before going to support, then close copy logs box once continue button is clicked
  	$('.ns-support-widget a').click(function(e){
  		$('.ns-cloner-copy-logs').fadeIn();
  		e.preventDefault();
  	});
  	$('.ns-cloner-copy-logs-content a').click(function(){
  		$('.ns-cloner-copy-logs').fadeOut();
  	});
  	$('.ns-cloner-copy-logs').click(function(e){
  		if( e.target === this ){
  			$(this).fadeOut();
  		}
  	});
  	
  	// position clone button fixed 
  	$(window).on("load resize",function(){
  		$('.ns-cloner-button-wrapper').css({
  			position : 'fixed',
  			bottom: '.7em',
  			left : $('.ns-cloner-wrapper').offset().left, // position left so it matches up with rest of cloner items
  			width: $('.ns-cloner-wrapper').width() - 36 // set width to size of cloner ui and subtract 1em padding + .7em padding on right side of sidebar
  		});
  	});
  	
  	// update ui when refresh is triggered by changed setting, etc.
  	$('.ns-cloner-form').on('ns_cloner_form_refresh',function(){
  		var $mode_selector = $('.ns-cloner-select-mode');
  		var $selected_option = $mode_selector.children('option[value='+$mode_selector.val()+']');
  		// show correct metaboxes
  		var mode_slug = $mode_selector.val();
  		var mode_description = $selected_option.attr('data-description');
  		var mode_button_text = $selected_option.attr('data-button-text');
  		$('.ns-cloner-section').filter(':not([data-modes~='+mode_slug+'])').not('#ns-cloner-section-modes').slideUp().promise().done(function(){
  			$('.ns-cloner-section').filter('[data-modes~='+mode_slug+']').slideDown().promise().done(function(){
  				$('.ns-cloner-form').trigger('ns_cloner.form_refresh');
  			});
  		});
  		// show correct description for current mode
  		$('.ns-cloner-mode-description').fadeOut(function(){
  			$(this).html( mode_description ).fadeIn();
  		});
  		// hide/remove all preview step labels in button at bottom and replace with one from each active section that has a step phrase like "Create Site"
  		$('.ns-cloner-button-steps').children().addClass('going-away').animate({width:0},{duration:500,complete:function(){
  			$(this).remove();
	  	}});
  		$('.ns-cloner-section[data-modes~='+mode_slug+']:not([data-button-step=""])').each(function(){
  			var button_step_text = $(this).attr('data-button-step');
  			if( button_step_text ){
  				$('<span/>').css({'width':0}).text(button_step_text).addClass('ns-cloner-button-steps-item').appendTo('.ns-cloner-button-steps');
  			}
  		});
  		// show new step labels and set submit button text
  		var step_count = 0;
  		$('.ns-cloner-button-steps').children(':not(.going-away)').delay(500).animateAuto('width',400,function(){
  			step_count++;
  			if( step_count==$('.ns-cloner-button-steps').children(':not(.going-away)').length ){
  				$('.ns-cloner-form, .ns-cloner-sidebar').css( 'padding-bottom', $('.ns-cloner-button-wrapper').height()*.6 );
  			}
  		});
  		$('.ns-cloner-button').val( mode_button_text );
  	});  	
  	
  	// validate and either show errors or submit form when clone button is clicked
  	$('input.ns-cloner-button[type=submit]').click(function(){
  		var $form = $('.ns-cloner-form');
  		var $button = $(this);
  		// don't let it get submitted again if it's already in process
  		if( $button.data('current_action') ){
  			return false;
  		}
  		// but if it's not in process, change text to indicate and set current action from button text
  		else{
  			$button.data( 'current_action', $button.val() ).val('Working...').css('cursor','default');
  		}
  		// remove old error messages
  		$form.find('.ns-cloner-error-message').remove();
  		// send validation ajax request
  		$.post(
  			$form.attr('action').replace('process','ajax_validate'),
  			$form.serialize(),
  			function( response ){
  				// validation was successful so submit
  				if( response.status=='success' ){
  					$form.submit();
  				}
  				// it was not, and errors were returned so show them
  				else if( response.status=='error' && response.messages.length>0 ){
  					// add error message html for each message returned - prepended to section if specified or just main form if not
  					$.each( response.messages, function(index,item){
  						$section = $('#ns-cloner-section-'+item.section);
  						$error_display_location = ($section.length > 0) ? $section.find('.ns-cloner-section-content') : $form;
  						$('<span class="ns-cloner-error-message"></span>').text( item.message ).prependTo( $error_display_location );
  					});
  					// scroll up to the first error message on the page, minus 40px (higher on page) for the admin bar plus a little extra padding
  					var first_error_message_scroll_location = $('.ns-cloner-error-message:first').offset().top - 40;
  					$('html,body').animate({scrollTop:first_error_message_scroll_location});
  					// set button back to be clickable again for after they fix errors
  					$button.val( $button.data('current_action') ).css('cursor','pointer');
  					$button.removeData();  					
  				}
  				// something weird fell through if it hits here - shouldn't other than maybe a connection error
  				else{
  					alert('Sorry, an unidentified error occured. Please refresh and try again.');
  					// set button back to be clickable again for after they fix errors
  					$button.val( $button.data('current_action') ).css('cursor','pointer');
  					$button.removeData();  	
  				}
  			}
  		).fail(function(){
  			alert('Sorry, an unidentified error occured. Please refresh try again.');
  		});
  	});

	// add autocomplete for search box
	$('.ns-cloner-site-search').autocomplete({
		source: ns_cloner.ajaxurl + '?action=ns_cloner_search_sites&clone_nonce=' + ns_cloner.nonce,
		search: function( e, ui ){
			$(this).css('background','white url('+ns_cloner.loadingimg+') 99% 50% no-repeat');
		},
		response: function( e, ui ){
			$(this).css('background-image','none');
		},
		select: function( e, ui ){
			$(this).nextAll('.ns-cloner-site-select').val( ui.item.value ).trigger('change');
			$(this).nextAll('.button-primary').focus();
			$(this).val( ui.item.label.match(/\((http.*)\)/)[1] );
			return false;
		}
	});
	
  	// turn on repeaters
  	$('.ns-repeater').nsRepeater();
  	
  	// trigger setup
  	$(window).load(function(){
  		$('.ns-cloner-form').trigger('ns_cloner_form_refresh').trigger('ns_cloner_source_refresh');
  	});
  	
  	/************* Addons page *****************/
  	
  	// toggle grid/list display when icons are clicked
  	$('.ns-cloner-addons-display-grid').click(function(){
  		if( !$(this).hasClass('active') ){
	  		$(this).addClass('active').next().removeClass('active');
	  		$('.ns-cloner-addons').fadeOut(function(){
	  			$(this).addClass('grid').fadeIn();
	  		});
  		}
  	});
  	$('.ns-cloner-addons-display-list').click(function(){
  		if( !$(this).hasClass('active') ){
	  		$(this).addClass('active').prev().removeClass('active');
	  		$('.ns-cloner-addons').fadeOut(function(){
	  			$(this).removeClass('grid').fadeIn();
	  		});
  		}
  	});
  	
  });
})(jQuery);

jQuery.fn.animateAuto = function(prop, speed, callback){
    var elem, height, width;
    return this.each(function(i, el){
        el = jQuery(el), elem = el.clone().css({"height":"auto","width":"auto"}).appendTo("body");
        height = elem.css("height"),
        width = elem.css("width"),
        elem.remove();        
        if(prop === "height")
            el.animate({"height":height}, speed, callback);
        else if(prop === "width")
            el.animate({"width":width}, speed, callback);  
        else if(prop === "both")
            el.animate({"width":width,"height":height}, speed, callback);
    });  
};

jQuery.fn.nsRepeater = function(){
	this.on( 'click', '.ns-repeater-remove', function(){
		var repeater = jQuery(this).parents('.ns-repeater');
		if( repeater.find('li').length > 1 ){
			jQuery(this).parent('li').remove();
		}
		else{
			jQuery(this).parent('li').hide().find('textarea,input,select').removeAttr('checked selected').val('');
		}
	});
	this.next('.ns-repeater-add').click(function(){
		var repeater = jQuery(this).prev('.ns-repeater');
		var field = repeater.find('li:last').clone();
		field.show().find('textarea,input,select').removeAttr('checked selected').val('');
		repeater.append(field);
	});
};

/* Placeholders.js v3.0.2 */
(function(t){"use strict";function e(t,e,r){return t.addEventListener?t.addEventListener(e,r,!1):t.attachEvent?t.attachEvent("on"+e,r):void 0}function r(t,e){var r,n;for(r=0,n=t.length;n>r;r++)if(t[r]===e)return!0;return!1}function n(t,e){var r;t.createTextRange?(r=t.createTextRange(),r.move("character",e),r.select()):t.selectionStart&&(t.focus(),t.setSelectionRange(e,e))}function a(t,e){try{return t.type=e,!0}catch(r){return!1}}t.Placeholders={Utils:{addEventListener:e,inArray:r,moveCaret:n,changeType:a}}})(this),function(t){"use strict";function e(){}function r(){try{return document.activeElement}catch(t){}}function n(t,e){var r,n,a=!!e&&t.value!==e,u=t.value===t.getAttribute(V);return(a||u)&&"true"===t.getAttribute(P)?(t.removeAttribute(P),t.value=t.value.replace(t.getAttribute(V),""),t.className=t.className.replace(R,""),n=t.getAttribute(z),parseInt(n,10)>=0&&(t.setAttribute("maxLength",n),t.removeAttribute(z)),r=t.getAttribute(D),r&&(t.type=r),!0):!1}function a(t){var e,r,n=t.getAttribute(V);return""===t.value&&n?(t.setAttribute(P,"true"),t.value=n,t.className+=" "+I,r=t.getAttribute(z),r||(t.setAttribute(z,t.maxLength),t.removeAttribute("maxLength")),e=t.getAttribute(D),e?t.type="text":"password"===t.type&&K.changeType(t,"text")&&t.setAttribute(D,"password"),!0):!1}function u(t,e){var r,n,a,u,i,l,o;if(t&&t.getAttribute(V))e(t);else for(a=t?t.getElementsByTagName("input"):f,u=t?t.getElementsByTagName("textarea"):h,r=a?a.length:0,n=u?u.length:0,o=0,l=r+n;l>o;o++)i=r>o?a[o]:u[o-r],e(i)}function i(t){u(t,n)}function l(t){u(t,a)}function o(t){return function(){b&&t.value===t.getAttribute(V)&&"true"===t.getAttribute(P)?K.moveCaret(t,0):n(t)}}function c(t){return function(){a(t)}}function s(t){return function(e){return A=t.value,"true"===t.getAttribute(P)&&A===t.getAttribute(V)&&K.inArray(C,e.keyCode)?(e.preventDefault&&e.preventDefault(),!1):void 0}}function d(t){return function(){n(t,A),""===t.value&&(t.blur(),K.moveCaret(t,0))}}function v(t){return function(){t===r()&&t.value===t.getAttribute(V)&&"true"===t.getAttribute(P)&&K.moveCaret(t,0)}}function g(t){return function(){i(t)}}function p(t){t.form&&(T=t.form,"string"==typeof T&&(T=document.getElementById(T)),T.getAttribute(U)||(K.addEventListener(T,"submit",g(T)),T.setAttribute(U,"true"))),K.addEventListener(t,"focus",o(t)),K.addEventListener(t,"blur",c(t)),b&&(K.addEventListener(t,"keydown",s(t)),K.addEventListener(t,"keyup",d(t)),K.addEventListener(t,"click",v(t))),t.setAttribute(j,"true"),t.setAttribute(V,x),(b||t!==r())&&a(t)}var f,h,b,m,A,y,E,x,L,T,S,N,w,B=["text","search","url","tel","email","password","number","textarea"],C=[27,33,34,35,36,37,38,39,40,8,46],k="#ccc",I="placeholdersjs",R=RegExp("(?:^|\\s)"+I+"(?!\\S)"),V="data-placeholder-value",P="data-placeholder-active",D="data-placeholder-type",U="data-placeholder-submit",j="data-placeholder-bound",q="data-placeholder-focus",Q="data-placeholder-live",z="data-placeholder-maxlength",F=document.createElement("input"),G=document.getElementsByTagName("head")[0],H=document.documentElement,J=t.Placeholders,K=J.Utils;if(J.nativeSupport=void 0!==F.placeholder,!J.nativeSupport){for(f=document.getElementsByTagName("input"),h=document.getElementsByTagName("textarea"),b="false"===H.getAttribute(q),m="false"!==H.getAttribute(Q),y=document.createElement("style"),y.type="text/css",E=document.createTextNode("."+I+" { color:"+k+"; }"),y.styleSheet?y.styleSheet.cssText=E.nodeValue:y.appendChild(E),G.insertBefore(y,G.firstChild),w=0,N=f.length+h.length;N>w;w++)S=f.length>w?f[w]:h[w-f.length],x=S.attributes.placeholder,x&&(x=x.nodeValue,x&&K.inArray(B,S.type)&&p(S));L=setInterval(function(){for(w=0,N=f.length+h.length;N>w;w++)S=f.length>w?f[w]:h[w-f.length],x=S.attributes.placeholder,x?(x=x.nodeValue,x&&K.inArray(B,S.type)&&(S.getAttribute(j)||p(S),(x!==S.getAttribute(V)||"password"===S.type&&!S.getAttribute(D))&&("password"===S.type&&!S.getAttribute(D)&&K.changeType(S,"text")&&S.setAttribute(D,"password"),S.value===S.getAttribute(V)&&(S.value=x),S.setAttribute(V,x)))):S.getAttribute(P)&&(n(S),S.removeAttribute(V));m||clearInterval(L)},100)}K.addEventListener(t,"beforeunload",function(){J.disable()}),J.disable=J.nativeSupport?e:i,J.enable=J.nativeSupport?e:l}(this),function(t){"use strict";var e=t.fn.val,r=t.fn.prop;Placeholders.nativeSupport||(t.fn.val=function(t){var r=e.apply(this,arguments),n=this.eq(0).data("placeholder-value");return void 0===t&&this.eq(0).data("placeholder-active")&&r===n?"":r},t.fn.prop=function(t,e){return void 0===e&&this.eq(0).data("placeholder-active")&&"value"===t?"":r.apply(this,arguments)})}(jQuery);
