(function (blocks, element, i18n, blockEditor, components, serverSideRender) {
  "use strict";

  const el = element.createElement;
  const useBlockProps = blockEditor.useBlockProps;
  const InnerBlocks = blockEditor.InnerBlocks;
  const InspectorControls = blockEditor.InspectorControls;
  const PanelBody = components.PanelBody;
  const TextControl = components.TextControl;
  const ServerSideRender =
    serverSideRender && (serverSideRender.default || serverSideRender);

  blocks.registerBlockType("theme/product-category-filter-card", {
    attributes: {
      title: {
        type: "string",
        default: "فیلتر",
      },
      categoryLabel: {
        type: "string",
        default: "دسته‌بندی",
      },
      colorLabel: {
        type: "string",
        default: "رنگ",
      },
    },
    edit: function (props) {
      const attributes = props.attributes || {};
      const setAttributes = props.setAttributes;
      const blockProps = useBlockProps();
      const template = [
        [
          "theme/product-category-color-filter",
          {
            label: attributes.colorLabel || "رنگ",
          },
        ],
        ["theme/product-category-brand-filter"],
        ["theme/product-category-price-filter"],
      ];

      return el(
        "div",
        blockProps,
        el(
          InspectorControls,
          null,
          el(
            PanelBody,
            {
              title: i18n.__("Filter card text", "my-theme"),
              initialOpen: true,
            },
            el(TextControl, {
              label: i18n.__("Title", "my-theme"),
              value: attributes.title || "",
              onChange: function (title) {
                setAttributes({ title: title });
              },
            }),
            el(TextControl, {
              label: i18n.__("Category label", "my-theme"),
              value: attributes.categoryLabel || "",
              onChange: function (categoryLabel) {
                setAttributes({ categoryLabel: categoryLabel });
              },
            }),
            el(TextControl, {
              label: i18n.__("Color label", "my-theme"),
              value: attributes.colorLabel || "",
              onChange: function (colorLabel) {
                setAttributes({ colorLabel: colorLabel });
              },
            })
          )
        ),
        el(ServerSideRender, {
          block: "theme/product-category-filter-card",
          attributes: attributes,
        }),
        el(InnerBlocks, {
          allowedBlocks: [
            "theme/product-category-color-filter",
            "theme/product-category-brand-filter",
            "theme/product-category-price-filter",
          ],
          template: template,
          templateLock: "insert",
        })
      );
    },
    save: function () {
      return el(InnerBlocks.Content);
    },
    title: i18n.__("Product Category Filter Card", "my-theme"),
    category: "woocommerce",
    icon: "filter",
    parent: ["theme/product-category-sidebar"],
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
      border: {
        color: true,
        radius: true,
        style: true,
        width: true,
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
