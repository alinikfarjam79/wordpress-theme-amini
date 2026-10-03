(function (blocks, element, i18n, blockEditor) {
  "use strict";

  const el = element.createElement;
  const InnerBlocks = blockEditor.InnerBlocks;
  const useBlockProps = blockEditor.useBlockProps;

  blocks.registerBlockType("theme/product-category-sidebar", {
    edit: function () {
      const blockProps = useBlockProps({
        className: "product-category-layout__sidebar",
      });

      return el(
        "aside",
        blockProps,
        el(
          "div",
          {
            className: "product-category-filter-sticky",
          },
          el(InnerBlocks, {
            renderAppender: InnerBlocks.ButtonBlockAppender,
          })
        )
      );
    },
    save: function () {
      return el(InnerBlocks.Content);
    },
    deprecated: [
      {
        save: function () {
          const blockProps = blockEditor.useBlockProps.save({
            className: "product-category-layout__sidebar",
          });

          return el("aside", blockProps, el(InnerBlocks.Content));
        },
      },
    ],
    title: i18n.__("Product Category Sidebar", "my-theme"),
    category: "woocommerce",
    icon: "sidebar",
    parent: ["theme/product-category-layout"],
    supports: {
      align: false,
      anchor: true,
      className: true,
      html: false,
      spacing: {
        margin: true,
        padding: true,
        blockGap: true,
      },
      color: {
        background: true,
        text: true,
      },
      typography: {
        fontSize: true,
        lineHeight: true,
      },
    },
  });
})(window.wp.blocks, window.wp.element, window.wp.i18n, window.wp.blockEditor);
