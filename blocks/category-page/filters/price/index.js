(function (blocks, element, i18n, blockEditor, components, serverSideRender) {
  "use strict";

  const el = element.createElement;
  const useBlockProps = blockEditor.useBlockProps;
  const InspectorControls = blockEditor.InspectorControls;
  const PanelBody = components.PanelBody;
  const TextControl = components.TextControl;
  const ServerSideRender =
    serverSideRender && (serverSideRender.default || serverSideRender);

  blocks.registerBlockType("theme/product-category-price-filter", {
    attributes: {
      label: {
        type: "string",
        default: "محدوده قیمت",
      },
      minLabel: {
        type: "string",
        default: "محدوده قیمت از",
      },
      maxLabel: {
        type: "string",
        default: "محدوده قیمت تا",
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
              title: i18n.__("Price filter", "my-theme"),
              initialOpen: true,
            },
            el(TextControl, {
              label: i18n.__("Accordion label", "my-theme"),
              value: attributes.label || "",
              onChange: function (label) {
                setAttributes({ label: label });
              },
            }),
            el(TextControl, {
              label: i18n.__("Minimum label", "my-theme"),
              value: attributes.minLabel || "",
              onChange: function (minLabel) {
                setAttributes({ minLabel: minLabel });
              },
            }),
            el(TextControl, {
              label: i18n.__("Maximum label", "my-theme"),
              value: attributes.maxLabel || "",
              onChange: function (maxLabel) {
                setAttributes({ maxLabel: maxLabel });
              },
            })
          )
        ),
        el(ServerSideRender, {
          block: "theme/product-category-price-filter",
          attributes: attributes,
        })
      );
    },
    save: function () {
      return null;
    },
    title: i18n.__("Product Category Price Filter", "my-theme"),
    category: "woocommerce",
    icon: "money-alt",
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
