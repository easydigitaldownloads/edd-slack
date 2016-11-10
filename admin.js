!function(e){"use strict";var t=function(e){return e},n=function(t){return e.isArray(t)},r=function(e){return!n(e)&&e instanceof Object},i=function(t,n){return e.inArray(n,t)},a=function(e,t){return-1!==i(e,t)},u=function(e,t){for(var n in e)e.hasOwnProperty(n)&&t(e[n],n,e)},c=function(e){return e[e.length-1]},o=function(e){return Array.prototype.slice.call(e)},d=function(){var e={};return u(o(arguments),function(t){u(t,function(t,n){e[n]=t})}),e},s=function(e,t){var n=[];return u(e,function(e,r,i){n.push(t(e,r,i))}),n},l=function(e,t,n){var r={};return u(e,function(e,i,a){i=n?n(i,e):i,r[i]=t(e,i,a)}),r},f=function(e,t,r){return n(e)?s(e,t):l(e,t,r)},p=function(e,t){return f(e,function(e){return e[t]})},h=function(e,t,n){return f(e,function(e,r){return e[t].apply(e,n||[])})},v=function(e){e=e||{};var t={};return e.publish=function(e,n){u(t[e],function(e){e(n)})},e.subscribe=function(e,n){t[e]=t[e]||[],t[e].push(n)},e.unsubscribe=function(e){u(t,function(t){var n=i(t,e);-1!==n&&t.splice(n,1)})},e};!function(e){var t=function(e,t){var n=v(),r=e.$;return n.getType=function(){throw'implement me (return type. "text", "radio", etc.)'},n.$=function(e){return e?r.find(e):r},n.disable=function(){n.$().prop("disabled",!0),n.publish("isEnabled",!1)},n.enable=function(){n.$().prop("disabled",!1),n.publish("isEnabled",!0)},t.equalTo=function(e,t){return e===t},t.publishChange=function(){var e;return function(r,i){var a=n.get();t.equalTo(a,e)||n.publish("change",{e:r,domElement:i}),e=a}}(),n},o=function(e,n){var r=t(e,n);return r.get=function(){return r.$().val()},r.set=function(e){r.$().val(e)},r.clear=function(){r.set("")},n.buildSetter=function(e){return function(t){e.call(r,t)}},r},d=function(e,t){e=n(e)?e:[e],t=n(t)?t:[t];var r=!0;return e.length!==t.length?r=!1:u(e,function(e){a(t,e)||(r=!1)}),r},s=function(e){var t={},n=o(e,t);return n.getType=function(){return"button"},n.$().on("change",function(e){t.publishChange(e,this)}),n},l=function(t){var r={},i=o(t,r);return i.getType=function(){return"checkbox"},i.get=function(){var t=[];return i.$().filter(":checked").each(function(){t.push(e(this).val())}),t},i.set=function(t){t=n(t)?t:[t],i.$().each(function(){e(this).prop("checked",!1)}),u(t,function(e){i.$().filter('[value="'+e+'"]').prop("checked",!0)})},r.equalTo=d,i.$().change(function(e){r.publishChange(e,this)}),i},f=function(e){var t={},n=x(e,t);return n.getType=function(){return"email"},n},p=function(n){var r={},i=t(n,r);return i.getType=function(){return"file"},i.get=function(){return c(i.$().val().split("\\"))},i.clear=function(){this.$().each(function(){e(this).wrap("<form>").closest("form").get(0).reset(),e(this).unwrap()})},i.$().change(function(e){r.publishChange(e,this)}),i},m=function(e){var t={},n=o(e,t);return n.getType=function(){return"hidden"},n.$().change(function(e){t.publishChange(e,this)}),n},g=function(n){var r={},i=t(n,r);return i.getType=function(){return"file[multiple]"},i.get=function(){var e,t=i.$().get(0).files||[],n=[];for(e=0;e<(t.length||0);e+=1)n.push(t[e].name);return n},i.clear=function(){this.$().each(function(){e(this).wrap("<form>").closest("form").get(0).reset(),e(this).unwrap()})},i.$().change(function(e){r.publishChange(e,this)}),i},y=function(e){var t={},r=o(e,t);return r.getType=function(){return"select[multiple]"},r.get=function(){return r.$().val()||[]},r.set=function(e){r.$().val(""===e?[]:n(e)?e:[e])},t.equalTo=d,r.$().change(function(e){t.publishChange(e,this)}),r},b=function(e){var t={},n=x(e,t);return n.getType=function(){return"password"},n},k=function(t){var n={},r=o(t,n);return r.getType=function(){return"radio"},r.get=function(){return r.$().filter(":checked").val()||null},r.set=function(t){t?r.$().filter('[value="'+t+'"]').prop("checked",!0):r.$().each(function(){e(this).prop("checked",!1)})},r.$().change(function(e){n.publishChange(e,this)}),r},$=function(e){var t={},n=o(e,t);return n.getType=function(){return"range"},n.$().change(function(e){t.publishChange(e,this)}),n},C=function(e){var t={},n=o(e,t);return n.getType=function(){return"select"},n.$().change(function(e){t.publishChange(e,this)}),n},x=function(e){var t={},n=o(e,t);return n.getType=function(){return"text"},n.$().on("change keyup keydown",function(e){t.publishChange(e,this)}),n},w=function(e){var t={},n=o(e,t);return n.getType=function(){return"textarea"},n.$().on("change keyup keydown",function(e){t.publishChange(e,this)}),n},T=function(e){var t={},n=x(e,t);return n.getType=function(){return"url"},n},_=function(t){var n={},a=t.$,c=t.constructorOverride||{button:s,text:x,url:T,email:f,password:b,range:$,textarea:w,select:C,"select[multiple]":y,radio:k,checkbox:l,file:p,"file[multiple]":g,hidden:m},o=function(t,i){var u=r(i)?i:a.find(i);u.each(function(){var r=e(this).attr("name");n[r]=c[t]({$:e(this)})})},d=function(t,o){var d=[],s=r(o)?o:a.find(o);r(o)?n[s.attr("name")]=c[t]({$:s}):(s.each(function(){-1===i(d,e(this).attr("name"))&&d.push(e(this).attr("name"))}),u(d,function(e){n[e]=c[t]({$:a.find('input[name="'+e+'"]')})}))};return a.is("input, select, textarea")?a.is('input[type="button"], button, input[type="submit"]')?o("button",a):a.is("textarea")?o("textarea",a):a.is('input[type="text"]')||a.is("input")&&!a.attr("type")?o("text",a):a.is('input[type="password"]')?o("password",a):a.is('input[type="email"]')?o("email",a):a.is('input[type="url"]')?o("url",a):a.is('input[type="range"]')?o("range",a):a.is("select")?a.is("[multiple]")?o("select[multiple]",a):o("select",a):a.is('input[type="file"]')?a.is("[multiple]")?o("file[multiple]",a):o("file",a):a.is('input[type="hidden"]')?o("hidden",a):a.is('input[type="radio"]')?d("radio",a):a.is('input[type="checkbox"]')?d("checkbox",a):o("text",a):(o("button",'input[type="button"], button, input[type="submit"]'),o("text",'input[type="text"]'),o("password",'input[type="password"]'),o("email",'input[type="email"]'),o("url",'input[type="url"]'),o("range",'input[type="range"]'),o("textarea","textarea"),o("select","select:not([multiple])"),o("select[multiple]","select[multiple]"),o("file",'input[type="file"]:not([multiple])'),o("file[multiple]",'input[type="file"][multiple]'),o("hidden",'input[type="hidden"]'),d("radio",'input[type="radio"]'),d("checkbox",'input[type="checkbox"]')),n};e.fn.inputVal=function(t){var n=e(this),r=_({$:n});return n.is("input, textarea, select")?"undefined"==typeof t?r[n.attr("name")].get():(r[n.attr("name")].set(t),n):"undefined"==typeof t?h(r,"get"):(u(t,function(e,t){r[t].set(e)}),n)},e.fn.inputOnChange=function(t){var n=e(this),r=_({$:n});return u(r,function(e){e.subscribe("change",function(e){t.call(e.domElement,e.e)})}),n},e.fn.inputDisable=function(){var t=e(this);return h(_({$:t}),"disable"),t},e.fn.inputEnable=function(){var t=e(this);return h(_({$:t}),"enable"),t},e.fn.inputClear=function(){var t=e(this);return h(_({$:t}),"clear"),t}}(jQuery),e.fn.repeaterVal=function(){var t=function(e){var t=[];return u(e,function(e,n){var r=[];"undefined"!==n&&(r.push(n.match(/^[^\[]*/)[0]),r=r.concat(f(n.match(/\[[^\]]*\]/g),function(e){return e.replace(/[\[\]]/g,"")})),t.push({val:e,key:r}))}),t},n=function(e){if(1===e.length&&(0===e[0].key.length||1===e[0].key.length&&!e[0].key[0]))return e[0].val;u(e,function(e){e.head=e.key.shift()});var t,r=function(){var t={};return u(e,function(e){t[e.head]||(t[e.head]=[]),t[e.head].push(e)}),t}();return/^[0-9]+$/.test(e[0].head)?(t=[],u(r,function(e){t.push(n(e))})):(t={},u(r,function(e,r){t[r]=n(e)})),t};return n(t(e(this).inputVal()))},e.fn.repeater=function(n){return n=n||{},e(this).each(function(){var r=e(this),i=n.show||function(){e(this).show()},a=n.hide||function(e){e()},o=r.find("[data-repeater-list]").first(),s=function(t,n){return t.filter(function(){return!n||0===e(this).closest(p(n,"selector").join(",")).length})},l=function(){return s(o.find("[data-repeater-item]"),n.repeaters)},h=o.find("[data-repeater-item]").first().clone().hide(),v=e(this).find("[data-repeater-item]").first().find("[data-repeater-delete]");n.isFirstItemUndeletable&&v&&v.remove();var m=function(){var e=o.data("repeater-list");return n.$parent?n.$parent.data("item-name")+"["+e+"]":e},g=function(t){n.repeaters&&t.each(function(){var t=e(this);u(n.repeaters,function(e){t.find(e.selector).repeater(d(e,{$parent:t}))})})},y=function(e,t,n){e&&u(e,function(e){n.call(t.find(e.selector)[0],e)})},b=function(t,n,r){t.each(function(t){var i=e(this);i.data("item-name",n+"["+t+"]"),s(i.find("[name]"),r).each(function(){var a=e(this),u=a.attr("name").match(/\[[^\]]+\]/g),o=u?c(u).replace(/\[|\]/g,""):a.attr("name"),d=n+"["+t+"]["+o+"]"+(a.is(":checkbox")||a.attr("multiple")?"[]":"");a.attr("name",d),y(r,i,function(r){var i=e(this);b(s(i.find("[data-repeater-item]"),r.repeaters||[]),n+"["+t+"]["+i.find("[data-repeater-list]").first().data("repeater-list")+"]",r.repeaters)})})}),o.find("input[name][checked]").removeAttr("checked").prop("checked",!0)};b(l(),m(),n.repeaters),g(l()),n.ready&&n.ready(function(){b(l(),m(),n.repeaters)});var k=function(){var r=function(n,i,a){if(i){var u={};s(n.find("[name]"),a).each(function(){var t=e(this).attr("name").match(/\[([^\]]*)(\]|\]\[\])$/)[1];u[t]=e(this).attr("name")}),n.inputVal(f(i,t,function(e){return u[e]}))}y(a,n,function(t){var n=e(this);s(n.find("[data-repeater-item]"),t.repeaters).each(function(){r(e(this),t.defaultValues,t.repeaters||[])})})};return function(t){o.append(t),b(l(),m(),n.repeaters),t.find("[name]").each(function(){e(this).inputClear()}),r(t,n.defaultValues,n.repeaters)}}(),$=function(){var e=h.clone();k(e),n.repeaters&&g(e),i.call(e.get(0))};r.children().each(function(){e(this).is("[data-repeater-list]")||0!==e(this).find("[data-repeater-list]").length||(e(this).is("[data-repeater-create]")?e(this).click($):0!==e(this).find("[data-repeater-create]").length&&e(this).find("[data-repeater-create]").click($))}),o.on("click","[data-repeater-delete]",function(){var t=e(this).closest("[data-repeater-item]").get(0);a.call(t,function(){e(t).remove(),b(l(),m(),n.repeaters)})})}),this}}(jQuery),function(e){function t(){var t=/value="(#(?:[0-9a-f]{3}){1,2})"/i;e(".edd-repeater .edd-color-picker").length&&e(".edd-repeater").each(function(n,r){e(r).find(".edd-repeater-item.opened").each(function(n,r){e(r).find(".edd-color-picker").each(function(n,r){var i=t.exec(e(r)[0].outerHTML)[1];e(r).val(i).attr("value",i).wpColorPicker()})})})}function n(){e(".edd-repeater .edd-chosen").length&&e(".edd-repeater").each(function(t,n){e(n).find(".edd-repeater-item.opened").each(function(t,n){e(n).find(".edd-chosen").chosen()})})}var r=e("[data-edd-repeater]");if(r.length){t(),n();var i=function(){e(this).find(".repeater-header h2 span.title").html(e(this).find(".repeater-header h2").data("repeater-collapsable-default")),e(this).find("select").each(function(t,n){var r=e(n).find("option[selected]").val();e(n).val(r)}),e(this).find('input[type="checkbox"].default-checked').each(function(t,n){e(n).prop("checked",!0)});var r=e(this).closest("[data-edd-repeater]"),i=e(this).find(".nested-repeater");e(i).each(function(t,n){var i=e(n).find(".edd-repeater-item").get().reverse();return 1==i.length||void e(i).each(function(t,n){return t!=i.length-1&&(e(n).stop().slideUp(300,function(){e(this).remove()}),void e(r).trigger("edd-nested-repeater-cleanup",[e(n)]))})}),e(this).addClass("opened").removeClass("closed").stop().slideDown(),t(),n(),e(r).trigger("edd-repeater-add",[e(this)])},a=function(){var t=e(this).closest("[data-edd-repeater]");e(this).stop().slideUp(300,function(){e(this).remove()}),e(t).trigger("edd-repeater-remove",[e(this)])};r.each(function(){var r=e(this),u=r.find("[data-repeater-dummy]");r.repeater({repeaters:[{selector:".nested-repeater",show:i,hide:a}],show:i,hide:a,ready:function(e){r.find("tbody").on("sortupdate",e)}}),u.length&&u.remove(),"undefined"!=typeof r.attr("data-repeater-sortable")&&r.find(".edd-repeater-list").sortable({axis:"y",handle:"[data-repeater-item-handle]",forcePlaceholderSize:!0,update:function(e,n){t()}}),"undefined"!=typeof r.attr("data-repeater-collapsable")&&r.find(".edd-repeater-content").first().hide(),e(document).on("click touchend",".edd-repeater[data-repeater-collapsable] [data-repeater-collapsable-handle]",function(){var t=e(this).closest(".edd-repeater-item"),r=t.find(".edd-repeater-content").first(),i=t.hasClass("opened")?"closing":"opening";"opening"==i?(r.stop().slideDown(),t.addClass("opened"),t.removeClass("closed"),n()):(r.stop().slideUp(),t.addClass("closed"),t.removeClass("opened"))}),e(document).on("keyup change",'.edd-repeater .edd-repeater-content td:first-of-type *[type!="hidden"]',function(){if(""!==e(this).val())e(this).closest(".edd-repeater-item").find(".repeater-header h2 span.title").html(e(this).val());else{var t=e(this).closest(".edd-repeater-item").find(".repeater-header h2").data("repeater-collapsable-default");e(this).closest(".edd-repeater-item").find(".repeater-header h2 span.title").html(t)}})})}}(jQuery),function(e){"use strict";var t=function(t,n){"edd-repeater-add"==t.type&&(t=n,n=0);var r=e(t).closest(".edd-repeater-item");0==n?(e(r).find("select").each(function(t,n){e(n).val(0),e(n).hasClass("edd-chosen")&&e(n).trigger("chosen:updated")}),e(r).find(".edd-slack-conditional").closest("td").addClass("hidden"),e(r).find(".edd-slack-replacement-instruction").closest("td").addClass("hidden")):(e(r).find(".edd-slack-conditional."+n).closest("td.hidden").removeClass("hidden"),e(r).find(".edd-slack-conditional").not("."+n).closest("td").addClass("hidden"),e(r).find(".edd-slack-replacement-instruction."+n).removeClass("hidden"),e(r).find(".edd-slack-replacement-instruction").not("."+n).addClass("hidden"))},n=function(t,n){var r=n.find('input[name$="[slack_post_id]"]').val(),i=e('input[type="hidden"][name^="edd_slack_deleted_"]'),a=i.val();r&&(a=a?a+","+r:r,i.val(a))},r=function(){var r=e("[data-edd-repeater]");void 0!==typeof EDD_Slack_Admin,r.length&&(r.on("edd-repeater-add",t),r.on("repeater-show",t),r.on("edd-repeater-remove",n))};r(),e(document).ready(function(){e(".edd-slack-trigger").each(function(n,r){t(r,e(r).val())}),e(document).on("change",".edd-slack-trigger",function(){t(e(this),e(this).val())})})}(jQuery);
//# sourceMappingURL=admin.js.map
