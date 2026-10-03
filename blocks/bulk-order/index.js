(function(blocks,element,i18n,blockEditor,components){
  "use strict";
  const el=element.createElement,useBlockProps=blockEditor.useBlockProps,useInnerBlocksProps=blockEditor.useInnerBlocksProps,InnerBlocks=blockEditor.InnerBlocks,InspectorControls=blockEditor.InspectorControls,PanelBody=components.PanelBody,RangeControl=components.RangeControl;
  blocks.registerBlockType("my-theme/bulk-order",{
    edit:function(props){
      const attrs=props.attributes;
      const buttonHeight=Math.min(80,Math.max(32,Number(attrs.buttonHeight)||44));
      const template=[
        ["core/group",{className:"bulk-content",layout:{type:"constrained"}},[
          ["core/heading",{textAlign:"center",level:2,content:attrs.title||i18n.__("خرید عمده رنگ و ابزارآلات","my-theme")}],
          ["core/paragraph",{align:"center",content:attrs.description||i18n.__("با بهترین قیمت، مستقیم از نمایندگی برندهای معروف","my-theme")}],
          ["core/buttons",{layout:{type:"flex",justifyContent:"center"}},[
            ["core/button",{text:attrs.buttonText||i18n.__("مشاهده پیش فاکتور","my-theme"),url:attrs.buttonUrl||"#",className:"latest-articles-btn"}]
          ]]
        ]],
        ["core/image",{id:attrs.imageId||0,url:attrs.imageUrl||"https://aminirang.ir/wp-content/uploads/2026/06/images-1-1.png",alt:attrs.imageAlt||i18n.__("خرید عمده رنگ","my-theme")}]
      ];
      const blockProps=useBlockProps({
        className:"wp-block-group alignfull bulk-pattern is-layout-constrained wp-block-group-is-layout-constrained",
        style:{"--bulk-order-button-height":buttonHeight+"px"}
      });
      const innerProps=useInnerBlocksProps({
        className:"wp-block-group bulk-inner has-secondary-background-color has-background is-content-justification-space-between is-nowrap is-layout-flex wp-block-group-is-layout-flex"
      },{
        allowedBlocks:["core/group","core/image"],
        template:template,
        templateLock:false,
        renderAppender:InnerBlocks.ButtonBlockAppender
      });
      return el(element.Fragment,null,
        el(InspectorControls,null,
          el(PanelBody,{title:i18n.__("تنظیمات دکمه","my-theme"),initialOpen:true},
            el(RangeControl,{
              label:i18n.__("ارتفاع دکمه (پیکسل)","my-theme"),
              value:buttonHeight,
              min:32,
              max:80,
              step:1,
              onChange:function(value){props.setAttributes({buttonHeight:value});}
            })
          )
        ),
        el("div",blockProps,el("div",innerProps))
      );
    },
    save:function(){
      return el(InnerBlocks.Content);
    }
  });
})(window.wp.blocks,window.wp.element,window.wp.i18n,window.wp.blockEditor,window.wp.components);
