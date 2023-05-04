(window["webpackJsonp"]=window["webpackJsonp"]||[]).push([["chunk-28474e70"],{"7da8":function(t,e,i){},"8fe0":function(t,e,i){"use strict";i("a481");e["a"]={methods:{calcLastReadingColor:function(t){var e="black";return"undefined"!==typeof t.date_diff&&"N/A"!=t.date_diff&&(t.date_diff.h<3&&(e="green"),t.date_diff.h>=3&&t.date_diff.h<6&&(e="orange"),t.date_diff.h>=6&&(e="red"),(t.date_diff.d>0||t.date_diff.days>0)&&(e="red"),(t.date_diff.y>0||t.date_diff.m>0||t.date_diff.d>0||t.date_diff.days>0)&&(e="red")),e},isAdmin:function(){var t=arguments.length>0&&void 0!==arguments[0]?arguments[0]:"";return this.$store.getters.isAdmin(t)},isDistributor:function(){var t=arguments.length>0&&void 0!==arguments[0]?arguments[0]:"";return this.$store.getters.isDistributor(t)},isRestricted:function(){return this.$store.getters.isRestricted()},userCan:function(t,e){var i=arguments.length>2&&void 0!==arguments[2]?arguments[2]:null,r=arguments.length>3&&void 0!==arguments[3]?arguments[3]:"";return this.$store.getters.userCan(t,e,i,r)},userLimits:function(t,e){var i=arguments.length>2&&void 0!==arguments[2]?arguments[2]:"";return this.$store.getters.userLimits(t,e,i)},convertNodeTypeToGraphRouteName:function(t){return"Soil Moisture"==t?"soil_moisture_graph":"Nutrients"==t?"nutrients_graph":"Wells"==t?"well_controls_graph":"Water Meter"==t?"meters_graph":""},convertNodeTypeToSubsystem:function(t){return"Soil Moisture"==t?"Soil Moisture":"Nutrients"==t?"Nutrients":"Wells"==t?"Well Controls":"Water Meter"==t?"Meters":""},convertToInches:function(t){return t=t.replace("mm",""),t=parseInt(parseInt(t)/25)+'"',t},convertToIndex:function(t){return t=t.replace("mm",""),t=parseInt(parseInt(t)/100),t},truncateString:function(t){return t.length<=24?t:t.substring(0,21)+"..."}}}},b7f5:function(t,e,i){"use strict";
/*!
 * perfect-scrollbar v1.5.3
 * Copyright 2021 Hyunje Jun, MDBootstrap and Contributors
 * Licensed under MIT
 */function r(t){return getComputedStyle(t)}function n(t,e){for(var i in e){var r=e[i];"number"===typeof r&&(r+="px"),t.style[i]=r}return t}function l(t){var e=document.createElement("div");return e.className=t,e}var o="undefined"!==typeof Element&&(Element.prototype.matches||Element.prototype.webkitMatchesSelector||Element.prototype.mozMatchesSelector||Element.prototype.msMatchesSelector);function s(t,e){if(!o)throw new Error("No element matching method supported");return o.call(t,e)}function a(t){t.remove?t.remove():t.parentNode&&t.parentNode.removeChild(t)}function c(t,e){return Array.prototype.filter.call(t.children,(function(t){return s(t,e)}))}var h={main:"ps",rtl:"ps__rtl",element:{thumb:function(t){return"ps__thumb-"+t},rail:function(t){return"ps__rail-"+t},consuming:"ps__child--consume"},state:{focus:"ps--focus",clicking:"ps--clicking",active:function(t){return"ps--active-"+t},scrolling:function(t){return"ps--scrolling-"+t}}},u={x:null,y:null};function d(t,e){var i=t.element.classList,r=h.state.scrolling(e);i.contains(r)?clearTimeout(u[e]):i.add(r)}function f(t,e){u[e]=setTimeout((function(){return t.isAlive&&t.element.classList.remove(h.state.scrolling(e))}),t.settings.scrollingThreshold)}function p(t,e){d(t,e),f(t,e)}var g=function(t){this.element=t,this.handlers={}},b={isEmpty:{configurable:!0}};g.prototype.bind=function(t,e){"undefined"===typeof this.handlers[t]&&(this.handlers[t]=[]),this.handlers[t].push(e),this.element.addEventListener(t,e,!1)},g.prototype.unbind=function(t,e){var i=this;this.handlers[t]=this.handlers[t].filter((function(r){return!(!e||r===e)||(i.element.removeEventListener(t,r,!1),!1)}))},g.prototype.unbindAll=function(){for(var t in this.handlers)this.unbind(t)},b.isEmpty.get=function(){var t=this;return Object.keys(this.handlers).every((function(e){return 0===t.handlers[e].length}))},Object.defineProperties(g.prototype,b);var v=function(){this.eventElements=[]};function m(t){if("function"===typeof window.CustomEvent)return new CustomEvent(t);var e=document.createEvent("CustomEvent");return e.initCustomEvent(t,!1,!1,void 0),e}function Y(t,e,i,r,n){var l;if(void 0===r&&(r=!0),void 0===n&&(n=!1),"top"===e)l=["contentHeight","containerHeight","scrollTop","y","up","down"];else{if("left"!==e)throw new Error("A proper axis should be provided");l=["contentWidth","containerWidth","scrollLeft","x","left","right"]}w(t,i,l,r,n)}function w(t,e,i,r,n){var l=i[0],o=i[1],s=i[2],a=i[3],c=i[4],h=i[5];void 0===r&&(r=!0),void 0===n&&(n=!1);var u=t.element;t.reach[a]=null,u[s]<1&&(t.reach[a]="start"),u[s]>t[l]-t[o]-1&&(t.reach[a]="end"),e&&(u.dispatchEvent(m("ps-scroll-"+a)),e<0?u.dispatchEvent(m("ps-scroll-"+c)):e>0&&u.dispatchEvent(m("ps-scroll-"+h)),r&&p(t,a)),t.reach[a]&&(e||n)&&u.dispatchEvent(m("ps-"+a+"-reach-"+t.reach[a]))}function y(t){return parseInt(t,10)||0}function X(t){return s(t,"input,[contenteditable]")||s(t,"select,[contenteditable]")||s(t,"textarea,[contenteditable]")||s(t,"button,[contenteditable]")}function W(t){var e=r(t);return y(e.width)+y(e.paddingLeft)+y(e.paddingRight)+y(e.borderLeftWidth)+y(e.borderRightWidth)}v.prototype.eventElement=function(t){var e=this.eventElements.filter((function(e){return e.element===t}))[0];return e||(e=new g(t),this.eventElements.push(e)),e},v.prototype.bind=function(t,e,i){this.eventElement(t).bind(e,i)},v.prototype.unbind=function(t,e,i){var r=this.eventElement(t);r.unbind(e,i),r.isEmpty&&this.eventElements.splice(this.eventElements.indexOf(r),1)},v.prototype.unbindAll=function(){this.eventElements.forEach((function(t){return t.unbindAll()})),this.eventElements=[]},v.prototype.once=function(t,e,i){var r=this.eventElement(t),n=function(t){r.unbind(e,n),i(t)};r.bind(e,n)};var L={isWebKit:"undefined"!==typeof document&&"WebkitAppearance"in document.documentElement.style,supportsTouch:"undefined"!==typeof window&&("ontouchstart"in window||"maxTouchPoints"in window.navigator&&window.navigator.maxTouchPoints>0||window.DocumentTouch&&document instanceof window.DocumentTouch),supportsIePointer:"undefined"!==typeof navigator&&navigator.msMaxTouchPoints,isChrome:"undefined"!==typeof navigator&&/Chrome/i.test(navigator&&navigator.userAgent)};function R(t){var e=t.element,i=Math.floor(e.scrollTop),r=e.getBoundingClientRect();t.containerWidth=Math.round(r.width),t.containerHeight=Math.round(r.height),t.contentWidth=e.scrollWidth,t.contentHeight=e.scrollHeight,e.contains(t.scrollbarXRail)||(c(e,h.element.rail("x")).forEach((function(t){return a(t)})),e.appendChild(t.scrollbarXRail)),e.contains(t.scrollbarYRail)||(c(e,h.element.rail("y")).forEach((function(t){return a(t)})),e.appendChild(t.scrollbarYRail)),!t.settings.suppressScrollX&&t.containerWidth+t.settings.scrollXMarginOffset<t.contentWidth?(t.scrollbarXActive=!0,t.railXWidth=t.containerWidth-t.railXMarginWidth,t.railXRatio=t.containerWidth/t.railXWidth,t.scrollbarXWidth=T(t,y(t.railXWidth*t.containerWidth/t.contentWidth)),t.scrollbarXLeft=y((t.negativeScrollAdjustment+e.scrollLeft)*(t.railXWidth-t.scrollbarXWidth)/(t.contentWidth-t.containerWidth))):t.scrollbarXActive=!1,!t.settings.suppressScrollY&&t.containerHeight+t.settings.scrollYMarginOffset<t.contentHeight?(t.scrollbarYActive=!0,t.railYHeight=t.containerHeight-t.railYMarginHeight,t.railYRatio=t.containerHeight/t.railYHeight,t.scrollbarYHeight=T(t,y(t.railYHeight*t.containerHeight/t.contentHeight)),t.scrollbarYTop=y(i*(t.railYHeight-t.scrollbarYHeight)/(t.contentHeight-t.containerHeight))):t.scrollbarYActive=!1,t.scrollbarXLeft>=t.railXWidth-t.scrollbarXWidth&&(t.scrollbarXLeft=t.railXWidth-t.scrollbarXWidth),t.scrollbarYTop>=t.railYHeight-t.scrollbarYHeight&&(t.scrollbarYTop=t.railYHeight-t.scrollbarYHeight),H(e,t),t.scrollbarXActive?e.classList.add(h.state.active("x")):(e.classList.remove(h.state.active("x")),t.scrollbarXWidth=0,t.scrollbarXLeft=0,e.scrollLeft=!0===t.isRtl?t.contentWidth:0),t.scrollbarYActive?e.classList.add(h.state.active("y")):(e.classList.remove(h.state.active("y")),t.scrollbarYHeight=0,t.scrollbarYTop=0,e.scrollTop=0)}function T(t,e){return t.settings.minScrollbarLength&&(e=Math.max(e,t.settings.minScrollbarLength)),t.settings.maxScrollbarLength&&(e=Math.min(e,t.settings.maxScrollbarLength)),e}function H(t,e){var i={width:e.railXWidth},r=Math.floor(t.scrollTop);e.isRtl?i.left=e.negativeScrollAdjustment+t.scrollLeft+e.containerWidth-e.contentWidth:i.left=t.scrollLeft,e.isScrollbarXUsingBottom?i.bottom=e.scrollbarXBottom-r:i.top=e.scrollbarXTop+r,n(e.scrollbarXRail,i);var l={top:r,height:e.railYHeight};e.isScrollbarYUsingRight?e.isRtl?l.right=e.contentWidth-(e.negativeScrollAdjustment+t.scrollLeft)-e.scrollbarYRight-e.scrollbarYOuterWidth-9:l.right=e.scrollbarYRight-t.scrollLeft:e.isRtl?l.left=e.negativeScrollAdjustment+t.scrollLeft+2*e.containerWidth-e.contentWidth-e.scrollbarYLeft-e.scrollbarYOuterWidth:l.left=e.scrollbarYLeft+t.scrollLeft,n(e.scrollbarYRail,l),n(e.scrollbarX,{left:e.scrollbarXLeft,width:e.scrollbarXWidth-e.railBorderXWidth}),n(e.scrollbarY,{top:e.scrollbarYTop,height:e.scrollbarYHeight-e.railBorderYWidth})}function S(t){t.element;t.event.bind(t.scrollbarY,"mousedown",(function(t){return t.stopPropagation()})),t.event.bind(t.scrollbarYRail,"mousedown",(function(e){var i=e.pageY-window.pageYOffset-t.scrollbarYRail.getBoundingClientRect().top,r=i>t.scrollbarYTop?1:-1;t.element.scrollTop+=r*t.containerHeight,R(t),e.stopPropagation()})),t.event.bind(t.scrollbarX,"mousedown",(function(t){return t.stopPropagation()})),t.event.bind(t.scrollbarXRail,"mousedown",(function(e){var i=e.pageX-window.pageXOffset-t.scrollbarXRail.getBoundingClientRect().left,r=i>t.scrollbarXLeft?1:-1;t.element.scrollLeft+=r*t.containerWidth,R(t),e.stopPropagation()}))}function E(t){M(t,["containerWidth","contentWidth","pageX","railXWidth","scrollbarX","scrollbarXWidth","scrollLeft","x","scrollbarXRail"]),M(t,["containerHeight","contentHeight","pageY","railYHeight","scrollbarY","scrollbarYHeight","scrollTop","y","scrollbarYRail"])}function M(t,e){var i=e[0],r=e[1],n=e[2],l=e[3],o=e[4],s=e[5],a=e[6],c=e[7],u=e[8],p=t.element,g=null,b=null,v=null;function m(e){e.touches&&e.touches[0]&&(e[n]=e.touches[0].pageY),p[a]=g+v*(e[n]-b),d(t,c),R(t),e.stopPropagation(),e.type.startsWith("touch")&&e.changedTouches.length>1&&e.preventDefault()}function Y(){f(t,c),t[u].classList.remove(h.state.clicking),t.event.unbind(t.ownerDocument,"mousemove",m)}function w(e,o){g=p[a],o&&e.touches&&(e[n]=e.touches[0].pageY),b=e[n],v=(t[r]-t[i])/(t[l]-t[s]),o?t.event.bind(t.ownerDocument,"touchmove",m):(t.event.bind(t.ownerDocument,"mousemove",m),t.event.once(t.ownerDocument,"mouseup",Y),e.preventDefault()),t[u].classList.add(h.state.clicking),e.stopPropagation()}t.event.bind(t[o],"mousedown",(function(t){w(t)})),t.event.bind(t[o],"touchstart",(function(t){w(t,!0)}))}function A(t){var e=t.element,i=function(){return s(e,":hover")},r=function(){return s(t.scrollbarX,":focus")||s(t.scrollbarY,":focus")};function n(i,r){var n=Math.floor(e.scrollTop);if(0===i){if(!t.scrollbarYActive)return!1;if(0===n&&r>0||n>=t.contentHeight-t.containerHeight&&r<0)return!t.settings.wheelPropagation}var l=e.scrollLeft;if(0===r){if(!t.scrollbarXActive)return!1;if(0===l&&i<0||l>=t.contentWidth-t.containerWidth&&i>0)return!t.settings.wheelPropagation}return!0}t.event.bind(t.ownerDocument,"keydown",(function(l){if(!(l.isDefaultPrevented&&l.isDefaultPrevented()||l.defaultPrevented)&&(i()||r())){var o=document.activeElement?document.activeElement:t.ownerDocument.activeElement;if(o){if("IFRAME"===o.tagName)o=o.contentDocument.activeElement;else while(o.shadowRoot)o=o.shadowRoot.activeElement;if(X(o))return}var s=0,a=0;switch(l.which){case 37:s=l.metaKey?-t.contentWidth:l.altKey?-t.containerWidth:-30;break;case 38:a=l.metaKey?t.contentHeight:l.altKey?t.containerHeight:30;break;case 39:s=l.metaKey?t.contentWidth:l.altKey?t.containerWidth:30;break;case 40:a=l.metaKey?-t.contentHeight:l.altKey?-t.containerHeight:-30;break;case 32:a=l.shiftKey?t.containerHeight:-t.containerHeight;break;case 33:a=t.containerHeight;break;case 34:a=-t.containerHeight;break;case 36:a=t.contentHeight;break;case 35:a=-t.contentHeight;break;default:return}t.settings.suppressScrollX&&0!==s||t.settings.suppressScrollY&&0!==a||(e.scrollTop-=a,e.scrollLeft+=s,R(t),n(s,a)&&l.preventDefault())}}))}function P(t){var e=t.element;function i(i,r){var n,l=Math.floor(e.scrollTop),o=0===e.scrollTop,s=l+e.offsetHeight===e.scrollHeight,a=0===e.scrollLeft,c=e.scrollLeft+e.offsetWidth===e.scrollWidth;return n=Math.abs(r)>Math.abs(i)?o||s:a||c,!n||!t.settings.wheelPropagation}function n(t){var e=t.deltaX,i=-1*t.deltaY;return"undefined"!==typeof e&&"undefined"!==typeof i||(e=-1*t.wheelDeltaX/6,i=t.wheelDeltaY/6),t.deltaMode&&1===t.deltaMode&&(e*=10,i*=10),e!==e&&i!==i&&(e=0,i=t.wheelDelta),t.shiftKey?[-i,-e]:[e,i]}function l(t,i,n){if(!L.isWebKit&&e.querySelector("select:focus"))return!0;if(!e.contains(t))return!1;var l=t;while(l&&l!==e){if(l.classList.contains(h.element.consuming))return!0;var o=r(l);if(n&&o.overflowY.match(/(scroll|auto)/)){var s=l.scrollHeight-l.clientHeight;if(s>0&&(l.scrollTop>0&&n<0||l.scrollTop<s&&n>0))return!0}if(i&&o.overflowX.match(/(scroll|auto)/)){var a=l.scrollWidth-l.clientWidth;if(a>0&&(l.scrollLeft>0&&i<0||l.scrollLeft<a&&i>0))return!0}l=l.parentNode}return!1}function o(r){var o=n(r),s=o[0],a=o[1];if(!l(r.target,s,a)){var c=!1;t.settings.useBothWheelAxes?t.scrollbarYActive&&!t.scrollbarXActive?(a?e.scrollTop-=a*t.settings.wheelSpeed:e.scrollTop+=s*t.settings.wheelSpeed,c=!0):t.scrollbarXActive&&!t.scrollbarYActive&&(s?e.scrollLeft+=s*t.settings.wheelSpeed:e.scrollLeft-=a*t.settings.wheelSpeed,c=!0):(e.scrollTop-=a*t.settings.wheelSpeed,e.scrollLeft+=s*t.settings.wheelSpeed),R(t),c=c||i(s,a),c&&!r.ctrlKey&&(r.stopPropagation(),r.preventDefault())}}"undefined"!==typeof window.onwheel?t.event.bind(e,"wheel",o):"undefined"!==typeof window.onmousewheel&&t.event.bind(e,"mousewheel",o)}function x(t){if(L.supportsTouch||L.supportsIePointer){var e=t.element,i={},n=0,l={},o=null;L.supportsTouch?(t.event.bind(e,"touchstart",d),t.event.bind(e,"touchmove",p),t.event.bind(e,"touchend",g)):L.supportsIePointer&&(window.PointerEvent?(t.event.bind(e,"pointerdown",d),t.event.bind(e,"pointermove",p),t.event.bind(e,"pointerup",g)):window.MSPointerEvent&&(t.event.bind(e,"MSPointerDown",d),t.event.bind(e,"MSPointerMove",p),t.event.bind(e,"MSPointerUp",g)))}function s(i,r){var n=Math.floor(e.scrollTop),l=e.scrollLeft,o=Math.abs(i),s=Math.abs(r);if(s>o){if(r<0&&n===t.contentHeight-t.containerHeight||r>0&&0===n)return 0===window.scrollY&&r>0&&L.isChrome}else if(o>s&&(i<0&&l===t.contentWidth-t.containerWidth||i>0&&0===l))return!0;return!0}function a(i,r){e.scrollTop-=r,e.scrollLeft-=i,R(t)}function c(t){return t.targetTouches?t.targetTouches[0]:t}function u(t){return(!t.pointerType||"pen"!==t.pointerType||0!==t.buttons)&&(!(!t.targetTouches||1!==t.targetTouches.length)||!(!t.pointerType||"mouse"===t.pointerType||t.pointerType===t.MSPOINTER_TYPE_MOUSE))}function d(t){if(u(t)){var e=c(t);i.pageX=e.pageX,i.pageY=e.pageY,n=(new Date).getTime(),null!==o&&clearInterval(o)}}function f(t,i,n){if(!e.contains(t))return!1;var l=t;while(l&&l!==e){if(l.classList.contains(h.element.consuming))return!0;var o=r(l);if(n&&o.overflowY.match(/(scroll|auto)/)){var s=l.scrollHeight-l.clientHeight;if(s>0&&(l.scrollTop>0&&n<0||l.scrollTop<s&&n>0))return!0}if(i&&o.overflowX.match(/(scroll|auto)/)){var a=l.scrollWidth-l.clientWidth;if(a>0&&(l.scrollLeft>0&&i<0||l.scrollLeft<a&&i>0))return!0}l=l.parentNode}return!1}function p(t){if(u(t)){var e=c(t),r={pageX:e.pageX,pageY:e.pageY},o=r.pageX-i.pageX,h=r.pageY-i.pageY;if(f(t.target,o,h))return;a(o,h),i=r;var d=(new Date).getTime(),p=d-n;p>0&&(l.x=o/p,l.y=h/p,n=d),s(o,h)&&t.preventDefault()}}function g(){t.settings.swipeEasing&&(clearInterval(o),o=setInterval((function(){t.isInitialized?clearInterval(o):l.x||l.y?Math.abs(l.x)<.01&&Math.abs(l.y)<.01?clearInterval(o):t.element?(a(30*l.x,30*l.y),l.x*=.8,l.y*=.8):clearInterval(o):clearInterval(o)}),10))}}var k=function(){return{handlers:["click-rail","drag-thumb","keyboard","wheel","touch"],maxScrollbarLength:null,minScrollbarLength:null,scrollingThreshold:1e3,scrollXMarginOffset:0,scrollYMarginOffset:0,suppressScrollX:!1,suppressScrollY:!1,swipeEasing:!0,useBothWheelAxes:!1,wheelPropagation:!0,wheelSpeed:1}},_={"click-rail":S,"drag-thumb":E,keyboard:A,wheel:P,touch:x},D=function(t,e){var i=this;if(void 0===e&&(e={}),"string"===typeof t&&(t=document.querySelector(t)),!t||!t.nodeName)throw new Error("no element is specified to initialize PerfectScrollbar");for(var o in this.element=t,t.classList.add(h.main),this.settings=k(),e)this.settings[o]=e[o];this.containerWidth=null,this.containerHeight=null,this.contentWidth=null,this.contentHeight=null;var s=function(){return t.classList.add(h.state.focus)},a=function(){return t.classList.remove(h.state.focus)};this.isRtl="rtl"===r(t).direction,!0===this.isRtl&&t.classList.add(h.rtl),this.isNegativeScroll=function(){var e=t.scrollLeft,i=null;return t.scrollLeft=-1,i=t.scrollLeft<0,t.scrollLeft=e,i}(),this.negativeScrollAdjustment=this.isNegativeScroll?t.scrollWidth-t.clientWidth:0,this.event=new v,this.ownerDocument=t.ownerDocument||document,this.scrollbarXRail=l(h.element.rail("x")),t.appendChild(this.scrollbarXRail),this.scrollbarX=l(h.element.thumb("x")),this.scrollbarXRail.appendChild(this.scrollbarX),this.scrollbarX.setAttribute("tabindex",0),this.event.bind(this.scrollbarX,"focus",s),this.event.bind(this.scrollbarX,"blur",a),this.scrollbarXActive=null,this.scrollbarXWidth=null,this.scrollbarXLeft=null;var c=r(this.scrollbarXRail);this.scrollbarXBottom=parseInt(c.bottom,10),isNaN(this.scrollbarXBottom)?(this.isScrollbarXUsingBottom=!1,this.scrollbarXTop=y(c.top)):this.isScrollbarXUsingBottom=!0,this.railBorderXWidth=y(c.borderLeftWidth)+y(c.borderRightWidth),n(this.scrollbarXRail,{display:"block"}),this.railXMarginWidth=y(c.marginLeft)+y(c.marginRight),n(this.scrollbarXRail,{display:""}),this.railXWidth=null,this.railXRatio=null,this.scrollbarYRail=l(h.element.rail("y")),t.appendChild(this.scrollbarYRail),this.scrollbarY=l(h.element.thumb("y")),this.scrollbarYRail.appendChild(this.scrollbarY),this.scrollbarY.setAttribute("tabindex",0),this.event.bind(this.scrollbarY,"focus",s),this.event.bind(this.scrollbarY,"blur",a),this.scrollbarYActive=null,this.scrollbarYHeight=null,this.scrollbarYTop=null;var u=r(this.scrollbarYRail);this.scrollbarYRight=parseInt(u.right,10),isNaN(this.scrollbarYRight)?(this.isScrollbarYUsingRight=!1,this.scrollbarYLeft=y(u.left)):this.isScrollbarYUsingRight=!0,this.scrollbarYOuterWidth=this.isRtl?W(this.scrollbarY):null,this.railBorderYWidth=y(u.borderTopWidth)+y(u.borderBottomWidth),n(this.scrollbarYRail,{display:"block"}),this.railYMarginHeight=y(u.marginTop)+y(u.marginBottom),n(this.scrollbarYRail,{display:""}),this.railYHeight=null,this.railYRatio=null,this.reach={x:t.scrollLeft<=0?"start":t.scrollLeft>=this.contentWidth-this.containerWidth?"end":null,y:t.scrollTop<=0?"start":t.scrollTop>=this.contentHeight-this.containerHeight?"end":null},this.isAlive=!0,this.settings.handlers.forEach((function(t){return _[t](i)})),this.lastScrollTop=Math.floor(t.scrollTop),this.lastScrollLeft=t.scrollLeft,this.event.bind(this.element,"scroll",(function(t){return i.onScroll(t)})),R(this)};D.prototype.update=function(){this.isAlive&&(this.negativeScrollAdjustment=this.isNegativeScroll?this.element.scrollWidth-this.element.clientWidth:0,n(this.scrollbarXRail,{display:"block"}),n(this.scrollbarYRail,{display:"block"}),this.railXMarginWidth=y(r(this.scrollbarXRail).marginLeft)+y(r(this.scrollbarXRail).marginRight),this.railYMarginHeight=y(r(this.scrollbarYRail).marginTop)+y(r(this.scrollbarYRail).marginBottom),n(this.scrollbarXRail,{display:"none"}),n(this.scrollbarYRail,{display:"none"}),R(this),Y(this,"top",0,!1,!0),Y(this,"left",0,!1,!0),n(this.scrollbarXRail,{display:""}),n(this.scrollbarYRail,{display:""}))},D.prototype.onScroll=function(t){this.isAlive&&(R(this),Y(this,"top",this.element.scrollTop-this.lastScrollTop),Y(this,"left",this.element.scrollLeft-this.lastScrollLeft),this.lastScrollTop=Math.floor(this.element.scrollTop),this.lastScrollLeft=this.element.scrollLeft)},D.prototype.destroy=function(){this.isAlive&&(this.event.unbindAll(),a(this.scrollbarX),a(this.scrollbarY),a(this.scrollbarXRail),a(this.scrollbarYRail),this.removePsClasses(),this.element=null,this.scrollbarX=null,this.scrollbarY=null,this.scrollbarXRail=null,this.scrollbarYRail=null,this.isAlive=!1)},D.prototype.removePsClasses=function(){this.element.className=this.element.className.split(" ").filter((function(t){return!t.match(/^ps([-_].+|)$/)})).join(" ")},e["a"]=D}}]);
//# sourceMappingURL=chunk-28474e70.91d44d7b.js.map