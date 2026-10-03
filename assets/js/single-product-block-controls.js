(function (wp) {
  "use strict";
  const el = wp.element.createElement;
  const Fragment = wp.element.Fragment;
  const InspectorControls = wp.blockEditor.InspectorControls;
  const PanelBody = wp.components.PanelBody;
  const RangeControl = wp.components.RangeControl;
  const SelectControl = wp.components.SelectControl;
  const ColorPalette = wp.blockEditor.ColorPalette;
  const createHigherOrderComponent = wp.compose.createHigherOrderComponent;
  const useSelect = wp.data.useSelect;
  const useDispatch = wp.data.useDispatch;
  const keys = {
    designTextColor: { type: "string", default: "" }, designBackgroundColor: { type: "string", default: "" },
    designFontSize: { type: "number" }, designFontWeight: { type: "string", default: "" }, designLineHeight: { type: "number" },
    designMarginTop: { type: "number" }, designMarginBottom: { type: "number" }, designPaddingTop: { type: "number" },
    designPaddingBottom: { type: "number" }, designPaddingInline: { type: "number" }, designGap: { type: "number" },
    designBorderColor: { type: "string", default: "" }, designBorderWidth: { type: "number" }, designBorderRadius: { type: "number" },
    designWidth: { type: "number" }, designMaxWidth: { type: "number" },
  };
  const contentFields = {
    "theme/product-attributes": [["featuresButtonLabel","متن مشاهده همه ویژگی‌ها","مشاهده همه ویژگی‌ها"]],
    "theme/product-wishlist": [["wishlistLabel","متن علاقه‌مندی","افزودن به علاقمندی"]],
    "theme/product-share": [["shareLabel","عنوان اشتراک‌گذاری","اشتراک گذاری:"]],
    "theme/product-category-meta": [["categoryLabel","عنوان دسته‌بندی","دسته‌بندی:"]],
    "theme/product-brand-meta": [["brandLabel","عنوان برند","برند:"]],
    "theme/product-price": [["currencyLabel","واحد پول","تومان"]],
    "theme/product-details": [["descriptionLabel","عنوان توضیحات","توضیحات"],["specificationsLabel","عنوان مشخصات","مشخصات"],["reviewsLabel","عنوان دیدگاه‌ها","دیدگاه‌ها"],["reviewsMobileLabel","عنوان دیدگاه موبایل","امتیاز و دیدگاه مشتری‌ها"],["brandLabel","عنوان درباره برند","درباره برند"],["tabsAriaLabel","برچسب دسترس‌پذیری تب‌ها","اطلاعات محصول"],["moreLabel","متن بیشتر","بیشتر"],["viewAllSpecificationsLabel","متن مشاهده همه مشخصات","مشاهده همه مشخصات"]],
    "theme/product-similar-products": [["sectionTitle","عنوان سکشن","محصولات مشابه"],["previousLabel","برچسب دکمه قبلی","محصولات قبلی"],["nextLabel","برچسب دکمه بعدی","محصولات بعدی"]],
    "theme/product-related-products": [["sectionTitle","عنوان سکشن","محصولات مرتبط"],["previousLabel","برچسب دکمه قبلی","محصولات قبلی"],["nextLabel","برچسب دکمه بعدی","محصولات بعدی"]]
  };
  const toggleFields = {};
  const containers = ["theme/single-product-content","theme/product-main","theme/product-information","theme/product-overview","theme/product-actions","theme/product-meta","theme/product-purchase","theme/product-purchase-info","theme/product-purchase-actions","theme/product-benefits"];
  function isContainer(name){return containers.indexOf(name)!==-1;}
  function target(name) { return name === "theme/single-product-content" || name.indexOf("theme/product-") === 0; }
  wp.hooks.addFilter("blocks.registerBlockType", "my-theme/product-design-attributes", function (settings, name) {
    if (!target(name)) return settings;
    const designKeys=Object.assign({},keys);
    if(isContainer(name)){delete designKeys.designTextColor;delete designKeys.designFontSize;delete designKeys.designFontWeight;delete designKeys.designLineHeight;}
    settings.attributes = Object.assign({}, designKeys, settings.attributes || {});
    if(isContainer(name)){delete settings.attributes.designTextColor;delete settings.attributes.designFontSize;delete settings.attributes.designFontWeight;delete settings.attributes.designLineHeight;}
    (contentFields[name] || []).forEach(function(field){ settings.attributes[field[0]]={type:"string",default:field[2]}; });
    (toggleFields[name] || []).forEach(function(field){ settings.attributes[field[0]]={type:"boolean",default:true}; });
    return settings;
  });
  const withControls = createHigherOrderComponent(function (BlockEdit) {
    return function (props) {
      if (!target(props.name)) return el(BlockEdit, props);
      const a = props.attributes || {}; const set = props.setAttributes;
      const innerItems = useSelect(function(select){
        function flatten(blocks,depth){let out=[];(blocks||[]).forEach(function(block){out.push({clientId:block.clientId,name:block.name,depth:depth});out=out.concat(flatten(block.innerBlocks,depth+1));});return out;}
        return flatten(select("core/block-editor").getBlocks(props.clientId),0);
      },[props.clientId]);
      const blockEditorDispatch = useDispatch("core/block-editor");
      function range(label, key, min, max, step) { return el(RangeControl, { label: label, value: a[key], min: min, max: max, step: step || 1, allowReset: true, onChange: function (v) { const u={}; u[key]=v; set(u); } }); }
      const fields = contentFields[props.name] || [];
      const toggles = toggleFields[props.name] || [];
      const container = isContainer(props.name);
      return el(Fragment, {}, el(BlockEdit, props), el(InspectorControls, {},
        innerItems.length ? el(PanelBody,{title:"انتخاب آیتم داخلی",initialOpen:true},
          el(SelectControl,{label:"آیتمی که می‌خواهید تغییر دهید",value:"",options:[{label:"انتخاب کنید…",value:""}].concat(innerItems.map(function(item){const type=wp.blocks.getBlockType(item.name);return{label:Array(item.depth+1).join("— ")+(type&&type.title?type.title:item.name),value:item.clientId};})),onChange:function(clientId){if(clientId){blockEditorDispatch.selectBlock(clientId);}}})
        ):null,
        fields.length || toggles.length ? el(PanelBody, { title: "محتوا", initialOpen: true },
          toggles.map(function(field){return el(wp.components.ToggleControl,{key:field[0],label:field[1],checked:a[field[0]]!==false,onChange:function(v){const u={};u[field[0]]=v;set(u);}});}),
          fields.map(function(field){ return el(wp.components.TextControl,{key:field[0],label:field[1],value:a[field[0]]||"",onChange:function(v){const u={};u[field[0]]=v;set(u);}}); })
        ) : null,
        !container ? el(PanelBody, { title: "رنگ و تایپوگرافی", initialOpen: false },
          el("p", {}, "رنگ متن"), el(ColorPalette, { value: a.designTextColor, onChange: function(v){set({designTextColor:v||""});} }),
          range("اندازه فونت", "designFontSize", 8, 120),
          el(SelectControl, { label: "وزن فونت", value: a.designFontWeight || "", options: [{label:"پیش‌فرض",value:""},{label:"300",value:"300"},{label:"400",value:"400"},{label:"500",value:"500"},{label:"600",value:"600"},{label:"700",value:"700"},{label:"900",value:"900"}], onChange:function(v){set({designFontWeight:v});} }),
          range("ارتفاع خط", "designLineHeight", .5, 4, .1)
        ) : null,
        el(PanelBody, { title: "فاصله‌ها و اندازه", initialOpen: false },
          el("p", {}, "رنگ پس‌زمینه"), el(ColorPalette, { value: a.designBackgroundColor, onChange: function(v){set({designBackgroundColor:v||""});} }),
          range("فاصله بالا", "designMarginTop", 0, 300), range("فاصله پایین", "designMarginBottom", 0, 300),
          range("پدینگ بالا", "designPaddingTop", 0, 300), range("پدینگ پایین", "designPaddingBottom", 0, 300),
          range("پدینگ افقی", "designPaddingInline", 0, 300), range("فاصله داخلی", "designGap", 0, 200),
          range("عرض", "designWidth", 1, 2000), range("حداکثر عرض", "designMaxWidth", 1, 2000)
        ),
        el(PanelBody, { title: "کادر", initialOpen: false },
          el("p", {}, "رنگ کادر"), el(ColorPalette, { value:a.designBorderColor, onChange:function(v){set({designBorderColor:v||""});} }),
          range("ضخامت کادر", "designBorderWidth", 0, 20), range("گردی گوشه‌ها", "designBorderRadius", 0, 200)
        )
      ));
    };
  }, "withProductDesignControls");
  wp.hooks.addFilter("editor.BlockEdit", "my-theme/product-design-controls", withControls);
  const withCanvasStyles = createHigherOrderComponent(function(BlockListBlock){
    return function(props){
      if(!target(props.name)) return el(BlockListBlock,props);
      const a=props.attributes||{}, style=Object.assign({},(props.wrapperProps&&props.wrapperProps.style)||{}), classes=[];
      const container=isContainer(props.name);
      if(!container&&a.designTextColor){style["--product-block-text-color"]=a.designTextColor;classes.push("has-product-design-text-color");}
      if(a.designBackgroundColor)style.backgroundColor=a.designBackgroundColor;
      if(!container&&a.designFontSize!==undefined){style["--product-block-font-size"]=a.designFontSize+"px";classes.push("has-product-design-font-size");}
      if(!container&&a.designFontWeight){style["--product-block-font-weight"]=a.designFontWeight;classes.push("has-product-design-font-weight");}
      if(!container&&a.designLineHeight!==undefined){style["--product-block-line-height"]=a.designLineHeight;classes.push("has-product-design-line-height");}
      [["designMarginTop","marginTop"],["designMarginBottom","marginBottom"],["designPaddingTop","paddingTop"],["designPaddingBottom","paddingBottom"],["designPaddingInline","paddingInline"],["designGap","gap"],["designBorderWidth","borderWidth"],["designBorderRadius","borderRadius"],["designWidth","width"],["designMaxWidth","maxWidth"]].forEach(function(pair){if(a[pair[0]]!==undefined)style[pair[1]]=a[pair[0]]+"px";});
      if(a.designBorderColor)style.borderColor=a.designBorderColor;
      if(a.designBorderWidth>0)style.borderStyle="solid";
      const next=Object.assign({},props,{className:[props.className||"",classes.join(" ")].join(" ").trim(),wrapperProps:Object.assign({},props.wrapperProps||{},{style:style})});
      return el(BlockListBlock,next);
    };
  },"withProductCanvasStyles");
  wp.hooks.addFilter("editor.BlockListBlock","my-theme/product-canvas-styles",withCanvasStyles);
})(window.wp);
