(function (blocks, element, i18n, blockEditor, components, data, ServerSideRender) {
  "use strict";
  const el = element.createElement;
  const Fragment = element.Fragment;
  const useBlockProps = blockEditor.useBlockProps;
  const InspectorControls = blockEditor.InspectorControls;
  const PanelBody = components.PanelBody;
  const TextControl = components.TextControl;
  const RangeControl = components.RangeControl;
  const ToggleControl = components.ToggleControl;
  const CheckboxControl = components.CheckboxControl;
  const ColorPalette = components.ColorPalette;
  const BaseControl = components.BaseControl;
  const Spinner = components.Spinner;
  const Button = components.Button;

  const palette = [
    { name: "سبز", color: "#16a34a" }, { name: "مشکی", color: "#111111" },
    { name: "متن", color: "#1a1e1b" }, { name: "سفید", color: "#ffffff" },
    { name: "خاکستری", color: "#d9d9d9" }, { name: "قرمز", color: "#dc2626" }
  ];

  blocks.registerBlockType("my-theme/category-products", {
    edit: function (props) {
      const attrs = props.attributes;
      const setAttributes = props.setAttributes;
      const searchState = element.useState("");
      const search = searchState[0], setSearch = searchState[1];
      const selectedIds = Array.isArray(attrs.categoryIds) ? attrs.categoryIds : [];
      const categoryData = data.useSelect(function (select) {
        if (!attrs.manualCategories) return { terms: [], loading: false, error: false };
        const store = select("core");
        const terms = [];
        // Request every page, including stores with more than 100 categories.
        for (let page = 1; ; page++) {
          const query = { per_page: 100, page: page, hide_empty: false, orderby: "name", order: "asc" };
          const records = store.getEntityRecords("taxonomy", "product_cat", query);
          if (!records) {
            const finished = store.hasFinishedResolution("getEntityRecords", ["taxonomy", "product_cat", query]);
            return { terms: terms, loading: !finished, error: finished };
          }
          terms.push.apply(terms, records);
          const total = store.getEntityRecordsTotalItems("taxonomy", "product_cat", query);
          if (records.length < 100 || (typeof total === "number" && terms.length >= total)) {
            return { terms: terms, loading: false, error: false };
          }
        }
      }, [attrs.manualCategories]);
      function moveCategory(index, direction) {
        const next = selectedIds.slice();
        const target = index + direction;
        if (target < 0 || target >= next.length) return;
        const id = next[index]; next[index] = next[target]; next[target] = id;
        setAttributes({ categoryIds: next });
      }
      function colorControl(label, key) {
        return el(BaseControl, { label: label }, el(ColorPalette, { colors: palette, value: attrs[key], clearable: false, onChange: function (value) { const patch = {}; patch[key] = value; setAttributes(patch); } }));
      }
      function rangeControl(label, key, min, max) {
        return el(RangeControl, { label: label, value: attrs[key], min: min, max: max, onChange: function (value) { const patch = {}; patch[key] = value; setAttributes(patch); } });
      }
      return el(Fragment, {},
        el(InspectorControls, {},
          el(PanelBody, { title: i18n.__("محتوا", "my-theme"), initialOpen: true },
            el(TextControl, { label: i18n.__("عنوان بخش", "my-theme"), value: attrs.title || "", onChange: function (value) { setAttributes({ title: value }); } }),
            el(RangeControl, { label: i18n.__("تعداد محصول در هر دسته", "my-theme"), value: attrs.productsCount, min: 1, max: 24, onChange: function (value) { setAttributes({ productsCount: value }); } }),
            el(TextControl, { label: i18n.__("متن دکمه", "my-theme"), value: attrs.buttonText || "", onChange: function (value) { setAttributes({ buttonText: value }); } }),
            el(TextControl, { label: i18n.__("لینک دکمه", "my-theme"), value: attrs.buttonUrl || "", onChange: function (value) { setAttributes({ buttonUrl: value }); } })
          ),
          el(PanelBody, { title: i18n.__("دسته‌بندی‌ها", "my-theme"), initialOpen: true },
            el(ToggleControl, { label: i18n.__("انتخاب دستی دسته‌بندی‌ها", "my-theme"), checked: !!attrs.manualCategories, onChange: function (value) { setAttributes({ manualCategories: value }); } }),
            !attrs.manualCategories
              ? el("p", { className: "category-products-editor__hint" }, i18n.__("این بخش به‌صورت خودکار ۷ دسته‌بندی دارای بیشترین تعداد محصول را نمایش می‌دهد.", "my-theme"))
              : el(Fragment, {},
                el(TextControl, { label: i18n.__("جستجوی دسته‌بندی", "my-theme"), value: search, onChange: setSearch }),
                categoryData.loading ? el(Spinner) : null,
                categoryData.error ? el("p", { role: "alert" }, i18n.__("دریافت دسته‌بندی‌ها انجام نشد. فعال بودن ووکامرس و دسترسی به دسته‌بندی محصولات را بررسی کنید.", "my-theme")) : null,
                el("div", { className: "category-products-editor__categories" },
                  categoryData.terms.filter(function (term) { return term.name.toLocaleLowerCase().includes(search.trim().toLocaleLowerCase()); }).map(function (term) {
                    return el(CheckboxControl, { key: term.id, label: term.name, checked: selectedIds.includes(term.id), onChange: function (checked) {
                      setAttributes({ categoryIds: checked ? selectedIds.concat(term.id) : selectedIds.filter(function (id) { return id !== term.id; }) });
                    } });
                  })
                ),
                !categoryData.loading && !categoryData.error && !categoryData.terms.length ? el("p", {}, i18n.__("هنوز دسته‌بندی محصولی وجود ندارد.", "my-theme")) : null,
                el("p", { className: "category-products-editor__hint" }, i18n.__("فقط دسته‌های تیک‌خورده نمایش داده می‌شوند؛ دسته‌های بدون محصول نیز قابل انتخاب‌اند.", "my-theme")),
                selectedIds.length ? el("div", { className: "category-products-editor__order" },
                  el("strong", {}, i18n.__("ترتیب نمایش", "my-theme")),
                  selectedIds.map(function (id, index) {
                    const term = categoryData.terms.find(function (item) { return item.id === id; });
                    return el("div", { key: id, className: "category-products-editor__order-item" },
                      el("span", {}, term ? term.name : ("#" + id)),
                      el("div", {},
                        el(Button, { variant: "tertiary", disabled: index === 0, onClick: function () { moveCategory(index, -1); } }, i18n.__("قبل", "my-theme")),
                        el(Button, { variant: "tertiary", disabled: index === selectedIds.length - 1, onClick: function () { moveCategory(index, 1); } }, i18n.__("بعد", "my-theme")),
                        el(Button, { variant: "tertiary", isDestructive: true, onClick: function () { setAttributes({ categoryIds: selectedIds.filter(function (item) { return item !== id; }) }); } }, i18n.__("حذف", "my-theme"))
                      )
                    );
                  })
                ) : el("p", {}, i18n.__("برای نمایش تب‌ها، حداقل یک دسته‌بندی انتخاب کنید.", "my-theme"))
              )
          ),
          el(PanelBody, { title: i18n.__("اسلایدر", "my-theme"), initialOpen: false },
            el(ToggleControl, { label: i18n.__("نمایش فلش‌ها", "my-theme"), checked: attrs.showNavigation, onChange: function (value) { setAttributes({ showNavigation: value }); } }),
            el(ToggleControl, { label: i18n.__("نمایش نقطه‌ها", "my-theme"), checked: attrs.showPagination, onChange: function (value) { setAttributes({ showPagination: value }); } }),
            rangeControl(i18n.__("فاصله کارت‌ها در دسکتاپ", "my-theme"), "desktopGap", 0, 100),
            rangeControl(i18n.__("فاصله کارت‌ها در تبلت", "my-theme"), "tabletGap", 0, 100),
            rangeControl(i18n.__("فاصله کارت‌ها در موبایل", "my-theme"), "mobileGap", 0, 100)
          ),
          el(PanelBody, { title: i18n.__("استایل عنوان و دسته‌ها", "my-theme"), initialOpen: false },
            colorControl(i18n.__("رنگ عنوان بخش", "my-theme"), "headingColor"),
            rangeControl(i18n.__("اندازه عنوان بخش", "my-theme"), "headingSize", 14, 72),
            colorControl(i18n.__("رنگ دسته‌ها", "my-theme"), "tabColor"),
            colorControl(i18n.__("رنگ دسته فعال", "my-theme"), "activeTabColor"),
            colorControl(i18n.__("رنگ خط دسته فعال", "my-theme"), "activeTabBorderColor"),
            rangeControl(i18n.__("اندازه نوشته دسته‌ها", "my-theme"), "tabSize", 10, 28)
          ),
          el(PanelBody, { title: i18n.__("استایل کارت محصول", "my-theme"), initialOpen: false },
            colorControl(i18n.__("رنگ پس‌زمینه کارت", "my-theme"), "cardBackground"),
            colorControl(i18n.__("رنگ حاشیه کارت", "my-theme"), "cardBorderColor"),
            rangeControl(i18n.__("ضخامت حاشیه", "my-theme"), "cardBorderWidth", 0, 8),
            rangeControl(i18n.__("گردی گوشه‌ها", "my-theme"), "cardRadius", 0, 60),
            rangeControl(i18n.__("فاصله داخلی کارت", "my-theme"), "cardPadding", 0, 50),
            rangeControl(i18n.__("فاصله داخلی کارت در موبایل", "my-theme"), "mobileCardPadding", 0, 50),
            rangeControl(i18n.__("عرض کارت", "my-theme"), "cardWidth", 140, 400),
            rangeControl(i18n.__("ارتفاع کارت", "my-theme"), "cardHeight", 180, 520),
            el(ToggleControl, { label: i18n.__("نمایش سایه کارت", "my-theme"), checked: attrs.cardShadow, onChange: function (value) { setAttributes({ cardShadow: value }); } }),
            rangeControl(i18n.__("ارتفاع تصویر محصول", "my-theme"), "imageHeight", 60, 320),
            rangeControl(i18n.__("اندازه خود تصویر در موبایل", "my-theme"), "mobileImageHeight", 60, 200),
            rangeControl(i18n.__("ضخامت حاشیه در موبایل", "my-theme"), "mobileCardBorderWidth", 0, 8),
            colorControl(i18n.__("رنگ نام محصول", "my-theme"), "productTitleColor"),
            rangeControl(i18n.__("اندازه نام محصول", "my-theme"), "productTitleSize", 10, 30)
          ),
          el(PanelBody, { title: i18n.__("استایل کارت در موبایل", "my-theme"), initialOpen: false },
            rangeControl(i18n.__("عرض کارت موبایل", "my-theme"), "mobileCardWidth", 180, 320),
            rangeControl(i18n.__("ارتفاع کارت موبایل", "my-theme"), "mobileCardHeight", 240, 420),
            rangeControl(i18n.__("ارتفاع محفظه تصویر", "my-theme"), "mobileImageContainerHeight", 160, 260),
            rangeControl(i18n.__("گردی کارت موبایل", "my-theme"), "mobileCardRadius", 0, 60),
            el(ToggleControl, { label: i18n.__("نمایش سایه کارت در موبایل", "my-theme"), checked: attrs.mobileCardShadow, onChange: function (value) { setAttributes({ mobileCardShadow: value }); } }),
            rangeControl(i18n.__("اندازه نام محصول در موبایل", "my-theme"), "mobileProductTitleSize", 12, 24),
            rangeControl(i18n.__("اندازه عنوان سکشن در موبایل", "my-theme"), "mobileHeadingSize", 16, 36),
            rangeControl(i18n.__("اندازه دسته‌ها در موبایل", "my-theme"), "mobileTabSize", 10, 24),
            el(Button, { variant: "secondary", onClick: function () { setAttributes({ mobileCardPadding: 0, mobileCardWidth: 200, mobileCardHeight: 268, mobileCardRadius: 8, mobileCardShadow: true, mobileImageHeight: 150, mobileImageContainerHeight: 200, mobileCardBorderWidth: 1, mobileProductTitleSize: 16, mobileHeadingSize: 22, mobileTabSize: 14 }); } }, i18n.__("بازگردانی استایل موبایل مطابق طرح اصلی", "my-theme"))
          ),
          el(PanelBody, { title: i18n.__("فلش‌ها و نقطه‌ها", "my-theme"), initialOpen: false },
            colorControl(i18n.__("پس‌زمینه فلش", "my-theme"), "arrowBackground"),
            colorControl(i18n.__("رنگ فلش", "my-theme"), "arrowColor"),
            rangeControl(i18n.__("اندازه فلش", "my-theme"), "arrowSize", 28, 80),
            colorControl(i18n.__("رنگ نقطه‌ها", "my-theme"), "bulletColor"),
            colorControl(i18n.__("رنگ نقطه فعال", "my-theme"), "bulletActiveColor")
          ),
          el(PanelBody, { title: i18n.__("استایل دکمه مشاهده همه", "my-theme"), initialOpen: false },
            colorControl(i18n.__("رنگ پس‌زمینه دکمه", "my-theme"), "buttonBackground"),
            colorControl(i18n.__("رنگ نوشته دکمه", "my-theme"), "buttonColor"),
            rangeControl(i18n.__("گردی دکمه", "my-theme"), "buttonRadius", 0, 80)
          )
        ),
        el("div", useBlockProps({ className: "category-products-editor" }),
          el(ServerSideRender, { block: "my-theme/category-products", attributes: attrs, httpMethod: "POST", LoadingResponsePlaceholder: function () { return el("div", { className: "category-products-editor__loading" }, el(Spinner), i18n.__("در حال ساخت پیش‌نمایش…", "my-theme")); } })
        )
      );
    },
    save: function () { return null; }
  });
})(window.wp.blocks, window.wp.element, window.wp.i18n, window.wp.blockEditor, window.wp.components, window.wp.data, window.wp.serverSideRender);
