(function (blocks, element, i18n, blockEditor, components, serverSideRender) {
  "use strict";

  const el = element.createElement;
  const useBlockProps = blockEditor.useBlockProps;
  const InspectorControls = blockEditor.InspectorControls;
  const ColorPalette = blockEditor.ColorPalette;
  const FontSizePicker = blockEditor.FontSizePicker;
  const PanelBody = components.PanelBody;
  const SelectControl = components.SelectControl;
  const TextControl = components.TextControl;
  const ToggleControl = components.ToggleControl;
  const RangeControl = components.RangeControl;
  const ServerSideRender =
    serverSideRender && (serverSideRender.default || serverSideRender);

  blocks.registerBlockType("theme/product-breadcrumb", {
    attributes: {
      fontSize: {
        type: "string",
      },
      fontWeight: {
        type: "string",
        default: "400",
      },
      inactiveColor: {
        type: "string",
        default: "rgba(103, 111, 113, 1)",
      },
      activeColor: {
        type: "string",
        default: "rgba(26, 30, 27, 1)",
      },
      separator: {
        type: "string",
        default: "/",
      },
      homeLabel: { type: "string", default: "خانه" },
      showHome: { type: "boolean", default: true },
      showCurrent: { type: "boolean", default: true },
      backgroundColor: { type: "string", default: "transparent" },
      borderColor: { type: "string", default: "transparent" },
      borderWidth: { type: "number", default: 0 },
      borderRadius: { type: "number", default: 0 },
      marginTop: { type: "number", default: 0 },
      marginBottom: { type: "number", default: 32 },
      paddingTop: { type: "number", default: 0 },
      paddingBottom: { type: "number", default: 0 },
      paddingInline: { type: "number", default: 0 },
      separatorGap: { type: "number", default: 8 },
    },
    edit: function (props) {
      const attributes = props.attributes || {};
      const setAttributes = props.setAttributes;
      const blockProps = useBlockProps();
      const fontSizes = [
        { name: "Small", slug: "sm", size: 14 },
        { name: "Base", slug: "base", size: 16 },
        { name: "Medium", slug: "md", size: 18 },
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
              title: i18n.__("محتوا و تایپوگرافی", "my-theme"),
              initialOpen: true,
            },
            el(TextControl, { label: i18n.__("عنوان خانه", "my-theme"), value: attributes.homeLabel || "", onChange: function (homeLabel) { setAttributes({ homeLabel: homeLabel }); } }),
            el(ToggleControl, { label: i18n.__("نمایش خانه", "my-theme"), checked: attributes.showHome !== false, onChange: function (showHome) { setAttributes({ showHome: showHome }); } }),
            el(ToggleControl, { label: i18n.__("نمایش عنوان محصول فعلی", "my-theme"), checked: attributes.showCurrent !== false, onChange: function (showCurrent) { setAttributes({ showCurrent: showCurrent }); } }),
            el(FontSizePicker, {
              fontSizes: fontSizes,
              value: attributes.fontSize,
              onChange: function (fontSize) {
                setAttributes({ fontSize: fontSize });
              },
            }),
            el(SelectControl, {
              label: i18n.__("Font weight", "my-theme"),
              value: attributes.fontWeight || "400",
              options: [
                { label: "400", value: "400" },
                { label: "500", value: "500" },
                { label: "700", value: "700" },
              ],
              onChange: function (fontWeight) {
                setAttributes({ fontWeight: fontWeight });
              },
            }),
            el(TextControl, {
              label: i18n.__("Separator", "my-theme"),
              value: attributes.separator || "/",
              onChange: function (separator) {
                setAttributes({ separator: separator || "/" });
              },
            }),
            el("p", null, i18n.__("Inactive color", "my-theme")),
            el(ColorPalette, {
              value: attributes.inactiveColor,
              onChange: function (inactiveColor) {
                setAttributes({ inactiveColor: inactiveColor });
              },
            }),
            el("p", null, i18n.__("Current color", "my-theme")),
            el(ColorPalette, {
              value: attributes.activeColor,
              onChange: function (activeColor) {
                setAttributes({ activeColor: activeColor });
              },
            })
          ),
          el(PanelBody, { title: i18n.__("فاصله‌ها و کادر", "my-theme"), initialOpen: false },
            el(RangeControl, { label: i18n.__("فاصله بالا", "my-theme"), value: attributes.marginTop, min: 0, max: 160, onChange: function (marginTop) { setAttributes({ marginTop: marginTop }); } }),
            el(RangeControl, { label: i18n.__("فاصله پایین", "my-theme"), value: attributes.marginBottom, min: 0, max: 160, onChange: function (marginBottom) { setAttributes({ marginBottom: marginBottom }); } }),
            el(RangeControl, { label: i18n.__("پدینگ افقی", "my-theme"), value: attributes.paddingInline, min: 0, max: 160, onChange: function (paddingInline) { setAttributes({ paddingInline: paddingInline }); } }),
            el(RangeControl, { label: i18n.__("پدینگ بالا", "my-theme"), value: attributes.paddingTop, min: 0, max: 120, onChange: function (paddingTop) { setAttributes({ paddingTop: paddingTop }); } }),
            el(RangeControl, { label: i18n.__("پدینگ پایین", "my-theme"), value: attributes.paddingBottom, min: 0, max: 120, onChange: function (paddingBottom) { setAttributes({ paddingBottom: paddingBottom }); } }),
            el(RangeControl, { label: i18n.__("فاصله جداکننده", "my-theme"), value: attributes.separatorGap, min: 0, max: 48, onChange: function (separatorGap) { setAttributes({ separatorGap: separatorGap }); } }),
            el(RangeControl, { label: i18n.__("ضخامت کادر", "my-theme"), value: attributes.borderWidth, min: 0, max: 12, onChange: function (borderWidth) { setAttributes({ borderWidth: borderWidth }); } }),
            el(RangeControl, { label: i18n.__("گردی گوشه‌ها", "my-theme"), value: attributes.borderRadius, min: 0, max: 80, onChange: function (borderRadius) { setAttributes({ borderRadius: borderRadius }); } }),
            el("p", null, i18n.__("رنگ پس‌زمینه", "my-theme")),
            el(ColorPalette, { value: attributes.backgroundColor, onChange: function (backgroundColor) { setAttributes({ backgroundColor: backgroundColor || "transparent" }); } }),
            el("p", null, i18n.__("رنگ کادر", "my-theme")),
            el(ColorPalette, { value: attributes.borderColor, onChange: function (borderColor) { setAttributes({ borderColor: borderColor || "transparent" }); } })
          )
        ),
        el(ServerSideRender, {
          block: "theme/product-breadcrumb",
          attributes: attributes,
        })
      );
    },
    save: function () {
      return null;
    },
    title: i18n.__("Product Breadcrumb", "my-theme"),
    category: "woocommerce",
    icon: "products",
    supports: {
      align: false,
      anchor: false,
      customClassName: true,
      html: false,
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
