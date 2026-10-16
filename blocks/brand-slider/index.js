(function (blocks, element, i18n, blockEditor, components, ServerSideRender) {
  "use strict";
  const el = element.createElement;
  const Fragment = element.Fragment;
  const useBlockProps = blockEditor.useBlockProps;
  const InspectorControls = blockEditor.InspectorControls;
  const MediaUpload = blockEditor.MediaUpload;
  const MediaUploadCheck = blockEditor.MediaUploadCheck;
  const PanelBody = components.PanelBody;
  const TextControl = components.TextControl;
  const ToggleControl = components.ToggleControl;
  const RangeControl = components.RangeControl;
  const Button = components.Button;
  const Notice = components.Notice;

  blocks.registerBlockType("my-theme/brand-slider", {
    edit: function (props) {
      const attrs = props.attributes;
      const setAttributes = props.setAttributes;
      const brands = Array.isArray(attrs.brands) ? attrs.brands : [];
      const selectedIds = Array.isArray(attrs.brandIds) ? attrs.brandIds : [];
      const choicesState = element.useState({ items: [], loading: false, error: false });
      const choices = choicesState[0], setChoices = choicesState[1];
      const searchState = element.useState("");
      const search = searchState[0], setSearch = searchState[1];
      const retryState = element.useState(0);
      element.useEffect(function () {
        if (!attrs.useRegisteredBrands || !attrs.manualRegisteredBrands) return;
        let cancelled = false;
        setChoices({ items: [], loading: true, error: false });
        window.wp.apiFetch({ path: "/my-theme/v1/brand-choices" }).then(function (items) {
          if (!cancelled) setChoices({ items: items, loading: false, error: false });
        }).catch(function () {
          if (!cancelled) setChoices({ items: [], loading: false, error: true });
        });
        return function () { cancelled = true; };
      }, [attrs.useRegisteredBrands, attrs.manualRegisteredBrands, retryState[0]]);
      function moveRegisteredBrand(index, delta) {
        const target = index + delta;
        if (target < 0 || target >= selectedIds.length) return;
        const next = selectedIds.slice();
        const id = next[index]; next[index] = next[target]; next[target] = id;
        setAttributes({ brandIds: next });
      }
      function patchBrand(index, patch) { setAttributes({ brands: brands.map(function (item, i) { return i === index ? Object.assign({}, item, patch) : item; }) }); }
      function addBrand() {
        setAttributes({ brands: brands.concat([{
          uid: "brand-" + Date.now() + "-" + Math.random().toString(36).slice(2, 8),
          imageId: 0,
          imageUrl: "",
          alt: "",
          name: i18n.__("برند جدید", "my-theme"),
          url: ""
        }]) });
      }
      function removeBrand(index) { setAttributes({ brands: brands.filter(function (_, i) { return i !== index; }) }); }
      function moveBrand(index, delta) { const target = index + delta; if (target < 0 || target >= brands.length) return; const next = brands.slice(); const item = next[index]; next[index] = next[target]; next[target] = item; setAttributes({ brands: next }); }
      return el(Fragment, {},
        el(InspectorControls, {},
          el(PanelBody, { title: i18n.__("محتوا", "my-theme"), initialOpen: true },
            el(TextControl, { label: i18n.__("عنوان سکشن", "my-theme"), value: attrs.title || "", onChange: function (value) { setAttributes({ title: value }); } }),
            el(ToggleControl, { label: i18n.__("استفاده از برندهای ثبت‌شده", "my-theme"), help: i18n.__("با خاموش‌کردن این گزینه می‌توانید لوگوها را دستی مدیریت کنید.", "my-theme"), checked: attrs.useRegisteredBrands, onChange: function (value) { setAttributes({ useRegisteredBrands: value }); } }),
            el(ToggleControl, { label: i18n.__("لینک‌دار کردن لوگوها", "my-theme"), checked: attrs.linkBrands, onChange: function (value) { setAttributes({ linkBrands: value }); } })
          ),
          attrs.useRegisteredBrands ? el(PanelBody, { title: i18n.__("برندهای نمایشی", "my-theme"), initialOpen: true },
            el(ToggleControl, { label: i18n.__("انتخاب دستی برندهای ثبت‌شده", "my-theme"), checked: !!attrs.manualRegisteredBrands, onChange: function (value) { setAttributes({ manualRegisteredBrands: value }); } }),
            !attrs.manualRegisteredBrands ? el("p", {}, i18n.__("همه برندهای منتشرشده به‌صورت خودکار نمایش داده می‌شوند.", "my-theme")) : el(Fragment, {},
              el(TextControl, { label: i18n.__("جستجوی برند", "my-theme"), value: search, onChange: setSearch }),
              choices.loading ? el(components.Spinner) : null,
              choices.error ? el(Notice, { status: "error", isDismissible: false },
                i18n.__("دریافت برندها انجام نشد.", "my-theme"),
                el(Button, { variant: "secondary", onClick: function () { retryState[1](retryState[0] + 1); } }, i18n.__("تلاش دوباره", "my-theme"))
              ) : null,
              el("div", { className: "brand-slider-editor__choices" }, choices.items.filter(function (item) { return item.name.toLocaleLowerCase().includes(search.trim().toLocaleLowerCase()); }).map(function (item) {
                return el(components.CheckboxControl, { key: item.id, label: item.name, checked: selectedIds.includes(item.id), onChange: function (checked) {
                  setAttributes({ brandIds: checked ? selectedIds.concat(item.id) : selectedIds.filter(function (id) { return id !== item.id; }) });
                } });
              })),
              !choices.loading && !choices.error && !choices.items.length ? el("p", {}, i18n.__("هنوز برندی منتشر نشده است.", "my-theme")) : null,
              selectedIds.length ? el("div", {},
                el("strong", {}, i18n.__("ترتیب نمایش", "my-theme")),
                selectedIds.map(function (id, index) {
                  const item = choices.items.find(function (brand) { return brand.id === id; });
                  return el("div", { key: id, className: "brand-slider-editor__choice-order" },
                    el("span", {}, item ? item.name : ("#" + id)),
                    el("div", { className: "brand-slider-editor__actions" },
                      el(Button, { variant: "tertiary", disabled: index === 0, onClick: function () { moveRegisteredBrand(index, -1); } }, i18n.__("قبل", "my-theme")),
                      el(Button, { variant: "tertiary", disabled: index === selectedIds.length - 1, onClick: function () { moveRegisteredBrand(index, 1); } }, i18n.__("بعد", "my-theme")),
                      el(Button, { variant: "tertiary", isDestructive: true, onClick: function () { setAttributes({ brandIds: selectedIds.filter(function (itemId) { return itemId !== id; }) }); } }, i18n.__("حذف", "my-theme"))
                    )
                  );
                })
              ) : el("p", {}, i18n.__("برای نمایش لوگوها، حداقل یک برند انتخاب کنید.", "my-theme"))
            )
          ) : null,
          el(PanelBody, { title: i18n.__("تنظیمات اسلایدر", "my-theme"), initialOpen: false },
            el(ToggleControl, { label: i18n.__("نمایش فلش‌ها", "my-theme"), checked: attrs.showNavigation, onChange: function (value) { setAttributes({ showNavigation: value }); } }),
            el(ToggleControl, { label: i18n.__("تکرار اسلایدر", "my-theme"), checked: attrs.loop, onChange: function (value) { setAttributes({ loop: value }); } }),
            el(ToggleControl, { label: i18n.__("پخش خودکار", "my-theme"), checked: attrs.autoplay, onChange: function (value) { setAttributes({ autoplay: value }); } }),
            attrs.autoplay ? el(RangeControl, { label: i18n.__("زمان تعویض اسلاید", "my-theme"), value: attrs.delay, min: 1000, max: 10000, step: 500, onChange: function (value) { setAttributes({ delay: value }); } }) : null,
            el(RangeControl, { label: i18n.__("فاصله لوگوها در دسکتاپ", "my-theme"), value: attrs.desktopGap, min: 0, max: 120, onChange: function (value) { setAttributes({ desktopGap: value }); } }),
            el(RangeControl, { label: i18n.__("فاصله لوگوها در تبلت", "my-theme"), value: attrs.tabletGap, min: 0, max: 120, onChange: function (value) { setAttributes({ tabletGap: value }); } }),
            el(RangeControl, { label: i18n.__("فاصله لوگوها در موبایل", "my-theme"), value: attrs.mobileGap, min: 0, max: 120, onChange: function (value) { setAttributes({ mobileGap: value }); } })
          ),
          !attrs.useRegisteredBrands ? el(PanelBody, { title: i18n.__("مدیریت دستی برندها", "my-theme"), initialOpen: true },
            brands.length ? brands.map(function (brand, index) {
              const itemKey = brand.uid || ("brand-" + (brand.imageId || brand.imageUrl || brand.name || index));
              return el("div", { className: "brand-slider-editor__item", key: itemKey },
                el("strong", {}, (index + 1) + ". " + (brand.name || i18n.__("برند", "my-theme"))),
                brand.imageUrl ? el("img", { src: brand.imageUrl, alt: brand.alt || "" }) : null,
                el(MediaUploadCheck, {}, el(MediaUpload, { allowedTypes: ["image"], value: brand.imageId || 0, onSelect: function (media) { patchBrand(index, { imageId: media.id || 0, imageUrl: media.url || "", alt: media.alt || brand.alt || "", name: brand.name || media.title || "" }); }, render: function (mediaProps) { return el(Button, { variant: "secondary", onClick: mediaProps.open }, brand.imageUrl ? i18n.__("تغییر لوگو", "my-theme") : i18n.__("انتخاب لوگو", "my-theme")); } })),
                el(TextControl, { label: i18n.__("نام برند", "my-theme"), value: brand.name || "", onChange: function (value) { patchBrand(index, { name: value }); } }),
                el(TextControl, { label: i18n.__("متن جایگزین تصویر", "my-theme"), value: brand.alt || "", onChange: function (value) { patchBrand(index, { alt: value }); } }),
                el(TextControl, { label: i18n.__("لینک برند", "my-theme"), value: brand.url || "", onChange: function (value) { patchBrand(index, { url: value }); } }),
                el("div", { className: "brand-slider-editor__actions" },
                  el(Button, { variant: "secondary", disabled: index === 0, onClick: function () { moveBrand(index, -1); } }, i18n.__("قبل", "my-theme")),
                  el(Button, { variant: "secondary", disabled: index === brands.length - 1, onClick: function () { moveBrand(index, 1); } }, i18n.__("بعد", "my-theme")),
                  el(Button, { variant: "secondary", isDestructive: true, onClick: function () { removeBrand(index); } }, i18n.__("حذف", "my-theme"))
                )
              );
            }) : el(Notice, { status: "info", isDismissible: false }, i18n.__("هنوز برندی اضافه نشده است.", "my-theme")),
            el(Button, { variant: "primary", onClick: addBrand }, i18n.__("افزودن برند", "my-theme"))
          ) : null
        ),
        el("div", useBlockProps({ className: "brand-slider-editor" }), el(ServerSideRender, { block: "my-theme/brand-slider", attributes: attrs, httpMethod: "POST" }))
      );
    },
    save: function () { return null; }
  });
})(window.wp.blocks, window.wp.element, window.wp.i18n, window.wp.blockEditor, window.wp.components, window.wp.serverSideRender);
