/* @license GPL-2.0-or-later https://www.drupal.org/licensing/faq */
(($,Drupal,once)=>{const {ajax,behaviors,debounce,announce,formatPlural}=Drupal;behaviors.layoutBuilderDisableInteractiveElements={attach(){const $blocks=$('#layout-builder [data-block-uuid]');$blocks.find('input, textarea, select').prop('disabled',true);$blocks.find('a').not((index,element)=>$(element).closest('[data-contextual-id]').length>0).each((index,link)=>{$(link).on('click mouseup touchstart',(e)=>{e.preventDefault();e.stopPropagation();});});$blocks.find('button, [href], input, select, textarea, iframe, [tabindex]:not([tabindex="-1"]):not(.tabbable)').not((index,element)=>$(element).closest('[data-contextual-id]').length>0).attr('tabindex',-1);}};behaviors.layoutBuilderToggleContentPreview={attach(context){const $layoutBuilder=$('#layout-builder');const $layoutBuilderContentPreview=$('#layout-builder-content-preview');const contentPreviewId=$layoutBuilderContentPreview.data('content-preview-id');const isContentPreview=JSON.parse(localStorage.getItem(contentPreviewId))!==false;const disableContentPreview=()=>{$layoutBuilder.addClass('layout-builder--content-preview-disabled');$('[data-layout-content-preview-placeholder-label]',context).each((i,element)=>{const $element=$(element);$element.children(':not([data-contextual-id])').hide(0);const contentPreviewPlaceholderText=$element.attr('data-layout-content-preview-placeholder-label');const contentPreviewPlaceholderLabel=Drupal.theme('layoutBuilderPrependContentPreviewPlaceholderLabel',contentPreviewPlaceholderText);$element.prepend(contentPreviewPlaceholderLabel);});};const enableContentPreview=()=>{$layoutBuilder.removeClass('layout-builder--content-preview-disabled');$('.js-layout-builder-content-preview-placeholder-label').remove();$('[data-layout-content-preview-placeholder-label]').each((i,element)=>{$(element).children().show();});};$('#layout-builder-content-preview',context).on('change',(event)=>{const isChecked=$(event.currentTarget).is(':checked');localStorage.setItem(contentPreviewId,JSON.stringify(isChecked));if(isChecked){enableContentPreview();announce(Drupal.t('Block previews are visible. Block labels are hidden.'));}else{disableContentPreview();announce(Drupal.t('Block previews are hidden. Block labels are visible.'));}});if(!isContentPreview){$layoutBuilderContentPreview.attr('checked',false);disableContentPreview();}}};Drupal.theme.layoutBuilderPrependContentPreviewPlaceholderLabel=(contentPreviewPlaceholderText)=>{const contentPreviewPlaceholderLabel=document.createElement('div');contentPreviewPlaceholderLabel.className='layout-builder-block__content-preview-placeholder-label js-layout-builder-content-preview-placeholder-label';contentPreviewPlaceholderLabel.innerHTML=contentPreviewPlaceholderText;return `<div class="layout-builder-block__content-preview-placeholder-label js-layout-builder-content-preview-placeholder-label">${contentPreviewPlaceholderText}</div>`;};})(jQuery,Drupal,once);;
(function($){$.fn.dndPageScroll=function(options){options||(options={});var defaults={topId:'top_scroll_page',bottomId:'bottom_scroll_page',delay:50,height:20};var options=$.extend(defaults,options);var top_el=$('#'+options.topId);if(!top_el.length)top_el=$('<div id="top_scroll_page">&nbsp;</div>').appendTo('body');var bottom_el=$('#'+options.bottomId);if(!bottom_el.length)bottom_el=$('<div id="bottom_scroll_page">&nbsp;</div>').appendTo('body');var both_el=$('#top_scroll_page, #bottom_scroll_page');both_el.hide();both_el.css({position:'fixed',left:0,right:0,height:options.height,zIndex:999999});top_el.css({top:0});bottom_el.css({bottom:0});var lastTop;var lastBottom;both_el.on('dragenter',function(e){var direction=($(this).attr('id')==options.topId)?'up':'down';return true;});both_el.on('dragover',function(e){if($('html,body').is(':animated'))return true;var scrollTop=$(window).scrollTop();var direction=($(this).attr('id')==options.topId)?-1:1;var last=(direction==-1)?lastTop:lastBottom;var current=(direction==-1)?scrollTop:$(document).height()-(scrollTop+$(window).height());if(last!=undefined&&last==current&&current>0){var newScrollTop=scrollTop+direction*50;$('html,body').animate({scrollTop:newScrollTop},options.delay,'linear');}if(direction==-1)lastTop=current;else lastBottom=current;return true;});var _hide=function(e){both_el.hide();timestamp=undefined;scrolltop=0;scrollbottom=0;return true;};$(document).on('dragstart',function(e){both_el.show();return true;});$(document).on('dragend',_hide);both_el.on('mouseover',_hide);};})(jQuery);;
(function($,Drupal){'use strict';Drupal.behaviors.adminToolbarSearch={extraFetched:false,attach:function(context){if(context!=document)return;var $self=this;const elements=once('admin-toolbar-search','#toolbar-bar',context);$(elements).each(function(){$self.links=[];var $searchTab=$(this).find('#admin-toolbar-search-tab');var $searchInput=$searchTab.find('#admin-toolbar-search-input');if($searchInput.length===0)return;$searchInput.autocomplete({minLength:2,position:{collision:'fit'},source:function(request,response){var data=$self.handleAutocomplete(request.term);if(!$self.extraFetched&&drupalSettings.adminToolbarSearch.loadExtraLinks)$.getJSON(Drupal.url('admin/admin-toolbar-search'),function(data){$(data).each(function(){var item=this;item.label=this.labelRaw+' '+this.value;$self.links.push(item);});$self.extraFetched=true;var results=$self.handleAutocomplete(request.term);response(results);});else response(data);},open:function(){var zIndex=$('#toolbar-item-administration-tray').css('z-index')+1;$(this).autocomplete('widget').css('z-index',zIndex);return false;},select:function(event,ui){if(ui.item.value){location.href=ui.item.value;return false;}}}).data('ui-autocomplete')._renderItem=(function(ul,item){ul.addClass('admin-toolbar-search-autocomplete-list');return $('<li>').append('<div ><a href="'+item.value+'" onclick="window.open(this.href); return false;" >'+item.labelRaw+' <span class="admin-toolbar-search-url">'+item.value+'</span></a></div>').appendTo(ul);});$searchInput.focus(function(){Drupal.behaviors.adminToolbarSearch.populateLinks($self);});$('#admin-toolbar-mobile-search-tab .toolbar-item',context).click(function(e){e.preventDefault();$(this).toggleClass('is-active');$searchTab.toggleClass('visible');$searchInput.focus();});});},getItemLabel:function(item){var breadcrumbs=[];$(item).parents().each(function(){if($(this).hasClass('menu-item')){var $link=$(this).find('a:first');if($link.length&&!$link.hasClass('admin-toolbar-search-ignore'))breadcrumbs.unshift($link.text());}});return breadcrumbs.join(' > ');},handleAutocomplete:function(term){var $self=this;var keywords=term.split(" ");var suggestions=[];$self.links.forEach(function(element){var label=element.label.toLowerCase();if(label.indexOf(term.toLowerCase())>=0)suggestions.push(element);else{var matchCount=0;keywords.forEach(function(keyword){if(label.indexOf(keyword.toLowerCase())>=0)matchCount++;});if(matchCount==keywords.length)suggestions.push(element);}});return suggestions;},populateLinks:function($self){if($self.links.length===0){var getUrl=window.location;var baseUrl=getUrl.protocol+"//"+getUrl.host+"/";$('.toolbar-tray a[data-drupal-link-system-path]').each(function(){if(this.href!==baseUrl){var label=$self.getItemLabel(this);$self.links.push({'value':this.href,'label':label+' '+this.href,'labelRaw':Drupal.checkPlain(label)});}});}}};})(jQuery,Drupal);;
(function($,Drupal,{tabbable,isTabbable}){function TabbingManager(){this.stack=[];}function TabbingContext(options){$.extend(this,{level:null,$tabbableElements:$(),$disabledElements:$(),released:false,active:false,trapFocus:false},options);}$.extend(TabbingManager.prototype,{constrain(elements,{trapFocus=false}={}){const il=this.stack.length;for(let i=0;i<il;i++)this.stack[i].deactivate();let tabbableElements=[];$(elements).each((index,rootElement)=>{tabbableElements=[...tabbableElements,...tabbable(rootElement)];if(isTabbable(rootElement))tabbableElements=[...tabbableElements,rootElement];});const tabbingContext=new TabbingContext({level:this.stack.length,$tabbableElements:$(tabbableElements),trapFocus});this.stack.push(tabbingContext);tabbingContext.activate();$(document).trigger('drupalTabbingConstrained',tabbingContext);return tabbingContext;},release(){let toActivate=this.stack.length-1;while(toActivate>=0&&this.stack[toActivate].released)toActivate--;this.stack.splice(toActivate+1);if(toActivate>=0)this.stack[toActivate].activate();},activate(tabbingContext){const $set=tabbingContext.$tabbableElements;const level=tabbingContext.level;const $disabledSet=$(tabbable(document.body)).not($set);tabbingContext.$disabledElements=$disabledSet;const il=$disabledSet.length;for(let i=0;i<il;i++)this.recordTabindex($disabledSet.eq(i),level);$disabledSet.prop('tabindex',-1).prop('autofocus',false);let $hasFocus=$set.filter('[autofocus]').eq(-1);if($hasFocus.length===0)$hasFocus=$set.eq(0);$hasFocus.trigger('focus');if($set.length&&tabbingContext.trapFocus){$set.last().on('keydown.focus-trap',(event)=>{if(event.key==='Tab'&&!event.shiftKey){event.preventDefault();$set.first().focus();}});$set.first().on('keydown.focus-trap',(event)=>{if(event.key==='Tab'&&event.shiftKey){event.preventDefault();$set.last().focus();}});}},deactivate(tabbingContext){const $set=tabbingContext.$disabledElements;const level=tabbingContext.level;const il=$set.length;tabbingContext.$tabbableElements.first().off('keydown.focus-trap');tabbingContext.$tabbableElements.last().off('keydown.focus-trap');for(let i=0;i<il;i++)this.restoreTabindex($set.eq(i),level);},recordTabindex($el,level){const tabInfo=$el.data('drupalOriginalTabIndices')||{};tabInfo[level]={tabindex:$el[0].getAttribute('tabindex'),autofocus:$el[0].hasAttribute('autofocus')};$el.data('drupalOriginalTabIndices',tabInfo);},restoreTabindex($el,level){const tabInfo=$el.data('drupalOriginalTabIndices');if(tabInfo&&tabInfo[level]){const data=tabInfo[level];if(data.tabindex)$el[0].setAttribute('tabindex',data.tabindex);else $el[0].removeAttribute('tabindex');if(data.autofocus)$el[0].setAttribute('autofocus','autofocus');if(level===0)$el.removeData('drupalOriginalTabIndices');else{let levelToDelete=level;while(tabInfo.hasOwnProperty(levelToDelete)){delete tabInfo[levelToDelete];levelToDelete++;}$el.data('drupalOriginalTabIndices',tabInfo);}}}});$.extend(TabbingContext.prototype,{release(){if(!this.released){this.deactivate();this.released=true;Drupal.tabbingManager.release(this);$(document).trigger('drupalTabbingContextReleased',this);}},activate(){if(!this.active&&!this.released){this.active=true;Drupal.tabbingManager.activate(this);$(document).trigger('drupalTabbingContextActivated',this);}},deactivate(){if(this.active){this.active=false;Drupal.tabbingManager.deactivate(this);$(document).trigger('drupalTabbingContextDeactivated',this);}}});if(Drupal.tabbingManager)return;Drupal.tabbingManager=new TabbingManager();})(jQuery,Drupal,window.tabbable);;
(function($,Drupal,Backbone){const strings={tabbingReleased:Drupal.t('Tabbing is no longer constrained by the Contextual module.'),tabbingConstrained:Drupal.t('Tabbing is constrained to a set of @contextualsCount and the edit mode toggle.'),pressEsc:Drupal.t('Press the esc key to exit.')};function initContextualToolbar(context){if(!Drupal.contextual||!Drupal.contextual.collection)return;const contextualToolbar=Drupal.contextualToolbar;contextualToolbar.model=new contextualToolbar.StateModel({isViewing:document.querySelector('body .contextual-region')===null||localStorage.getItem('Drupal.contextualToolbar.isViewing')!=='false'},{contextualCollection:Drupal.contextual.collection});const viewOptions={el:$('.toolbar .toolbar-bar .contextual-toolbar-tab'),model:contextualToolbar.model,strings};new contextualToolbar.VisualView(viewOptions);new contextualToolbar.AuralView(viewOptions);}Drupal.behaviors.contextualToolbar={attach(context){if(once('contextualToolbar-init','body').length)initContextualToolbar(context);}};Drupal.contextualToolbar={model:null};})(jQuery,Drupal,Backbone);;
(function(Drupal,Backbone){Drupal.contextualToolbar.StateModel=Backbone.Model.extend({defaults:{isViewing:true,isVisible:false,contextualCount:0,tabbingContext:null},initialize(attrs,options){this.listenTo(options.contextualCollection,'reset remove add',this.countContextualLinks);this.listenTo(options.contextualCollection,'add',this.lockNewContextualLinks);this.listenTo(this,'change:contextualCount',this.updateVisibility);this.listenTo(this,'change:isViewing',(model,isViewing)=>{options.contextualCollection.each((contextualModel)=>{contextualModel.set('isLocked',!isViewing);});});},countContextualLinks(contextualModel,contextualCollection){this.set('contextualCount',contextualCollection.length);},lockNewContextualLinks(contextualModel,contextualCollection){if(!this.get('isViewing'))contextualModel.set('isLocked',true);},updateVisibility(){this.set('isVisible',this.get('contextualCount')>0);}});})(Drupal,Backbone);;
(function($,Drupal,Backbone,_){Drupal.contextualToolbar.AuralView=Backbone.View.extend({announcedOnce:false,initialize(options){this.options=options;this.listenTo(this.model,'change',this.render);this.listenTo(this.model,'change:isViewing',this.manageTabbing);$(document).on('keyup',_.bind(this.onKeypress,this));this.manageTabbing();},render(){this.$el.find('button').attr('aria-pressed',!this.model.get('isViewing'));return this;},manageTabbing(){let tabbingContext=this.model.get('tabbingContext');if(tabbingContext){if(tabbingContext.active)Drupal.announce(this.options.strings.tabbingReleased);tabbingContext.release();}if(!this.model.get('isViewing')){tabbingContext=Drupal.tabbingManager.constrain($('.contextual-toolbar-tab, .contextual'));this.model.set('tabbingContext',tabbingContext);this.announceTabbingConstraint();this.announcedOnce=true;}},announceTabbingConstraint(){const strings=this.options.strings;Drupal.announce(Drupal.formatString(strings.tabbingConstrained,{'@contextualsCount':Drupal.formatPlural(Drupal.contextual.collection.length,'@count contextual link','@count contextual links')}));Drupal.announce(strings.pressEsc);},onKeypress(event){if(!this.announcedOnce&&event.keyCode===9&&!this.model.get('isViewing')){this.announceTabbingConstraint();this.announcedOnce=true;}if(event.keyCode===27)this.model.set('isViewing',true);}});})(jQuery,Drupal,Backbone,_);;
(function(Drupal,Backbone){Drupal.contextualToolbar.VisualView=Backbone.View.extend({events(){const touchEndToClick=function(event){event.preventDefault();event.target.click();};return {click(){this.model.set('isViewing',!this.model.get('isViewing'));},touchend:touchEndToClick};},initialize(){this.listenTo(this.model,'change',this.render);this.listenTo(this.model,'change:isViewing',this.persist);},render(){this.$el.toggleClass('hidden',!this.model.get('isVisible'));this.$el.find('button').toggleClass('is-active',!this.model.get('isViewing'));return this;},persist(model,isViewing){if(!isViewing)localStorage.setItem('Drupal.contextualToolbar.isViewing','false');else localStorage.removeItem('Drupal.contextualToolbar.isViewing');}});})(Drupal,Backbone);;
(function($,Drupal){Drupal.behaviors.adminToolbar={attach:function(context,settings){$('a.toolbar-icon',context).removeAttr('title');$('ul.toolbar-menu li.menu-item--expanded a',context).on('focusin',function(){$('li.menu-item--expanded',context).removeClass('hover-intent');$(this).parents('li.menu-item--expanded').addClass('hover-intent');});$('ul.toolbar-menu li.menu-item a',context).keydown(function(e){if((e.shiftKey&&(e.keyCode||e.which)==9))if($(this).parent('.menu-item').prev().hasClass('menu-item--expanded'))$(this).parent('.menu-item').prev().addClass('hover-intent');});$('.toolbar-menu:first-child > .menu-item:not(.menu-item--expanded) a, .toolbar-tab > a',context).on('focusin',function(){$('.menu-item--expanded').removeClass('hover-intent');});$('.toolbar-menu:first-child > .menu-item',context).on('hover',function(){$(this,'a').css("background: #fff;");});$('ul:not(.toolbar-menu)',context).on({mousemove:function(){$('li.menu-item--expanded').removeClass('hover-intent');},hover:function(){$('li.menu-item--expanded').removeClass('hover-intent');}});if(window.matchMedia("(max-width: 767px)").matches&&$('body').hasClass('toolbar-tray-open')){$('body').removeClass('toolbar-tray-open');$('#toolbar-item-administration').removeClass('is-active');$('#toolbar-item-administration-tray').removeClass('is-active');};}};})(jQuery,Drupal);;
;(function(factory){'use strict';if(typeof define==='function'&&define.amd)define(['jquery'],factory);else{if(jQuery&&!jQuery.fn.hoverIntent)factory(jQuery);}})(function($){'use strict';var _cfg={interval:100,sensitivity:6,timeout:0};var INSTANCE_COUNT=0;var cX,cY;var track=function(ev){cX=ev.pageX;cY=ev.pageY;};var compare=function(ev,$el,s,cfg){if(Math.sqrt((s.pX-cX)*(s.pX-cX)+(s.pY-cY)*(s.pY-cY))<cfg.sensitivity){$el.off(s.event,track);delete s.timeoutId;s.isActive=true;ev.pageX=cX;ev.pageY=cY;delete s.pX;delete s.pY;return cfg.over.apply($el[0],[ev]);}else{s.pX=cX;s.pY=cY;s.timeoutId=setTimeout(function(){compare(ev,$el,s,cfg);},cfg.interval);}};var delay=function(ev,$el,s,out){delete $el.data('hoverIntent')[s.id];return out.apply($el[0],[ev]);};$.fn.hoverIntent=function(handlerIn,handlerOut,selector){var instanceId=INSTANCE_COUNT++;var cfg=$.extend({},_cfg);if($.isPlainObject(handlerIn)){cfg=$.extend(cfg,handlerIn);if(!$.isFunction(cfg.out))cfg.out=cfg.over;}else if($.isFunction(handlerOut))cfg=$.extend(cfg,{over:handlerIn,out:handlerOut,selector});else cfg=$.extend(cfg,{over:handlerIn,out:handlerIn,selector:handlerOut});var handleHover=function(e){var ev=$.extend({},e);var $el=$(this);var hoverIntentData=$el.data('hoverIntent');if(!hoverIntentData)$el.data('hoverIntent',(hoverIntentData={}));var state=hoverIntentData[instanceId];if(!state)hoverIntentData[instanceId]=state={id:instanceId};if(state.timeoutId)state.timeoutId=clearTimeout(state.timeoutId);var mousemove=state.event='mousemove.hoverIntent.hoverIntent'+instanceId;if(e.type==='mouseenter'){if(state.isActive)return;state.pX=ev.pageX;state.pY=ev.pageY;$el.off(mousemove,track).on(mousemove,track);state.timeoutId=setTimeout(function(){compare(ev,$el,state,cfg);},cfg.interval);}else{if(!state.isActive)return;$el.off(mousemove,track);state.timeoutId=setTimeout(function(){delay(ev,$el,state,cfg.out);},cfg.timeout);}};return this.on({'mouseenter.hoverIntent':handleHover,'mouseleave.hoverIntent':handleHover},cfg.selector);};});;
(function($){$(document).ready(function(){$('.toolbar-tray-horizontal li.menu-item--expanded, .toolbar-tray-horizontal ul li.menu-item--expanded .menu-item').hoverIntent({over:function(){$(this).parent().find('li').removeClass('hover-intent');$(this).addClass('hover-intent');},out:function(){$(this).removeClass('hover-intent');},timeout:250});});})(jQuery);;
(function($,Drupal,drupalSettings){const pathInfo=drupalSettings.path;const escapeAdminPath=sessionStorage.getItem('escapeAdminPath');const windowLocation=window.location;if(!pathInfo.currentPathIsAdmin&&!/destination=/.test(windowLocation.search))sessionStorage.setItem('escapeAdminPath',windowLocation);Drupal.behaviors.escapeAdmin={attach(){const toolbarEscape=once('escapeAdmin','[data-toolbar-escape-admin]');if(toolbarEscape.length&&pathInfo.currentPathIsAdmin&&escapeAdminPath!==null)$(toolbarEscape).attr('href',escapeAdminPath);}};})(jQuery,Drupal,drupalSettings);;
((Drupal,drupalSettings)=>{const replacementsSelector=`script[data-big-pipe-replacement-for-placeholder-with-id]`;const ajaxObject=Drupal.ajax({url:'',base:false,element:false,progress:false});function mapTextContentToAjaxResponse(content){if(content==='')return false;try{return JSON.parse(content);}catch(e){return false;}}function processReplacement(replacement){const id=replacement.dataset.bigPipeReplacementForPlaceholderWithId;const content=replacement.textContent.trim();if(typeof drupalSettings.bigPipePlaceholderIds[id]==='undefined')return;const response=mapTextContentToAjaxResponse(content);if(response===false)return;delete drupalSettings.bigPipePlaceholderIds[id];ajaxObject.success(response,'success');}function checkMutation(node){return Boolean(node.nodeType===Node.ELEMENT_NODE&&node.nodeName==='SCRIPT'&&node.dataset&&node.dataset.bigPipeReplacementForPlaceholderWithId&&typeof drupalSettings.bigPipePlaceholderIds[node.dataset.bigPipeReplacementForPlaceholderWithId]!=='undefined');}function checkMutationAndProcess(node){if(checkMutation(node))processReplacement(node);else{if(node.parentNode!==null&&checkMutation(node.parentNode))processReplacement(node.parentNode);}}function processMutations(mutations){mutations.forEach(({addedNodes,type,target})=>{addedNodes.forEach(checkMutationAndProcess);if(type==='characterData'&&checkMutation(target.parentNode)&&drupalSettings.bigPipePlaceholderIds[target.parentNode.dataset.bigPipeReplacementForPlaceholderWithId]===true)processReplacement(target.parentNode);});}const observer=new MutationObserver(processMutations);Drupal.attachBehaviors(document);document.querySelectorAll(replacementsSelector).forEach(processReplacement);observer.observe(document.body,{childList:true,subtree:true,characterData:true});window.addEventListener('DOMContentLoaded',()=>{const mutations=observer.takeRecords();observer.disconnect();if(mutations.length)processMutations(mutations);Drupal.ajax.instances[ajaxObject.instanceIndex]=null;});})(Drupal,drupalSettings);;
