// Wait for jQuery to be available
function initServiceCollaborative() {
    if (typeof jQuery === 'undefined') {
        console.log('ServiceCollaborative: jQuery not ready yet, waiting...');
        setTimeout(initServiceCollaborative, 100);
        return;
    }

    var $ = jQuery;
    var bkntcCollabNewServiceId = null;

    var ServiceCollaborative = {
        init: function () {
            this.bindSaveEvent();
            this.hookIntoBookneticAjax();
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
                                    setTimeout(function () { ServiceCollaborative.injectServiceFields(); }, 300);
                                } else if (el.querySelector && el.querySelector('.modal')) {
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
                        if (response && response.status === 'ok') {
                            console.log('ServiceCollaborative: AJAX response:', response);
                            // Capture the service ID from response if present (for new services)
                            if (response.data && (response.data.id || response.data.service_id)) {
                                bkntcCollabNewServiceId = response.data.id || response.data.service_id;
                                console.log('ServiceCollaborative: Captured new service ID:', bkntcCollabNewServiceId);
                            }
                            // Ensure collab settings saved shortly after core save completes
                            setTimeout(function () {
                                ServiceCollaborative.saveServiceSettings();
                            }, 150);
                        }
                    }
                }
            });
        },

        bindSaveEvent: function () {
            // alert('ServiceCollaborative: bindSaveEvent called');
            var self = this;
            // Listen for successful Booknetic save via AJAX
            $(document).ajaxSuccess(function (event, xhr, settings) {
                // Try to parse response if responseJSON is not available
                // alert('AJAX completed: ' + settings.url + '\nData: ' + settings.data);
                var response = xhr.responseJSON;
                if (!response && xhr.responseText) {
                    try {
                        response = JSON.parse(xhr.responseText);
                    } catch (e) {
                        // Not JSON
                    }
                }

                // Check if response indicates a category save
                if (response && response.id && response.status === 'ok') {
                    // Check if URL contains Booknetic

                    if (settings.url && (settings.url.includes('page=' + bkntcCollabCategory.slug) || settings.url.includes('?page=' + bkntcCollabCategory.slug))) {
                        var serviceId = parseInt(response.id);
                        setTimeout(function () {
                            self.performSave(serviceId);
                        }, 300);
                        return;
                    }
                }


                // Check if this is a category save action
                if (settings.data && typeof settings.data === 'string' &&
                    settings.data.includes('module=services') &&
                    (settings.data.includes('action=save') || settings.data.includes('action=create') || settings.data.includes('action=update'))) {

                    // Check if response indicates success
                    if (response && response.status === 'ok') {
                        var serviceId = response.id ? parseInt(response.id) : self.getServiceIdFromForm();
                        if (serviceId && serviceId > 0) {
                            setTimeout(function () {
                                self.performSave(serviceId);
                            }, 300);
                            return;
                        }
                    }
                }

            });

            // Also listen for AJAX errors
            $(document).ajaxError(function (event, xhr, settings) {
                if (settings.data && typeof settings.data === 'string') {
                    if (settings.data.includes('module=services') &&
                        (settings.data.includes('action=save') ||
                            settings.data.includes('action=create') ||
                            settings.data.includes('action=update') ||
                            settings.data.includes('action=edit'))) {
                        // alert('Error saving service. Please try again.');
                    }
                }
            });
            // This function is now handled by delegated click listeners on save buttons, so it can be left empty or used for additional bindings if needed
        },

        hookIntoBookneticAjax: function () {
            var self = this;

            // Hook into jQuery AJAX complete event
            $(document).ajaxComplete(function (event, xhr, settings) {
                // Check if this is a Booknetic modal load for service_categories

                if (settings.data && typeof settings.data === 'string') {
                    if (settings.data.includes('module=services') &&
                        (settings.data.includes('action=add_new') || settings.data.includes('action=edit'))) {

                        setTimeout(function () {
                            self.injectServiceFields();
                        }, 500);
                    }
                }


            });
        },

        injectServiceFields: function () {
            console.log('ServiceCollaborative: injectServiceFields called');
            // Try common modal containers and direct form presence (Booknetic returns raw HTML)
            var modal = $('.modal:visible, .fs-modal:visible').last();

            // If no modal wrapper, try to find the Booknetic form directly (it may be injected without wrapper yet)
            var form = modal.length ? modal.find('form').first() : $();
            if (!form.length) {
                form = $('form#addServiceForm, form#addServiceForm:visible, .fs-modal form#addServiceForm').first();
            }

            // As another fallback, try to find any visible form that looks like the service form
            if (!form.length) {
                form = $('.fs-modal:visible form, .modal:visible form, form:visible').filter(function () {
                    return $(this).find('input[name="name"]').length || $(this).find('select[name="category_id"]').length;
                }).first();
            }

            if (form.length === 0) return;

            // Check if already injected inside this modal/form
            if (modal.find('#bkntc_collab_service_fields').length > 0 || form.find('#bkntc_collab_service_fields').length > 0) {
                return;
            }

            // Check if this is the service modal (look for service-related fields)
            // Booknetic's markup may use ids like #input_name and #input_category instead of name attributes
            var hasName = form.find('input[name="name"]').length > 0 || form.find('#input_name, input[id*="name"]').length > 0 || form.find('input[data-multilang][id*="name"]').length > 0;
            var hasCategory = form.find('select[name="category_id"]').length > 0 || form.find('#input_category, select[id*="category"]').length > 0;
            console.log('ServiceCollaborative: form hasName:', hasName, 'hasCategory:', hasCategory);
            if (!hasName && !hasCategory) {
                return;
            }

            // Check if this is an edit (existing service) - if so, don't inject collaborative fields
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
                    break;
                }
            }

            // if (serviceId && serviceId > 0) {
            //     console.log('ServiceCollaborative: Editing existing service (ID:', serviceId, '), skipping collaborative fields injection');
            //     return; // Only inject for new services
            // }


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
            if (injected.length) {
                console.log('ServiceCollaborative: injected element parent:', injected.parent().get(0));
            }

            // Try to load existing settings if editing - with delay to ensure form is populated
            var self = this;
            setTimeout(function () {
                var serviceId = self.getServiceIdFromForm();
                console.log('Checking for service ID to load settings:', serviceId);
                if (serviceId && serviceId > 0) {
                    console.log('Loading settings for service:', serviceId);
                    self.loadServiceSettings(serviceId);
                } else {
                    console.log('No valid service ID found, this is a new service');
                }
            }, 600);
        },

        loadServiceSettings: function () {
            // alert('loadServiceSettings called');
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
                    break;
                }
            }

            if (!serviceId || serviceId === 0) {
                console.log('ServiceCollaborative: No serviceId found, assuming new service (defaults remain) - candidates:', candidates);
                return;
            }

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

        getServiceIdFromForm: function () {
            var serviceId = 0;

            console.log('=== Detecting Service ID ===');

            // Method 1: Check URL parameters (for edit action)
            var urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('id')) {
                serviceId = parseInt(urlParams.get('id'));
            }

            // Method 2: Look for edit action data in AJAX
            if (!serviceId) {
                var modal = $('.fs-modal:visible, .modal:visible').last();
                // Check if modal has data-id attribute
                if (modal.data('id')) {
                    serviceId = parseInt(modal.data('id'));
                    console.log('Method 2 - Modal data-id:', serviceId);
                }
            }

            // Method 3: Try hidden input with name="id" from any visible form
            if (!serviceId) {
                var form = $('.fs-modal:visible form, .modal:visible form, form:visible').last();
                console.log('Found form:', form.length > 0);

                var idInput = form.find('input[name="id"], input[id="id"], input[type="hidden"]').filter(function () {
                    return $(this).attr('name') === 'id' || $(this).attr('id') === 'id';
                });

                console.log('ID input elements found:', idInput.length);
                idInput.each(function (i) {
                    console.log('  Input ' + i + ':', {
                        name: $(this).attr('name'),
                        id: $(this).attr('id'),
                        value: $(this).val()
                    });
                });

                if (idInput.length && idInput.val()) {
                    serviceId = parseInt(idInput.val());
                }
            }

            // Method 4: Check if there's an input with class or data attribute
            if (!serviceId) {
                var allInputs = $('form:visible input[type="hidden"]');
                console.log('All hidden inputs in visible forms:', allInputs.length);
                allInputs.each(function () {
                    var val = $(this).val();
                    var name = $(this).attr('name');
                    if (name === 'id' && val && !isNaN(val) && parseInt(val) > 0) {
                        serviceId = parseInt(val);
                        console.log('Method 4 - Found via scan:', serviceId);
                        return false; // break
                    }
                });
            }

            // Method 5: Try to get from modal title or header
            if (!serviceId) {
                var modalTitle = $('.fs-modal:visible .fs-modal-title, .modal:visible .modal-title').text();
                console.log('Modal title:', modalTitle);
                // If title contains "Edit" and numbers, try to extract ID
                var match = modalTitle.match(/\#(\d+)/);
                if (match) {
                    serviceId = parseInt(match[1]);
                    console.log('Method 5 - From modal title:', serviceId);
                }
            }

            // Method 6: Check the script tag's data-service-id
            if (!serviceId) {
                var scriptDataId = $("#add_new_JS").data('service-id');
                if (scriptDataId && !isNaN(scriptDataId) && parseInt(scriptDataId) > 0) {
                    serviceId = parseInt(scriptDataId);
                    console.log('Method 6 - From script data-service-id:', serviceId);
                }
            }

            console.log('=== Final service ID:', serviceId, '===');
            return serviceId;
        },
        performSave: function (serviceId) {
            var minStaff = $('#bkntc_collab_service_min_staff').val();
            var maxStaff = $('#bkntc_collab_service_max_staff').val();

            console.log('ServiceCollaborative: Saving settings for service ID:', serviceId, 'min:', minStaff, 'max:', maxStaff);
            if (!serviceId || serviceId == '0') {
                console.log('ServiceCollaborative: No service ID available, cannot save settings');
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
                    if (response.success) {
                        console.log('ServiceCollaborative: Settings saved successfully');
                        // Trigger custom event for other scripts to hook into
                        $(document).trigger('bkntcCollabServiceSaved', {
                            serviceId: serviceId,
                            minStaff: minStaff,
                            maxStaff: maxStaff,
                            response: response
                        });
                    } else {
                        console.error('ServiceCollaborative: Save failed:', response.data ? response.data.message : 'Unknown error');
                    }
                },
                error: function (xhr, status, error) {
                    console.error('ServiceCollaborative: AJAX error:', error);
                }
            });
        },

        saveServiceSettings: function () {
            var serviceId = this.getServiceIdFromForm();
            if (!serviceId || serviceId == 0) {
                console.log('Service ID is 0 (new service), settings saved on next edit');
                return;
            }

            this.performSave(serviceId);

        }
    };

    $(document).ready(function () {
        ServiceCollaborative.init();
    });

}

// Start initialization
initServiceCollaborative();
