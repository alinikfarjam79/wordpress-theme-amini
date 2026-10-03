(function (blocks, element, i18n, blockEditor, serverSideRender) {
  "use strict";

  const el = element.createElement;
  const useBlockProps = blockEditor.useBlockProps;
  const ServerSideRender =
    serverSideRender && (serverSideRender.default || serverSideRender);

  blocks.registerBlockType("my-theme/mini-cart", {
    edit: function () {
      const blockProps = useBlockProps({
        className: "mini-cart-block-editor-preview",
      });

      return el(
        "div",
        blockProps,
        el(ServerSideRender, {
          block: "my-theme/mini-cart",
        })
      );
    },
    save: function () {
      return null;
    },
    title: i18n.__("سبد خرید کوچک", "my-theme"),
    description: i18n.__("نمایش سبد خرید کوچک و تعداد محصولات", "my-theme"),
    category: "theme",
    icon: "cart",
    keywords: [
      i18n.__("سبد خرید", "my-theme"),
      i18n.__("سبد خرید کوچک", "my-theme"),
      "mini cart",
      "cart",
      "woocommerce",
    ],
    supports: {
      align: false,
      anchor: false,
      className: true,
      html: false,
      inserter: true,
      multiple: true,
      reusable: true,
      lock: true,
    },
  });
})(
  window.wp.blocks,
  window.wp.element,
  window.wp.i18n,
  window.wp.blockEditor,
  window.wp.serverSideRender
);
