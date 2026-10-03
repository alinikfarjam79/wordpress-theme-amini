(function (blocks, element, i18n, blockEditor, components) {
  "use strict";
  const el=element.createElement, Fragment=element.Fragment, useBlockProps=blockEditor.useBlockProps, InspectorControls=blockEditor.InspectorControls, MediaUpload=blockEditor.MediaUpload, MediaUploadCheck=blockEditor.MediaUploadCheck;
  const PanelBody=components.PanelBody, TextControl=components.TextControl, Button=components.Button;
  blocks.registerBlockType("my-theme/why-amini", {
    edit:function(props){
      const attrs=props.attributes, setAttributes=props.setAttributes, items=Array.isArray(attrs.items)?attrs.items:[];
      function updateItem(index,value){setAttributes({items:items.map(function(item,i){return i===index?value:item;})});}
      function move(index,delta){const target=index+delta;if(target<0||target>=items.length)return;const next=items.slice(),item=next[index];next[index]=next[target];next[target]=item;setAttributes({items:next});}
      return el(Fragment,{},
        el(InspectorControls,{},
          el(PanelBody,{title:i18n.__("محتوای سکشن","my-theme"),initialOpen:true},
            el(TextControl,{label:i18n.__("عنوان","my-theme"),value:attrs.title||"",onChange:function(value){setAttributes({title:value});}}),
            el(TextControl,{label:i18n.__("متن جایگزین تصویر","my-theme"),value:attrs.imageAlt||"",onChange:function(value){setAttributes({imageAlt:value});}}),
            el(MediaUploadCheck,{},el(MediaUpload,{allowedTypes:["image"],value:attrs.imageId||0,onSelect:function(media){setAttributes({imageId:media.id||0,imageUrl:media.url||"",imageAlt:media.alt||attrs.imageAlt||""});},render:function(mediaProps){return el(Button,{variant:"secondary",onClick:mediaProps.open},attrs.imageUrl?i18n.__("تغییر تصویر","my-theme"):i18n.__("انتخاب تصویر","my-theme"));}}))
          ),
          el(PanelBody,{title:i18n.__("مزیت‌ها","my-theme"),initialOpen:true},
            items.map(function(item,index){return el("div",{className:"why-amini-editor__item",key:index},
              el(TextControl,{label:i18n.__("متن مزیت","my-theme")+" "+(index+1),value:typeof item==="string"?item:(item.text||""),onChange:function(value){updateItem(index,value);}}),
              el("div",{className:"why-amini-editor__actions"},
                el(Button,{variant:"secondary",disabled:index===0,onClick:function(){move(index,-1);}},i18n.__("قبل","my-theme")),
                el(Button,{variant:"secondary",disabled:index===items.length-1,onClick:function(){move(index,1);}},i18n.__("بعد","my-theme")),
                el(Button,{variant:"secondary",isDestructive:true,onClick:function(){setAttributes({items:items.filter(function(_,i){return i!==index;})});}},i18n.__("حذف","my-theme"))
              ));}),
            el(Button,{variant:"primary",onClick:function(){setAttributes({items:items.concat([i18n.__("مزیت جدید","my-theme")])});}},i18n.__("افزودن مزیت","my-theme"))
          )
        ),
        el("div",useBlockProps({className:"wp-block-group why-box is-nowrap is-layout-flex wp-block-group-is-layout-flex why-amini-editor-preview"}),
          el("div",{className:"wp-block-group why-text is-layout-constrained wp-block-group-is-layout-constrained"},el("h2",{className:"wp-block-heading"},attrs.title||""),el("ul",{className:"why-list-items wp-block-list"},items.map(function(item,index){return el("li",{className:"why-list",key:index},el("span",{className:"why-amini-editor__icon"}),typeof item==="string"?item:(item.text||""));}))),
          attrs.imageUrl?el("figure",{className:"wp-block-image size-large why-image"},el("img",{src:attrs.imageUrl,alt:attrs.imageAlt||""})):null
        )
      );
    },save:function(){return null;}
  });
})(window.wp.blocks,window.wp.element,window.wp.i18n,window.wp.blockEditor,window.wp.components);
