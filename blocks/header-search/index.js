(function (blocks, element, i18n, blockEditor, serverSideRender) {
  "use strict";

  const el = element.createElement;
  const useBlockProps = blockEditor.useBlockProps;
  const ServerSideRender =
    serverSideRender && (serverSideRender.default || serverSideRender);

  blocks.registerBlockType("my-theme/header-search", {
    edit: function () {
      const blockProps = useBlockProps({
        className: "header-search-block-editor-preview",
      });

      return el(
        "div",
        blockProps,
        el(ServerSideRender, {
          block: "my-theme/header-search",
        })
      );
    },
    save: function () {
      return null;
    },
    title: i18n.__("جستجوی هدر", "my-theme"),
    description: i18n.__("فرم جستجوی مشترک هدر سایت", "my-theme"),
    category: "theme",
    icon: "search",
    supports: {
      align: false,
      anchor: false,
      className: true,
      html: false,
      reusable: false,
    },
  });
})(
  window.wp.blocks,
  window.wp.element,
  window.wp.i18n,
  window.wp.blockEditor,
  window.wp.serverSideRender
);
