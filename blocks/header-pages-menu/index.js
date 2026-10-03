(function (blocks, element, blockEditor, components, data, i18n) {
  "use strict";

  const el = element.createElement;
  const Fragment = element.Fragment;
  const useBlockProps = blockEditor.useBlockProps;
  const InspectorControls = blockEditor.InspectorControls;
  const PanelBody = components.PanelBody;
  const ToggleControl = components.ToggleControl;
  const TextControl = components.TextControl;
  const RangeControl = components.RangeControl;
  const Button = components.Button;
  const useSelect = data.useSelect;

  function plainTitle(page) {
    const html =
      page && page.title && page.title.rendered
        ? page.title.rendered
        : i18n.__("بدون عنوان", "my-theme");
    const holder = document.createElement("div");
    holder.innerHTML = html;
    return holder.textContent || holder.innerText || html;
  }

  function normalizeMegaCells(cells, count) {
    const normalized = Array.isArray(cells) ? cells.slice(0, count) : [];
    while (normalized.length < count) {
      normalized.push({ title: "", description: "", url: "" });
    }
    return normalized;
  }

  blocks.registerBlockType("my-theme/header-pages-menu", {
    edit: function (props) {
      const attrs = props.attributes;
      const setAttributes = props.setAttributes;
      const manualMode = !!attrs.manualMode;
      const autoCount = Number(attrs.autoCount) || 6;
      const savedItems = Array.isArray(attrs.items) ? attrs.items : [];

      const pages = useSelect(function (select) {
        return select("core").getEntityRecords("postType", "page", {
          per_page: autoCount,
          status: "publish",
          order: "asc",
          orderby: "menu_order"
        });
      }, [autoCount]);

      const automaticItems = Array.isArray(pages)
        ? pages.map(function (page) {
            return { label: plainTitle(page), url: page.link || "" };
          })
        : [];

      const visibleItems = manualMode ? savedItems : automaticItems;

      function setManualMode(enabled) {
        setAttributes({
          manualMode: enabled,
          items:
            enabled && !savedItems.length ? automaticItems : savedItems
        });
      }

      function updateItem(index, key, value) {
        setAttributes({
          items: savedItems.map(function (item, itemIndex) {
            if (itemIndex !== index) return item;
            const next = Object.assign({}, item);
            next[key] = value;
            return next;
          })
        });
      }

      function updateMegaDimensions(index, item, rows, columns) {
        const nextRows = Math.max(1, Math.min(6, Number(rows) || 1));
        const nextColumns = Math.max(1, Math.min(6, Number(columns) || 1));
        setAttributes({
          items: savedItems.map(function (current, itemIndex) {
            if (itemIndex !== index) return current;
            return Object.assign({}, current, {
              megaMenuRows: nextRows,
              megaMenuColumns: nextColumns,
              megaMenuCells: normalizeMegaCells(
                current.megaMenuCells,
                nextRows * nextColumns
              )
            });
          })
        });
      }

      function updateMegaCell(itemIndex, item, cellIndex, key, value) {
        const rows = Number(item.megaMenuRows) || 2;
        const columns = Number(item.megaMenuColumns) || 3;
        const cells = normalizeMegaCells(item.megaMenuCells, rows * columns);
        cells[cellIndex] = Object.assign({}, cells[cellIndex], {
          [key]: value
        });
        updateItem(itemIndex, "megaMenuCells", cells);
      }

      function removeItem(index) {
        setAttributes({
          items: savedItems.filter(function (_, itemIndex) {
            return itemIndex !== index;
          })
        });
      }

      function addItem() {
        setAttributes({
          items: savedItems.concat([
            {
              label: i18n.__("آیتم جدید", "my-theme"),
              url: "#",
              megaMenuEnabled: false,
              megaMenuRows: 2,
              megaMenuColumns: 3,
              megaMenuCells: []
            }
          ])
        });
      }

      const inspector = el(
        InspectorControls,
        {},
        el(
          PanelBody,
          {
            title: i18n.__("تنظیمات منوی هدر", "my-theme"),
            initialOpen: true
          },
          el(ToggleControl, {
            label: i18n.__("ویرایش دستی آیتم‌های منو", "my-theme"),
            help: manualMode
              ? i18n.__("عنوان، لینک و تعداد آیتم‌ها قابل تغییر است.", "my-theme")
              : i18n.__("در حالت خودکار، شش صفحهٔ منتشرشده نمایش داده می‌شود.", "my-theme"),
            checked: manualMode,
            onChange: setManualMode
          }),
          !manualMode
            ? el(RangeControl, {
                label: i18n.__("تعداد آیتم‌های خودکار", "my-theme"),
                value: autoCount,
                min: 1,
                max: 12,
                onChange: function (value) {
                  setAttributes({ autoCount: value });
                }
              })
            : null,
          manualMode
            ? savedItems.map(function (item, index) {
                return el(
                  "div",
                  { className: "site-header-pages-menu-editor__item", key: "item-" + index },
                  el(TextControl, {
                    label: i18n.__("عنوان آیتم", "my-theme") + " " + (index + 1),
                    value: item.label || "",
                    onChange: function (value) { updateItem(index, "label", value); }
                  }),
                  el(TextControl, {
                    label: i18n.__("لینک", "my-theme"),
                    value: item.url || "",
                    type: "url",
                    onChange: function (value) { updateItem(index, "url", value); }
                  }),
                  el(ToggleControl, {
                    label: i18n.__("فعال‌سازی مگا منو", "my-theme"),
                    help: item.megaMenuEnabled
                      ? i18n.__("مگا منو فقط برای همین آیتم فعال است.", "my-theme")
                      : i18n.__("این گزینه روی منوهای فوتر اثری ندارد.", "my-theme"),
                    checked: !!item.megaMenuEnabled,
                    onChange: function (enabled) {
                      const rows = Number(item.megaMenuRows) || 2;
                      const columns = Number(item.megaMenuColumns) || 3;
                      const nextItems = savedItems.map(function (current, itemIndex) {
                        if (itemIndex !== index) return current;
                        return Object.assign({}, current, {
                          megaMenuEnabled: enabled,
                          megaMenuRows: rows,
                          megaMenuColumns: columns,
                          megaMenuCells: normalizeMegaCells(
                            current.megaMenuCells,
                            rows * columns
                          )
                        });
                      });
                      setAttributes({ items: nextItems });
                    }
                  }),
                  item.megaMenuEnabled
                    ? el(
                        Fragment,
                        {},
                        el(RangeControl, {
                          label: i18n.__("تعداد سطرها", "my-theme"),
                          value: Number(item.megaMenuRows) || 2,
                          min: 1,
                          max: 6,
                          onChange: function (value) {
                            updateMegaDimensions(
                              index,
                              item,
                              value,
                              Number(item.megaMenuColumns) || 3
                            );
                          }
                        }),
                        el(RangeControl, {
                          label: i18n.__("تعداد ستون‌ها", "my-theme"),
                          value: Number(item.megaMenuColumns) || 3,
                          min: 1,
                          max: 6,
                          onChange: function (value) {
                            updateMegaDimensions(
                              index,
                              item,
                              Number(item.megaMenuRows) || 2,
                              value
                            );
                          }
                        }),
                        el(
                          "div",
                          {
                            className: "site-header-pages-menu-editor__mega-preview",
                            style: {
                              display: "grid",
                              gap: "6px",
                              gridTemplateColumns:
                                "repeat(" +
                                (Number(item.megaMenuColumns) || 3) +
                                ", minmax(70px, 1fr))",
                              marginBottom: "12px"
                            }
                          },
                          normalizeMegaCells(
                            item.megaMenuCells,
                            (Number(item.megaMenuRows) || 2) *
                              (Number(item.megaMenuColumns) || 3)
                          ).map(function (cell, cellIndex) {
                            return el(
                              "span",
                              {
                                key: "mega-preview-" + cellIndex,
                                style: {
                                  background: cell.title ? "#f0f8f0" : "#f6f7f7",
                                  border: "1px dashed #c3c4c7",
                                  borderRadius: "3px",
                                  fontSize: "11px",
                                  minHeight: "36px",
                                  padding: "6px"
                                }
                              },
                              cell.title || i18n.__("خانه ", "my-theme") + (cellIndex + 1)
                            );
                          })
                        ),
                        normalizeMegaCells(
                          item.megaMenuCells,
                          (Number(item.megaMenuRows) || 2) *
                            (Number(item.megaMenuColumns) || 3)
                        ).map(function (cell, cellIndex) {
                          return el(
                            "div",
                            {
                              className: "site-header-pages-menu-editor__mega-cell",
                              key: "mega-cell-" + cellIndex,
                              style: {
                                border: "1px solid #dcdcde",
                                borderRadius: "4px",
                                marginBottom: "10px",
                                padding: "10px"
                              }
                            },
                            el("strong", {}, i18n.__("خانه ", "my-theme") + (cellIndex + 1)),
                            el(TextControl, {
                              label: i18n.__("عنوان", "my-theme"),
                              value: cell.title || "",
                              onChange: function (value) {
                                updateMegaCell(index, item, cellIndex, "title", value);
                              }
                            }),
                            el(TextControl, {
                              label: i18n.__("توضیح", "my-theme"),
                              value: cell.description || "",
                              onChange: function (value) {
                                updateMegaCell(index, item, cellIndex, "description", value);
                              }
                            }),
                            el(TextControl, {
                              label: i18n.__("لینک", "my-theme"),
                              value: cell.url || "",
                              type: "url",
                              onChange: function (value) {
                                updateMegaCell(index, item, cellIndex, "url", value);
                              }
                            })
                          );
                        })
                      )
                    : null,
                  el(
                    Button,
                    {
                      variant: "secondary",
                      isDestructive: true,
                      onClick: function () { removeItem(index); }
                    },
                    i18n.__("حذف آیتم", "my-theme")
                  )
                );
              })
            : null,
          manualMode
            ? el(Button, { variant: "primary", onClick: addItem }, i18n.__("افزودن آیتم جدید", "my-theme"))
            : null
        )
      );

      const preview = visibleItems.length
        ? el(
            "nav",
            {
              className: "wp-block-navigation site-header-pages-navigation",
              "aria-label": i18n.__("منوی صفحات هدر", "my-theme")
            },
            el(
              "ul",
              { className: "wp-block-navigation__container" },
              visibleItems.map(function (item, index) {
                return el(
                  "li",
                  {
                    className:
                      "wp-block-navigation-item" +
                      (item.megaMenuEnabled ? " has-custom-mega-menu-editor" : ""),
                    key: "preview-" + index
                  },
                  el(
                    "span",
                    { className: "wp-block-navigation-item__content" },
                    item.label || i18n.__("بدون عنوان", "my-theme")
                  )
                );
              })
            )
          )
        : el(
            "p",
            { className: "site-header-pages-menu-editor__notice" },
            manualMode
              ? i18n.__("منو خالی است؛ از تنظیمات بلوک یک آیتم اضافه کنید.", "my-theme")
              : Array.isArray(pages)
                ? i18n.__("صفحهٔ منتشرشده‌ای برای نمایش وجود ندارد.", "my-theme")
                : i18n.__("در حال بارگذاری منوی هدر…", "my-theme")
          );

      return el(
        Fragment,
        {},
        inspector,
        el(
          "div",
          useBlockProps({ className: "site-header-pages-menu-editor" }),
          preview
        )
      );
    },
    save: function () {
      return null;
    }
  });
})(
  window.wp.blocks,
  window.wp.element,
  window.wp.blockEditor,
  window.wp.components,
  window.wp.data,
  window.wp.i18n
);
