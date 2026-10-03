(function(blocks,element,i18n,blockEditor,components,data,ServerSideRender){
  "use strict";
  const el=element.createElement,Fragment=element.Fragment,useState=element.useState,useBlockProps=blockEditor.useBlockProps,InspectorControls=blockEditor.InspectorControls,PanelColorSettings=blockEditor.PanelColorSettings;
  const PanelBody=components.PanelBody,TextControl=components.TextControl,TextareaControl=components.TextareaControl,ToggleControl=components.ToggleControl,RangeControl=components.RangeControl,ComboboxControl=components.ComboboxControl,SelectControl=components.SelectControl,Button=components.Button,Notice=components.Notice;
  function titleOf(product){return product&&product.title?(product.title.rendered||product.title.raw||""):"";}
  blocks.registerBlockType("my-theme/special-offers",{
    edit:function(props){
      const attrs=props.attributes,setAttributes=props.setAttributes,rawIds=Array.isArray(attrs.productIds)?attrs.productIds:[],ids=rawIds.reduce(function(result,value){const id=Number(value);if(id>0&&result.indexOf(id)===-1)result.push(id);return result;},[]),searchState=useState(""),search=searchState[0],setSearch=searchState[1],replaceState=useState(0),replaceId=replaceState[0],setReplaceId=replaceState[1];
      const found=data.useSelect(function(select){return select("core").getEntityRecords("postType","product",{search:search,per_page:20,status:"publish"});},[search]);
      const selected=data.useSelect(function(select){return ids.length?select("core").getEntityRecords("postType","product",{include:ids,per_page:Math.max(ids.length,1),status:"publish"}):[];},[ids.join(",")]);
      const selectedMap={};(selected||[]).forEach(function(item){selectedMap[item.id]=item;});
      function move(index,delta){const target=index+delta;if(target<0||target>=ids.length)return;const next=ids.slice(),item=next[index];next[index]=next[target];next[target]=item;setAttributes({productIds:next});}
      function replace(index,value){const id=Number(value);if(!id||ids.indexOf(id)!==-1)return;const next=ids.slice();next[index]=id;setAttributes({productIds:next});setReplaceId(0);}
      return el(Fragment,{},
        el(InspectorControls,{},
          el(PanelBody,{title:i18n.__("محتوای سکشن","my-theme"),initialOpen:true},
            el(TextareaControl,{label:i18n.__("عنوان سکشن","my-theme"),help:i18n.__("برای شکستن عنوان در دو خط از Enter استفاده کنید.","my-theme"),value:attrs.title||"",onChange:function(value){setAttributes({title:value});}}),
            el(TextControl,{label:i18n.__("ابتدای متن دکمه","my-theme"),value:attrs.buttonLead||"",onChange:function(value){setAttributes({buttonLead:value});}}),
            el(TextControl,{label:i18n.__("متن اصلی دکمه","my-theme"),value:attrs.buttonText||"",onChange:function(value){setAttributes({buttonText:value});}}),
            el(TextControl,{label:i18n.__("لینک دکمه","my-theme"),value:attrs.buttonUrl||"",onChange:function(value){setAttributes({buttonUrl:value});}})
          ),
          el(PanelBody,{title:i18n.__("انتخاب محصولات","my-theme"),initialOpen:true},
            el(ComboboxControl,{label:i18n.__("جستجو و افزودن محصول","my-theme"),value:null,options:(found||[]).filter(function(item){return ids.indexOf(item.id)===-1;}).map(function(item){return{value:String(item.id),label:titleOf(item)};}),onFilterValueChange:setSearch,onChange:function(value){const id=Number(value);if(id&&ids.indexOf(id)===-1)setAttributes({productIds:ids.concat([id])});}}),
            ids.length?ids.map(function(id,index){const product=selectedMap[id];return el("div",{className:"special-offers-editor__item",key:id},
              el("div",{className:"special-offers-editor__product"},
                el("span",{className:"special-offers-editor__product-title"},product?titleOf(product):i18n.sprintf(i18n.__("محصول شماره %d (حذف‌شده یا منتشرنشده)","my-theme"),id)),
                el("div",{className:"special-offers-editor__actions"},
                  el(Button,{variant:"tertiary",disabled:index===0,onClick:function(){move(index,-1);}},i18n.__("قبل","my-theme")),
                  el(Button,{variant:"tertiary",disabled:index===ids.length-1,onClick:function(){move(index,1);}},i18n.__("بعد","my-theme")),
                  el(Button,{variant:"tertiary",onClick:function(){setReplaceId(replaceId===id?0:id);}},i18n.__("جایگزینی","my-theme")),
                  el(Button,{variant:"tertiary",isDestructive:true,onClick:function(){setReplaceId(0);setAttributes({productIds:ids.filter(function(item){return item!==id;})});}},i18n.__("حذف","my-theme"))
                )
              ),
              replaceId===id?el(ComboboxControl,{className:"special-offers-editor__replace",label:i18n.__("محصول جایگزین","my-theme"),value:null,options:(found||[]).filter(function(item){return ids.indexOf(item.id)===-1;}).map(function(item){return{value:String(item.id),label:titleOf(item)};}),onFilterValueChange:setSearch,onChange:function(value){replace(index,value);}}):null
            );}):el(Notice,{status:"info",isDismissible:false},i18n.__("اگر محصولی انتخاب نشود، جدیدترین محصولات نمایش داده می‌شوند.","my-theme")),
            !ids.length?el(ToggleControl,{label:i18n.__("فقط محصولات حراج‌شده","my-theme"),checked:attrs.onlyOnSale,onChange:function(value){setAttributes({onlyOnSale:value});}}):null,
            !ids.length?el(RangeControl,{label:i18n.__("تعداد محصولات","my-theme"),value:attrs.productsCount,min:1,max:24,onChange:function(value){setAttributes({productsCount:value});}}):null
          ),
          el(PanelBody,{title:i18n.__("تنظیمات اسلایدر","my-theme"),initialOpen:false},
            el(ToggleControl,{label:i18n.__("نمایش فلش‌ها","my-theme"),checked:attrs.showNavigation,onChange:function(value){setAttributes({showNavigation:value});}}),
            el(SelectControl,{label:i18n.__("تعداد کارت در دسکتاپ","my-theme"),value:String(attrs.slidesPerView||0),options:[{label:i18n.__("خودکار (حالت اصلی)","my-theme"),value:"0"},{label:"2",value:"2"},{label:"3",value:"3"},{label:"4",value:"4"},{label:"5",value:"5"},{label:"6",value:"6"}],onChange:function(value){setAttributes({slidesPerView:Number(value)});}}),
            el(RangeControl,{label:i18n.__("فاصله کارت‌ها","my-theme"),value:attrs.spaceBetween,min:0,max:80,onChange:function(value){setAttributes({spaceBetween:value});}})
          ),
          el(PanelColorSettings,{title:i18n.__("رنگ‌های آیتم محصول","my-theme"),initialOpen:false,colorSettings:[
            {value:attrs.itemBackgroundColor,onChange:function(value){setAttributes({itemBackgroundColor:value||""});},label:i18n.__("پس‌زمینه کارت","my-theme")},
            {value:attrs.itemBorderColor,onChange:function(value){setAttributes({itemBorderColor:value||""});},label:i18n.__("رنگ حاشیه کارت","my-theme")},
            {value:attrs.itemTitleColor,onChange:function(value){setAttributes({itemTitleColor:value||""});},label:i18n.__("رنگ عنوان محصول","my-theme")},
            {value:attrs.itemPriceColor,onChange:function(value){setAttributes({itemPriceColor:value||""});},label:i18n.__("رنگ قیمت","my-theme")},
            {value:attrs.itemButtonColor,onChange:function(value){setAttributes({itemButtonColor:value||""});},label:i18n.__("رنگ دکمه افزودن","my-theme")}
          ]}),
          el(PanelBody,{title:i18n.__("ابعاد و ظاهر آیتم محصول","my-theme"),initialOpen:false},
            el(RangeControl,{label:i18n.__("ضخامت حاشیه","my-theme"),value:attrs.itemBorderWidth,min:0,max:10,onChange:function(value){setAttributes({itemBorderWidth:value});}}),
            el(RangeControl,{label:i18n.__("گردی گوشه‌ها","my-theme"),value:attrs.itemBorderRadius,min:0,max:60,onChange:function(value){setAttributes({itemBorderRadius:value});}}),
            el(RangeControl,{label:i18n.__("فاصله داخلی کارت","my-theme"),value:attrs.itemPadding,min:0,max:40,onChange:function(value){setAttributes({itemPadding:value});}}),
            el(RangeControl,{label:i18n.__("ارتفاع کارت","my-theme"),value:attrs.itemHeight,min:240,max:440,onChange:function(value){setAttributes({itemHeight:value});}}),
            el(RangeControl,{label:i18n.__("ارتفاع تصویر","my-theme"),value:attrs.itemImageHeight,min:70,max:220,onChange:function(value){setAttributes({itemImageHeight:value});}}),
            el(RangeControl,{label:i18n.__("اندازه عنوان محصول","my-theme"),value:attrs.itemTitleSize,min:10,max:28,onChange:function(value){setAttributes({itemTitleSize:value});}}),
            el(RangeControl,{label:i18n.__("اندازه قیمت","my-theme"),value:attrs.itemPriceSize,min:12,max:36,onChange:function(value){setAttributes({itemPriceSize:value});}}),
            el(Button,{variant:"secondary",onClick:function(){setAttributes({itemBackgroundColor:"",itemBorderColor:"",itemBorderWidth:0,itemBorderRadius:15,itemPadding:16,itemHeight:300,itemImageHeight:130,itemTitleColor:"",itemTitleSize:14,itemPriceColor:"",itemPriceSize:20,itemButtonColor:""});}},i18n.__("بازگردانی استایل اصلی آیتم‌ها","my-theme"))
          ),
          el(PanelBody,{title:i18n.__("نشان تخفیف ویژه","my-theme"),initialOpen:false},
            el(ToggleControl,{label:i18n.__("نمایش نشان تخفیف ویژه","my-theme"),checked:attrs.showSpecialBadge,onChange:function(value){setAttributes({showSpecialBadge:value});}}),
            attrs.showSpecialBadge?el(TextControl,{label:i18n.__("متن نشان","my-theme"),value:attrs.specialBadgeText||"",onChange:function(value){setAttributes({specialBadgeText:value});}}):null,
            attrs.showSpecialBadge?el(PanelColorSettings,{title:i18n.__("رنگ‌های نشان","my-theme"),initialOpen:false,colorSettings:[
              {value:attrs.specialBadgeBackgroundColor,onChange:function(value){setAttributes({specialBadgeBackgroundColor:value||""});},label:i18n.__("پس‌زمینه نشان","my-theme")},
              {value:attrs.specialBadgeTextColor,onChange:function(value){setAttributes({specialBadgeTextColor:value||""});},label:i18n.__("رنگ متن نشان","my-theme")}
            ]}):null,
            attrs.showSpecialBadge?el(RangeControl,{label:i18n.__("اندازه متن نشان","my-theme"),value:attrs.specialBadgeFontSize,min:10,max:28,onChange:function(value){setAttributes({specialBadgeFontSize:value});}}):null,
            attrs.showSpecialBadge?el(RangeControl,{label:i18n.__("گردی نشان","my-theme"),value:attrs.specialBadgeRadius,min:0,max:999,onChange:function(value){setAttributes({specialBadgeRadius:value});}}):null
          ),
          el(PanelBody,{title:i18n.__("نشان درصد تخفیف","my-theme"),initialOpen:false},
            el(ToggleControl,{label:i18n.__("نمایش درصد تخفیف","my-theme"),checked:attrs.showDiscountBadge,onChange:function(value){setAttributes({showDiscountBadge:value});}}),
            attrs.showDiscountBadge?el(PanelColorSettings,{title:i18n.__("رنگ‌های درصد تخفیف","my-theme"),initialOpen:false,colorSettings:[
              {value:attrs.discountBadgeBackgroundColor,onChange:function(value){setAttributes({discountBadgeBackgroundColor:value||""});},label:i18n.__("پس‌زمینه درصد","my-theme")},
              {value:attrs.discountBadgeTextColor,onChange:function(value){setAttributes({discountBadgeTextColor:value||""});},label:i18n.__("رنگ عدد درصد","my-theme")}
            ]}):null,
            attrs.showDiscountBadge?el(RangeControl,{label:i18n.__("اندازه عدد درصد","my-theme"),value:attrs.discountBadgeFontSize,min:10,max:28,onChange:function(value){setAttributes({discountBadgeFontSize:value});}}):null,
            attrs.showDiscountBadge?el(RangeControl,{label:i18n.__("گردی نشان درصد","my-theme"),value:attrs.discountBadgeRadius,min:0,max:999,onChange:function(value){setAttributes({discountBadgeRadius:value});}}):null,
            el(Button,{variant:"secondary",onClick:function(){setAttributes({showSpecialBadge:true,specialBadgeText:"تخفیف ویژه",specialBadgeBackgroundColor:"",specialBadgeTextColor:"",specialBadgeFontSize:14,specialBadgeRadius:999,showDiscountBadge:true,discountBadgeBackgroundColor:"",discountBadgeTextColor:"",discountBadgeFontSize:16,discountBadgeRadius:999});}},i18n.__("بازگردانی استایل اصلی نشان‌ها","my-theme"))
          )
        ),
        el("div",useBlockProps({className:"special-offers-editor"}),el(ServerSideRender,{block:"my-theme/special-offers",attributes:attrs,httpMethod:"POST"}))
      );
    },save:function(){return null;}
  });
})(window.wp.blocks,window.wp.element,window.wp.i18n,window.wp.blockEditor,window.wp.components,window.wp.data,window.wp.serverSideRender);
