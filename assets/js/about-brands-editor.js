(function (hooks, compose, element, components, blockEditor, blocks, data, i18n) {
  "use strict";

  const el = element.createElement;
  const Fragment = element.Fragment;
  const InspectorControls = blockEditor.InspectorControls;
  const PanelBody = components.PanelBody;
  const Button = components.Button;

  function hasClass(attributes, className) {
    const classes = String(attributes.className || "").split(/\s+/);
    return classes.indexOf(className) !== -1;
  }

  const withAboutBrandsControls = compose.createHigherOrderComponent(
    function (BlockEdit) {
      return function (props) {
        if (
          props.name !== "core/group" ||
          !hasClass(props.attributes, "swiper-wrapper")
        ) {
          return el(BlockEdit, props);
        }

        function addBrand() {
          const image = blocks.createBlock("core/image", {
            sizeSlug: "medium",
            linkDestination: "none",
            className: "about-brands-slider__image",
          });
          const slide = blocks.createBlock(
            "core/group",
            {
              className:
                "swiper-slide about-brands-slider__slide about-brands-slider__item",
              layout: { type: "constrained" },
            },
            [image]
          );

          data.dispatch("core/block-editor").insertBlocks(
            slide,
            undefined,
            props.clientId
          );
        }

        function removeLastBrand() {
          const parent = data
            .select("core/block-editor")
            .getBlock(props.clientId);
          const items = parent && parent.innerBlocks ? parent.innerBlocks : [];

          if (items.length) {
            data
              .dispatch("core/block-editor")
              .removeBlock(items[items.length - 1].clientId, false);
          }
        }

        const parent = data.select("core/block-editor").getBlock(props.clientId);
        const count =
          parent && parent.innerBlocks ? parent.innerBlocks.length : 0;

        return el(
          Fragment,
          null,
          el(BlockEdit, props),
          el(
            InspectorControls,
            null,
            el(
              PanelBody,
              {
                title: i18n.__("مدیریت برندها", "my-theme"),
                initialOpen: true,
              },
              el(
                "p",
                { className: "about-brands-editor__count" },
                i18n.sprintf(
                  i18n.__("تعداد برندها: %d", "my-theme"),
                  count
                )
              ),
              el(
                "div",
                { className: "about-brands-editor__actions" },
                el(
                  Button,
                  { variant: "primary", onClick: addBrand },
                  i18n.__("افزودن برند", "my-theme")
                ),
                el(
                  Button,
                  {
                    variant: "secondary",
                    isDestructive: true,
                    disabled: count === 0,
                    onClick: removeLastBrand,
                  },
                  i18n.__("حذف آخرین برند", "my-theme")
                )
              )
            )
          )
        );
      };
    },
    "withAboutBrandsControls"
  );

  hooks.addFilter(
    "editor.BlockEdit",
    "my-theme/about-brands-controls",
    withAboutBrandsControls
  );
})(
  window.wp.hooks,
  window.wp.compose,
  window.wp.element,
  window.wp.components,
  window.wp.blockEditor,
  window.wp.blocks,
  window.wp.data,
  window.wp.i18n
);
