(function (blocks, element, i18n, blockEditor, components, serverSideRender) {
  "use strict";
  const el = element.createElement;
  const InspectorControls = blockEditor.InspectorControls;
  const useBlockProps = blockEditor.useBlockProps;
  const PanelBody = components.PanelBody;
  const TextControl = components.TextControl;
  const ServerSideRender = serverSideRender && (serverSideRender.default || serverSideRender);

  blocks.registerBlockType("my-theme/account-link", {
    edit: function (props) {
      return el("div", useBlockProps({ className: "account-link-block-editor-preview" }),
        el(InspectorControls, {}, el(PanelBody, { title: i18n.__("تنظیمات دکمه", "my-theme"), initialOpen: true },
          el(TextControl, { label: i18n.__("متن دکمه", "my-theme"), value: props.attributes.label, onChange: function (value) { props.setAttributes({ label: value }); } }),
          el(TextControl, { label: i18n.__("نشانی دلخواه (اختیاری)", "my-theme"), help: i18n.__("در صورت خالی بودن، برگه حساب کاربری ووکامرس استفاده می‌شود.", "my-theme"), value: props.attributes.url, onChange: function (value) { props.setAttributes({ url: value }); } })
        )),
        el(ServerSideRender, { block: "my-theme/account-link", attributes: props.attributes })
      );
    },
    save: function () { return null; }
  });
})(window.wp.blocks, window.wp.element, window.wp.i18n, window.wp.blockEditor, window.wp.components, window.wp.serverSideRender);
