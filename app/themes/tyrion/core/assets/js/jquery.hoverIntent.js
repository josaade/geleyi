(function(e){e.fn.hoverIntent=function(m,n){var d={sensitivity:7,interval:100,timeout:0},d=e.extend(d,n?{over:m,out:n}:m),f,h,j,k,l=function(c){f=c.pageX;h=c.pageY},p=function(c,a){a.hoverIntent_t=clearTimeout(a.hoverIntent_t);if(Math.abs(j-f)+Math.abs(k-h)<d.sensitivity)return e(a).unbind("mousemove",l),a.hoverIntent_s=1,d.over.apply(a,[c]);j=f;k=h;a.hoverIntent_t=setTimeout(function(){p(c,a)},d.interval)},q=function(c){for(var a=("mouseover"==c.type?c.fromElement:c.toElement)||c.relatedTarget;a&&
    a!=this;)try{a=a.parentNode}catch(f){a=this}if(a==this)return!1;var g=jQuery.extend({},c),b=this;b.hoverIntent_t&&(b.hoverIntent_t=clearTimeout(b.hoverIntent_t));"mouseover"==c.type?(j=g.pageX,k=g.pageY,e(b).bind("mousemove",l),1!=b.hoverIntent_s&&(b.hoverIntent_t=setTimeout(function(){p(g,b)},d.interval))):(e(b).unbind("mousemove",l),1==b.hoverIntent_s&&(b.hoverIntent_t=setTimeout(function(){b.hoverIntent_t=clearTimeout(b.hoverIntent_t);b.hoverIntent_s=0;d.out.apply(b,[g])},d.timeout)))};return this.mouseover(q).mouseout(q)}})(jQuery);