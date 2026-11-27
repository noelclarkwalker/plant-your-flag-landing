/**
 * Interactive Pull Quotes — Phase 1 editor:
 * block, Add as pull quote, Your Pull Quotes panel, restore, preview.
 */
(function (wp, logic) {
  "use strict";

  if (!wp || !logic) {
    return;
  }

  var el = wp.element.createElement;
  var Fragment = wp.element.Fragment;
  var registerBlockType = wp.blocks.registerBlockType;
  var createBlock = wp.blocks.createBlock;
  var useBlockProps = wp.blockEditor.useBlockProps;
  var RichText = wp.blockEditor.RichText;
  var BlockControls = wp.blockEditor.BlockControls;
  var InspectorControls = wp.blockEditor.InspectorControls;
  var useSelect = wp.data.useSelect;
  var useDispatch = wp.data.useDispatch;
  var select = wp.data.select;
  var dispatch = wp.data.dispatch;
  var ToolbarGroup = wp.components.ToolbarGroup;
  var ToolbarButton = wp.components.ToolbarButton;
  var Button = wp.components.Button;
  var PanelBody = wp.components.PanelBody;
  var registerPlugin = wp.plugins.registerPlugin;
  var PluginSidebar = wp.editPost.PluginSidebar;
  var PluginSidebarMoreMenuItem = wp.editPost.PluginSidebarMoreMenuItem;
  var PluginDocumentSettingPanel = wp.editPost.PluginDocumentSettingPanel;
  var create = wp.richText.create;
  var slice = wp.richText.slice;
  var toHTMLString = wp.richText.toHTMLString;
  var addFilter = wp.hooks.addFilter;
  var createHigherOrderComponent = wp.compose.createHigherOrderComponent;
  var createNotice = function (status, message) {
    dispatch("core/notices").createNotice(status, message, {
      type: "snackbar",
      isDismissible: true,
    });
  };

  var BLOCK_NAME = (window.noelclarkV1PullQuotes && window.noelclarkV1PullQuotes.blockName) ||
    "noelclark/interactive-pull-quote";
  var WRITING_TEMPLATE =
    (window.noelclarkV1PullQuotes && window.noelclarkV1PullQuotes.writingTemplate) ||
    "page-ps-writing-essay.php";

  function isWritingEssayEditor() {
    var meta = select("core/editor");
    if (!meta || typeof meta.getEditedPostAttribute !== "function") {
      return false;
    }
    var template = meta.getEditedPostAttribute("template");
    return template === WRITING_TEMPLATE;
  }

  function getSelectedRichRange() {
    var start = select("core/block-editor").getSelectionStart();
    var end = select("core/block-editor").getSelectionEnd();
    if (!start || !end || !start.clientId || !end.clientId) {
      return null;
    }
    return {
      start: start,
      end: end,
      crossBlock: start.clientId !== end.clientId,
    };
  }

  function convertSelectionToPullQuote() {
    if (!isWritingEssayEditor()) {
      createNotice(
        "warning",
        "Interactive pull quotes are available on pages that use the P.S. — Writing Essay template."
      );
      return;
    }

    var range = getSelectedRichRange();
    if (!range) {
      createNotice("warning", "Select the passage you want to turn into a pull quote.");
      return;
    }

    var block = select("core/block-editor").getBlock(range.start.clientId);
    if (!block) {
      createNotice("warning", "Select the passage you want to turn into a pull quote.");
      return;
    }

    var startOffset = typeof range.start.offset === "number" ? range.start.offset : null;
    var endOffset = typeof range.end.offset === "number" ? range.end.offset : null;
    var value = create({ html: block.attributes.content || "" });
    var partialLink = false;

    if (
      startOffset !== null &&
      endOffset !== null &&
      !range.crossBlock &&
      block.name === "core/paragraph"
    ) {
      partialLink = logic.selectionCutsPartialLink(value.formats || [], startOffset, endOffset);
    }

    var selectedSlice =
      startOffset !== null && endOffset !== null && endOffset > startOffset
        ? slice(value, startOffset, endOffset)
        : null;
    var empty = !selectedSlice || !String(selectedSlice.text || "").trim();

    var assessment = logic.assessSelection({
      blockName: block.name,
      startOffset: startOffset,
      endOffset: endOffset,
      crossBlock: range.crossBlock,
      partialLink: partialLink,
      empty: empty,
    });

    if (!assessment.ok) {
      createNotice("warning", assessment.reason);
      return;
    }

    var beforeValue = slice(value, 0, startOffset);
    var afterValue = slice(value, endOffset);
    var beforeHtml = toHTMLString({ value: beforeValue });
    var selectedHtml = toHTMLString({ value: selectedSlice });
    var afterHtml = toHTMLString({ value: afterValue });
    var quoteId = logic.createQuoteId();
    var neighborHash = logic.hashNeighbors(beforeHtml, afterHtml);

    var replacements = [];
    if (logic.stripTags(beforeHtml)) {
      replacements.push(createBlock("core/paragraph", { content: beforeHtml }));
    }
    replacements.push(
      createBlock(BLOCK_NAME, {
        content: selectedHtml,
        quoteId: quoteId,
        beforeHtml: beforeHtml,
        afterHtml: afterHtml,
        neighborHash: neighborHash,
        marks: [],
        isInteractive: false,
      })
    );
    if (logic.stripTags(afterHtml)) {
      replacements.push(createBlock("core/paragraph", { content: afterHtml }));
    }

    dispatch("core/block-editor").replaceBlocks(block.clientId, replacements);
    createNotice("success", "Pull quote added. Use Undo if you change your mind.");
  }

  function findAdjacentParagraphHtml(clientId, direction) {
    var root = select("core/block-editor").getBlockRootClientId(clientId);
    var order = select("core/block-editor").getBlockOrder(root);
    var index = order.indexOf(clientId);
    if (index < 0) {
      return { html: "", clientId: null, present: false };
    }
    var neighborIndex = direction === "before" ? index - 1 : index + 1;
    if (neighborIndex < 0 || neighborIndex >= order.length) {
      return { html: "", clientId: null, present: false };
    }
    var neighbor = select("core/block-editor").getBlock(order[neighborIndex]);
    if (!neighbor || neighbor.name !== "core/paragraph") {
      return { html: "", clientId: null, present: false };
    }
    return {
      html: neighbor.attributes.content || "",
      clientId: neighbor.clientId,
      present: true,
    };
  }

  function restorePullQuote(clientId) {
    var block = select("core/block-editor").getBlock(clientId);
    if (!block || block.name !== BLOCK_NAME) {
      return;
    }

    var beforeNeighbor = findAdjacentParagraphHtml(clientId, "before");
    var afterNeighbor = findAdjacentParagraphHtml(clientId, "after");
    var plan = logic.planRestore({
      neighborHash: block.attributes.neighborHash || "",
      beforeHtml: block.attributes.beforeHtml || "",
      afterHtml: block.attributes.afterHtml || "",
      currentBeforeHtml: beforeNeighbor.html,
      currentAfterHtml: afterNeighbor.html,
      neighborsPresent: beforeNeighbor.present || afterNeighbor.present,
    });

    var quoteHtml = block.attributes.content || "";

    if (plan.mode === "merge") {
      var merged = "";
      var replaceIds = [];
      if (beforeNeighbor.present && logic.stripTags(beforeNeighbor.html)) {
        merged += beforeNeighbor.html;
        replaceIds.push(beforeNeighbor.clientId);
      }
      merged += quoteHtml;
      if (afterNeighbor.present && logic.stripTags(afterNeighbor.html)) {
        merged += afterNeighbor.html;
        replaceIds.push(afterNeighbor.clientId);
      }
      replaceIds.push(clientId);

      var paragraph = createBlock("core/paragraph", { content: merged });
      dispatch("core/block-editor").replaceBlocks(replaceIds, [paragraph]);
      createNotice("success", "Words put back in the paragraph. Use Undo if needed.");
      return;
    }

    dispatch("core/block-editor").replaceBlocks(clientId, [
      createBlock("core/paragraph", { content: quoteHtml }),
    ]);
    createNotice("info", plan.message);
  }

  function focusPullQuote(clientId) {
    dispatch("core/block-editor").selectBlock(clientId);
    window.setTimeout(function () {
      var node = document.getElementById("block-" + clientId);
      if (node && typeof node.scrollIntoView === "function") {
        node.scrollIntoView({ behavior: "smooth", block: "center" });
      }
    }, 50);
  }

  function openDraftPreview() {
    var previewLink =
      select("core/editor").getEditedPostPreviewLink &&
      select("core/editor").getEditedPostPreviewLink();
    var permalink =
      select("core/editor").getPermalink && select("core/editor").getPermalink();
    var url = previewLink || permalink;
    if (!url) {
      createNotice(
        "warning",
        "Save a draft first, then use Preview essay to open the complete page."
      );
      return;
    }
    window.open(url, "_blank", "noopener");
  }

  /* ------------------------------------------------------------------------ */
  /* Block                                                                    */
  /* ------------------------------------------------------------------------ */

  registerBlockType(BLOCK_NAME, {
    apiVersion: 2,
    title: "Interactive Pull Quote",
    icon: "editor-quote",
    category: "text",
    attributes: {
      content: { type: "string", default: "" },
      quoteId: { type: "string", default: "" },
      beforeHtml: { type: "string", default: "" },
      afterHtml: { type: "string", default: "" },
      neighborHash: { type: "string", default: "" },
      marks: { type: "array", default: [] },
      isInteractive: { type: "boolean", default: false },
    },
    supports: {
      html: false,
      multiple: true,
      reusable: false,
    },
    edit: function (props) {
      var attributes = props.attributes;
      var setAttributes = props.setAttributes;
      var clientId = props.clientId;
      var blockProps = useBlockProps({ className: "ps-pull-quote-editor" });

      return el(
        Fragment,
        null,
        el(
          BlockControls,
          null,
          el(
            ToolbarGroup,
            null,
            el(ToolbarButton, {
              icon: "undo",
              label: "Put these words back in the paragraph",
              onClick: function () {
                restorePullQuote(clientId);
              },
            })
          )
        ),
        el(
          InspectorControls,
          null,
          el(
            PanelBody,
            { title: "Pull quote", initialOpen: true },
            el(
              "p",
              { className: "ps-pull-quotes-panel__hint" },
              "This passage keeps the essay’s normal typography. The pen interaction comes later — not in this Phase 1 foundation."
            ),
            el(
              Button,
              {
                variant: "secondary",
                onClick: function () {
                  restorePullQuote(clientId);
                },
              },
              "Put these words back in the paragraph"
            )
          )
        ),
        el(
          "figure",
          blockProps,
          el("span", { className: "ps-pull-quote-editor__label" }, "Pull quote"),
          el(RichText, {
            tagName: "div",
            className: "ps-pull-quote-editor__text",
            value: attributes.content,
            onChange: function (next) {
              setAttributes({ content: next });
            },
            placeholder: "Pull quote text…",
            allowedFormats: ["core/bold", "core/italic", "core/link"],
          })
        )
      );
    },
    save: function () {
      return null;
    },
  });

  /* ------------------------------------------------------------------------ */
  /* Paragraph toolbar: Add as pull quote                                     */
  /* ------------------------------------------------------------------------ */

  var withPullQuoteToolbar = createHigherOrderComponent(function (BlockEdit) {
    return function (props) {
      if (props.name !== "core/paragraph" || !isWritingEssayEditor()) {
        return el(BlockEdit, props);
      }

      return el(
        Fragment,
        null,
        el(BlockEdit, props),
        el(
          BlockControls,
          { group: "other" },
          el(
            ToolbarGroup,
            null,
            el(ToolbarButton, {
              icon: "editor-quote",
              label: "Add as pull quote",
              onClick: convertSelectionToPullQuote,
            })
          )
        )
      );
    };
  }, "withNoelClarkPullQuoteToolbar");

  addFilter(
    "editor.BlockEdit",
    "noelclark/interactive-pull-quote/paragraph-toolbar",
    withPullQuoteToolbar
  );

  /* ------------------------------------------------------------------------ */
  /* Your Pull Quotes panel                                                   */
  /* ------------------------------------------------------------------------ */

  function YourPullQuotesPanel() {
    var quotes = useSelect(function (sel) {
      var blocks = sel("core/block-editor").getBlocks();
      var found = [];
      function walk(list) {
        (list || []).forEach(function (block) {
          if (block.name === BLOCK_NAME) {
            found.push(block);
          }
          if (block.innerBlocks && block.innerBlocks.length) {
            walk(block.innerBlocks);
          }
        });
      }
      walk(blocks);
      return found;
    }, []);

    if (!isWritingEssayEditor()) {
      return null;
    }

    return el(
      Fragment,
      null,
      el(
        PluginSidebarMoreMenuItem,
        { target: "noelclark-your-pull-quotes", icon: "editor-quote" },
        "Your Pull Quotes"
      ),
      el(
        PluginSidebar,
        {
          name: "noelclark-your-pull-quotes",
          title: "Your Pull Quotes",
          icon: "editor-quote",
        },
        el(
          "div",
          { style: { padding: "16px" } },
          quotes.length === 0
            ? el(
                "p",
                { className: "ps-pull-quotes-panel__empty" },
                "No pull quotes yet. Select a passage in a paragraph and choose Add as pull quote."
              )
            : el(
                "ol",
                { className: "ps-pull-quotes-panel__list" },
                quotes.map(function (block, index) {
                  return el(
                    "li",
                    { key: block.clientId, className: "ps-pull-quotes-panel__item" },
                    el(
                      "span",
                      { className: "ps-pull-quotes-panel__index" },
                      String(index + 1).padStart(2, "0")
                    ),
                    el(
                      "p",
                      { className: "ps-pull-quotes-panel__excerpt" },
                      logic.excerptText(block.attributes.content || "")
                    ),
                    el(
                      "div",
                      { className: "ps-pull-quotes-panel__actions" },
                      el(
                        Button,
                        {
                          variant: "secondary",
                          onClick: function () {
                            focusPullQuote(block.clientId);
                          },
                        },
                        "Find in essay"
                      ),
                      el(
                        Button,
                        {
                          variant: "secondary",
                          onClick: function () {
                            focusPullQuote(block.clientId);
                          },
                        },
                        "Correct"
                      ),
                      el(
                        Button,
                        {
                          variant: "secondary",
                          isDestructive: true,
                          onClick: function () {
                            restorePullQuote(block.clientId);
                          },
                        },
                        "Put these words back in the paragraph"
                      )
                    )
                  );
                })
              ),
          el(
            "p",
            { className: "ps-pull-quotes-panel__hint" },
            "Collect as many passages as you like. Assigning pen effects comes in a later phase and is not available here yet."
          ),
          el(
            Button,
            {
              variant: "primary",
              style: { marginTop: "12px" },
              onClick: openDraftPreview,
            },
            "Preview essay"
          ),
          el(
            "p",
            { className: "ps-pull-quotes-panel__hint" },
            "Preview opens the complete draft without publishing so you can check placement and typography. “See All Interactions” arrives when effects are available."
          )
        )
      ),
      el(
        PluginDocumentSettingPanel,
        {
          name: "noelclark-your-pull-quotes-doc",
          title: "Your Pull Quotes",
          className: "ps-pull-quotes-doc-panel",
        },
        el(
          "p",
          { className: "ps-pull-quotes-panel__empty" },
          quotes.length
            ? quotes.length +
                " pull quote" +
                (quotes.length === 1 ? "" : "s") +
                " collected. Open “Your Pull Quotes” in the sidebar for the full list."
            : "No pull quotes yet."
        ),
        el(
          Button,
          { variant: "secondary", onClick: openDraftPreview },
          "Preview essay"
        )
      )
    );
  }

  registerPlugin("noelclark-your-pull-quotes", {
    render: YourPullQuotesPanel,
    icon: "editor-quote",
  });
})(window.wp, window.NoelClarkPullQuotesLogic);
