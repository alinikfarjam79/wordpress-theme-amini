(function (blocks, element, i18n, blockEditor, components, serverSideRender) {
  "use strict";
  const el = element.createElement;
  const InnerBlocks = blockEditor.InnerBlocks;
  const InspectorControls = blockEditor.InspectorControls;
  const TextControl = components.TextControl;
  const PanelBody = components.PanelBody;
  const ServerSideRender = serverSideRender && (serverSideRender.default || serverSideRender);

  function container(name, className, children, innerClassName) {
    blocks.registerBlockType(name, {
      edit: function () {
        const innerBlocks = el(InnerBlocks, {
          allowedBlocks: children,
          template: children.map(function (child) { return [child]; }),
          templateLock: false,
          renderAppender: InnerBlocks.ButtonBlockAppender,
        });
        return el("div", blockEditor.useBlockProps({ className: className }),
          innerClassName ? el("aside", { className: innerClassName }, innerBlocks) : innerBlocks
        );
      },
      save: function () { return el(InnerBlocks.Content); },
    });
  }

  container("theme/product-purchase", "single-product-section__purchase", [
    "theme/product-purchase-info", "theme/product-price", "theme/product-purchase-actions", "theme/product-shipping",
  ], "single-product-purchase-card");
  container("theme/product-purchase-info", "single-product-purchase-card__info-list", [
    "theme/product-guarantee", "theme/product-weight",
  ]);
  container("theme/product-purchase-actions", "single-product-purchase-card__actions", [
    "theme/product-add-to-cart", "theme/product-bulk-order",
  ]);

  [
    ["theme/product-guarantee", i18n.__("ضمانت محصول", "my-theme"), "shield", [{ key: "label", label: i18n.__("متن ضمانت", "my-theme") }]],
    ["theme/product-weight", i18n.__("وزن محصول", "my-theme"), "editor-ol", []],
    ["theme/product-price", i18n.__("قیمت محصول", "my-theme"), "money-alt", []],
    ["theme/product-add-to-cart", i18n.__("افزودن به سبد خرید", "my-theme"), "cart", [
      { key: "label", label: i18n.__("متن دکمه افزودن به سبد", "my-theme") },
      { key: "contactLabel", label: i18n.__("متن دکمه تماس", "my-theme") },
      { key: "contactUrl", label: i18n.__("لینک دکمه تماس (اختیاری)", "my-theme") },
    ]],
    ["theme/product-bulk-order", i18n.__("سفارش عمده", "my-theme"), "products", [{ key: "label", label: i18n.__("متن دکمه", "my-theme") }]],
    ["theme/product-shipping", i18n.__("روش و هزینه ارسال", "my-theme"), "car", [
      { key: "title", label: i18n.__("عنوان", "my-theme") },
      { key: "detailsLabel", label: i18n.__("متن لینک جزئیات", "my-theme") },
    ]],
  ].forEach(function (item) {
    blocks.registerBlockType(item[0], {
      edit: function (props) {
        const controls = item[3].map(function (field) {
          return el(TextControl, { key: field.key, label: field.label, value: props.attributes[field.key] || "", onChange: function (value) {
            const update = {}; update[field.key] = value; props.setAttributes(update);
          }});
        });
        return el(element.Fragment, {}, controls.length ? el(InspectorControls, {}, el(PanelBody, { title: i18n.__("تنظیمات محتوا", "my-theme"), initialOpen: true }, controls)) : null,
          el(ServerSideRender, { block: item[0], attributes: props.attributes }));
      },
      save: function () { return null; },
      title: item[1], category: "woocommerce", icon: item[2],
    });
  });
})(window.wp.blocks, window.wp.element, window.wp.i18n, window.wp.blockEditor, window.wp.components, window.wp.serverSideRender);
