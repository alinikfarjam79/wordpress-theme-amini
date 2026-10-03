(function(blocks,element,i18n,blockEditor,serverSideRender){"use strict";
const el=element.createElement,InnerBlocks=blockEditor.InnerBlocks,ServerSideRender=serverSideRender&&(serverSideRender.default||serverSideRender);
function container(name,className,children){blocks.registerBlockType(name,{edit:function(){return el("div",blockEditor.useBlockProps({className:className}),el(InnerBlocks,{allowedBlocks:children,template:children.map(function(n){return[n];}),templateLock:false,renderAppender:InnerBlocks.ButtonBlockAppender}));},save:function(){return el(InnerBlocks.Content);}});}
container("theme/product-information","single-product-section__details",["theme/product-overview","theme/product-actions","theme/product-meta"]);
container("theme/product-overview","single-product-overview",["theme/product-title","theme/product-rating","theme/product-attributes"]);
container("theme/product-actions","single-product-actions-row",["theme/product-wishlist","theme/product-share"]);
container("theme/product-meta","single-product-meta-summary",["theme/product-category-meta","theme/product-brand-meta"]);
[
 ["theme/product-title","عنوان محصول","heading"],["theme/product-rating","امتیاز محصول","star-filled"],["theme/product-attributes","ویژگی‌های محصول","list-view"],
 ["theme/product-wishlist","علاقه‌مندی","heart"],["theme/product-share","اشتراک‌گذاری","share"],
 ["theme/product-category-meta","دسته‌بندی محصول","category"],["theme/product-brand-meta","برند محصول","tag"]
].forEach(function(x){blocks.registerBlockType(x[0],{edit:function(p){return el(ServerSideRender,{block:x[0],attributes:p.attributes});},save:function(){return null;},title:i18n.__(x[1],"my-theme"),category:"woocommerce",icon:x[2]});});
})(window.wp.blocks,window.wp.element,window.wp.i18n,window.wp.blockEditor,window.wp.serverSideRender);
