(function (blocks, element, i18n, blockEditor, components) {
  "use strict";
  const el = element.createElement;
  const Fragment = element.Fragment;
  const useState = element.useState;
  const useBlockProps = blockEditor.useBlockProps;
  const InspectorControls = blockEditor.InspectorControls;
  const MediaUpload = blockEditor.MediaUpload;
  const MediaUploadCheck = blockEditor.MediaUploadCheck;
  const Button = components.Button;
  const PanelBody = components.PanelBody;
  const TextControl = components.TextControl;
  const ToggleControl = components.ToggleControl;
  const RangeControl = components.RangeControl;
  const SelectControl = components.SelectControl;
  const Notice = components.Notice;
  const FocalPointPicker = components.FocalPointPicker;

  blocks.registerBlockType("my-theme/hero-slider", {
    edit: function (props) {
      const slides = Array.isArray(props.attributes.slides) ? props.attributes.slides : [];
      const setAttributes = props.setAttributes;
      const state = useState(0);
      const activeIndex = Math.min(state[0], Math.max(slides.length - 1, 0));
      const setActiveIndex = state[1];

      function updateSlide(index, patch) {
        setAttributes({ slides: slides.map(function (slide, i) { return i === index ? Object.assign({}, slide, patch) : slide; }) });
      }
      function addSlide() {
        const next = slides.concat([{ imageId: 0, imageUrl: "", imageAlt: "", title: i18n.__("عنوان اسلاید جدید", "my-theme"), buttonText: i18n.__("مشاهده بیشتر", "my-theme"), buttonUrl: "#", contentPosition: "right", mobileFocalPoint: { x: 0.5, y: 0.5 } }]);
        setAttributes({ slides: next }); setActiveIndex(next.length - 1);
      }
      function removeSlide(index) {
        if (slides.length <= 1) { return; }
        setAttributes({ slides: slides.filter(function (_, i) { return i !== index; }) });
        setActiveIndex(Math.max(0, index - 1));
      }
      function moveSlide(index, direction) {
        const target = index + direction;
        if (target < 0 || target >= slides.length) { return; }
        const next = slides.slice(); const item = next[index]; next[index] = next[target]; next[target] = item;
        setAttributes({ slides: next }); setActiveIndex(target);
      }

      const active = slides[activeIndex] || {};
      const mobileFocalPoint = active.mobileFocalPoint && typeof active.mobileFocalPoint === "object"
        ? {
            x: typeof active.mobileFocalPoint.x === "number" ? active.mobileFocalPoint.x : 0.5,
            y: typeof active.mobileFocalPoint.y === "number" ? active.mobileFocalPoint.y : 0.5
          }
        : { x: 0.5, y: 0.5 };
      const previewStyle = active.imageUrl ? { backgroundImage: 'url("' + active.imageUrl.replace(/"/g, "%22") + '")' } : {};
      return el(Fragment, {},
        el(InspectorControls, {},
          el(PanelBody, { title: i18n.__("تنظیمات پخش", "my-theme"), initialOpen: true },
            el(ToggleControl, { label: i18n.__("پخش خودکار", "my-theme"), checked: props.attributes.autoplay, onChange: function (value) { setAttributes({ autoplay: value }); } }),
            props.attributes.autoplay ? el(RangeControl, { label: i18n.__("فاصله تغییر اسلاید (میلی‌ثانیه)", "my-theme"), value: props.attributes.delay, min: 1000, max: 10000, step: 500, onChange: function (value) { setAttributes({ delay: value }); } }) : null,
            el(ToggleControl, { label: i18n.__("تکرار بی‌نهایت", "my-theme"), checked: props.attributes.loop, onChange: function (value) { setAttributes({ loop: value }); } }),
            el(ToggleControl, { label: i18n.__("نمایش فلش‌ها", "my-theme"), checked: props.attributes.showNavigation, onChange: function (value) { setAttributes({ showNavigation: value }); } }),
            el(ToggleControl, { label: i18n.__("نمایش نقطه‌ها", "my-theme"), checked: props.attributes.showPagination, onChange: function (value) { setAttributes({ showPagination: value }); } })
          ),
          el(PanelBody, { title: i18n.__("ویرایش اسلاید انتخاب‌شده", "my-theme"), initialOpen: true },
            el(MediaUploadCheck, {}, el(MediaUpload, { allowedTypes: ["image"], value: active.imageId || 0, onSelect: function (media) { updateSlide(activeIndex, { imageId: media.id || 0, imageUrl: media.url || "", imageAlt: media.alt || "" }); }, render: function (mediaProps) { return el(Button, { variant: "secondary", onClick: mediaProps.open }, active.imageUrl ? i18n.__("تغییر تصویر", "my-theme") : i18n.__("انتخاب تصویر", "my-theme")); } })),
            el(TextControl, { label: i18n.__("عنوان", "my-theme"), value: active.title || "", onChange: function (value) { updateSlide(activeIndex, { title: value }); } }),
            el(TextControl, { label: i18n.__("متن دکمه", "my-theme"), value: active.buttonText || "", onChange: function (value) { updateSlide(activeIndex, { buttonText: value }); } }),
            el(TextControl, { label: i18n.__("لینک دکمه", "my-theme"), value: active.buttonUrl || "", onChange: function (value) { updateSlide(activeIndex, { buttonUrl: value }); } }),
            el(SelectControl, {
              label: i18n.__("جایگاه باکس متن", "my-theme"),
              value: active.contentPosition === "left" ? "left" : "right",
              options: [
                { label: i18n.__("سمت راست", "my-theme"), value: "right" },
                { label: i18n.__("سمت چپ", "my-theme"), value: "left" }
              ],
              onChange: function (value) { updateSlide(activeIndex, { contentPosition: value === "left" ? "left" : "right" }); }
            }),
            active.imageUrl && FocalPointPicker ? el("div", { className: "hero-slider-mobile-focal-control" },
              el("p", { className: "components-base-control__label" }, i18n.__("محل برش تصویر در موبایل", "my-theme")),
              el(FocalPointPicker, {
                url: active.imageUrl,
                value: mobileFocalPoint,
                onChange: function (value) {
                  updateSlide(activeIndex, {
                    mobileFocalPoint: {
                      x: Math.max(0, Math.min(1, Number(value.x))),
                      y: Math.max(0, Math.min(1, Number(value.y)))
                    }
                  });
                }
              }),
              el("p", { className: "components-base-control__help" }, i18n.__("نقطه را روی سوژه قرار دهید؛ این قسمت در نمایش موبایل داخل کادر می‌ماند.", "my-theme")),
              el(Button, {
                variant: "tertiary",
                onClick: function () { updateSlide(activeIndex, { mobileFocalPoint: { x: 0.5, y: 0.5 } }); }
              }, i18n.__("بازنشانی به مرکز", "my-theme"))
            ) : null
          )
        ),
        el("div", useBlockProps({ className: "hero-slider hero-slider-editor" }),
          slides.length ? el("div", { className: "hero-slide-editor-preview hero-slide-editor-preview--" + (active.contentPosition === "left" ? "left" : "right"), style: previewStyle },
            el("div", { className: "hero-slide-card" }, el("h1", {}, active.title || ""), active.buttonText ? el("span", { className: "hero-slide-button" }, active.buttonText) : null)
          ) : el(Notice, { status: "warning", isDismissible: false }, i18n.__("حداقل یک اسلاید اضافه کنید.", "my-theme")),
          el("div", { className: "hero-slider-editor-tabs" }, slides.map(function (_, index) { return el(Button, { key: index, variant: index === activeIndex ? "primary" : "secondary", onClick: function () { setActiveIndex(index); } }, i18n.__("اسلاید", "my-theme") + " " + (index + 1)); })),
          el("div", { className: "hero-slider-editor-actions" },
            el(Button, { variant: "secondary", disabled: activeIndex === 0, onClick: function () { moveSlide(activeIndex, -1); } }, i18n.__("انتقال به قبل", "my-theme")),
            el(Button, { variant: "secondary", disabled: activeIndex >= slides.length - 1, onClick: function () { moveSlide(activeIndex, 1); } }, i18n.__("انتقال به بعد", "my-theme")),
            el(Button, { isDestructive: true, variant: "secondary", disabled: slides.length <= 1, onClick: function () { removeSlide(activeIndex); } }, i18n.__("حذف اسلاید", "my-theme")),
            el(Button, { variant: "primary", onClick: addSlide }, i18n.__("افزودن اسلاید", "my-theme"))
          )
        )
      );
    },
    save: function () { return null; }
  });
})(window.wp.blocks, window.wp.element, window.wp.i18n, window.wp.blockEditor, window.wp.components);
