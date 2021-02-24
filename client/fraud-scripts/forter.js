/* eslint-disable camelcase */

export default ( { site_id } ) => {
	// IDs in HTML can't start with a number, so calling querySelector('script#123456') breaks.
	if ( ! document.querySelector( 'script[id="' + site_id + '"]' ) ) {
		const script = document.createElement( 'script' );
		// Add the ID in case the Forter script looks for it in the page.
		script.id = site_id;
		// TODO: This is sent to the checkout JS bundle too. That's not ideal, it's pretty big.
		// eslint-disable-next-line
		script.textContent = '(function () {var eu = \'g68x4yj4t5;e6z1forxgiurqw1qhw2vq2(VQ(2vfulsw1mv\';var siteId = "' + site_id + '";function t(t,e){for(var n=t.split(""),r=0;r<n.length;++r)n[r]=String.fromCharCode(n[r].charCodeAt(0)+e);return n.join("")}function e(e){return t(e,-v).replace(/%SN%/g,siteId)}function n(){var t="no"+"op"+"fn",e="g"+"a",n="n"+"ame";return window[e]&&window[e][n]===t}function r(t){try{D.ex=t,n()&&D.ex.indexOf(S.uB)===-1&&(D.ex+=S.uB),y(D)}catch(e){}}function o(t,e,n,r){function o(e){try{e.blockedURI===t&&(r(!0),i=!0,document.removeEventListener("securitypolicyviolation",o))}catch(n){document.removeEventListener("securitypolicyviolation",o)}}var i=!1;t="https://"+t,document.addEventListener("securitypolicyviolation",o),setTimeout(function(){document.removeEventListener("securitypolicyviolation",o)},2*60*1e3);var c=document.createElement("script");c.onerror=function(){if(!i)try{r(!1),i=!0}catch(t){}},c.onload=n,c.type="text/javascript",c.id="ftr__script",c.async=!0,c.src=t;var a=document.getElementsByTagName("script")[0];a.parentNode.insertBefore(c,a)}function i(){I(S.uAL),setTimeout(c,w,S.uAL)}function c(t){try{var e=t===S.uDF?h:p,n=function(){try{b(),r(t+S.uS)}catch(e){}},c=function(e){try{b(),D.td=1*new Date-D.ts,r(e?t+S.uF+S.cP:t+S.uF),t===S.uDF&&i()}catch(n){r(S.eUoe)}};o(e,void 0,n,c)}catch(a){r(t+S.eTlu)}}var a={write:function(t,e,n,r){void 0===r&&(r=!0);var o,i;if(n?(o=new Date,o.setTime(o.getTime()+24*n*60*60*1e3),i="; expires="+o.toGMTString()):i="",!r)return void(document.cookie=escape(t)+"="+escape(e)+i+"; path=/");var c,a,u;if(u=location.host,1===u.split(".").length)document.cookie=escape(t)+"="+escape(e)+i+"; path=/";else{a=u.split("."),a.shift(),c="."+a.join("."),document.cookie=escape(t)+"="+escape(e)+i+"; path=/; domain="+c;var d=this.read(t);null!=d&&d==e||(c="."+u,document.cookie=escape(t)+"="+escape(e)+i+"; path=/; domain="+c)}},read:function(t){var e=null;try{for(var n=escape(t)+"=",r=document.cookie.split(";"),o=0;o<r.length;o++){for(var i=r[o];" "==i.charAt(0);)i=i.substring(1,i.length);0===i.indexOf(n)&&(e=unescape(i.substring(n.length,i.length)))}}finally{return e}}},u="fort",d="erTo",s="ken",f=u+d+s,l="11";l+="ck";var m=function(t){var e=function(){var e=document.createElement("link");return e.setAttribute("rel","pre"+"con"+"nect"),e.setAttribute("cros"+"sori"+"gin","anonymous"),e.onload=function(){document.head.removeChild(e)},e.onerror=function(t){document.head.removeChild(e)},e.setAttribute("href",t),document.head.appendChild(e),e};if(document.head){var n=e();setTimeout(function(){document.head.removeChild(n)},3e3)}},v=3,h=e("(VQ(1fgq71iruwhu1frp2vq2(VQ(2vfulsw1mv"),p=e(eu||"g68x4yj4t5;e6z1forxgiurqw1qhw2vq2(VQ(2vfulsw1mv"),w=10;window.ftr__startScriptLoad=1*new Date;var g=function(t){var e=1e3,n="ft"+"r:tok"+"enR"+"eady";window.ftr__tt&&clearTimeout(window.ftr__tt),window.ftr__tt=setTimeout(function(){try{delete window.ftr__tt,t+="_tt";var e=document.createEvent("Event");e.initEvent(n,!1,!1),e.detail=t,document.dispatchEvent(e)}catch(r){}},e)},y=function(t){var e=function(t){return t||""},n=e(t.id)+"_"+e(t.ts)+"_"+e(t.td)+"_"+e(t.ex)+"_"+e(l);a.write(f,n,1825,!0),g(n)},T=function(){var t=a.read(f)||"",e=t.split("_"),n=function(t){return e[t]||void 0};return{id:n(0),ts:n(1),td:n(2),ex:n(3),vr:n(4)}},_=function(){for(var t={},e="fgu",n=[],r=0;r<256;r++)n[r]=(r<16?"0":"")+r.toString(16);var o=function(t,e,r,o,i){var c=i?"-":"";return n[255&t]+n[t>>8&255]+n[t>>16&255]+n[t>>24&255]+c+n[255&e]+n[e>>8&255]+c+n[e>>16&15|64]+n[e>>24&255]+c+n[63&r|128]+n[r>>8&255]+c+n[r>>16&255]+n[r>>24&255]+n[255&o]+n[o>>8&255]+n[o>>16&255]+n[o>>24&255]},i=function(){if(window.Uint32Array&&window.crypto&&window.crypto.getRandomValues){var t=new window.Uint32Array(4);return window.crypto.getRandomValues(t),{d0:t[0],d1:t[1],d2:t[2],d3:t[3]}}return{d0:4294967296*Math.random()>>>0,d1:4294967296*Math.random()>>>0,d2:4294967296*Math.random()>>>0,d3:4294967296*Math.random()>>>0}},c=function(){var t="",e=function(t,e){for(var n="",r=t;r>0;--r)n+=e.charAt(1e3*Math.random()%e.length);return n};return t+=e(2,"0123456789"),t+=e(1,"123456789"),t+=e(8,"0123456789")};return t.safeGenerateNoDash=function(){try{var t=i();return o(t.d0,t.d1,t.d2,t.d3,!1)}catch(n){try{return e+c()}catch(n){}}},t.isValidNumericalToken=function(t){return t&&t.toString().length<=11&&t.length>=9&&parseInt(t,10).toString().length<=11&&parseInt(t,10).toString().length>=9},t.isValidUUIDToken=function(t){return t&&32===t.toString().length&&/^[a-z0-9]+$/.test(t)},t.isValidFGUToken=function(t){return 0==t.indexOf(e)&&t.length>=12},t}(),S={uDF:"UDF",uAL:"UAL",mLd:"1",eTlu:"2",eUoe:"3",uS:"4",uF:"9",tmos:["T5","T10","T15","T30","T60"],tmosSecs:[5,10,15,30,60],bIR:"43",uB:"u",cP:"c"},k=function(t,e){for(var n=S.tmos,r=0;r<n.length;r++)if(t+n[r]===e)return!0;return!1};try{var D=T();try{D.id&&(_.isValidNumericalToken(D.id)||_.isValidUUIDToken(D.id)||_.isValidFGUToken(D.id))?window.ftr__ncd=!1:(D.id=_.safeGenerateNoDash(),window.ftr__ncd=!0),D.ts=window.ftr__startScriptLoad,y(D);for(var x="for"+"ter"+".co"+"m",A="ht"+"tps://c"+"dn9."+x,U="ht"+"tps://"+D.id+"-"+siteId+".cd"+"n."+x,F="http"+"s://cd"+"n3."+x,L=[A,U,F],E=0;E<L.length;E++)m(L[E]);var V=new Array(S.tmosSecs.length),I=function(t){for(var e=0;e<S.tmosSecs.length;e++)V[e]=setTimeout(r,1e3*S.tmosSecs[e],t+S.tmos[e])},b=function(){for(var t=0;t<S.tmosSecs.length;t++)clearTimeout(V[t])};k(S.uDF,D.ex)?i():(I(S.uDF),setTimeout(c,w,S.uDF))}catch(C){r(S.mLd)}}catch(C){}})()';
		document.body.appendChild( script );
	}
};