(function (blocks, element, i18n, blockEditor, components, serverSideRender) {
  "use strict";
  const el = element.createElement;
  const MediaUpload = blockEditor.MediaUpload;
  const MediaUploadCheck = blockEditor.MediaUploadCheck;
  const InspectorControls = blockEditor.InspectorControls;
  const useBlockProps = blockEditor.useBlockProps;
  const Button = components.Button;
  const PanelBody = components.PanelBody;
  const TextControl = components.TextControl;
  const ServerSideRender = serverSideRender && (serverSideRender.default || serverSideRender);

  blocks.registerBlockType("my-theme/header-logo", {
    edit: function (props) {
      const attributes = props.attributes;
      const setAttributes = props.setAttributes;
      const chooseImage = el(MediaUploadCheck, {}, el(MediaUpload, {
        allowedTypes: ["image"],
        value: attributes.mediaId,
        onSelect: function (media) {
          setAttributes({ mediaId: media.id || 0, mediaUrl: media.url || "", alt: media.alt || "" });
        },
        render: function (mediaProps) {
          return el(Button, { variant: attributes.mediaUrl ? "secondary" : "primary", onClick: mediaProps.open }, attributes.mediaUrl ? i18n.__("تغییر لوگو", "my-theme") : i18n.__("انتخاب لوگو", "my-theme"));
        }
      }));

      return el("div", useBlockProps({ className: "header-logo-block-editor-preview" }),
        el(InspectorControls, {}, el(PanelBody, { title: i18n.__("تنظیمات لوگو", "my-theme"), initialOpen: true },
          chooseImage,
          attributes.mediaUrl ? el(Button, { isDestructive: true, variant: "link", onClick: function () { setAttributes({ mediaId: 0, mediaUrl: "", alt: "" }); } }, i18n.__("استفاده از لوگوی پیش‌فرض قالب", "my-theme")) : null,
          el(TextControl, { label: i18n.__("متن جایگزین", "my-theme"), value: attributes.alt, onChange: function (value) { setAttributes({ alt: value }); } })
        )),
        el(ServerSideRender, { block: "my-theme/header-logo", attributes: attributes })
      );
    },
    save: function () { return null; }
  });
})(window.wp.blocks, window.wp.element, window.wp.i18n, window.wp.blockEditor, window.wp.components, window.wp.serverSideRender);
