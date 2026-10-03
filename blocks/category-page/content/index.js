(function (blocks, element, i18n, blockEditor, serverSideRender) {
  "use strict";

  const el = element.createElement;
  const useBlockProps = blockEditor.useBlockProps;
  const ServerSideRender =
    serverSideRender && (serverSideRender.default || serverSideRender);

  blocks.registerBlockType("theme/product-category-content", {
    edit: function (props) {
      const blockProps = useBlockProps({
        className: "product-category-layout__content",
      });

      return el(
        "div",
        blockProps,
        el(ServerSideRender, {
          block: "theme/product-category-content",
          attributes: props.attributes || {},
        })
      );
    },
    save: function () {
      return null;
    },
    title: i18n.__("Product Category Content", "my-theme"),
    category: "woocommerce",
    icon: "grid-view",
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
})(
  window.wp.blocks,
  window.wp.element,
  window.wp.i18n,
  window.wp.blockEditor,
  window.wp.serverSideRender
);
