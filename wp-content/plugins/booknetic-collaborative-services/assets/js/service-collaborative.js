// Wait for jQuery to be available
function initServiceCollaborative() {
    if (typeof jQuery === 'undefined') {
        console.log('ServiceCollaborative: jQuery not ready yet, waiting...');
        setTimeout(initServiceCollaborative, 100);
        return;
    }

    var $ = jQuery;
    console.log('ServiceCollaborative: jQuery loaded, initializing');

    var ServiceCollaborative = {
        init: function () {
            console.log('ServiceCollaborative: Initializing');

            // Hook into service modal open
            $(document).on('DOMNodeInserted', function (e) {
                if ($(e.target).hasClass('modal') || $(e.target).find('.modal').length > 0) {
                    setTimeout(function () {
                        ServiceCollaborative.injectServiceFields();
                    }, 500);
                }
            });

            // Hook into service save - listen for multiple possible save button selectors used by Booknetic
            var saveSelectors = '#addServiceSave, .fs-modal .validate-button, .fs-modal .btn-primary, .modal .btn-success, .fs-modal .btn-success';
            $(document).on('click', saveSelectors, function (e) {
                console.log('ServiceCollaborative: save button clicked - selector matched', e.currentTarget);
                // small delay to allow Booknetic to collect form data / perform its own actions
                setTimeout(function () {
                    ServiceCollaborative.saveServiceSettings();
                }, 150);
            });

            // Fallback: watch DOM mutations for inserted modals (more reliable than DOMNodeInserted)
            try {
                var observer = new MutationObserver(function (mutations) {
                    mutations.forEach(function (mutation) {
                        Array.prototype.forEach.call(mutation.addedNodes || [], function (node) {
                            if (node && node.nodeType === 1) {
                                var el = node;
                                if (el.classList && el.classList.contains('modal')) {
                                    console.log('ServiceCollaborative: MutationObserver detected modal node');
                                    setTimeout(function () { ServiceCollaborative.injectServiceFields(); }, 300);
                                } else if (el.querySelector && el.querySelector('.modal')) {
                                    console.log('ServiceCollaborative: MutationObserver detected nested modal');
                                    setTimeout(function () { ServiceCollaborative.injectServiceFields(); }, 300);
                                }
                            }
                        });
                    });
                });
                observer.observe(document.body, { childList: true, subtree: true });
            } catch (e) {
                console.warn('ServiceCollaborative: MutationObserver not available', e);
            }

            // Fallback: try injecting after any AJAX completes (helps when Booknetic loads via AJAX)
            $(document).ajaxComplete(function () {
                setTimeout(function () { ServiceCollaborative.injectServiceFields(); }, 400);
            });

            // Listen for Booknetic service save AJAX to persist collaborative settings before redirect
            $(document).ajaxSuccess(function (event, xhr, settings) {
                try {
                    var response = xhr.responseJSON;
                    if (!response && xhr.responseText) {
                        response = JSON.parse(xhr.responseText);
                    }
                } catch (e) {
                    response = null;
                }

                if (settings && settings.data && typeof settings.data === 'string') {
                    if (settings.data.includes('module=services') && (settings.data.includes('action=save') || settings.data.includes('action=create') || settings.data.includes('action=update'))) {
                        console.log('ServiceCollaborative: Detected Booknetic service save AJAX');
                        if (response && response.status === 'ok') {
                            // Ensure collab settings saved shortly after core save completes
                            setTimeout(function () {
                                ServiceCollaborative.saveServiceSettings();
                            }, 150);
                        }
                    }
                }
            });
        },

        injectServiceFields: function () {
            console.log('ServiceCollaborative: injectServiceFields called');
            // Try common modal containers and direct form presence (Booknetic returns raw HTML)
            var modal = $('.modal:visible, .fs-modal:visible').last();
            console.log('ServiceCollaborative: modal found counts -> .modal:visible=', $('.modal:visible').length, ', .fs-modal:visible=', $('.fs-modal:visible').length);

            // If no modal wrapper, try to find the Booknetic form directly (it may be injected without wrapper yet)
            var form = modal.length ? modal.find('form').first() : $();
            if (!form.length) {
                form = $('form#addServiceForm, form#addServiceForm:visible, .fs-modal form#addServiceForm').first();
                console.log('ServiceCollaborative: direct #addServiceForm found count:', $('form#addServiceForm').length);
            }

            // As another fallback, try to find any visible form that looks like the service form
            if (!form.length) {
                form = $('.fs-modal:visible form, .modal:visible form, form:visible').filter(function () {
                    return $(this).find('input[name="name"]').length || $(this).find('select[name="category_id"]').length;
                }).first();
                console.log('ServiceCollaborative: fallback visible form search result count:', form.length);
            }

            console.log('ServiceCollaborative: final form found:', form.length, 'id:', form.attr('id'), 'name inputs:', form.find('input[name="name"]').length, 'category selects:', form.find('select[name="category_id"]').length);
            if (form.length === 0) return;

            // Check if already injected inside this modal/form
            if (modal.find('#bkntc_collab_service_fields').length > 0 || form.find('#bkntc_collab_service_fields').length > 0) {
                console.log('ServiceCollaborative: Fields already injected in this modal/form');
                return;
            }

            // Check if this is the service modal (look for service-related fields)
            // Booknetic's markup may use ids like #input_name and #input_category instead of name attributes
            var hasName = form.find('input[name="name"]').length > 0 || form.find('#input_name, input[id*="name"]').length > 0 || form.find('input[data-multilang][id*="name"]').length > 0;
            var hasCategory = form.find('select[name="category_id"]').length > 0 || form.find('#input_category, select[id*="category"]').length > 0;
            console.log('ServiceCollaborative: form hasName:', hasName, 'hasCategory:', hasCategory);
            if (!hasName && !hasCategory) {
                console.log('ServiceCollaborative: form does not appear to be service modal (no name/category fields), aborting');
                return;
            }

            console.log('ServiceCollaborative: Injecting min/max staff fields into service modal');

            var html = '\
                <div id="bkntc_collab_service_fields" class="form-row">\
                    <div class="form-group col-md-12">\
                        <label style="font-weight: 600; color: #2196F3; margin-bottom: 10px;">\
                            <span style="background: #2196F3; color: white; padding: 2px 8px; border-radius: 3px; font-size: 11px; margin-right: 5px;">COLLAB</span>\
                            Collaborative Booking Settings\
                        </label>\
                    </div>\
                    <div class="form-group col-md-6">\
                        <label for="bkntc_collab_service_min_staff">Minimum Staff Required</label>\
                        <input type="number" \
                               class="form-control" \
                               id="bkntc_collab_service_min_staff" \
                               name="collab_min_staff" \
                               min="1" \
                               value="1"\
                               placeholder="1">\
                        <small class="form-text text-muted">Minimum staff members required for this service</small>\
                    </div>\
                    <div class="form-group col-md-6">\
                        <label for="bkntc_collab_service_max_staff">Maximum Staff Allowed</label>\
                        <input type="number" \
                               class="form-control" \
                               id="bkntc_collab_service_max_staff" \
                               name="collab_max_staff" \
                               min="1" \
                               value="1"\
                               placeholder="1">\
                        <small class="form-text text-muted">Maximum staff members allowed for this service</small>\
                    </div>\
                </div>\
            ';

            // Prefer inserting into the active tab pane or details pane
            var targetPane = form.find('.tab-pane.active, #tab_details, #tab_service_details').first();
            if (targetPane.length === 0) {
                // fallback to the last form-row
                targetPane = form.find('.form-row').last();
            }

            console.log('ServiceCollaborative: targetPane selector chosen:', targetPane.length ? targetPane.get(0).className || targetPane.get(0).id : 'none');

            if (targetPane.length > 0) {
                // Try to insert before the first .form-row inside the pane if present
                var paneFirstRow = targetPane.find('.form-row').first();
                if (paneFirstRow.length > 0) {
                    paneFirstRow.before(html);
                } else {
                    targetPane.append(html);
                }
            } else {
                // Last resort
                form.append(html);
            }

            // Diagnostic: check injection result
            var injected = modal.find('#bkntc_collab_service_fields');
            if (!injected.length) injected = form.find('#bkntc_collab_service_fields');
            console.log('ServiceCollaborative: injected element found count:', injected.length);
            if (injected.length) {
                console.log('ServiceCollaborative: injected element parent:', injected.parent().get(0));
            }

            console.log('ServiceCollaborative: Fields injected successfully');

            // Load existing values if editing
            this.loadServiceSettings();
        },

        loadServiceSettings: function () {
            var modal = $('.modal:visible, .fs-modal:visible').last();

            // Try multiple ways to detect service ID in different Booknetic markup variants
            var candidates = [];
            try {
                candidates.push(modal.find('input[name="id"]').val());
            } catch (e) { }
            try {
                candidates.push(modal.find('input[name="service_id"]').val());
            } catch (e) { }
            try {
                candidates.push(modal.find('input#id').val());
            } catch (e) { }
            try {
                candidates.push(modal.find('input#service_id').val());
            } catch (e) { }
            // Fallback to script tag data attributes used in Booknetic modal HTML
            try {
                var scriptEl = $('#add_new_JS');
                if (scriptEl.length) {
                    candidates.push(scriptEl.data('service-id'));
                    candidates.push(scriptEl.data('serviceId'));
                }
            } catch (e) { }

            // Also try hidden inputs with different naming conventions
            try {
                candidates.push(modal.find('input[name="item[id]"]').val());
                candidates.push(modal.find('input[name="data[id]"]').val());
            } catch (e) { }

            // Normalize and pick the first valid numeric id
            var serviceId = 0;
            for (var i = 0; i < candidates.length; i++) {
                var v = candidates[i];
                if (typeof v !== 'undefined' && v !== null && v !== '' && !isNaN(parseInt(v))) {
                    serviceId = parseInt(v);
                    console.log('ServiceCollaborative: serviceId detected via candidate[' + i + ']:', v);
                    break;
                }
            }

            if (!serviceId || serviceId === 0) {
                console.log('ServiceCollaborative: No serviceId found, assuming new service (defaults remain) - candidates:', candidates);
                return;
            }

            console.log('ServiceCollaborative: Loading settings for service ID:', serviceId);

            var ajaxUrl = (typeof bkntcCollabCategory !== 'undefined' && bkntcCollabCategory.ajaxurl) ? bkntcCollabCategory.ajaxurl : (typeof ajaxurl !== 'undefined' ? ajaxurl : window.location.origin + '/wp-admin/admin-ajax.php');

            $.ajax({
                url: ajaxUrl,
                type: 'POST',
                data: {
                    action: 'bkntc_collab_get_service_settings',
                    service_id: serviceId
                },
                success: function (response) {
                    console.log('ServiceCollaborative: Load response:', response);
                    if (response && response.success && response.data) {
                        var min = parseInt(response.data.collab_min_staff) || 1;
                        var max = parseInt(response.data.collab_max_staff) || 1;
                        $('#bkntc_collab_service_min_staff').val(min);
                        $('#bkntc_collab_service_max_staff').val(max);
                        console.log('ServiceCollaborative: Loaded collab values min=', min, 'max=', max);
                    } else {
                        console.log('ServiceCollaborative: No data returned for service settings');
                    }
                },
                error: function (xhr, status, err) {
                    console.error('ServiceCollaborative: Error loading service settings:', status, err);
                }
            });
        },

        saveServiceSettings: function () {
            // Get the service ID from multiple possible locations
            var modal = $('.modal:visible, .fs-modal:visible').last();
            var serviceId = modal.find('input[name="id"]').val() || modal.find('input[id="id"]').val() || $("#add_new_JS").data('service-id') || $("#add_new_JS").data('serviceId') || 0;
            serviceId = serviceId || 0;
            var minStaff = $('#bkntc_collab_service_min_staff').val();
            var maxStaff = $('#bkntc_collab_service_max_staff').val();

            console.log('ServiceCollaborative: Saving settings for service ID:', serviceId, 'min:', minStaff, 'max:', maxStaff);
            alert('Saving Collaborative Service Settings:\nService ID: ' + serviceId + '\nMin Staff: ' + minStaff + '\nMax Staff: ' + maxStaff);
            if (!serviceId || serviceId == '0') {
                console.log('ServiceCollaborative: New service, settings will be saved on form submit');
                return;
            }

            var ajaxUrl = (typeof bkntcCollabCategory !== 'undefined' && bkntcCollabCategory.ajaxurl) ? bkntcCollabCategory.ajaxurl : (typeof ajaxurl !== 'undefined' ? ajaxurl : window.location.origin + '/wp-admin/admin-ajax.php');

            $.ajax({
                url: ajaxUrl,
                type: 'POST',
                data: {
                    action: 'bkntc_collab_save_service_settings',
                    service_id: serviceId,
                    collab_min_staff: minStaff,
                    collab_max_staff: maxStaff
                },
                success: function (response) {
                    console.log('ServiceCollaborative: Save response:', response);
                    if (response.success) {
                        console.log('ServiceCollaborative: Settings saved successfully');
                    } else {
                        console.error('ServiceCollaborative: Save failed:', response.data ? response.data.message : 'Unknown error');
                    }
                },
                error: function (xhr, status, error) {
                    console.error('ServiceCollaborative: AJAX error:', error);
                }
            });
        }
    };

    $(document).ready(function () {
        ServiceCollaborative.init();
    });

}

// Start initialization
initServiceCollaborative();
