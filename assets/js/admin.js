/**
 * WP MCP Server Admin JS
 */
(function ($) {
    'use strict';

    $(document).ready(function () {
        // Copy to clipboard buttons.
        // Supports both data-copy (direct text) and data-copy-target (element ID to copy from).
        $('.wp-mcp-copy-btn').on('click', function () {
            var button = $(this);
            var text = button.data('copy');

            // If data-copy is not set, read from the target element.
            if (!text) {
                var targetId = button.data('copy-target');
                if (targetId) {
                    var $target = $('#' + targetId);
                    text = $target.length ? $target.text() : '';
                }
            }

            if (!text) {
                return;
            }

            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(text).then(function () {
                    showCopied(button);
                }).catch(function () {
                    fallbackCopy(text, button);
                });
            } else {
                fallbackCopy(text, button);
            }
        });

        function showCopied(button) {
            var original = button.text();
            button.text('Copied!');
            setTimeout(function () {
                button.text(original);
            }, 2000);
        }

        function fallbackCopy(text, button) {
            var textarea = document.createElement('textarea');
            textarea.value = text;
            textarea.style.position = 'fixed';
            textarea.style.opacity = '0';
            document.body.appendChild(textarea);
            textarea.select();
            try {
                document.execCommand('copy');
                showCopied(button);
            } catch (e) {
                // Silently fail.
            }
            document.body.removeChild(textarea);
        }

        // Select All Tools checkbox.
        var $selectAll = $('#wp-mcp-select-all-tools');
        var $toolCheckboxes = $('.wp-mcp-tool-checkbox');
        var $categoryCheckboxes = $('.wp-mcp-category-checkbox');
        var $toolsTree = $('.wp-mcp-tools-tree');
        var $categoryFilter = $('#wp-mcp-filter-category');
        var $actionFilter = $('#wp-mcp-filter-action');
        var $filters = $('.wp-mcp-filters');

        // --- Multiselect dropdown helpers ---

        function getFilterValues($widget) {
            var values = [];
            $widget.find('.wp-mcp-multiselect-dropdown input:checked').each(function () {
                values.push($(this).val());
            });
            return values;
        }

        function updateMultiselectLabel($widget) {
            var $checked = $widget.find('.wp-mcp-multiselect-dropdown input:checked');
            var $label = $widget.find('.wp-mcp-multiselect-label');
            var defaultLabel = $widget.data('default-label');
            if ($checked.length === 0) {
                $label.text(defaultLabel);
            } else if ($checked.length === 1) {
                $label.text($checked.first().closest('label').text().trim());
            } else {
                $label.text($checked.length + ' selected');
            }
        }

        // Toggle open/close on button click.
        $filters.on('click', '.wp-mcp-multiselect-btn', function (e) {
            e.stopPropagation();
            var $widget = $(this).closest('.wp-mcp-multiselect');
            var isOpen = $widget.hasClass('is-open');
            // Close all other open dropdowns first.
            $('.wp-mcp-multiselect.is-open').not($widget).removeClass('is-open')
                .find('.wp-mcp-multiselect-btn').attr('aria-expanded', 'false');
            $widget.toggleClass('is-open', !isOpen);
            $(this).attr('aria-expanded', String(!isOpen));
        });

        // Prevent clicks inside the dropdown from closing it.
        $filters.on('click', '.wp-mcp-multiselect-dropdown', function (e) {
            e.stopPropagation();
        });

        // Close all dropdowns when clicking outside.
        $(document).on('click.wp-mcp-multiselect', function () {
            $('.wp-mcp-multiselect.is-open').removeClass('is-open')
                .find('.wp-mcp-multiselect-btn').attr('aria-expanded', 'false');
        });

        // When a filter checkbox changes, update label and re-run filter.
        $categoryFilter.add($actionFilter).on('change', 'input[type="checkbox"]', function () {
            var $widget = $(this).closest('.wp-mcp-multiselect');
            updateMultiselectLabel($widget);
            filterTools();
        });

        // --- End multiselect helpers ---

        // Toggle tools tree and filters visibility when select-all changes.
        $selectAll.on('change', function () {
            var checked = $(this).is(':checked');
            var hasFilter = getFilterValues($categoryFilter).length > 0 || getFilterValues($actionFilter).length > 0;

            if (checked) {
                if (hasFilter) {
                    // Only check visible items when filtered.
                    $toolCheckboxes.filter(':visible').prop('checked', true);
                    // Update category checkboxes for visible categories.
                    $('.wp-mcp-tool-category:visible').each(function () {
                        var category = $(this).find('.wp-mcp-category-checkbox').data('category');
                        $('.wp-mcp-category-checkbox[data-category="' + category + '"]').prop('checked', true);
                    });
                } else {
                    // Check all items when not filtered.
                    $toolCheckboxes.prop('checked', true);
                    $categoryCheckboxes.prop('checked', true);
                }
            } else {
                // Uncheck all.
                $toolCheckboxes.prop('checked', false);
                $categoryCheckboxes.prop('checked', false);
            }

            // Toggle tools tree and filters visibility.
            if (checked) {
                $toolsTree.slideUp(200);
                $filters.slideUp(200);
            } else {
                $toolsTree.slideDown(200);
                $filters.slideDown(200);
            }
        });

        // Category toggle checkboxes.
        $categoryCheckboxes.on('change', function () {
            var category = $(this).data('category');
            var checked = $(this).is(':checked');
            $('.wp-mcp-tool-checkbox[data-category="' + category + '"]').prop('checked', checked);
            updateSelectAllState();
        });

        // Individual tool checkbox changes.
        $toolCheckboxes.on('change', function () {
            var category = $(this).data('category');
            updateCategoryState(category);
            updateSelectAllState();
        });

        function updateCategoryState(category) {
            var $catBoxes = $('.wp-mcp-tool-checkbox[data-category="' + category + '"]');
            var allChecked = $catBoxes.length === $catBoxes.filter(':checked').length;
            $('.wp-mcp-category-checkbox[data-category="' + category + '"]').prop('checked', allChecked);
        }

        function updateSelectAllState() {
            var allChecked = $toolCheckboxes.length > 0 && $toolCheckboxes.length === $toolCheckboxes.filter(':checked').length;
            $selectAll.prop('checked', allChecked);
            // Also toggle tree visibility based on select-all state.
            if (allChecked) {
                $toolsTree.slideUp(200);
            }
        }

        // Initialize category toggle states on page load.
        $categoryCheckboxes.each(function () {
            var category = $(this).data('category');
            updateCategoryState(category);
        });
        updateSelectAllState();

        // Filter functionality.
        function filterTools() {
            var selectedCategories = getFilterValues($categoryFilter);
            var selectedActions = getFilterValues($actionFilter);
            var hasFilter = selectedCategories.length > 0 || selectedActions.length > 0;

            // Reset all categories to visible first so :visible checks on their
            // children are not falsely negative from a previous filter pass.
            $('.wp-mcp-tool-category').show();

            $toolCheckboxes.each(function () {
                var $this = $(this);
                var toolCategory = $this.data('category');
                var toolAction = $this.data('action');

                var categoryMatch = selectedCategories.length === 0 || selectedCategories.indexOf(toolCategory) !== -1;
                var actionMatch = selectedActions.length === 0 || selectedActions.indexOf(toolAction) !== -1;

                if (categoryMatch && actionMatch) {
                    $this.closest('.wp-mcp-tool-item').show();
                } else {
                    $this.closest('.wp-mcp-tool-item').hide();
                }
            });

            // Hide entire category if no visible tool items remain.
            $('.wp-mcp-tool-category').each(function () {
                var $catDiv = $(this);
                var visibleItems = $catDiv.find('.wp-mcp-tool-item:visible').length;
                if (visibleItems === 0) {
                    $catDiv.hide();
                }
            });

            // If user is filtering, check all visible items when select-all is checked.
            if ($selectAll.is(':checked') && hasFilter) {
                $toolCheckboxes.filter(':visible').prop('checked', true);
                // Update category checkboxes for visible categories.
                $('.wp-mcp-tool-category:visible').each(function () {
                    var category = $(this).find('.wp-mcp-category-checkbox').data('category');
                    var $visibleCatBoxes = $('.wp-mcp-tool-checkbox:visible[data-category="' + category + '"]');
                    var allVisibleChecked = $visibleCatBoxes.length > 0 && $visibleCatBoxes.length === $visibleCatBoxes.filter(':checked').length;
                    $('.wp-mcp-category-checkbox[data-category="' + category + '"]').prop('checked', allVisibleChecked);
                });
            }
        }

        // Handle form submission - convert "select all" to "*" shorthand.
        $('.wp-mcp-token-form').on('submit', function () {
            if ($selectAll.is(':checked')) {
                // If all tools are selected, just send "*".
                $toolCheckboxes.prop('checked', false);
                $(this).append('<input type="hidden" name="allowed_tools[]" value="*">');
            }
        });

        // Language searchable dropdown.
        var $langWidget = $('.wp-mcp-lang-select');
        if ($langWidget.length) {
            var $langHidden = $langWidget.find('#admin_language');
            var $langInput = $langWidget.find('#admin_language_display');
            var $langList = $langWidget.find('.wp-mcp-lang-options');
            var $langItems = $langList.find('li');
            var $highlighted = $();

            function langFilter(query) {
                var q = query.toLowerCase();
                $langItems.each(function () {
                    var label = ($(this).data('label') || '').toLowerCase();
                    var val = ($(this).data('value') || '').toLowerCase();
                    $(this).toggleClass('wp-mcp-lang-hidden', q !== '' && label.indexOf(q) === -1 && val.indexOf(q) === -1);
                });
                $langItems.filter('.wp-mcp-lang-hidden:first').prev().removeClass('wp-mcp-lang-hidden');
            }

            function langHighlight($item) {
                $highlighted.removeClass('wp-mcp-lang-highlight');
                $highlighted = $item.addClass('wp-mcp-lang-highlight');
                if ($item.length) {
                    var listTop = $langList.scrollTop();
                    var listBottom = listTop + $langList.height();
                    var itemTop = $item.position().top + listTop;
                    var itemBottom = itemTop + $item.outerHeight();
                    if (itemBottom > listBottom) {
                        $langList.scrollTop(itemBottom - $langList.height());
                    } else if (itemTop < listTop) {
                        $langList.scrollTop(itemTop);
                    }
                }
            }

            function langSelect($item) {
                var val = $item.data('value');
                var label = $item.data('label');
                $langHidden.val(val);
                $langInput.val(label).data('current-label', label);
                $langItems.removeClass('wp-mcp-lang-active');
                $item.addClass('wp-mcp-lang-active');
                $langWidget.removeClass('is-open');
                langFilter(''); // Reset filter when closed
            }

            // Store initial label
            $langInput.data('current-label', $langInput.val());

            $langInput.on('focus', function () {
                // When focused, clear the input to allow immediate searching
                $(this).val('');
                $langWidget.addClass('is-open');
                $langItems.removeClass('wp-mcp-lang-hidden');
                langHighlight($langItems.filter('.wp-mcp-lang-active'));
            }).on('blur', function () {
                // Short delay to allow click events on dropdown items to fire first
                setTimeout(function() {
                    if (!$langWidget.hasClass('is-open')) {
                        // Restore the selected label if closed
                        $langInput.val($langInput.data('current-label'));
                    }
                }, 150);
            }).on('click', function (e) {
                // If already focused and clicked, don't clear again
                if (!$langWidget.hasClass('is-open')) {
                    $(this).val('');
                    $langWidget.addClass('is-open');
                    $langItems.removeClass('wp-mcp-lang-hidden');
                    langHighlight($langItems.filter('.wp-mcp-lang-active'));
                }
            }).on('input', function () {
                $langWidget.addClass('is-open');
                langFilter($(this).val());
                var $visible = $langItems.not('.wp-mcp-lang-hidden');
                langHighlight($visible.first());
            }).on('keydown', function (e) {
                var $visible = $langItems.not('.wp-mcp-lang-hidden');
                if (e.key === 'ArrowDown') {
                    e.preventDefault();
                    var idx = $visible.index($highlighted);
                    langHighlight($visible.eq(Math.min(idx + 1, $visible.length - 1)));
                } else if (e.key === 'ArrowUp') {
                    e.preventDefault();
                    var idx = $visible.index($highlighted);
                    langHighlight($visible.eq(Math.max(idx - 1, 0)));
                } else if (e.key === 'Enter') {
                    e.preventDefault();
                    if ($highlighted.length) {
                        langSelect($highlighted);
                    }
                } else if (e.key === 'Escape') {
                    $langWidget.removeClass('is-open');
                }
            });

            $langList.on('mousedown', 'li:not(.wp-mcp-lang-hidden)', function (e) {
                e.preventDefault();
                langSelect($(this));
            }).on('mouseover', 'li:not(.wp-mcp-lang-hidden)', function () {
                langHighlight($(this));
            });

            $(document).on('click.wp-mcp-lang', function (e) {
                if (!$(e.target).closest($langWidget).length) {
                    if ($langWidget.hasClass('is-open')) {
                        $langWidget.removeClass('is-open');
                        $langInput.val($langInput.data('current-label'));
                        langFilter('');
                    }
                }
            });
        }

    });

    // ---------------------------------------------------------------
    // Plugin Integrations Page — collapse, search, toggle-all, type filter
    // ---------------------------------------------------------------

    $(document).on('click', '.wp-mcp-collapse-btn', function () {
        var $btn     = $(this);
        var $body    = $('#' + $btn.attr('aria-controls'));
        var expanded = $btn.attr('aria-expanded') === 'true';
        $body.attr('hidden', expanded ? true : null);
        $btn.attr('aria-expanded', expanded ? 'false' : 'true');
        $btn.find('.wp-mcp-collapse-icon')
            .removeClass('dashicons-arrow-right-alt2 dashicons-arrow-down-alt2')
            .addClass( expanded ? 'dashicons-arrow-right-alt2' : 'dashicons-arrow-down-alt2' );
    });

    // Group checkbox: only act when plugin is installed (disabled checkboxes can't fire, but guard anyway).
    $(document).on('change', '.wp-mcp-group-checkbox', function () {
        var $card   = $(this).closest('.wp-mcp-plugin-card');
        if ( $card.hasClass('is-not-installed') ) { return; }
        var enabled = $(this).is(':checked');
        $card.toggleClass('is-enabled', enabled).toggleClass('is-disabled', !enabled);
        $card.find('.wp-mcp-tool-checkbox').prop('checked', enabled);
        $card.find('.wp-mcp-tool-row').toggleClass('is-enabled', enabled).toggleClass('is-disabled', !enabled);
        updatePluginToolCount($card);
    });

    $(document).on('click', '.wp-mcp-enable-all-btn', function () {
        var $card = $(this).closest('.wp-mcp-plugin-card');
        $card.find('.wp-mcp-tool-row:visible .wp-mcp-tool-checkbox').prop('checked', true);
        $card.find('.wp-mcp-tool-row:visible').addClass('is-enabled').removeClass('is-disabled');
        updatePluginToolCount($card);
    });

    $(document).on('click', '.wp-mcp-disable-all-btn', function () {
        var $card = $(this).closest('.wp-mcp-plugin-card');
        $card.find('.wp-mcp-tool-row:visible .wp-mcp-tool-checkbox').prop('checked', false);
        $card.find('.wp-mcp-tool-row:visible').addClass('is-disabled').removeClass('is-enabled');
        updatePluginToolCount($card);
    });

    $(document).on('change', '.wp-mcp-tool-checkbox', function () {
        var $row  = $(this).closest('.wp-mcp-tool-row');
        var $card = $(this).closest('.wp-mcp-plugin-card');
        $row.toggleClass('is-enabled', $(this).is(':checked')).toggleClass('is-disabled', !$(this).is(':checked'));
        updatePluginToolCount($card);
    });

    // Text search — respects active type filter.
    $(document).on('input', '.wp-mcp-tool-search', function () {
        var q     = $(this).val().toLowerCase().trim();
        var $card = $(this).closest('.wp-mcp-plugin-card');
        var activeFilter = $card.find('.wp-mcp-type-filter-btn.is-active').data('filter') || 'all';
        applyToolVisibility($card, q, activeFilter);
    });

    // Type filter buttons.
    $(document).on('click', '.wp-mcp-type-filter-btn', function () {
        var $btn    = $(this);
        var $card   = $btn.closest('.wp-mcp-plugin-card');
        var filter  = $btn.data('filter');
        var q       = $card.find('.wp-mcp-tool-search').val().toLowerCase().trim();

        $card.find('.wp-mcp-type-filter-btn').removeClass('is-active');
        $btn.addClass('is-active');

        applyToolVisibility($card, q, filter);
    });

    function applyToolVisibility($card, q, typeFilter) {
        $card.find('.wp-mcp-tool-row').each(function () {
            var name     = $(this).find('.wp-mcp-tool-name').text().toLowerCase();
            var desc     = $(this).find('.wp-mcp-tool-description').text().toLowerCase();
            var toolType = $(this).data('tool-type') || 'all';
            var matchQ    = !q || name.indexOf(q) !== -1 || desc.indexOf(q) !== -1;
            var matchType = (typeFilter === 'all') || (toolType === typeFilter);
            $(this).toggle( matchQ && matchType );
        });
    }

    function updatePluginToolCount($card) {
        var total      = $card.find('.wp-mcp-tool-row').length;
        var enabled    = $card.find('.wp-mcp-tool-checkbox:checked').length;
        var readCount  = 0;
        var writeCount = 0;
        $card.find('.wp-mcp-tool-row').each(function () {
            if ( $(this).find('.wp-mcp-badge--read').length )  { readCount++; }
            if ( $(this).find('.wp-mcp-badge--write').length ) { writeCount++; }
        });
        var group = $card.data('group');
        $('[data-group="' + group + '"].wp-mcp-tool-counts').first().text(
            enabled + ' / ' + total + ' tools enabled \u00b7 ' + readCount + ' read, ' + writeCount + ' write'
        );
    }

    // ── Confirm-submit buttons (CSP-friendly replacement for inline onclick). ─
    // Wires up any <button class="wp-mcp-confirm-submit" data-confirm="...">.
    $(document).on('click', '.wp-mcp-confirm-submit', function (e) {
        var msg = $(this).data('confirm');
        if (msg && ! window.confirm(msg)) {
            e.preventDefault();
            return false;
        }
    });

    // ── Audit-log change-detail expand/collapse + AJAX detail loader. ────────
    // Activates only when wp_localize_script provided rankoutConnectorAudit on the
    // Audit Log admin page. AJAX endpoint returns server-rendered HTML
    // (escape responsibility lives in Admin_Page::ajax_get_changes_for_audit).
    if (typeof window.rankoutConnectorAudit !== 'undefined' && window.rankoutConnectorAudit && window.rankoutConnectorAudit.ajaxUrl) {
        var connectorAudit = window.rankoutConnectorAudit;

        function loadAuditDetail(auditId, detailRow) {
            var $body = $(detailRow).find('.rankout-connector-changes-detail-body');
            if (!$body.length || detailRow.dataset.loaded === '1') { return; }
            var fd = new FormData();
            fd.append('action',   'rankout_connector_get_changes_for_audit');
            fd.append('audit_id', auditId);
            fd.append('nonce',    connectorAudit.nonce);
            fetch(connectorAudit.ajaxUrl, { method: 'POST', body: fd, credentials: 'same-origin' })
                .then(function (r) { return r.json(); })
                .then(function (json) {
                    if (json && json.success && json.data && json.data.html) {
                        $body.html(json.data.html);
                    } else {
                        $body.html('<p class="description"></p>').find('p').text(connectorAudit.failedToLoadMsg);
                    }
                    detailRow.dataset.loaded = '1';
                })
                .catch(function () {
                    $body.html('<p class="description"></p>').find('p').text(connectorAudit.failedToLoadMsg);
                });
        }

        function toggleAuditRow(auditId) {
            var toggle    = document.querySelector('.rankout-connector-changes-toggle[data-audit-id="' + auditId + '"]');
            var detailRow = document.querySelector('.rankout-connector-changes-detail[data-audit-id="' + auditId + '"]');
            if (!toggle || !detailRow) { return; }
            if (detailRow.hidden === false) {
                detailRow.hidden = true;
                toggle.setAttribute('aria-expanded', 'false');
                var caret = toggle.querySelector('.rankout-connector-changes-caret');
                if (caret) { caret.textContent = '▾'; }
            } else {
                detailRow.hidden = false;
                toggle.setAttribute('aria-expanded', 'true');
                var c2 = toggle.querySelector('.rankout-connector-changes-caret');
                if (c2) { c2.textContent = '▴'; }
                loadAuditDetail(auditId, detailRow);
            }
        }

        document.addEventListener('click', function (e) {
            var t = e.target.closest && e.target.closest('.rankout-connector-changes-toggle');
            if (!t) { return; }
            e.preventDefault();
            toggleAuditRow(t.dataset.auditId);
        });

        // Auto-expand on deep-link from Change History (?audit_id=N).
        $(function () {
            var params = new URLSearchParams(window.location.search);
            var id     = params.get('audit_id');
            if (!id) { return; }
            toggleAuditRow(id);
            var row = document.getElementById('audit-' + id);
            if (row && row.scrollIntoView) {
                row.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        });
    }

})(jQuery);
