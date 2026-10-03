(function (blocks, element, blockEditor) {
  "use strict";

  const el = element.createElement;
  const useBlockProps = blockEditor.useBlockProps;

  blocks.registerBlockType("my-theme/navigation-divider", {
    edit: function () {
      return el(
        "span",
        useBlockProps({
          className: "theme-navigation-divider",
          role: "presentation",
          title: "جداکننده منو"
        })
      );
    },
    save: function () { return null; }
  });
})(window.wp.blocks, window.wp.element, window.wp.blockEditor);
