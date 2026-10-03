(function (blocks, element, i18n, blockEditor, serverSideRender) {
  "use strict";

  const el = element.createElement;
  const InnerBlocks = blockEditor.InnerBlocks;
  const useBlockProps = blockEditor.useBlockProps;
  const ServerSideRender = serverSideRender && (serverSideRender.default || serverSideRender);
  const children = [
    "theme/product-gallery",
    "theme/product-information",
    "theme/product-purchase",
  ];

  blocks.registerBlockType("theme/product-main", {
    edit: function () {
      return el(
        "div",
        useBlockProps({ className: "single-product-section" }),
        el(InnerBlocks, {
          allowedBlocks: children,
          template: children.map(function (name) { return [name]; }),
          templateLock: false,
          renderAppender: InnerBlocks.ButtonBlockAppender,
        })
      );
    },
    save: function () {
      return el(InnerBlocks.Content);
    },
  });

  [
    ["theme/product-gallery", i18n.__("گالری تصاویر محصول", "my-theme"), "format-gallery"],
  ].forEach(function (item) {
    blocks.registerBlockType(item[0], {
      edit: function () {
        return el(ServerSideRender, { block: item[0] });
      },
      save: function () { return null; },
      title: item[1],
      category: "woocommerce",
      icon: item[2],
      parent: ["theme/product-main"],
    });
  });
})(window.wp.blocks, window.wp.element, window.wp.i18n, window.wp.blockEditor, window.wp.serverSideRender);
