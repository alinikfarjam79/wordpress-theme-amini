(function (blocks, element, i18n, blockEditor, components, serverSideRender) {
  "use strict";

  const el = element.createElement;
  const useBlockProps = blockEditor.useBlockProps;
  const InspectorControls = blockEditor.InspectorControls;
  const PanelBody = components.PanelBody;
  const TextControl = components.TextControl;
  const ServerSideRender =
    serverSideRender && (serverSideRender.default || serverSideRender);

  blocks.registerBlockType("theme/product-category-brand-filter", {
    attributes: {
      label: {
        type: "string",
        default: "برند",
      },
      placeholder: {
        type: "string",
        default: "جستجو برند",
      },
    },
    edit: function (props) {
      const attributes = props.attributes || {};
      const setAttributes = props.setAttributes;
      const blockProps = useBlockProps();

      return el(
        "div",
        blockProps,
        el(
          InspectorControls,
          null,
          el(
            PanelBody,
            {
              title: i18n.__("Brand filter", "my-theme"),
              initialOpen: true,
            },
            el(TextControl, {
              label: i18n.__("Label", "my-theme"),
              value: attributes.label || "",
              onChange: function (label) {
                setAttributes({ label: label });
              },
            }),
            el(TextControl, {
              label: i18n.__("Search placeholder", "my-theme"),
              value: attributes.placeholder || "",
              onChange: function (placeholder) {
                setAttributes({ placeholder: placeholder });
              },
            })
          )
        ),
        el(ServerSideRender, {
          block: "theme/product-category-brand-filter",
          attributes: attributes,
        })
      );
    },
    save: function () {
      return null;
    },
    title: i18n.__("Product Category Brand Filter", "my-theme"),
    category: "woocommerce",
    icon: "tag",
    parent: ["theme/product-category-filter-card"],
    supports: {
      align: false,
      anchor: true,
      className: true,
      html: false,
      spacing: {
        margin: true,
        padding: true,
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
  window.wp.components,
  window.wp.serverSideRender
);
