
(function ($) {
    'use strict';

    console.log('════════════════════════════════════════════════════════');
    console.log('═══ Service Collaborative Script Loaded v2.1.0 ═══');
    console.log('════════════════════════════════════════════════════════');
    console.log('BookneticCollabFrontend available:', typeof BookneticCollabFrontend !== 'undefined');
    console.log('bookneticHooks available:', typeof bookneticHooks !== 'undefined');

    var collaborativeService = {
        categorySettings: {}, // map: category_id or category_name -> {settings, name}
        multiSelectCategories: {}, // map: category_id or category_name -> bool
        selectedServices: [], // Array of {service_id, assigned_to}
        isMultiSelectMode: false
    };

    // Make it globally accessible for other steps
    window.collaborativeService = collaborativeService;

    // Hook after booking panel loads
    bookneticHooks.addAction('booking_panel_loaded', function (booknetic) {
        console.log('Service Collaborative: Booking panel loaded');
    });

    // Before service step loads - call standard step loader
    bookneticHooks.addAction('before_step_loading', function (booknetic, new_step_id, old_step_id) {
        if (new_step_id !== 'service')
            return;

        console.log('Service Collaborative: Before service step loading');
        booknetic.stepManager.loadStandartSteps(new_step_id, old_step_id);
    });

    // After service step loads - check category and convert to multi-select if enabled
    bookneticHooks.addAction('loaded_step', function (booknetic, new_step_id) {
        if (new_step_id !== 'service')
            return;

        console.log('Service Collaborative: Service step loaded');

        // Reset mode for each service step load - will be set to true only if multi-select is enabled
        collaborativeService.isMultiSelectMode = false;
        collaborativeService.selectedServices = [];

        // Check if we need to enable multi-select mode
        setTimeout(function () {
            checkCategoryMultiSelect(booknetic);
        }, 200);
    });

    // Service step validation
    bookneticHooks.addFilter('step_validation_service', function (result, booknetic) {
        let booking_panel_js = booknetic.panel_js;

        // CRITICAL CHECK: Only apply collaborative validation when the selected
        // service's category actually allows multi-select. Note that
        // `collaborativeService.isMultiSelectMode` is true if ANY category on
        // the page allows multi-select. We must check the category of the
        // currently selected services instead of the global flag.
        console.log('Service Collaborative: Validating service step');
        console.log('Current selectedServices:', collaborativeService);
        if (!collaborativeService.selectedServices || collaborativeService.selectedServices.length === 0) {
            console.log('Service Collaborative: No selectedServices present, using default Booknetic validation');
            return result;
        }

        // Use the LAST selected service's category when deciding whether to
        // apply collaborative (multi-select) validation. This makes
        // `allow_multi_select` follow the most recent user selection.
        var lastIndexCheck = collaborativeService.selectedServices.length - 1;
        var categoryKeyCheck = collaborativeService.selectedServices[lastIndexCheck].category_id;
        var categorySettingsCheck = collaborativeService.categorySettings[categoryKeyCheck];
        if (!categorySettingsCheck || !categorySettingsCheck.settings || categorySettingsCheck.settings.allow_multi_select != 1) {
            console.log('Service Collaborative: Selected service category does not allow multi-select - using default Booknetic validation');
            return result; // use default validation when category doesn't allow multi-select
        }

        // If the main Booknetic-selected cart item (single-service selection)
        // exists and its category differs from the collaborative selected
        // services' category, then the user is mixing individual and
        // collaborative selections. In that case skip collaborative
        // validation and let Booknetic's default validation handle it.
        if (booknetic.cartArr && booknetic.cartArr.length > 0) {
            var currentCart = booknetic.cartArr[booknetic.cartCurrentIndex] || booknetic.cartArr[0];
            if (currentCart && currentCart.service_category && String(currentCart.service_category) !== String(categoryKeyCheck)) {
                console.log('Service Collaborative: Mixed categories detected (cart item category differs). Skipping collaborative validation.');
                return result;
            }
        }

        console.log('Service Collaborative: Multi-select mode validation (allow_multi_select = 1)');

        // Use the selectedServices that were populated when checkboxes were checked
        var selectedServices = JSON.parse(JSON.stringify(collaborativeService.selectedServices)) || [];

        console.log('✓ Using selectedServices from collaborative service state:', selectedServices);
        console.log('Found services count:', selectedServices.length);

        // Validate that services were selected
        if (selectedServices.length === 0) {
            console.log('⚠️ selectedServices is empty!');
            return {
                status: false,
                errorMsg: booknetic.__('select_service') || 'Please select at least one service.'
            };
        }

        console.log('Selected services after validation prep:', selectedServices);

        // Use the LAST selected service's category for the remaining checks
        var lastIndex = selectedServices.length - 1;
        var categoryKey = selectedServices[lastIndex].category_id;
        var categorySettings = collaborativeService.categorySettings[categoryKey];
        console.log('Category settings for selected services:', categorySettings);
        if (!categorySettings || !categorySettings.settings) {
            return {
                status: false,
                errorMsg: 'Category settings not found. Please refresh the page.'
            };
        }

        // If selected services span multiple categories, use the last
        // selected service's category to decide whether collaborative
        // validation applies; otherwise fall back to default validation.
        if (selectedServices.length > 0) {
            for (var i = 0; i < selectedServices.length; i++) {
                if (String(selectedServices[i].category_id) !== String(categoryKey)) {
                    console.log('Service Collaborative: Detected selected services from multiple categories; skipping collaborative validation.');
                    return result; // mixed categories — defer to Booknetic default
                }
            }
        }

        var limit = categorySettings.settings.service_selection_limit;

        // Check if assignment is set for all services
        for (var i = 0; i < selectedServices.length; i++) {
            if (!selectedServices[i].assigned_to) {
                return {
                    status: false,
                    errorMsg: 'Please assign each service to "Me" or "Guest".'
                };
            }
        }

        // Count unique services and assignments
        var uniqueServices = {};
        var meCount = 0;
        var guestCount = 0;

        for (var i = 0; i < selectedServices.length; i++) {
            var serviceId = selectedServices[i].service_id;
            if (!uniqueServices[serviceId]) {
                uniqueServices[serviceId] = 0;
            }
            uniqueServices[serviceId]++;

            if (selectedServices[i].assigned_to === 'me') {
                meCount++;
            } else if (selectedServices[i].assigned_to === 'guest') {
                guestCount++;
            }
        }

        var uniqueServiceCount = Object.keys(uniqueServices).length;
        var totalEntries = selectedServices.length; // Total number of assignments (entries)
        console.log('Unique services: ' + uniqueServiceCount + ', Total entries: ' + totalEntries + ', Me entries: ' + meCount + ', Guest entries: ' + guestCount);

        // Check service selection limit (based on total entries, not unique services)
        // This allows 1 service selected for both Me and Guest to count as 2 entries
        if (limit > 0) {
            if (totalEntries !== limit) {
                return {
                    status: false,
                    errorMsg: 'Please select exactly ' + limit + ' booking(s). (Tip: Selecting 1 service for both "Me" and "Guest" counts as 2 bookings.)'
                };
            }

            // Validation based on limit
            if (limit === 2) {
                // Couple Services: Exactly 1 "Me" and 1 "Guest"
                if (meCount !== 1 || guestCount !== 1) {
                    return {
                        status: false,
                        errorMsg: 'For a couple booking, you need exactly 1 assignment for "Me" and 1 for "Guest".'
                    };
                }
            } else if (limit === 5) {
                // Group Services: At least 1 "Me"
                if (meCount < 1) {
                    return {
                        status: false,
                        errorMsg: 'Please assign at least one booking to "Me".'
                    };
                }
            } else {
                // Default: At least 1 "Me"
                if (meCount < 1) {
                    return {
                        status: false,
                        errorMsg: 'Please assign at least one booking to "Me".'
                    };
                }
            }
        }

        // Per-person validation: Check min/max services per person
        var minPerPerson = categorySettings.settings.min_services_per_person || 0;
        var maxPerPerson = categorySettings.settings.max_services_per_person || Infinity;

        if (minPerPerson > 0 || maxPerPerson < Infinity) {
            if (meCount < minPerPerson || meCount > maxPerPerson) {
                return {
                    status: false,
                    errorMsg: 'Each person must have between ' + minPerPerson + ' and ' + (maxPerPerson === Infinity ? 'unlimited' : maxPerPerson) + ' services. Currently "Me" has ' + meCount + ' booking(s).'
                };
            }
            if (guestCount < minPerPerson || guestCount > maxPerPerson) {
                return {
                    status: false,
                    errorMsg: 'Each person must have between ' + minPerPerson + ' and ' + (maxPerPerson === Infinity ? 'unlimited' : maxPerPerson) + ' services. Currently "Guest" has ' + guestCount + ' booking(s).'
                };
            }
        }

        // Store selected services for cart
        collaborativeService.selectedServices = selectedServices;

        // Also store in panel data for access by other steps
        booking_panel_js.data('collaborative-selected-services', selectedServices);

        console.log('Service validation passed:', selectedServices);
        console.log('Stored in window.collaborativeService and panel data');

        return {
            status: true,
            errorMsg: ''
        };
    });
    bookneticHooks.addFilter('bkntc_cart', function (cartItem, booknetic) {
        console.log('=== bkntc_cart FILTER: Cart item before modification ===');
        console.log('isMultiSelectMode:', collaborativeService.isMultiSelectMode);
        console.log('selectedServices:', collaborativeService.selectedServices);
        console.log('cartItem:', cartItem);

        // If collaborative selections exist, always attach them to the cart
        // item. This guarantees every selected service will be carried through
        // to the server side for expansion (e.g. service 50 won't be lost).
        if (collaborativeService.selectedServices && collaborativeService.selectedServices.length > 0) {
            cartItem.selected_services = JSON.parse(JSON.stringify(collaborativeService.selectedServices));

            // CRITICAL: Set the first service as the main service for this cart item
            cartItem.service = collaborativeService.selectedServices[0].service_id;
            cartItem.assigned_to = collaborativeService.selectedServices[0].assigned_to;

            console.log('✓ Cart item updated with selectedServices');
            console.log('✓ Set main service to:', cartItem.service);
            console.log('✓ Selected services array length:', cartItem.selected_services.length);
            console.log('✓ Modified cartItem:', cartItem);
        } else {
            console.log('⚠️ No collaborative selected services found - leaving cartItem unchanged');
        }

        return cartItem;
    });


    // Hook after information step to expand cart into individual service items
    bookneticHooks.addAction('before_next_step_information', function (booknetic) {
        console.log('=== BEFORE LEAVING INFORMATION STEP ===');
        console.log('Checking if cart needs expansion...');
        expandCartForMultiService(booknetic);
    });

    // Also trigger before loading cart step or confirm_details step
    bookneticHooks.addAction('before_step_loading', function (booknetic, new_step_id, old_step_id) {
        if (new_step_id === 'cart' || new_step_id === 'confirm_details') {
            console.log('=== BEFORE LOADING ' + new_step_id.toUpperCase() + ' ===');
            console.log('Checking if cart needs expansion...');

            // Only expand if we have date and time
            if (booknetic.cartArr && booknetic.cartArr.length > 0) {
                var hasDateTime = booknetic.cartArr[0].date && booknetic.cartArr[0].time;
                if (hasDateTime) {
                    console.log('Date/time exists, proceeding with expansion');
                    expandCartForMultiService(booknetic);
                } else {
                    console.log('⏸️ Skipping expansion - no date/time in cart yet');
                }
            }
        }
    });

    // CRITICAL: After information step, update customer data in already-expanded cart items
    bookneticHooks.addAction('step_end_information', function (booknetic) {
        console.log('=== INFORMATION STEP COMPLETED ===');

        if (!booknetic.cartArr || booknetic.cartArr.length === 0) {
            return;
        }

        // Check if cart is already expanded
        var isExpanded = booknetic.cartArr.length > 1 && booknetic.cartArr[0].is_collaborative_booking;

        if (isExpanded) {
            console.log('🔄 Cart already expanded, updating customer data in all items...');

            // Get the main customer data from the first item (or current item)
            var mainCustomerData = booknetic.cartArr[0].customer_data;
            console.log('Main customer data:', mainCustomerData);

            // Get guest data
            var guestData = collaborativeService.guestInformation || {};
            console.log('Guest data:', guestData);

            // Update each cart item with proper customer data
            booknetic.cartArr.forEach(function (item, index) {
                console.log('Updating cart item ' + index + ' for service ' + item.service + ' (assigned to: ' + item.assigned_to + ')');

                if (item.assigned_to === 'guest' && guestData[item.service]) {
                    // This service is for a guest - use guest data
                    var guestInfo = guestData[item.service];
                    console.log('✓ Using guest data for service ' + item.service);

                    item.customer_data = {
                        email: guestInfo.email || '',
                        first_name: guestInfo.name ? guestInfo.name.split(' ')[0] : '',
                        last_name: guestInfo.name ? guestInfo.name.split(' ').slice(1).join(' ') : '',
                        phone: guestInfo.phone || ''
                    };

                    item.email = guestInfo.email || '';
                    item.first_name = item.customer_data.first_name;
                    item.last_name = item.customer_data.last_name;
                    item.name = guestInfo.name || '';
                    item.phone = guestInfo.phone || '';

                    console.log('✓ Updated guest customer_data:', item.customer_data);
                } else {
                    // This service is for main customer - use main customer data
                    console.log('✓ Using main customer data for service ' + item.service);

                    if (mainCustomerData && mainCustomerData.email) {
                        item.customer_data = JSON.parse(JSON.stringify(mainCustomerData));
                        item.email = mainCustomerData.email;
                        item.first_name = mainCustomerData.first_name;
                        item.last_name = mainCustomerData.last_name;
                        item.name = (mainCustomerData.first_name + ' ' + mainCustomerData.last_name).trim();
                        item.phone = mainCustomerData.phone;

                        console.log('✓ Updated main customer_data:', item.customer_data);
                    } else {
                        console.warn('⚠️ Main customer data missing!');
                    }
                }
            });

            console.log('✅ All cart items updated with customer data');
        } else {
            console.log('Cart not yet expanded, will expand later');
        }
    });

    // Function to expand cart into individual service items
    function expandCartForMultiService(booknetic) {
        // Determine the list of services to expand. Prefer the in-memory
        // `collaborativeService.selectedServices`; if that's empty (for
        // example after a page reload), fall back to the cart item's
        // `selected_services` payload.
        var servicesToExpand = [];

        if (collaborativeService.selectedServices && collaborativeService.selectedServices.length > 0) {
            servicesToExpand = collaborativeService.selectedServices;
        }

        // If nothing in memory, inspect the current cart item for an attached
        // `selected_services` array (this is the payload set earlier by the
        // bkntc_cart filter).
        var currentCartItemCheck = (booknetic.cartArr && booknetic.cartArr[booknetic.cartCurrentIndex]) ? booknetic.cartArr[booknetic.cartCurrentIndex] : null;
        if ((!servicesToExpand || servicesToExpand.length === 0) && currentCartItemCheck && currentCartItemCheck.selected_services && currentCartItemCheck.selected_services.length > 1) {
            servicesToExpand = currentCartItemCheck.selected_services;
        }

        if (!servicesToExpand || servicesToExpand.length <= 1) {
            return; // Single service, no need to expand
        }

        // Check if already expanded
        if (booknetic.cartArr.length > 1 && booknetic.cartArr[0].is_collaborative_booking) {
            console.log('Cart already expanded, skipping');
            return;
        }

        var currentCartItem = booknetic.cartArr[booknetic.cartCurrentIndex];
        if (!currentCartItem) {
            console.log('No current cart item found');
            return;
        }

        // Check if this cart item has already been expanded
        if (currentCartItem.is_collaborative_booking && !currentCartItem.selected_services) {
            console.log('Cart item already expanded');
            return;
        }

        console.log('=== EXPANDING CART: Creating individual items for each service ===');
        console.log('Current cart index:', booknetic.cartCurrentIndex);
        console.log('Current cart item:', currentCartItem);
        console.log('🔍 Customer data in cart item:', currentCartItem.customer_data);
        console.log('🔍 All keys in cart item:', Object.keys(currentCartItem));
        console.log('🔍 Customer ID:', currentCartItem.customer_id);
        console.log('🔍 Customer email field:', currentCartItem.email);
        console.log('🔍 Customer name field:', currentCartItem.name);
        console.log('🔍 Customer phone field:', currentCartItem.phone);

        // Get guest data if available
        var guestData = {};
        if (currentCartItem.guest_data) {
            guestData = currentCartItem.guest_data;
        } else if (window.BookneticCollaborativeInformation && typeof window.BookneticCollaborativeInformation.getGuestData === 'function') {
            guestData = window.BookneticCollaborativeInformation.getGuestData();
        }

        // Store the original cart item temporarily
        var originalItem = JSON.parse(JSON.stringify(currentCartItem));

        // Generate a unique group ID for this booking session
        var groupId = 'collab_' + Date.now();

        // Clear the current position
        booknetic.cartArr.splice(booknetic.cartCurrentIndex, 1);

        // Create individual cart items for each service
        servicesToExpand.forEach(function (service, index) {
            console.log('🔄 Processing service ' + (index + 1) + '/' + servicesToExpand.length + ': Service ID ' + service.service_id);

            var newItem = JSON.parse(JSON.stringify(originalItem));
            console.log('📋 New item created for service ' + service.service_id + ', has customer_data:', !!newItem.customer_data);
            if (newItem.customer_data) {
                console.log('📋 customer_data content:', newItem.customer_data);
            }

            // Set service-specific data
            newItem.service = service.service_id;
            newItem.assigned_to = service.assigned_to;
            newItem.is_collaborative_booking = true;
            newItem.collaborative_group_id = groupId;
            newItem.collaborative_service_index = index + 1;
            newItem.collaborative_total_services = servicesToExpand.length;

            // Preserve extras selected on the extras step (if provided)
            if (service.service_extras && service.service_extras.length) {
                newItem.service_extras = JSON.parse(JSON.stringify(service.service_extras));
            }

            // CRITICAL: Clear any cached service-specific data that might interfere with backend processing
            // This forces the backend to fetch fresh data for each service
            delete newItem.serviceInf;
            delete newItem.service_price;
            delete newItem.service_duration;
            delete newItem.service_name;

            // Preserve a human-friendly service name for UI headings
            if (service.service_name) {
                newItem.collaborative_service_name = service.service_name;
            }

            // CRITICAL: Ensure date and time are preserved for all items
            // All services share the same date/time in collaborative booking
            console.log('Preserving date/time for service ' + service.service_id + ':', {
                date: newItem.date,
                time: newItem.time,
                location: newItem.location,
                staff: newItem.staff,
                service_category: newItem.service_category
            });

            // CRITICAL FIX: Ensure all required fields are present and not undefined
            // This prevents the backend from showing "-" for date/time and "0.00" for price
            if (!newItem.date || !newItem.time) {
                console.error('⚠️ WARNING: Date or time is missing for service ' + service.service_id);
                console.log('Attempting to recover from original item...');
                newItem.date = originalItem.date;
                newItem.time = originalItem.time;
            }

            // Ensure staff ID is set (can be -1 for "any staff")
            if (newItem.staff === undefined || newItem.staff === null) {
                newItem.staff = originalItem.staff || -1;
            }

            // Ensure location is set
            if (!newItem.location && originalItem.location) {
                newItem.location = originalItem.location;
            }

            // Ensure service_category is set (use the original or clear it if not matching)
            if (!newItem.service_category && originalItem.service_category) {
                newItem.service_category = originalItem.service_category;
            }

            console.log('✓ Verified all required fields for service ' + service.service_id);
            console.log('✓ Cleared cached service data to force fresh backend lookup');

            // CRITICAL: Ensure customer data is copied to all cart items
            // The information step fills customer_data in the original item, and it needs to be in ALL items
            // Since newItem is already a deep copy of originalItem, it should have customer_data
            // But let's verify and copy explicitly to be safe

            console.log('Before customer data copy - Service ' + service.service_id + ':', {
                has_customer_data: !!newItem.customer_data,
                customer_data: newItem.customer_data,
                email: newItem.email,
                name: newItem.name
            });

            // Ensure customer_data exists in newItem (it should from the deep copy)
            console.log('🔍 Checking customer data for service ' + service.service_id + '...');
            console.log('🔍 originalItem has customer_data:', !!originalItem.customer_data);
            console.log('🔍 newItem has customer_data:', !!newItem.customer_data);

            if (originalItem.customer_data) {
                // Force re-copy to ensure it's there
                newItem.customer_data = JSON.parse(JSON.stringify(originalItem.customer_data));
                console.log('✓ Copied customer_data to service ' + service.service_id, newItem.customer_data);

                // Also copy customer data fields to root level for backend compatibility
                if (originalItem.customer_data.email) {
                    newItem.email = originalItem.customer_data.email;
                    console.log('✓ Copied email from customer_data to service ' + service.service_id + ':', newItem.email);
                } else {
                    console.log('⚠️ No email in customer_data for service ' + service.service_id);
                }

                // Handle first_name and last_name (Booknetic uses these instead of just "name")
                if (originalItem.customer_data.first_name || originalItem.customer_data.last_name) {
                    newItem.first_name = originalItem.customer_data.first_name;
                    newItem.last_name = originalItem.customer_data.last_name;
                    newItem.name = (originalItem.customer_data.first_name + ' ' + originalItem.customer_data.last_name).trim();
                    console.log('✓ Copied name from customer_data to service ' + service.service_id + ':', newItem.name);
                }

                if (originalItem.customer_data.phone) {
                    newItem.phone = originalItem.customer_data.phone;
                    console.log('✓ Copied phone from customer_data to service ' + service.service_id);
                } else {
                    console.log('⚠️ No phone in customer_data for service ' + service.service_id);
                }
            } else {
                console.log('⚠️ No customer_data object found in original item for service ' + service.service_id);
            }

            // Copy individual customer fields if they exist at root level (fallback for different Booknetic versions)
            if (originalItem.email && !newItem.email) {
                newItem.email = originalItem.email;
                console.log('✓ Copied email from root to service ' + service.service_id + ':', newItem.email);
            }
            if (originalItem.name && !newItem.name) {
                newItem.name = originalItem.name;
                console.log('✓ Copied name from root to service ' + service.service_id);
            }
            if (originalItem.phone && !newItem.phone) {
                newItem.phone = originalItem.phone;
                console.log('✓ Copied phone from root to service ' + service.service_id);
            }
            if (originalItem.customer_id) {
                newItem.customer_id = originalItem.customer_id;
                console.log('✓ Copied customer_id to service ' + service.service_id);
            }

            // If service is assigned to guest, use guest information for customer_data
            if (service.assigned_to === 'guest') {
                console.log('🎭 Service ' + service.service_id + ' assigned to guest');

                if (guestData[service.service_id]) {
                    // Guest data exists, use it for this service's customer_data
                    var guestInfo = guestData[service.service_id];
                    console.log('✓ Found guest data for service ' + service.service_id + ':', guestInfo);

                    // Overwrite customer_data with guest information
                    newItem.customer_data = {
                        email: guestInfo.email || '',
                        first_name: guestInfo.name ? guestInfo.name.split(' ')[0] : '',
                        last_name: guestInfo.name ? guestInfo.name.split(' ').slice(1).join(' ') : '',
                        phone: guestInfo.phone || ''
                    };

                    // Also set at root level
                    newItem.email = guestInfo.email || '';
                    newItem.first_name = newItem.customer_data.first_name;
                    newItem.last_name = newItem.customer_data.last_name;
                    newItem.name = guestInfo.name || '';
                    newItem.phone = guestInfo.phone || '';

                    console.log('✓ Applied guest customer_data for service ' + service.service_id + ':', newItem.customer_data);

                    // Also keep guest_info for reference
                    newItem.guest_info = guestInfo;
                    newItem.guest_info.service_id = service.service_id;
                } else {
                    // No guest data, use main customer data as fallback
                    console.log('⚠️ No guest data found for service ' + service.service_id + ', using main customer data as fallback');
                }
            } else {
                console.log('👤 Service ' + service.service_id + ' assigned to main customer (Me), using main customer data');
            }

            // Keep selected_services for reference but mark as expanded
            newItem.selected_services = servicesToExpand;
            newItem._cart_expanded = true;

            // Insert at the position
            booknetic.cartArr.splice(booknetic.cartCurrentIndex + index, 0, newItem);

            console.log('Created cart item #' + (index + 1) + ' for service ' + service.service_id + ' (assigned to: ' + service.assigned_to + ')');
            console.log('Cart item data:', newItem);
        });

        console.log('✓ Cart expanded from 1 to ' + collaborativeService.selectedServices.length + ' items');
        console.log('✓ Group ID: ' + groupId);
        console.log('Updated cartArr length:', booknetic.cartArr.length);
        console.log('Updated cartArr:', booknetic.cartArr);

        // IMPORTANT: Don't change cartCurrentIndex - it should stay at the original position
        // This ensures the cart data is properly saved across all items
        console.log('Cart current index remains:', booknetic.cartCurrentIndex);

        // Update cart counter if available
        if (typeof booknetic.updateCartCounter === 'function') {
            booknetic.updateCartCounter();
        }

        // Force cart refresh - clear ALL cart HTML storage to force reload
        var cartContainer = booknetic.panel_js.find('[data-step-id="cart"]');
        if (cartContainer.length > 0) {
            cartContainer.empty(); // Force reload on next visit
        }

        // Also clear the HTML storage for cart to force proper reload
        booknetic.cartHTMLBody = [];
        booknetic.cartHTMLSideBar = [];
        console.log('✓ Cleared cart HTML cache to force reload');
    }

    // Debug: Log cart before confirmation
    bookneticHooks.addAction('before_step_loading', function (booknetic, new_step_id, old_step_id) {
        if (new_step_id === 'confirm_details') {
            console.log('=== CART DEBUG: Before Confirm Details ===');
            console.log('Total cart items:', booknetic.cartArr.length);
            console.log('Current index:', booknetic.cartCurrentIndex);
            console.log('Cart array:', booknetic.cartArr);
        }
    });

    // Hook to intercept ajaxParameters and ensure cart data is sent correctly
    bookneticHooks.addFilter('appointment_ajax_data', function (data, booknetic) {
        console.log('=== APPOINTMENT_AJAX_DATA FILTER CALLED ===');
        console.log('Cart array at filter time:', booknetic.cartArr);
        console.log('Cart length:', booknetic.cartArr.length);
        console.log('Current index:', booknetic.cartCurrentIndex);

        // Get the current step to avoid expanding during date/time selection
        var currentStep = booknetic.panel_js.find('.booknetic_appointment_step_element.booknetic_active_step').data('step-id');
        console.log('Current active step:', currentStep);

        // DON'T expand during date_time step - user hasn't selected date/time yet!
        var isDateTimeStep = currentStep === 'date_time' ||
            currentStep === 'date_time_recurring' ||
            currentStep === 'date_time_non_recurring';

        if (isDateTimeStep) {
            console.log('Currently on date/time step, skipping cart expansion (date/time not selected yet)');
            return data;
        }

        // CRITICAL: Re-expand cart if it was somehow collapsed
        // This handles the case where cart might have been cleared between confirm_details load and actual confirmation
        // Re-expand only when selected services exist and the category allows multi-select
        var shouldReExpand = false;
        if (collaborativeService.selectedServices && collaborativeService.selectedServices.length > 1) {
            var reCat = collaborativeService.selectedServices[0].category_id;
            var reCatSettings = collaborativeService.categorySettings[reCat];
            shouldReExpand = reCatSettings && reCatSettings.settings && reCatSettings.settings.allow_multi_select == 1;
        }

        if (shouldReExpand) {

            // Check if cart needs expansion
            if (!booknetic.cartArr || booknetic.cartArr.length === 0) {
                console.log('🚨 CRITICAL: Cart is empty but we have selected services! Re-expanding now...');
                expandCartForMultiService(booknetic);
            } else if (booknetic.cartArr.length === 1 && booknetic.cartArr[0].selected_services) {
                // Before re-expanding, check if date/time exists
                if (!booknetic.cartArr[0].date || !booknetic.cartArr[0].time) {
                    console.log('⏸️ Skipping expansion - date/time not yet selected');
                    return data;
                }
                console.log('🚨 Cart has collapsed back to 1 item! Re-expanding now...');
                expandCartForMultiService(booknetic);
            } else if (booknetic.cartArr.length > 1 && !booknetic.cartArr[0].is_collaborative_booking) {
                console.log('🚨 Cart has multiple items but not marked as collaborative! Re-expanding now...');
                expandCartForMultiService(booknetic);
            }
        }

        // Check if this is a collaborative booking with multiple cart items
        if (booknetic.cartArr && booknetic.cartArr.length > 0) {
            var hasCollaborative = booknetic.cartArr.some(function (item) {
                return item && item.is_collaborative_booking;
            });

            if (hasCollaborative) {
                console.log('=== INTERCEPTING AJAX DATA FOR COLLABORATIVE BOOKING ===');
                console.log('Cart items count:', booknetic.cartArr.length);
                console.log('Current index:', booknetic.cartCurrentIndex);
                console.log('Full cart array:', booknetic.cartArr);

                // Re-serialize the cart to ensure all items are sent
                var cartData = JSON.stringify(booknetic.cartArr);
                console.log('Cart JSON being sent:', cartData);
                console.log('Cart JSON length:', cartData.length);

                // Update the cart data in FormData
                data.set('cart', cartData);
                data.set('current', booknetic.cartCurrentIndex);

                console.log('✓ Cart data updated in FormData');
            } else {
                console.log('No collaborative booking items found in cart');
            }
        } else {
            console.log('WARNING: Cart array is empty or undefined!');
            console.log('cartArr:', booknetic.cartArr);

            // Check if we have stored data
            if (collaborativeService.selectedServices && collaborativeService.selectedServices.length > 0) {
                console.log('WARNING: Cart is empty but we have selected services:', collaborativeService.selectedServices);
                console.log('This suggests the cart was cleared or expansion didn\'t happen');
            }
        }

        return data;
    });

    // Fetch category settings to check if multi-select is enabled (supports multiple categories on the page)
    function checkCategoryMultiSelect(booknetic) {
        let booking_panel_js = booknetic.panel_js;

        console.log('=== SERVICE COLLABORATIVE: CHECK MULTI-SELECT ===');
        console.log('BookneticCollabFrontend available:', typeof BookneticCollabFrontend !== 'undefined');

        // Gather categories by scanning the category header elements
        var categories = [];
        booking_panel_js.find('.booknetic_service_category').each(function () {
            var elem = $(this);
            // try attributes: category-id or data-parent (some themes use data-parent)
            var cid = elem.data('category-id') || elem.attr('data-category-id') || elem.data('parent') || elem.attr('data-parent');
            var name = elem.clone().children().remove().end().text().trim();
            if (!name) name = elem.text().trim();
            categories.push({ id: cid || null, name: name, elem: elem });
        });

        // Fallback: try previous single-detection methods
        if (categories.length === 0) {
            var single = getCurrentCategoryId(booking_panel_js);
            if (single) categories.push({ id: single, name: null, elem: null });
        }

        if (categories.length === 0) {
            console.log('Service Collaborative: No category header(s) found, skipping multi-select check');
            return;
        }

        console.log('Service Collaborative: Found categories on page:', categories.map(function (c) { return { id: c.id, name: c.name }; }));

        // Reset global flag; will set to true if any category allows multi-select
        collaborativeService.isMultiSelectMode = false;

        // Local helpers
        var doAjaxWithId = function (cid, categoryName) {
            console.log('Service Collaborative: Checking category settings for ID:', cid, ' (name:', categoryName, ')');
            $.ajax({
                url: BookneticCollabFrontend.ajaxurl,
                type: 'POST',
                data: {
                    action: 'bkntc_collab_get_category_settings_frontend',
                    nonce: BookneticCollabFrontend.nonce,
                    category_id: cid,
                    category_name: categoryName
                },
                success: function (response) {
                    console.log('=== CATEGORY SETTINGS RESPONSE for', cid, '===');
                    console.log('Full response:', response);
                    if (response.success && response.data) {
                        collaborativeService.categorySettings = collaborativeService.categorySettings || {};
                        collaborativeService.categorySettings[cid] = { settings: response.data, name: categoryName };
                        if (categoryName) collaborativeService.categorySettings[categoryName] = collaborativeService.categorySettings[cid];
                        var allow = response.data.allow_multi_select == 1;
                        collaborativeService.multiSelectCategories = collaborativeService.multiSelectCategories || {};
                        collaborativeService.multiSelectCategories[cid] = allow;
                        if (categoryName) collaborativeService.multiSelectCategories[categoryName] = allow;

                        console.log('allow_multi_select for', cid, ':', response.data.allow_multi_select);
                        if (allow) {
                            collaborativeService.isMultiSelectMode = true;
                            convertServiceToMultiSelect(booknetic, cid);
                        }
                    } else {
                        console.error('Invalid response structure for category', cid, response);
                    }
                },
                error: function (xhr, status, error) {
                    console.error('=== AJAX ERROR for category', cid, '===');
                    console.error('Status:', status);
                    console.error('Error:', error);
                    console.error('Response text:', xhr.responseText);
                }
            });
        };

        var doAjaxWithName = function (name) {
            console.log('Service Collaborative: Checking category settings for NAME:', name);
            $.ajax({
                url: BookneticCollabFrontend.ajaxurl,
                type: 'POST',
                data: {
                    action: 'bkntc_collab_get_category_settings_frontend',
                    nonce: BookneticCollabFrontend.nonce,
                    category_name: name
                },
                success: function (response) {
                    console.log('=== CATEGORY SETTINGS RESPONSE for name:', name, '===');
                    console.log('Full response:', response);
                    if (response.success && response.data) {
                        collaborativeService.categorySettings = collaborativeService.categorySettings || {};
                        var returnedId = response.data.category_id || null;
                        if (returnedId) {
                            collaborativeService.categorySettings[returnedId] = { settings: response.data, name: name };
                            collaborativeService.categorySettings[name] = collaborativeService.categorySettings[returnedId];
                        } else {
                            collaborativeService.categorySettings[name] = { settings: response.data, name: name };
                        }
                        var allow = response.data.allow_multi_select == 1;
                        collaborativeService.multiSelectCategories = collaborativeService.multiSelectCategories || {};
                        collaborativeService.multiSelectCategories[name] = allow;
                        if (returnedId) {
                            collaborativeService.multiSelectCategories[returnedId] = allow;
                        }
                        console.log('allow_multi_select for name', name, ':', response.data.allow_multi_select);
                        if (allow) {
                            collaborativeService.isMultiSelectMode = true;
                            convertServiceToMultiSelect(booknetic, name);
                        }
                    } else {
                        console.error('Invalid response structure for category name', name, response);
                    }
                },
                error: function (xhr, status, error) {
                    console.error('=== AJAX ERROR for category name', name, '===');
                    console.error('Status:', status);
                    console.error('Error:', error);
                    console.error('Response text:', xhr.responseText);
                }
            });
        };

        // Process each detected category
        categories.forEach(function (cat) {
            console.log('Processing category:', cat);
            if (cat.name) {
                // Always use name-based lookup since IDs may not be set on headers yet
                doAjaxWithName(cat.name);
            } else if (cat.id) {
                // Fallback: if no name but have ID, use ID
                doAjaxWithId(cat.id, cat.name);
            }
        });
    }

    // Get current category ID from service step
    function getCurrentCategoryId(panel) {
        console.log('>>> Getting category ID...');

        // Method 1: Check booknetic cartArr or data
        if (typeof window.BookneticData !== 'undefined' && window.BookneticData.category_id) {
            console.log('>>> Method 1: Found in BookneticData.category_id:', window.BookneticData.category_id);
            return window.BookneticData.category_id;
        }

        // Method 2: Check if category ID is in the step container
        var stepContainer = panel.find('[data-step-id="service"]');
        if (stepContainer.length > 0 && stepContainer.data('category-id')) {
            console.log('>>> Method 2: Found in step container:', stepContainer.data('category-id'));
            return stepContainer.data('category-id');
        }

        // Method 3: Try from category title element
        var categoryTitle = panel.find('.booknetic_category_title, [data-category-id]').first();
        if (categoryTitle.length > 0 && categoryTitle.data('category-id')) {
            console.log('>>> Method 3: Found in category title:', categoryTitle.data('category-id'));
            return categoryTitle.data('category-id');
        }

        // Method 4: Get from first service card's data-category attribute
        var firstServiceCard = panel.find('.booknetic_service_card').first();
        console.log('>>> First service card found:', firstServiceCard.length > 0);

        if (firstServiceCard.length > 0) {
            var categoryFromCard = firstServiceCard.data('category') || firstServiceCard.attr('data-category');
            if (categoryFromCard) {
                console.log('>>> Method 4a: Found in service card data-category:', categoryFromCard);
                return categoryFromCard;
            }

            // Try to get service ID and fetch category via AJAX
            var firstServiceId = firstServiceCard.data('id');
            console.log('>>> First service ID:', firstServiceId);

            if (firstServiceId) {
                console.log('>>> Method 4b: Trying AJAX to get category from service ID...');
                // Make synchronous AJAX call to get category from service
                var categoryId = null;
                $.ajax({
                    url: BookneticCollabFrontend.ajaxurl,
                    type: 'POST',
                    async: false,
                    data: {
                        action: 'bkntc_collab_get_service_category',
                        nonce: BookneticCollabFrontend.nonce,
                        service_id: firstServiceId
                    },
                    success: function (response) {
                        console.log('>>> AJAX response for service category:', response);
                        if (response.success && response.data && response.data.category_id) {
                            categoryId = response.data.category_id;
                            console.log('>>> Found category from AJAX:', categoryId);
                        }
                    },
                    error: function (xhr, status, error) {
                        console.error('>>> AJAX error getting service category:', error);
                    }
                });
                if (categoryId) {
                    return categoryId;
                }
            }
        }

        console.warn('>>> No category ID found by any method!');
        return null;
    }

    // Convert service cards to multi-select with checkboxes
    // `categoryKey` can be a category id (number/string) or a category name
    function convertServiceToMultiSelect(booknetic, categoryKey) {
        let panel = booknetic.panel_js;

        console.log('Service Collaborative: Converting to multi-select mode for category:', categoryKey);

        // Ensure all category headers have data-category-id set from settings
        var categoryHeaders = panel.find('.booknetic_service_category');
        categoryHeaders.each(function () {
            var header = $(this);
            var headerText = header.clone().children().remove().end().text().trim() || header.text().trim();
            var currentId = header.data('category-id') || header.attr('data-category-id');

            // If this header doesn't have an ID set, try to find it from settings
            if (!currentId) {
                // Check if we have settings for this header name
                var categorySettings = collaborativeService.categorySettings[headerText];
                if (categorySettings && categorySettings.settings && categorySettings.settings.category_id) {
                    header.attr('data-category-id', categorySettings.settings.category_id);
                }
            }
        });

        var serviceCards = panel.find('.booknetic_service_card');

        if (serviceCards.length === 0) {
            console.log('Service Collaborative: No service cards found');
            return;
        }

        // Add hint text (only once)
        if (panel.find('.booknetic_collab_hint').length === 0) {
            var hintHtml = '<div class="booknetic_collab_hint" style="background: #e3f2fd; padding: 12px; margin-bottom: 15px; border-left: 4px solid #2196F3; border-radius: 4px;">' +
                '<strong style="color: #1976d2;">Multi-Service Booking:</strong> ' +
                'Select multiple services and assign each to "Me" or "Guest".' +
                '</div>';

            panel.find('.booknetic_services_container').before(hintHtml);
        }

        // Convert each service card
        serviceCards.each(function () {
            var card = $(this);
            var serviceId = card.data('id');

            if (!serviceId) return;

            // Determine and set category data on the card for validation purposes
            var cardCategory = null;

            // First, try to find from nearest preceding category header
            var prevCat = card.prevAll('.booknetic_service_category').first();
            if (prevCat.length > 0) {
                cardCategory = prevCat.data('category-id') || prevCat.attr('data-category-id');
            }

            // Fallback to existing attributes on card
            if (!cardCategory) {
                cardCategory = card.data('category') || card.attr('data-category') || card.data('category-id') || card.attr('data-category-id');
            }

            // Set the category data on the card for validation purposes
            if (cardCategory) {
                card.attr('data-category', cardCategory);
            } else if (categoryKey && !isNaN(Number(categoryKey))) {
                card.attr('data-category', categoryKey);
                cardCategory = categoryKey;
            }

            // Only add multi-select UI if this card's category has multi-select enabled
            var hasMultiSelect = cardCategory && collaborativeService.multiSelectCategories[cardCategory];
            if (!hasMultiSelect) {
                // For categories without multi-select, ensure no collaborative UI is present
                card.find('.booknetic_collab_service_checkbox').remove();
                card.find('.booknetic_collab_assignment').remove();
                return; // skip this card
            }

            // CRITICAL: Unbind ALL existing click handlers from Booknetic's default behavior
            card.off('click');

            var header = card.find('.booknetic_service_card_header');
            var price = header.find('.booknetic_service_card_price');

            if (card.find('.booknetic_collab_service_checkbox').length === 0) {
                var checkboxHtml = '<div class="booknetic_collab_service_checkbox" style="float: right; height: 100%; display: flex; align-items: center; padding-right: 10px; padding-left: 20px;">' +
                    '<input type="checkbox" data-service-id="' + serviceId + '" style="width: 18px; height: 18px; cursor: pointer;">' +
                    '</div>';
                price.before(checkboxHtml);
            }

            // Add assignment checkboxes (allows selecting both Me and Guest)
            if (card.find('.booknetic_collab_assignment').length === 0) {
                var assignmentHtml = '<div class="booknetic_collab_assignment" style="padding: 10px; margin:10px; border-top: 1px solid #e0e0e0; display: none;">' +
                    '<label style="font-size: 12px; font-weight: 600; display: block; margin-bottom: 5px;">Assign to:</label>' +
                    '<div style="display: flex; gap: 15px;">' +
                    '<label style="display: flex; align-items: center; font-size: 13px; cursor: pointer;">' +
                    '<input type="checkbox" class="booknetic_assign_me" data-service-id="' + serviceId + '" value="me" style="margin-right: 6px;">' +
                    'Me' +
                    '</label>' +
                    '<label style="display: flex; align-items: center; font-size: 13px; cursor: pointer;">' +
                    '<input type="checkbox" class="booknetic_assign_guest" data-service-id="' + serviceId + '" value="guest" style="margin-right: 6px;">' +
                    'Guest' +
                    '</label>' +
                    '</div>' +
                    '<small style="color: #999; display: block; margin-top: 5px;">Select who will book this service</small>' +
                    '</div>';
                card.append(assignmentHtml);

                // Handle assignment checkbox changes
                card.find('.booknetic_assign_me, .booknetic_assign_guest').off('change').on('change', function () {
                    console.log('Assignment checkbox changed for service ' + serviceId);
                    rebuildServiceAssignments(booknetic, serviceId, card, panel);
                });
            }

            // Handle checkbox change
            card.find('.booknetic_collab_service_checkbox input').off('change').on('change', function (e) {
                e.stopPropagation();
                e.preventDefault();
                console.log('Service checkbox changed for service ' + serviceId + ':', $(this).is(':checked'));
                if ($(this).is(':checked')) {
                    card.addClass('booknetic_card_selected');
                    card.find('.booknetic_collab_assignment').slideDown(200);

                    // CRITICAL: Add entries for both selected assignments
                    rebuildServiceAssignments(booknetic, serviceId, card, panel);

                    // Check if service has custom durations
                    var hasCustomDuration = window.servicesWithCustomDuration && window.servicesWithCustomDuration.some(function (row) {
                        return row.service_id === serviceId.toString();
                    });

                    if (hasCustomDuration) {
                        // Show custom duration popup for this service
                        // Note: If both Me and Guest are selected, popup will show once and apply to both
                        showCustomDurationPopup(booknetic, serviceId, card);
                    }
                } else {
                    card.removeClass('booknetic_card_selected');
                    card.find('.booknetic_collab_assignment').slideUp(200);

                    // CRITICAL: Remove ALL entries for this service when unchecked
                    var initialLength = collaborativeService.selectedServices.length;
                    collaborativeService.selectedServices = collaborativeService.selectedServices.filter(function (s) {
                        var sid = (s.service_id !== undefined && s.service_id !== null) ? s.service_id : s.service;
                        if (sid === undefined || sid === null) return true;
                        return String(sid).trim() !== String(serviceId).trim();
                    });
                    console.log('✓ Removed service ' + serviceId + ' from selectedServices (was ' + initialLength + ', now ' + collaborativeService.selectedServices.length + ')');
                }

                updateSelectedCount(panel);
            });

            // Handle card click to toggle checkbox - use click with capture to intercept before Booknetic
            card.on('click.collaborative', function (e) {
                // Check what was clicked first
                var isCheckbox = $(e.target).is('input[type="checkbox"]');
                var isRadio = $(e.target).is('input[type="radio"]');
                var isLabel = $(e.target).is('label');
                var isButton = $(e.target).hasClass('booknetic_view_more_service_notes_button') || $(e.target).hasClass('booknetic_view_less_service_notes_button') || $(e.target).closest('.booknetic_view_more_service_notes_button, .booknetic_view_less_service_notes_button').length > 0;

                // ALWAYS stop Booknetic's default handler from running
                e.stopImmediatePropagation();

                // Only preventDefault for non-input clicks
                if (!isCheckbox && !isRadio && !isButton) {
                    e.preventDefault();
                }

                // Handle clicks on specific elements
                if (isCheckbox) {
                    // Checkbox clicked - let it toggle naturally, change event will handle UI
                    return;
                }

                if (isRadio) {
                    // Radio button clicked - let it work naturally
                    return;
                }

                if (isLabel) {
                    // Label clicked - check if it's for a radio button
                    var labelFor = $(e.target).closest('label').find('input[type="radio"]');
                    if (labelFor.length > 0) {
                        labelFor.prop('checked', true);
                        return;
                    }
                }

                if (isButton) {
                    // Button clicked - let it handle its own click
                    return;
                }

                // Card background clicked - toggle the checkbox
                // var checkbox = $(this).find('.booknetic_collab_service_checkbox input');
                // checkbox.prop('checked', !checkbox.prop('checked')).trigger('change');

                return false;
            });

            // Handle "Show more" button click to display full service description
            card.find('.booknetic_view_more_service_notes_button').on('click', function (e) {
                e.stopPropagation();
                card.find('.booknetic_service_card_description_wrapped').hide();
                card.find('.booknetic_service_card_description_fulltext').show();
                $(this).hide();
                card.find('.booknetic_view_less_service_notes_button').show();
            });

            // Handle "Show less" button click to display wrapped service description
            card.find('.booknetic_view_less_service_notes_button').on('click', function (e) {
                e.stopPropagation();
                card.find('.booknetic_service_card_description_fulltext').hide();
                card.find('.booknetic_service_card_description_wrapped').show();
                $(this).hide();
                card.find('.booknetic_view_more_service_notes_button').show();
            });
        });

        // Add selected count indicator
        addSelectedCountIndicator(panel);

        // Restore previous selections if navigating back
        restorePreviousSelections(panel);

        // Add custom styling
        injectMultiSelectStyles();
    }

    // Update selected count indicator
    function updateSelectedCount(panel) {
        var count = panel.find('.booknetic_collab_service_checkbox input:checked').length;
        panel.find('.booknetic_collab_count').text(count + ' selected');
    }

    // Add selected count indicator
    function addSelectedCountIndicator(panel) {
        if (panel.find('.booknetic_collab_count_container').length > 0) {
            return;
        }

        var countHtml = '<div class="booknetic_collab_count_container" style="text-align: center; margin: 15px 0; font-weight: 600; color: #2196F3;">' +
            '<span class="booknetic_collab_count">0 selected</span>' +
            '</div>';

        panel.find('.booknetic_services_container').after(countHtml);
    }

    // Restore previous selections when navigating back
    function restorePreviousSelections(panel) {
        if (collaborativeService.selectedServices.length === 0) {
            return;
        }

        console.log('Service Collaborative: Restoring previous selections:', collaborativeService.selectedServices);

        // Group selected services by service_id
        var serviceMap = {};
        collaborativeService.selectedServices.forEach(function (item) {
            if (!serviceMap[item.service_id]) {
                serviceMap[item.service_id] = {
                    service_id: item.service_id,
                    me: false,
                    guest: false,
                    category_id: item.category_id
                };
            }
            if (item.assigned_to === 'me') {
                serviceMap[item.service_id].me = true;
            } else if (item.assigned_to === 'guest') {
                serviceMap[item.service_id].guest = true;
            }
        });

        // Restore selections
        Object.keys(serviceMap).forEach(function (serviceId) {
            var serviceData = serviceMap[serviceId];
            var card = panel.find('.booknetic_service_card[data-id="' + serviceId + '"]');

            if (card.length > 0) {
                // Check the service checkbox
                card.find('.booknetic_collab_service_checkbox input').prop('checked', true).trigger('change');

                // Restore assignment checkboxes
                card.find('.booknetic_assign_me[data-service-id="' + serviceId + '"]').prop('checked', serviceData.me);
                card.find('.booknetic_assign_guest[data-service-id="' + serviceId + '"]').prop('checked', serviceData.guest);

                console.log('✓ Restored service ' + serviceId + ' - Me: ' + serviceData.me + ', Guest: ' + serviceData.guest);
            }
        });
    }

    // Function to rebuild service assignments when Me/Guest checkboxes change
    function rebuildServiceAssignments(booknetic, serviceId, card, panel) {
        console.log('Rebuilding assignments for service ' + serviceId);

        // Get which assignments are checked
        var isMe = card.find('.booknetic_assign_me[data-service-id="' + serviceId + '"]').is(':checked');
        var isGuest = card.find('.booknetic_assign_guest[data-service-id="' + serviceId + '"]').is(':checked');

        console.log('Service ' + serviceId + ' - Me: ' + isMe + ', Guest: ' + isGuest);

        // If neither Me nor Guest is selected, add a placeholder entry so
        // validation can require assignment selection explicitly.
        // Do NOT auto-select any option by default.

        // Remove all existing entries for this service
        var beforeCount = collaborativeService.selectedServices.length;
        var categoryId = card.data('category');
        var serviceName = normalizeServiceName(getServiceNameFromCard(card, serviceId));

        // Enforce single-category selection: clear all other categories first
        if (panel && categoryId) {
            clearSelectionsFromOtherCategories(panel, categoryId, serviceId);
        }

        collaborativeService.selectedServices = collaborativeService.selectedServices.filter(function (s) {
            return String(s.service_id) !== String(serviceId);
        });
        console.log('Removed existing entries for service ' + serviceId + ' (was ' + beforeCount + ', now ' + collaborativeService.selectedServices.length + ')');

        // Add new entries based on checked assignments. If none are checked
        // we push a placeholder with empty assigned_to so the validation
        // step can prompt the user to choose an assignment.
        if (isMe) {
            collaborativeService.selectedServices.push({
                service_id: String(serviceId),
                assigned_to: 'me',
                category_id: categoryId,
                custom_duration: null,
                service_name: serviceName
            });
            console.log('✓ Added entry: Service ' + serviceId + ' → Me');
        }

        if (isGuest) {
            collaborativeService.selectedServices.push({
                service_id: String(serviceId),
                assigned_to: 'guest',
                category_id: categoryId,
                custom_duration: null,
                service_name: serviceName
            });
            console.log('✓ Added entry: Service ' + serviceId + ' → Guest');
        }

        if (!isMe && !isGuest) {
            collaborativeService.selectedServices.push({
                service_id: String(serviceId),
                assigned_to: '',
                category_id: categoryId,
                custom_duration: null,
                service_name: serviceName
            });
            console.log('✓ Added placeholder entry for service ' + serviceId + ' with no assignment');
        }

        console.log('Updated selectedServices:', collaborativeService.selectedServices);

        // If both Me and Guest are selected, expand cart immediately
        if (isMe && isGuest && booknetic && typeof expandCartForMultiService === 'function') {
            var currentItem = booknetic.cartArr && booknetic.cartArr[booknetic.cartCurrentIndex];
            var hasDateTime = currentItem && currentItem.date && currentItem.time;
            if (hasDateTime) {
                console.log('Both Me and Guest selected - expanding cart immediately');
                expandCartForMultiService(booknetic);
            } else {
                console.log('Both Me and Guest selected - skipping immediate expansion (date/time not selected yet)');
            }
        }
    }

    function getServiceNameFromCard(card, serviceId) {
        if (!card || card.length === 0) {
            return '';
        }

        var titleEl = card.find('.booknetic_service_card_title, .booknetic_service_card_name, .booknetic_card_title, .booknetic_card_name').first();
        var title = titleEl.length > 0 ? titleEl.text().trim() : '';
        return title || ('Service #' + serviceId);
    }

    function normalizeServiceName(name) {
        if (!name) {
            return '';
        }

        var normalized = String(name).replace(/\s+/g, ' ').trim();
        // Prefer the first line if the name contains line breaks
        var firstLine = String(name).split(/\r?\n/).map(function (line) {
            return line.trim();
        }).filter(function (line) {
            return line.length > 0;
        })[0];

        return firstLine ? firstLine : normalized;
    }

    // Clear selections that belong to other categories
    function clearSelectionsFromOtherCategories(panel, activeCategoryId, activeServiceId) {
        if (!activeCategoryId) {
            return;
        }

        var activeCategoryStr = String(activeCategoryId);

        // Remove entries from selectedServices that are not in the active category
        var beforeCount = collaborativeService.selectedServices.length;
        collaborativeService.selectedServices = collaborativeService.selectedServices.filter(function (s) {
            if (!s || !s.category_id) {
                return true;
            }
            return String(s.category_id) === activeCategoryStr;
        });

        if (beforeCount !== collaborativeService.selectedServices.length) {
            console.log('Cleared selections from other categories (was ' + beforeCount + ', now ' + collaborativeService.selectedServices.length + ')');
        }

        // Uncheck and reset UI for cards that are not in the active category
        panel.find('.booknetic_service_card').each(function () {
            var card = $(this);
            var cardCategory = card.data('category');
            var cardServiceId = card.data('id');

            if (!cardCategory) {
                return;
            }

            if (String(cardCategory) !== activeCategoryStr) {
                // Uncheck service checkbox
                card.removeClass('booknetic_card_selected');
                card.find('.booknetic_collab_service_checkbox input').prop('checked', false);

                // Uncheck assignments and hide the assignment block
                card.find('.booknetic_assign_me, .booknetic_assign_guest').prop('checked', false);
                card.find('.booknetic_collab_assignment').hide();

                // Remove any leftover entries for this service from selectedServices
                if (cardServiceId !== undefined && cardServiceId !== null) {
                    collaborativeService.selectedServices = collaborativeService.selectedServices.filter(function (s) {
                        return String(s.service_id) !== String(cardServiceId);
                    });
                }
            }
        });
    }

    // Function to show custom duration popup for a specific service
    function showCustomDurationPopup(booknetic, serviceId, card) {
        console.log('Showing custom duration popup for service:', serviceId);

        // Create a temporary cart item with just this service for the AJAX call
        var tempCartItem = JSON.parse(JSON.stringify(booknetic.cartArr[booknetic.cartCurrentIndex]));
        tempCartItem.service = serviceId;

        // Add overlay if not exists
        if (!booknetic.panel_js.find('.booknetic_appointment_container').find('.popup-overlay').length > 0) {
            booknetic.panel_js.find('.booknetic_appointment_container').append($('<div>', {
                class: 'popup-overlay',
            }));
        }

        // Call AJAX to get custom duration popup using temp item data
        var tempCart = JSON.parse(JSON.stringify(booknetic.cartArr));
        tempCart[booknetic.cartCurrentIndex] = tempCartItem;

        // Build AJAX parameters with temp cart
        var ajaxParams = {
            cart: JSON.stringify(tempCart),
            current: booknetic.cartCurrentIndex
        };

        // Add any other parameters that might be needed
        if (BookneticCollabFrontend && BookneticCollabFrontend.nonce) {
            ajaxParams.nonce = BookneticCollabFrontend.nonce;
        }

        booknetic.ajax('get_custom_duration', ajaxParams, function (result) {
            console.log('Custom duration AJAX response:', result);
            booknetic.panel_js.find('.popup-overlay').addClass('enter');

            $(result['custom-durations']).appendTo(booknetic.panel_js.find('.booknetic_appointment_container')).queue(function () {
                setTimeout(function () {
                    booknetic.panel_js.find('.booknetic_custom_duration_popup').addClass('enter');
                }, 0);
            });

            // Handle duration selection
            booknetic.panel_js.find('.bkntc_custom_durations, .bkntc_custom_durations_labels').on('click', function (e) {
                var durationId = $(e.currentTarget).data('duration-id');
                console.log('Duration selected:', durationId, 'for service:', serviceId);

                // Update ALL entries for this service with the custom duration
                // (there might be multiple if service is assigned to both Me and Guest)
                var foundCount = 0;
                collaborativeService.selectedServices.forEach(function (service, index) {
                    if (service.service_id === serviceId) {
                        collaborativeService.selectedServices[index].custom_duration = durationId;
                        foundCount++;
                        console.log('✓ Updated custom_duration for service ' + serviceId + ' (entry ' + (foundCount) + '):', durationId);
                    }
                });

                if (foundCount === 0) {
                    console.warn('⚠️ Service ' + serviceId + ' not found in selectedServices!');
                } else {
                    console.log('✓ Updated ' + foundCount + ' entry/entries with duration ' + durationId);
                    console.log('Updated selectedServices:', collaborativeService.selectedServices);
                }

                // Close popup
                booknetic.panel_js.find('.popup-overlay').removeClass('enter');
                booknetic.panel_js.find('.booknetic_custom_duration_popup').remove();
            });

            // Handle close button
            booknetic.panel_js.find('.bkntc_popup-x').on('click', function () {
                console.log('Duration popup closed without selection for service:', serviceId);
                booknetic.panel_js.find('.booknetic_custom_duration_popup').remove();
                booknetic.panel_js.find('.popup-overlay').removeClass('enter');
            });

            // Handle continue without selection (optional)
            booknetic.panel_js.find('#bkntc_custom_duration_continue').off('click').on('click', function () {
                console.log('Continue without duration selection for service:', serviceId);

                // Update ALL entries for this service to have empty duration
                var foundCount = 0;
                collaborativeService.selectedServices.forEach(function (service, index) {
                    if (service.service_id === serviceId) {
                        collaborativeService.selectedServices[index].custom_duration = '';
                        foundCount++;
                        console.log('✓ Service ' + serviceId + ' (entry ' + foundCount + ') set to continue without duration');
                    }
                });

                if (foundCount === 0) {
                    console.warn('⚠️ Service ' + serviceId + ' not found in selectedServices!');
                } else {
                    console.log('✓ Cleared custom_duration for ' + foundCount + ' entry/entries');
                }

                booknetic.panel_js.find('.popup-overlay').removeClass('enter');
                booknetic.panel_js.find('.booknetic_custom_duration_popup').remove();
            });

        }, false);
    }

    // Inject custom styles for multi-select
    function injectMultiSelectStyles() {
        if ($('#booknetic_collab_service_styles').length > 0) {
            return;
        }

        var styles = '<style id="booknetic_collab_service_styles">' +
            '.booknetic_service_card { position: relative; cursor: pointer; transition: all 0.2s; }' +
            '.booknetic_service_card.booknetic_card_selected { border-color: #2196F3 !important; box-shadow: 0 0 0 2px rgba(33, 150, 243, 0.2); }' +
            '.booknetic_collab_service_checkbox input:hover { transform: scale(1.1); }' +
            '.booknetic_collab_assignment { animation: slideDown 0.2s; }' +
            '@keyframes slideDown { from { opacity: 0; } to { opacity: 1; } }' +
            '</style>';

        $('head').append(styles);
    }

})(jQuery);