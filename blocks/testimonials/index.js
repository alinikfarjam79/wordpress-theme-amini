(function(blocks,element,i18n,blockEditor,components){
  "use strict";
  const el=element.createElement,Fragment=element.Fragment,useBlockProps=blockEditor.useBlockProps,InspectorControls=blockEditor.InspectorControls,MediaUpload=blockEditor.MediaUpload,MediaUploadCheck=blockEditor.MediaUploadCheck;
  const PanelBody=components.PanelBody,TextControl=components.TextControl,TextareaControl=components.TextareaControl,ToggleControl=components.ToggleControl,RangeControl=components.RangeControl,Button=components.Button;
  blocks.registerBlockType("my-theme/testimonials",{
    edit:function(props){
      const attrs=props.attributes,setAttributes=props.setAttributes,items=Array.isArray(attrs.testimonials)?attrs.testimonials.filter(function(item){return item&&typeof item==="object"&&!Array.isArray(item);}):[];
      function patchItem(index,patch){setAttributes({testimonials:items.map(function(item,i){return i===index?Object.assign({},item,patch):item;})});}
      function move(index,delta){const target=index+delta;if(target<0||target>=items.length)return;const next=items.slice(),item=next[index];next[index]=next[target];next[target]=item;setAttributes({testimonials:next});}
      function add(){setAttributes({testimonials:items.concat([{imageId:0,imageUrl:"",imageAlt:"",name:i18n.__("نام مشتری","my-theme"),role:i18n.__("عنوان مشتری","my-theme"),text:i18n.__("متن نظر مشتری","my-theme")}])});}
      return el(Fragment,{},
        el(InspectorControls,{},
          el(PanelBody,{title:i18n.__("تنظیمات سکشن","my-theme"),initialOpen:true},
            el(TextControl,{label:i18n.__("عنوان سکشن","my-theme"),value:attrs.title||"",onChange:function(value){setAttributes({title:value});}}),
            el(ToggleControl,{label:i18n.__("نمایش فلش‌ها","my-theme"),checked:attrs.showNavigation,onChange:function(value){setAttributes({showNavigation:value});}}),
            el(ToggleControl,{label:i18n.__("تکرار اسلایدر","my-theme"),checked:attrs.loop,onChange:function(value){setAttributes({loop:value});}}),
            el(ToggleControl,{label:i18n.__("پخش خودکار","my-theme"),checked:attrs.autoplay,onChange:function(value){setAttributes({autoplay:value});}}),
            attrs.autoplay?el(RangeControl,{label:i18n.__("زمان تعویض اسلاید","my-theme"),value:attrs.delay,min:1000,max:10000,step:500,onChange:function(value){setAttributes({delay:value});}}):null
          ),
          el(PanelBody,{title:i18n.__("مدیریت نظرات","my-theme"),initialOpen:true},
            items.map(function(item,index){return el("div",{className:"testimonials-editor__item",key:index},
              el("strong",{},i18n.__("نظر","my-theme")+" "+(index+1)),
              item.imageUrl?el("img",{src:item.imageUrl,alt:item.imageAlt||""}):null,
              el(MediaUploadCheck,{},el(MediaUpload,{allowedTypes:["image"],value:item.imageId||0,onSelect:function(media){patchItem(index,{imageId:media.id||0,imageUrl:media.url||"",imageAlt:media.alt||item.imageAlt||""});},render:function(mediaProps){return el(Button,{variant:"secondary",onClick:mediaProps.open},item.imageUrl?i18n.__("تغییر تصویر","my-theme"):i18n.__("انتخاب تصویر","my-theme"));}})),
              el(TextControl,{label:i18n.__("نام مشتری","my-theme"),value:item.name||"",onChange:function(value){patchItem(index,{name:value});}}),
              el(TextControl,{label:i18n.__("عنوان یا شغل","my-theme"),value:item.role||"",onChange:function(value){patchItem(index,{role:value});}}),
              el(TextControl,{label:i18n.__("متن جایگزین تصویر","my-theme"),value:item.imageAlt||"",onChange:function(value){patchItem(index,{imageAlt:value});}}),
              el(TextareaControl,{label:i18n.__("متن نظر","my-theme"),value:item.text||"",onChange:function(value){patchItem(index,{text:value});}}),
              el("div",{className:"testimonials-editor__actions"},
                el(Button,{variant:"secondary",disabled:index===0,onClick:function(){move(index,-1);}},i18n.__("قبل","my-theme")),
                el(Button,{variant:"secondary",disabled:index===items.length-1,onClick:function(){move(index,1);}},i18n.__("بعد","my-theme")),
                el(Button,{variant:"secondary",isDestructive:true,onClick:function(){setAttributes({testimonials:items.filter(function(_,i){return i!==index;})});}},i18n.__("حذف","my-theme"))
              )
            );}),
            el(Button,{variant:"primary",onClick:add},i18n.__("افزودن نظر","my-theme"))
          )
        ),
        el("div",useBlockProps({className:"wp-block-group testimonials-section is-layout-constrained wp-block-group-is-layout-constrained testimonials-editor-preview"}),
          el("h2",{className:"wp-block-heading has-text-align-center section-title"},attrs.title||""),
          el("div",{className:"wp-block-group testimonial-slider-wrap is-layout-constrained wp-block-group-is-layout-constrained"},
            items.length?el("div",{className:"wp-block-group swiper testimonialSwiper is-layout-constrained wp-block-group-is-layout-constrained"},
              el("div",{className:"wp-block-group swiper-wrapper is-layout-constrained wp-block-group-is-layout-constrained"},items.slice(0,1).map(function(item,index){return el("div",{className:"wp-block-group swiper-slide is-layout-constrained wp-block-group-is-layout-constrained",key:index},el("div",{className:"wp-block-group testimonial-card is-nowrap is-layout-flex wp-block-group-is-layout-flex"},el("div",{className:"wp-block-group testimonial-user is-layout-constrained wp-block-group-is-layout-constrained"},item.imageUrl?el("figure",{className:"wp-block-image size-thumbnail is-resized"},el("img",{src:item.imageUrl,alt:item.imageAlt||"",style:{width:"94px",height:"94px"}})):null,el("div",{className:"testimonial-user-title"},el("p",{className:"wp-block-paragraph testimonial-user-name"},el("strong",{},item.name||"")),el("p",{className:"wp-block-paragraph testimonial-user-role"},item.role||""))),el("p",{className:"testimonial-text wp-block-paragraph"},item.text||"")));})),
              attrs.showNavigation&&items.length>1?el("div",{className:"swiper-button-prev testimonial-prev",role:"button","aria-label":i18n.__("نظر قبلی","my-theme")}):null,
              attrs.showNavigation&&items.length>1?el("div",{className:"swiper-button-next testimonial-next",role:"button","aria-label":i18n.__("نظر بعدی","my-theme")}):null
            ):el("p",{className:"testimonials-editor__empty"},i18n.__("هنوز نظری اضافه نشده است.","my-theme"))
          )
        )
      );
    },save:function(){return null;}
  });
})(window.wp.blocks,window.wp.element,window.wp.i18n,window.wp.blockEditor,window.wp.components);
