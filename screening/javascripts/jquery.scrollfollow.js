/**
 * jquery.scrollFollow.js
 * Copyright (c) 2008 Net Perspective (http://kitchen.net-perspective.com/)
 * Licensed under the MIT License (http://www.opensource.org/licenses/mit-license.php)
 * 
 * @author R.A. Ray
 *
 * @projectDescription	jQuery plugin for allowing an element to animate down as the user scrolls the page.
 * 
 * @version 0.4.0
 * 
 * @requires jquery.js (tested with 1.2.6)
 * @requires ui.core.js (tested with 1.5.2)
 * 
 * @optional jquery.cookie.js (http://www.stilbuero.de/2006/09/17/cookie-plugin-for-jquery/)
 * @optional jquery.easing.js (http://gsgd.co.uk/sandbox/jquery/easing/ - tested with 1.3)
 * 
 * @param speed		int - Duration of animation (in milliseconds)
 * 								default: 500
 * @param offset			int - Number of pixels box should remain from top of viewport
 * 								default: 0
 * @param easing		string - Any one of the easing options from the easing plugin - Requires jQuery Easing Plugin < http://gsgd.co.uk/sandbox/jquery/easing/ >
 * 								default: 'linear'
 * @param container	string - ID of the containing div
 * 								default: box's immediate parent
 * @param killSwitch	string - ID of the On/Off toggle element
 * 								default: 'killSwitch'
 * @param onText		string - killSwitch text to be displayed if sliding is enabled
 * 								default: 'Turn Slide Off'
 * @param offText		string - killSwitch text to be displayed if sliding is disabled
 * 								default: 'Turn Slide On'
 * @param relativeTo	string - Scroll animation can be relative to either the 'top' or 'bottom' of the viewport
 * 								default: 'top'
 * @param delay			int - Time between the end of the scroll and the beginning of the animation in milliseconds
 * 								default: 0
 */

( function( $ ) {
	
	$.scrollFollow = function ( box, options )
	{ 
		// Convert box into a jQuery object
		box = $( box );
		
		// 'box' is the object to be animated
		var position = box.css( 'position' );
		
		function ani()
		{		
			// The script runs on every scroll which really means many times during a scroll.
			// We don't want multiple slides to queue up.
			box.queue( [ ] );
		
			// A bunch of values we need to determine where to animate to
			var viewportHeight = parseInt( $( window ).height() );	
			var pageScroll =  parseInt( $( document ).scrollTop() );
			var parentTop =  parseInt( box.cont.offset().top );
			var parentHeight = parseInt( box.cont.attr( 'offsetHeight' ) );
			var boxHeight = parseInt( box.attr( 'offsetHeight' ) + ( parseInt( box.css( 'marginTop' ) ) || 0 ) + ( parseInt( box.css( 'marginBottom' ) ) || 0 ) );
			var aniTop;
			
			// Make sure the user wants the animation to happen
			if ( isActive )
			{
				// If the box should animate relative to the top of the window
				if ( options.relativeTo == 'top' )
				{
					// Don't animate until the top of the window is close enough to the top of the box
					if ( box.initialOffsetTop >= ( pageScroll + options.offset ) )
					{
						aniTop = box.initialTop;
					}
					else
					{
						aniTop = Math.min( ( Math.max( ( -parentTop ), ( pageScroll - box.initialOffsetTop + box.initialTop ) ) + options.offset ), ( parentHeight - boxHeight - box.paddingAdjustment ) );
					}
				}
				// If the box should animate relative to the bottom of the window
				else if ( options.relativeTo == 'bottom' )
				{
					// Don't animate until the bottom of the window is close enough to the bottom of the box
					if ( ( box.initialOffsetTop + boxHeight ) >= ( pageScroll + options.offset + viewportHeight ) )
					{
						aniTop = box.initialTop;
					}
					else
					{
						aniTop = Math.min( ( pageScroll + viewportHeight - boxHeight - options.offset ), ( parentHeight - boxHeight ) );
					}
				}
				
				// Checks to see if the relevant scroll was the last one
				// "-20" is to account for inaccuracy in the timeout
				if ( ( new Date().getTime() - box.lastScroll ) >= ( options.delay - 20 ) )
				{
					box.animate(
						{
							top: aniTop
						}, options.speed, options.easing
					);
				}
			}
		};
		
		// For user-initiated stopping of the slide
		var isActive = true;
		
		if ( $.cookie != undefined )
		{
			if( $.cookie( 'scrollFollowSetting' + box.attr( 'id' ) ) == 'false' )
			{
				var isActive = false;
				
				$( '#' + options.killSwitch ).text( options.offText )
					.toggle( 
						function ()
						{
							isActive = true;
							
							$( this ).text( options.onText );
							
							$.cookie( 'scrollFollowSetting' + box.attr( 'id' ), true, { expires: 365, path: '/'} );
							
							ani();
						},
						function ()
						{
							isActive = false;
							
							$( this ).text( options.offText );
							
							box.animate(
								{
									top: box.initialTop
								}, options.speed, options.easing
							);	
							
							$.cookie( 'scrollFollowSetting' + box.attr( 'id' ), false, { expires: 365, path: '/'} );
						}
					);
			}
			else
			{
				$( '#' + options.killSwitch ).text( options.onText )
					.toggle( 
						function ()
						{
							isActive = false;
							
							$( this ).text( options.offText );
							
							box.animate(
								{
									top: box.initialTop
								}, 0
							);	
							
							$.cookie( 'scrollFollowSetting' + box.attr( 'id' ), false, { expires: 365, path: '/'} );
						},
						function ()
						{
							isActive = true;
							
							$( this ).text( options.onText );
							
							$.cookie( 'scrollFollowSetting' + box.attr( 'id' ), true, { expires: 365, path: '/'} );
							
							ani();
						}
					);
			}
		}
		
		// If no parent ID was specified, and the immediate parent does not have an ID
		// options.container will be undefined. So we need to figure out the parent element.
		if ( options.container == '')
		{
			box.cont = box.parent();
		}
		else
		{
			box.cont = $( '#' + options.container );
		}
		
		// Finds the default positioning of the box.
		box.initialOffsetTop =  parseInt( box.offset().top );
		box.initialTop = parseInt( box.css( 'top' ) ) || 0;
		
		// Hack to fix different treatment of boxes positioned 'absolute' and 'relative'
		if ( box.css( 'position' ) == 'relative' )
		{
			box.paddingAdjustment = parseInt( box.cont.css( 'paddingTop' ) ) + parseInt( box.cont.css( 'paddingBottom' ) );
		}
		else
		{
			box.paddingAdjustment = 0;
		}
		
		// Animate the box when the page is scrolled
		$( window ).scroll( function ()
			{
				// Sets up the delay of the animation
				$.fn.scrollFollow.interval = setTimeout( function(){ ani();} , options.delay );
				
				// To check against right before setting the animation
				box.lastScroll = new Date().getTime();
			}
		);
		
		// Animate the box when the page is resized
		$( window ).resize( function ()
			{
				// Sets up the delay of the animation
				$.fn.scrollFollow.interval = setTimeout( function(){ ani();} , options.delay );
				
				// To check against right before setting the animation
				box.lastScroll = new Date().getTime();
			}
		);

		// Run an initial animation on page load
		box.lastScroll = 0;
		
		ani();
	};
	
	$.fn.scrollFollow = function ( options )
	{
		options = options || {};
		options.relativeTo = options.relativeTo || 'top';
		options.speed = options.speed || 500;
		options.offset = options.offset || 0;
		options.easing = options.easing || 'swing';
		options.container = options.container || this.parent().attr( 'id' );
		options.killSwitch = options.killSwitch || 'killSwitch';
		options.onText = options.onText || 'Turn Slide Off';
		options.offText = options.offText || 'Turn Slide On';
		options.delay = options.delay || 0;
		
		this.each( function() 
			{
				new $.scrollFollow( this, options );
			}
		);
		
		return this;
	};
})( jQuery );
;(function(d){var k=d.scrollTo=function(a,i,e){d(window).scrollTo(a,i,e)};k.defaults={axis:'xy',duration:parseFloat(d.fn.jquery)>=1.3?0:1};k.window=function(a){return d(window)._scrollable()};d.fn._scrollable=function(){return this.map(function(){var a=this,i=!a.nodeName||d.inArray(a.nodeName.toLowerCase(),['iframe','#document','html','body'])!=-1;if(!i)return a;var e=(a.contentWindow||a).document||a.ownerDocument||a;return d.browser.safari||e.compatMode=='BackCompat'?e.body:e.documentElement})};d.fn.scrollTo=function(n,j,b){if(typeof j=='object'){b=j;j=0}if(typeof b=='function')b={onAfter:b};if(n=='max')n=9e9;b=d.extend({},k.defaults,b);j=j||b.speed||b.duration;b.queue=b.queue&&b.axis.length>1;if(b.queue)j/=2;b.offset=p(b.offset);b.over=p(b.over);return this._scrollable().each(function(){var q=this,r=d(q),f=n,s,g={},u=r.is('html,body');switch(typeof f){case'number':case'string':if(/^([+-]=)?\d+(\.\d+)?(px|%)?$/.test(f)){f=p(f);break}f=d(f,this);case'object':if(f.is||f.style)s=(f=d(f)).offset()}d.each(b.axis.split(''),function(a,i){var e=i=='x'?'Left':'Top',h=e.toLowerCase(),c='scroll'+e,l=q[c],m=k.max(q,i);if(s){g[c]=s[h]+(u?0:l-r.offset()[h]);if(b.margin){g[c]-=parseInt(f.css('margin'+e))||0;g[c]-=parseInt(f.css('border'+e+'Width'))||0}g[c]+=b.offset[h]||0;if(b.over[h])g[c]+=f[i=='x'?'width':'height']()*b.over[h]}else{var o=f[h];g[c]=o.slice&&o.slice(-1)=='%'?parseFloat(o)/100*m:o}if(/^\d+$/.test(g[c]))g[c]=g[c]<=0?0:Math.min(g[c],m);if(!a&&b.queue){if(l!=g[c])t(b.onAfterFirst);delete g[c]}});t(b.onAfter);function t(a){r.animate(g,j,b.easing,a&&function(){a.call(this,n,b)})}}).end()};k.max=function(a,i){var e=i=='x'?'Width':'Height',h='scroll'+e;if(!d(a).is('html,body'))return a[h]-d(a)[e.toLowerCase()]();var c='client'+e,l=a.ownerDocument.documentElement,m=a.ownerDocument.body;return Math.max(l[h],m[h])-Math.min(l[c],m[c])};function p(a){return typeof a=='object'?a:{top:a,left:a}}})(jQuery);


