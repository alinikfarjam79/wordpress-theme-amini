(function(blocks,element,i18n,blockEditor,components,data,ServerSideRender){
  "use strict";
  const el=element.createElement,Fragment=element.Fragment,useState=element.useState,useBlockProps=blockEditor.useBlockProps,InspectorControls=blockEditor.InspectorControls,PanelColorSettings=blockEditor.PanelColorSettings;
  const PanelBody=components.PanelBody,TextControl=components.TextControl,ToggleControl=components.ToggleControl,RangeControl=components.RangeControl,ComboboxControl=components.ComboboxControl,SelectControl=components.SelectControl,Button=components.Button,Notice=components.Notice;
  function titleOf(post){return post&&post.title?(post.title.rendered||post.title.raw||""):"";}
  blocks.registerBlockType("my-theme/latest-articles",{
    edit:function(props){
      const attrs=props.attributes,setAttributes=props.setAttributes,ids=Array.isArray(attrs.postIds)?attrs.postIds:[],state=useState(""),search=state[0],setSearch=state[1];
      const found=data.useSelect(function(select){return select("core").getEntityRecords("postType","post",{search:search,per_page:20,status:"publish"});},[search]);
      const selected=data.useSelect(function(select){return ids.length?select("core").getEntityRecords("postType","post",{include:ids,per_page:Math.max(ids.length,1),status:"publish"}):[];},[ids.join(",")]);
      const categories=data.useSelect(function(select){return select("core").getEntityRecords("taxonomy","category",{per_page:100,hide_empty:false});},[]);
      const selectedMap={};(selected||[]).forEach(function(item){selectedMap[item.id]=item;});
      function move(index,delta){const target=index+delta;if(target<0||target>=ids.length)return;const next=ids.slice(),item=next[index];next[index]=next[target];next[target]=item;setAttributes({postIds:next});}
      return el(Fragment,{},
        el(InspectorControls,{},
          el(PanelBody,{title:i18n.__("محتوای سکشن","my-theme"),initialOpen:true},
            el(TextControl,{label:i18n.__("عنوان سکشن","my-theme"),value:attrs.title||"",onChange:function(value){setAttributes({title:value});}}),
            el(ToggleControl,{label:i18n.__("نمایش دکمه مشاهده همه","my-theme"),checked:attrs.showViewAll,onChange:function(value){setAttributes({showViewAll:value});}}),
            attrs.showViewAll?el(TextControl,{label:i18n.__("ابتدای متن دکمه","my-theme"),value:attrs.viewAllLead||"",onChange:function(value){setAttributes({viewAllLead:value});}}):null,
            attrs.showViewAll?el(TextControl,{label:i18n.__("متن اصلی دکمه","my-theme"),value:attrs.viewAllText||"",onChange:function(value){setAttributes({viewAllText:value});}}):null,
            attrs.showViewAll?el(TextControl,{label:i18n.__("لینک مشاهده همه","my-theme"),value:attrs.viewAllUrl||"",onChange:function(value){setAttributes({viewAllUrl:value});}}):null
          ),
          el(PanelBody,{title:i18n.__("انتخاب مقالات","my-theme"),initialOpen:true},
            el(ToggleControl,{label:i18n.__("نمایش مقالات مرتبط با نوشته فعلی","my-theme"),checked:attrs.relatedToCurrentPost,onChange:function(value){setAttributes({relatedToCurrentPost:value,postIds:value?[]:attrs.postIds});}}),
            !attrs.relatedToCurrentPost?el(ComboboxControl,{label:i18n.__("جستجو و افزودن مقاله","my-theme"),value:null,options:(found||[]).filter(function(item){return ids.indexOf(item.id)===-1;}).map(function(item){return{value:String(item.id),label:titleOf(item)};}),onFilterValueChange:setSearch,onChange:function(value){const id=Number(value);if(id&&ids.indexOf(id)===-1)setAttributes({postIds:ids.concat([id])});}}):null,
            !attrs.relatedToCurrentPost?(ids.length?ids.map(function(id,index){const post=selectedMap[id];return el("div",{className:"latest-articles-editor__post",key:id},el("span",{},post?titleOf(post):("#"+id)),el("div",{},el(Button,{variant:"tertiary",disabled:index===0,onClick:function(){move(index,-1);}},i18n.__("قبل","my-theme")),el(Button,{variant:"tertiary",disabled:index===ids.length-1,onClick:function(){move(index,1);}},i18n.__("بعد","my-theme")),el(Button,{variant:"tertiary",isDestructive:true,onClick:function(){setAttributes({postIds:ids.filter(function(item){return item!==id;})});}},i18n.__("حذف","my-theme"))));}):el(Notice,{status:"info",isDismissible:false},i18n.__("اگر مقاله‌ای انتخاب نشود، آخرین مقالات به‌صورت خودکار نمایش داده می‌شوند.","my-theme"))):el(Notice,{status:"info",isDismissible:false},i18n.__("مقالات هم‌دسته با نوشته فعلی نمایش داده می‌شوند.","my-theme")),
            !attrs.relatedToCurrentPost&&!ids.length?el(SelectControl,{label:i18n.__("دسته‌بندی","my-theme"),value:String(attrs.categoryId||0),options:[{label:i18n.__("همه دسته‌ها","my-theme"),value:"0"}].concat((categories||[]).map(function(item){return{label:item.name,value:String(item.id)};})),onChange:function(value){setAttributes({categoryId:Number(value)});}}):null,
            !ids.length?el(RangeControl,{label:i18n.__("تعداد مقالات","my-theme"),value:attrs.postsCount,min:1,max:12,onChange:function(value){setAttributes({postsCount:value});}}):null,
            !ids.length?el(SelectControl,{label:i18n.__("مرتب‌سازی بر اساس","my-theme"),value:attrs.orderBy,options:[{label:i18n.__("تاریخ انتشار","my-theme"),value:"date"},{label:i18n.__("عنوان","my-theme"),value:"title"},{label:i18n.__("تصادفی","my-theme"),value:"rand"}],onChange:function(value){setAttributes({orderBy:value});}}):null,
            !ids.length&&attrs.orderBy!=="rand"?el(SelectControl,{label:i18n.__("ترتیب","my-theme"),value:attrs.order,options:[{label:i18n.__("نزولی","my-theme"),value:"DESC"},{label:i18n.__("صعودی","my-theme"),value:"ASC"}],onChange:function(value){setAttributes({order:value});}}):null
          ),
          el(PanelBody,{title:i18n.__("محتوای کارت مقاله","my-theme"),initialOpen:false},
            el(ToggleControl,{label:i18n.__("نمایش خلاصه","my-theme"),checked:attrs.showExcerpt,onChange:function(value){setAttributes({showExcerpt:value});}}),
            attrs.showExcerpt?el(RangeControl,{label:i18n.__("تعداد کلمات خلاصه","my-theme"),value:attrs.excerptLength,min:5,max:50,onChange:function(value){setAttributes({excerptLength:value});}}):null,
            el(ToggleControl,{label:i18n.__("نمایش لینک بیشتر بخوانید","my-theme"),checked:attrs.showReadMore,onChange:function(value){setAttributes({showReadMore:value});}}),
            attrs.showReadMore?el(TextControl,{label:i18n.__("متن لینک","my-theme"),value:attrs.readMoreText||"",onChange:function(value){setAttributes({readMoreText:value});}}):null,
            el(ToggleControl,{label:i18n.__("نمایش نقطه‌های اسلایدر موبایل","my-theme"),checked:attrs.showPagination,onChange:function(value){setAttributes({showPagination:value});}})
          ),
          el(PanelColorSettings,{title:i18n.__("رنگ‌های کارت مقاله","my-theme"),initialOpen:false,colorSettings:[
            {value:attrs.cardBackgroundColor,onChange:function(value){setAttributes({cardBackgroundColor:value||""});},label:i18n.__("پس‌زمینه کادر متن","my-theme")},
            {value:attrs.cardTitleColor,onChange:function(value){setAttributes({cardTitleColor:value||""});},label:i18n.__("رنگ عنوان مقاله","my-theme")},
            {value:attrs.cardExcerptColor,onChange:function(value){setAttributes({cardExcerptColor:value||""});},label:i18n.__("رنگ خلاصه","my-theme")},
            {value:attrs.cardLinkColor,onChange:function(value){setAttributes({cardLinkColor:value||""});},label:i18n.__("رنگ بیشتر بخوانید","my-theme")}
          ]}),
          el(PanelBody,{title:i18n.__("ظاهر کارت مقاله","my-theme"),initialOpen:false},
            el(RangeControl,{label:i18n.__("گردی تصویر","my-theme"),value:attrs.imageRadius,min:0,max:60,onChange:function(value){setAttributes({imageRadius:value});}}),
            el(RangeControl,{label:i18n.__("گردی کادر متن","my-theme"),value:attrs.cardRadius,min:0,max:60,onChange:function(value){setAttributes({cardRadius:value});}}),
            el(RangeControl,{label:i18n.__("اندازه عنوان مقاله","my-theme"),value:attrs.titleSize,min:12,max:32,onChange:function(value){setAttributes({titleSize:value});}}),
            el(RangeControl,{label:i18n.__("اندازه خلاصه","my-theme"),value:attrs.excerptSize,min:9,max:22,onChange:function(value){setAttributes({excerptSize:value});}}),
            el(RangeControl,{label:i18n.__("اندازه بیشتر بخوانید","my-theme"),value:attrs.readMoreSize,min:10,max:24,onChange:function(value){setAttributes({readMoreSize:value});}}),
            el(Button,{variant:"secondary",onClick:function(){setAttributes({cardBackgroundColor:"",cardTitleColor:"",cardExcerptColor:"",cardLinkColor:"",cardRadius:12,imageRadius:10,titleSize:20,excerptSize:12,readMoreSize:14});}},i18n.__("بازگردانی استایل اصلی کارت‌ها","my-theme"))
          )
        ),
        el("div",useBlockProps({className:"latest-articles-editor"}),el(ServerSideRender,{block:"my-theme/latest-articles",attributes:attrs,httpMethod:"POST"}))
      );
    },save:function(){return null;}
  });
})(window.wp.blocks,window.wp.element,window.wp.i18n,window.wp.blockEditor,window.wp.components,window.wp.data,window.wp.serverSideRender);
