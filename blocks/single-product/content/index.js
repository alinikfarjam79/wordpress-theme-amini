(function (blocks, element, i18n, blockEditor, components, serverSideRender) {
  "use strict";

  const el = element.createElement;
  const InnerBlocks = blockEditor.InnerBlocks;
  const InspectorControls = blockEditor.InspectorControls;
  const ServerSideRender =
    serverSideRender && (serverSideRender.default || serverSideRender);
  const useBlockProps = blockEditor.useBlockProps;
  const ColorPalette = blockEditor.ColorPalette;
  const FontSizePicker = blockEditor.FontSizePicker;
  const PanelBody = components.PanelBody;
  const SelectControl = components.SelectControl;
  const TextControl = components.TextControl;

  const parentBlockName = "theme/single-product-content";
  const allowedBlocks = [
    "theme/product-breadcrumb",
    "theme/product-main",
    "theme/product-benefits",
    "theme/product-details",
    "theme/product-similar-products",
    "theme/product-related-products",
  ];
  const template = allowedBlocks.map(function (blockName) {
    return [blockName];
  });

  function serverSidePreview(blockName) {
    return el(ServerSideRender, {
      block: blockName,
    });
  }

  blocks.registerBlockType(parentBlockName, {
    edit: function () {
      const blockProps = useBlockProps({
        className: "single-product-page single-product-content",
        "data-single-product-root": true,
      });

      return el(
        "div",
        blockProps,
        el(InnerBlocks, {
          allowedBlocks: allowedBlocks,
          template: template,
          templateLock: false,
          renderAppender: InnerBlocks.ButtonBlockAppender,
        })
      );
    },
    save: function () {
      return el(InnerBlocks.Content);
    },
    title: i18n.__("Single Product Content", "my-theme"),
    description: i18n.__(
      "Displays the custom dynamic WooCommerce single-product layout.",
      "my-theme"
    ),
    icon: "products",
    category: "woocommerce",
    supports: {
      align: false,
      anchor: false,
      customClassName: true,
      html: false,
    },
  });

  [
    {
      name: "theme/product-details",
      title: i18n.__("Product Details", "my-theme"),
    },
    {
      name: "theme/product-similar-products",
      title: i18n.__("Similar Products", "my-theme"),
    },
    {
      name: "theme/product-related-products",
      title: i18n.__("Related Products", "my-theme"),
    },
  ].forEach(function (block) {
    const settings = {
      edit: function () {
        return serverSidePreview(block.name);
      },
      save: function () {
        return null;
      },
      title: block.title,
      category: "woocommerce",
      icon: "products",
      supports: {
        align: false,
        anchor: false,
        customClassName: true,
        html: false,
      },
    };

    settings.parent = [parentBlockName];

    blocks.registerBlockType(block.name, settings);
  });
})(
  window.wp.blocks,
  window.wp.element,
  window.wp.i18n,
  window.wp.blockEditor,
  window.wp.components,
  window.wp.serverSideRender
);
