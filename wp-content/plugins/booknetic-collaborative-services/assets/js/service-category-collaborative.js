(function () {
    'use strict';

    // Wait for jQuery to be available
    function initCollaborativeCategories() {
        if (typeof jQuery === 'undefined') {
            setTimeout(initCollaborativeCategories, 100);
            return;
        }

        var $ = jQuery;
        console.log('Booknetic Collaborative Category Script Loaded with jQuery');

        var bkntcCollab = {
            currentCategoryId: null,
            settingsLoaded: false,

            init: function () {
                console.log('Initializing collaborative category features');
                this.hookIntoBookneticAjax();
                this.bindSaveEvent();
            },

            hookIntoBookneticAjax: function () {
                var self = this;

                // Hook into jQuery AJAX complete event
                $(document).ajaxComplete(function (event, xhr, settings) {
                    // Check if this is a Booknetic modal load for service_categories

                    if (settings.data && typeof settings.data === 'string') {
                        if (settings.data.includes('module=service_categories') &&
                            (settings.data.includes('action=add_new') || settings.data.includes('action=edit'))) {

                            setTimeout(function () {
                                self.injectCollaborativeFields();
                            }, 500);
                        }
                    }


                });
            },

            bindSaveEvent: function () {
                var self = this;

                // Listen for successful Booknetic save via AJAX
                $(document).ajaxSuccess(function (event, xhr, settings) {
                    // Try to parse response if responseJSON is not available
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
                        if (settings.url && (settings.url.includes('page=booknetic') || settings.url.includes('?page=booknetic'))) {
                            var categoryId = parseInt(response.id);

                            setTimeout(function () {
                                self.performSave(categoryId);
                            }, 300);
                            return;
                        }
                    }


                    // Check if this is a category save action
                    if (settings.data && typeof settings.data === 'string' &&
                        settings.data.includes('module=service_categories') &&
                        (settings.data.includes('action=save') || settings.data.includes('action=create') || settings.data.includes('action=update'))) {

                        // Check if response indicates success
                        if (response && response.status === 'ok') {
                            var categoryId = response.id ? parseInt(response.id) : self.getCategoryIdFromForm();
                            if (categoryId && categoryId > 0) {

                                setTimeout(function () {
                                    self.performSave(categoryId);
                                }, 300);
                                return;
                            }
                        }
                    }

                });

                // Also listen for AJAX errors
                $(document).ajaxError(function (event, xhr, settings) {
                    if (settings.data && typeof settings.data === 'string') {
                        if (settings.data.includes('module=service_categories') &&
                            (settings.data.includes('action=save') ||
                                settings.data.includes('action=create') ||
                                settings.data.includes('action=update') ||
                                settings.data.includes('action=edit'))) {
                        }
                    }
                });
            },

            injectCollaborativeFields: function () {
                console.log('Attempting to inject collaborative fields...');
                $("#bkntc_collab_service_fields").hide()
                // Reset settings loaded flag
                this.settingsLoaded = false;

                // Check if fields already exist
                if ($('#bkntc_collab_fields').length > 0) {
                    console.log('Fields already exist, skipping injection');
                    return;
                }

                // Look for the specific form in service category modal
                var form = $('#addServiceForm');

                if (form.length === 0) {
                    console.log('addServiceForm not found yet');
                    return;
                }

                console.log('Found addServiceForm, injecting fields...');

                var html = '\
                    <div id="bkntc_collab_fields" class="bkntc-collab-section">\
                        <div class="bkntc-collab-header">\
                            <h5>Collaborative Service Settings</h5>\
                            <span class="bkntc-collab-badge">COLLAB</span>\
                        </div>\
                        \
                        <div class="form-row">\
                            <div class="form-group col-md-12">\
                                <div class="form-check">\
                                    <input type="checkbox" \
                                           class="form-check-input" \
                                           id="bkntc_collab_allow_multi_select" \
                                           value="1">\
                                    <label class="form-check-label" for="bkntc_collab_allow_multi_select">\
                                        <strong>Enable Multiple Service Selection</strong>\
                                    </label>\
                                </div>\
                                <small class="form-text text-muted">Allow customers to select multiple services from this category in one booking</small>\
                            </div>\
                            <div class="form-group col-md-12">\
                                <label class="form-label" for="bkntc_collab_service_selection_limit">\
                                    <strong>Service Selection Limit</strong>\
                                </label>\
                                <input type="text" \
                                       class="form-control" \
                                       id="bkntc_collab_service_selection_limit" \
                                       name="service_selection_limit" \
                                       value="1">\
                                <small class="form-text text-muted">Set the maximum number of services a customer can book within this category group.</small>\
                            </div >\
                        </div>\
                    </div>\
                ';

                form.append(html);
                console.log('Collaborative fields injected successfully');

                // Try to load existing settings if editing - with delay to ensure form is populated
                var self = this;
                setTimeout(function () {
                    var categoryId = self.getCategoryIdFromForm();
                    console.log('Checking for category ID to load settings:', categoryId);
                    if (categoryId && categoryId > 0) {
                        console.log('Loading settings for category:', categoryId);
                        self.loadCategorySettings(categoryId);
                    } else {
                        console.log('No valid category ID found, this is a new category');
                    }
                }, 600);

            },

            loadCategorySettings: function (categoryId) {
                var self = this;
                self.settingsLoaded = true; // Mark that we've attempted to load

                $.ajax({
                    url: bkntcCollabCategory.ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'bkntc_collab_get_category_settings',
                        nonce: bkntcCollabCategory.nonce,
                        category_id: categoryId
                    },
                    success: function (response) {

                        if (response.success) {
                            var data = response.data;
                            var checkbox = $('#bkntc_collab_allow_multi_select');
                            checkbox.prop('checked', data.allow_multi_select == 1);
                            $('#bkntc_collab_service_selection_limit').val(data.service_selection_limit);

                        } else {
                            console.error('Failed to load settings:', response.data ? response.data.message : 'Unknown error');
                        }
                    },
                    error: function (xhr, status, error) {
                        console.error('=== AJAX Error Loading Settings ===');

                    }
                });
            },

            saveCategorySettings: function () {
                // For new categories, we need to get the ID from the response
                var categoryId = this.getCategoryIdFromForm();

                if (!categoryId || categoryId == 0) {
                    console.log('Category ID is 0 (new category), settings saved on next edit');
                    return;
                }

                this.performSave(categoryId);
            },

            performSave: function (categoryId) {
                console.log('performSave called with categoryId:', categoryId);

                var checkbox = $('#bkntc_collab_allow_multi_select');
                var allowMultiSelect = checkbox.is(':checked') ? 1 : 0;
                var serviceSelectionLimit = parseInt($('#bkntc_collab_service_selection_limit').val()) || 1;

                $.ajax({
                    url: bkntcCollabCategory.ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'bkntc_collab_save_category_settings',
                        nonce: bkntcCollabCategory.nonce,
                        category_id: categoryId,
                        allow_multi_select: allowMultiSelect,
                        service_selection_limit: serviceSelectionLimit
                    },
                    success: function (response) {
                        console.log('=== COLLABORATIVE SETTINGS SAVE RESPONSE ===');

                        if (response.success) {
                            console.log('✓ Settings saved for category ' + categoryId);

                            if (typeof booknetic !== 'undefined' && booknetic.toast) {
                                booknetic.toast('Collaborative settings saved', 'success');
                            }
                        } else {
                            console.error('✗ Save failed:', response);
                            if (typeof booknetic !== 'undefined' && booknetic.toast) {
                                booknetic.toast('Error: ' + (response.data ? response.data.message : 'Unknown error'), 'error');
                            }
                        }
                    },
                    error: function (xhr, status, error) {
                        console.error('=== COLLABORATIVE SETTINGS AJAX ERROR ===');

                        if (typeof booknetic !== 'undefined' && booknetic.toast) {
                            booknetic.toast('AJAX Error: ' + error, 'error');
                        }
                    }
                });
            },

            getCategoryIdFromForm: function () {
                var categoryId = 0;

                console.log('=== Detecting Category ID ===');

                // Method 1: Check URL parameters (for edit action)
                var urlParams = new URLSearchParams(window.location.search);
                if (urlParams.has('id')) {
                    categoryId = parseInt(urlParams.get('id'));
                }

                // Method 2: Look for edit action data in AJAX
                if (!categoryId) {
                    var modal = $('.fs-modal:visible, .modal:visible').last();
                    // Check if modal has data-id attribute
                    if (modal.data('id')) {
                        categoryId = parseInt(modal.data('id'));
                        console.log('Method 2 - Modal data-id:', categoryId);
                    }
                }

                // Method 3: Try hidden input with name="id" from any visible form
                if (!categoryId) {
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
                        categoryId = parseInt(idInput.val());
                    }
                }

                // Method 4: Check if there's an input with class or data attribute
                if (!categoryId) {
                    var allInputs = $('form:visible input[type="hidden"]');
                    console.log('All hidden inputs in visible forms:', allInputs.length);
                    allInputs.each(function () {
                        var val = $(this).val();
                        var name = $(this).attr('name');
                        if (name === 'id' && val && !isNaN(val) && parseInt(val) > 0) {
                            categoryId = parseInt(val);
                            console.log('Method 4 - Found via scan:', categoryId);
                            return false; // break
                        }
                    });
                }

                // Method 5: Try to get from modal title or header
                if (!categoryId) {
                    var modalTitle = $('.fs-modal:visible .fs-modal-title, .modal:visible .modal-title').text();
                    console.log('Modal title:', modalTitle);
                    // If title contains "Edit" and numbers, try to extract ID
                    var match = modalTitle.match(/\#(\d+)/);
                    if (match) {
                        categoryId = parseInt(match[1]);
                        console.log('Method 5 - From modal title:', categoryId);
                    }
                }

                // Method 6: Check the script tag's data-category-id
                if (!categoryId) {
                    var scriptDataId = $("#add_new_JS").data('category-id');
                    if (scriptDataId && !isNaN(scriptDataId) && parseInt(scriptDataId) > 0) {
                        categoryId = parseInt(scriptDataId);
                        console.log('Method 6 - From script data-category-id:', categoryId);
                    }
                }

                console.log('=== Final category ID:', categoryId, '===');
                return categoryId;
            }
        };

        // Initialize when DOM is ready
        $(document).ready(function () {
            bkntcCollab.init();
        });
    }

    // Start initialization
    initCollaborativeCategories();
})();
