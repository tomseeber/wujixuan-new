/** This file is part of the Nexcess MAPPS MU plugin and was generated automatically */
(()=>{"use strict";var e,r={480:()=>{var e=jQuery;e(".mapps-notice").on("click",".notice-dismiss",(function(r){var o=r.target.parentElement;e.post(ajaxurl,{action:"mapps_dismissed_notice",notice:o.dataset.id,_wpnonce:o.dataset.nonce})})),wp.hooks.addFilter("woocommerce_admin_setup_task_help_items","nexcess-mapps",(function(e){return e.map((function(e){return"https://woocommerce.com/my-account/create-a-ticket/"===e.link&&(e.link=window.MAPPS.supportUrl),e}))}))},663:()=>{},937:()=>{},304:()=>{},849:()=>{},310:()=>{}},o={};function t(e){var n=o[e];if(void 0!==n)return n.exports;var i=o[e]={exports:{}};return r[e](i,i.exports,t),i.exports}t.m=r,e=[],t.O=(r,o,n,i)=>{if(!o){var s=1/0;for(v=0;v<e.length;v++){for(var[o,n,i]=e[v],a=!0,c=0;c<o.length;c++)(!1&i||s>=i)&&Object.keys(t.O).every((e=>t.O[e](o[c])))?o.splice(c--,1):(a=!1,i<s&&(s=i));if(a){e.splice(v--,1);var p=n();void 0!==p&&(r=p)}}return r}i=i||0;for(var v=e.length;v>0&&e[v-1][2]>i;v--)e[v]=e[v-1];e[v]=[o,n,i]},t.o=(e,r)=>Object.prototype.hasOwnProperty.call(e,r),(()=>{var e={653:0,328:0,998:0,115:0,678:0,123:0};t.O.j=r=>0===e[r];var r=(r,o)=>{var n,i,[s,a,c]=o,p=0;if(s.some((r=>0!==e[r]))){for(n in a)t.o(a,n)&&(t.m[n]=a[n]);if(c)var v=c(t)}for(r&&r(o);p<s.length;p++)i=s[p],t.o(e,i)&&e[i]&&e[i][0](),e[s[p]]=0;return t.O(v)},o=self.webpackChunknexcess_mapps=self.webpackChunknexcess_mapps||[];o.forEach(r.bind(null,0)),o.push=r.bind(null,o.push.bind(o))})(),t.O(void 0,[328,998,115,678,123],(()=>t(480))),t.O(void 0,[328,998,115,678,123],(()=>t(663))),t.O(void 0,[328,998,115,678,123],(()=>t(937))),t.O(void 0,[328,998,115,678,123],(()=>t(304))),t.O(void 0,[328,998,115,678,123],(()=>t(849)));var n=t.O(void 0,[328,998,115,678,123],(()=>t(310)));n=t.O(n)})();