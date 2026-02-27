(function ($) {
    'use strict';

    var getCollaborativeService = function () {
        return typeof window.collaborativeService !== 'undefined' ? window.collaborativeService : null;
    };

    // Store extras for the current cart item when user is on service_extras
    bookneticHooks.addFilter('bkntc_cart', function (cartItem, booknetic) {
        var panel = booknetic.panel_js;
        var activeStep = panel.find('.booknetic_appointment_step_element.booknetic_active_step').data('step-id');
        if (activeStep === 'service_extras') {
            var extrasForCurrent = getExtrasForCartIndex(panel, booknetic.cartCurrentIndex);
            if (extrasForCurrent) {
                cartItem.service_extras = extrasForCurrent;
            }
        }
        return cartItem;
    });

    // Prevent auto-skip of service extras when multiple services are selected
    bookneticHooks.addAction('before_step_loading', function (booknetic, new_step_id) {
        if (new_step_id !== 'service_extras') {
            return;
        }

        if (isCollaborativeMultiService(booknetic)) {
            var collab = getCollaborativeService();
            if (collab) {
                collab._skipExtrasSetting = window.BookneticData ? window.BookneticData['skip_extras_step_if_need'] : undefined;
            }
            if (window.BookneticData) {
                window.BookneticData['skip_extras_step_if_need'] = 'off';
            }
        }
    });

    // Build multi-service extras UI on load
    bookneticHooks.addAction('loaded_step', function (booknetic, new_step_id) {
        if (new_step_id !== 'service_extras') {
            return;
        }

        buildMultiServiceExtras(booknetic);
    });

    bookneticHooks.addAction('loaded_cached_step', function (booknetic, step_id) {
        if (step_id !== 'service_extras') {
            return;
        }

        buildMultiServiceExtras(booknetic);
    });

    // Sync extras to each cart item before leaving extras step
    bookneticHooks.addAction('before_step_loading', function (booknetic, new_step_id, old_step_id) {
        if (old_step_id !== 'service_extras') {
            return;
        }

        var panel = booknetic.panel_js;
        if (isCollaborativeMultiService(booknetic)) {
            syncExtrasFromSections(booknetic, panel);
        }

        var collab = getCollaborativeService();
        if (collab && collab._skipExtrasSetting !== undefined && window.BookneticData) {
            window.BookneticData['skip_extras_step_if_need'] = collab._skipExtrasSetting;
            collab._skipExtrasSetting = undefined;
        }
    });

    function isCollaborativeMultiService(booknetic) {
        return booknetic.cartArr && booknetic.cartArr.length > 1 && booknetic.cartArr.some(function (item) {
            return item && item.is_collaborative_booking;
        });
    }

    function getExtrasForCartIndex(panel, cartIndex) {
        var section = panel.find('.bkntc_collab_extras_section[data-cart-index="' + cartIndex + '"]');
        if (section.length === 0) {
            return null;
        }

        var extras = [];
        section.find('.booknetic_service_extra_card_selected').each(function () {
            var extraId = $(this).data('id');
            var quantity = parseInt($(this).find('.booknetic_service_extra_quantity_input').val(), 10);
            if (quantity > 0) {
                extras.push({
                    extra: extraId,
                    quantity: quantity
                });
            }
        });

        return extras;
    }

    function syncExtrasFromSections(booknetic, panel) {
        panel.find('.bkntc_collab_extras_section').each(function () {
            var section = $(this);
            var cartIndex = parseInt(section.data('cart-index'), 10);
            var serviceId = section.data('service-id');

            var extras = getExtrasForCartIndex(panel, cartIndex) || [];

            if (Number.isInteger(cartIndex) && booknetic.cartArr[cartIndex]) {
                booknetic.cartArr[cartIndex].service_extras = extras;
            } else {
                // Cart not expanded yet - store extras on collaborativeService selections
                var collab = getCollaborativeService();
                if (collab && collab.selectedServices && serviceId) {
                    collab.selectedServices.forEach(function (item) {
                        if (String(item.service_id) === String(serviceId)) {
                            item.service_extras = extras;
                        }
                    });
                }
            }
        });
    }

    function buildMultiServiceExtras(booknetic) {
        var panel = booknetic.panel_js;
        var stepContainer = panel.find('.booknetic_appointment_container_body [data-step-id="service_extras"]');
        if (stepContainer.length === 0) {
            return;
        }

        var collab = getCollaborativeService();
        var useSelectedServices = false;
        var items = [];

        if (isCollaborativeMultiService(booknetic)) {
            items = booknetic.cartArr.filter(function (item) {
                return item && item.is_collaborative_booking;
            });
        }

        if (items.length === 0 && collab && collab.selectedServices && collab.selectedServices.length > 1) {
            useSelectedServices = true;
            items = collab.selectedServices.slice();
        }

        if (items.length === 0) {
            return;
        }

        var originalIndex = booknetic.cartCurrentIndex;
        var originalCartArr = booknetic.cartArr;
        var sectionsHtml = [];

        stepContainer.addClass('bkntc_collab_extras_loading').empty().append('<div class="booknetic_loading">Loading extras...</div>');

        var index = 0;
        var loadNext = function () {
            if (index >= items.length) {
                stepContainer.removeClass('bkntc_collab_extras_loading').html(sectionsHtml.join(''));
                booknetic.cartCurrentIndex = originalIndex;
                booknetic.handleScroll();
                return;
            }

            var itemIndex = useSelectedServices ? index : booknetic.cartArr.indexOf(items[index]);
            if (!useSelectedServices && itemIndex < 0) {
                index++;
                loadNext();
                return;
            }

            if (useSelectedServices) {
                var baseItem = originalCartArr[originalIndex] ? JSON.parse(JSON.stringify(originalCartArr[originalIndex])) : {};
                baseItem.service = items[index].service_id;
                baseItem.assigned_to = items[index].assigned_to || baseItem.assigned_to;
                if (items[index].category_id) {
                    baseItem.service_category = items[index].category_id;
                }
                if (collab && collab.selectedServices) {
                    baseItem.selected_services = JSON.parse(JSON.stringify(collab.selectedServices));
                }

                booknetic.cartArr = [baseItem];
                booknetic.cartCurrentIndex = 0;
            } else {
                booknetic.cartCurrentIndex = itemIndex;
            }

            var bookingPanelParams = {
                current_step: 'service_extras',
                previous_step: panel.find('.booknetic_appointment_step_element.booknetic_active_step').data('step-id') || 'service',
                info: panel.data('info')
            };

            booknetic.ajax('get_data', booknetic.ajaxParameters(bookingPanelParams, false), function (result) {
                var html = result && result['html'] ? result['html'] : '';
                html = normalizeExtrasHtml(booknetic, html);
                var serviceLabel = buildServiceLabel(items[index], itemIndex);
                var serviceId = useSelectedServices ? items[index].service_id : (items[index].service || '');

                if (useSelectedServices) {
                    booknetic.cartArr = originalCartArr;
                    booknetic.cartCurrentIndex = originalIndex;
                }

                sectionsHtml.push(
                    '<div class="bkntc_collab_extras_section" data-cart-index="' + itemIndex + '" data-service-id="' + serviceId + '">' +
                    '<div class="bkntc_collab_extras_heading">' + escapeHtml(serviceLabel) + '</div>' +
                    html +
                    '</div>'
                );

                index++;
                loadNext();
            }, false);
        };

        loadNext();
    }

    function normalizeExtrasHtml(booknetic, html) {
        if (!html) {
            return '';
        }

        if (booknetic && typeof booknetic.sanitizeHTML === 'function' && typeof booknetic.htmlspecialchars_decode === 'function') {
            return booknetic.htmlspecialchars_decode(booknetic.sanitizeHTML(html));
        }

        return html;
    }

    function buildServiceLabel(item, itemIndex) {
        var label = 'Service #' + (item && item.service ? item.service : itemIndex + 1);

        if (item && item.collaborative_service_name) {
            label = normalizeServiceName(item.collaborative_service_name);
        } else if (item && item.service) {
            var fromSelected = getSelectedServiceName(item.service);
            if (fromSelected) {
                label = normalizeServiceName(fromSelected);
            }
        } else if (item && item.service_name) {
            label = normalizeServiceName(item.service_name);
        }

        if (item && item.assigned_to) {
            label += ' (' + item.assigned_to + ')';
        }
        return label;
    }

    function getSelectedServiceName(serviceId) {
        var collab = getCollaborativeService();
        if (!collab || !collab.selectedServices || collab.selectedServices.length === 0) {
            return '';
        }

        for (var i = 0; i < collab.selectedServices.length; i++) {
            if (String(collab.selectedServices[i].service_id) === String(serviceId)) {
                return collab.selectedServices[i].service_name || '';
            }
        }

        return '';
    }

    function normalizeServiceName(name) {
        if (!name) {
            return '';
        }

        var normalized = String(name).replace(/\s+/g, ' ').trim();
        var firstLine = String(name).split(/\r?\n/).map(function (line) {
            return line.trim();
        }).filter(function (line) {
            return line.length > 0;
        })[0];

        return firstLine ? firstLine : normalized;
    }

    function escapeHtml(input) {
        if (input === null || input === undefined) {
            return '';
        }
        return String(input)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function injectExtrasStyles() {
        if ($('#bkntc_collab_extras_styles').length > 0) {
            return;
        }

        var styles = '<style id="bkntc_collab_extras_styles">' +
            '.bkntc_collab_extras_section { margin-bottom: 20px; }' +
            '.bkntc_collab_extras_heading { font-weight: 600; font-size: 16px; padding: 10px 12px; margin: 0 0 10px 0; background: #f7f7f7; border-left: 4px solid #2196F3; border-radius: 4px; }' +
            '.bkntc_collab_extras_loading .booknetic_loading { padding: 20px 0; text-align: center; }' +
            '</style>';

        $('head').append(styles);
    }

    injectExtrasStyles();

})(jQuery);
