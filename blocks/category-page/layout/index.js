(function (blocks, element, i18n, blockEditor) {
  "use strict";

  const el = element.createElement;
  const InnerBlocks = blockEditor.InnerBlocks;
  const useBlockProps = blockEditor.useBlockProps;
  const allowedBlocks = [
    "theme/product-category-sidebar",
    "theme/product-category-content",
  ];
  const template = [
    [
      "theme/product-category-sidebar",
      {},
      [
        [
          "theme/product-category-filter-card",
          {},
          [
            ["theme/product-category-color-filter"],
            ["theme/product-category-brand-filter"],
            ["theme/product-category-price-filter"],
          ],
        ],
      ],
    ],
    ["theme/product-category-content"],
  ];

  blocks.registerBlockType("theme/product-category-layout", {
    edit: function () {
      const blockProps = useBlockProps({
        className: "product-category-layout",
      });

      return el(
        "section",
        blockProps,
        el(InnerBlocks, {
          allowedBlocks: allowedBlocks,
          template: template,
          templateLock: "insert",
        })
      );
    },
    save: function () {
      return el(InnerBlocks.Content);
    },
    deprecated: [
      {
        save: function () {
          const blockProps = blockEditor.useBlockProps.save({
            className: "product-category-layout",
          });

          return el("section", blockProps, el(InnerBlocks.Content));
        },
      },
    ],
    title: i18n.__("Product Category Layout", "my-theme"),
    category: "woocommerce",
    icon: "columns",
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
