(function (blocks, element, i18n, blockEditor, components, serverSideRender) {
  "use strict";
  const el = element.createElement;
  const InnerBlocks = blockEditor.InnerBlocks;
  const InspectorControls = blockEditor.InspectorControls;
  const MediaUpload = blockEditor.MediaUpload;
  const MediaUploadCheck = blockEditor.MediaUploadCheck;
  const ServerSideRender = serverSideRender && (serverSideRender.default || serverSideRender);
  const template = [
    ["theme/product-benefit-item", { icon: "delivery", title: "ارسال سریع", description: "با هماهنگی قبلی" }],
    ["theme/product-benefit-item", { icon: "return", title: "ضمانت مرجوعی", description: "تا ۷ روز کاری" }],
    ["theme/product-benefit-item", { icon: "authenticity", title: "تضمین اصالت", description: "از بهترین برندها" }],
    ["theme/product-benefit-item", { icon: "consultation", title: "مشاوره تخصصی", description: "قبل و بعد از خرید" }],
  ];

  blocks.registerBlockType("theme/product-benefits", {
    edit: function () {
      return el("section", blockEditor.useBlockProps({ className: "product-benefits-section" }),
        el("div", { className: "product-benefits-box" }, el("div", { className: "product-benefits-list" },
          el(InnerBlocks, { allowedBlocks: ["theme/product-benefit-item"], template: template, templateLock: false, renderAppender: InnerBlocks.ButtonBlockAppender })
        ))
      );
    },
    save: function () { return el(InnerBlocks.Content); },
  });

  blocks.registerBlockType("theme/product-benefit-item", {
    edit: function (props) {
      const a = props.attributes;
      const set = props.setAttributes;
      return el(element.Fragment, {},
        el(InspectorControls, {}, el(components.PanelBody, { title: "محتوای مزیت", initialOpen: true },
          el(components.TextControl, { label: "عنوان", value: a.title || "", onChange: function (v) { set({ title: v }); } }),
          el(components.TextControl, { label: "توضیحات", value: a.description || "", onChange: function (v) { set({ description: v }); } }),
          el(components.SelectControl, { label: "آیکون پیش‌فرض", value: a.icon || "delivery", options: [{ label: "ارسال", value: "delivery" }, { label: "مرجوعی", value: "return" }, { label: "اصالت", value: "authenticity" }, { label: "مشاوره", value: "consultation" }], onChange: function (v) { set({ icon: v }); } }),
          el(MediaUploadCheck, {}, el(MediaUpload, { allowedTypes: ["image"], value: a.imageId || 0, onSelect: function (m) { set({ imageId: m.id || 0, imageUrl: m.url || "", imageAlt: m.alt || "" }); }, render: function (open) { return el(components.Button, { variant: "secondary", onClick: open.open }, a.imageUrl ? "تغییر تصویر" : "انتخاب تصویر"); } })),
          a.imageUrl ? el(components.Button, { isDestructive: true, onClick: function () { set({ imageId: 0, imageUrl: "", imageAlt: "" }); } }, "حذف تصویر") : null
        )),
        el(ServerSideRender, { block: "theme/product-benefit-item", attributes: a })
      );
    },
    save: function () { return null; },
    title: i18n.__("آیتم مزیت محصول", "my-theme"), category: "woocommerce", icon: "yes-alt", parent: ["theme/product-benefits"],
  });
})(window.wp.blocks, window.wp.element, window.wp.i18n, window.wp.blockEditor, window.wp.components, window.wp.serverSideRender);
