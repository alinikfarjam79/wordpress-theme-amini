(function (blocks, element, blockEditor, serverSideRender) {
  "use strict";
  blocks.registerBlockType("my-theme/about-features-section", {
    edit: function (props) {
      return element.createElement(
        "div",
        blockEditor.useBlockProps({ dir: "rtl" }),
        element.createElement(serverSideRender, {
          block: "my-theme/about-features-section",
          attributes: props.attributes,
        })
      );
    },
    save: function () { return null; },
  });
})(window.wp.blocks, window.wp.element, window.wp.blockEditor, window.wp.serverSideRender);
