# Custom Duration Plugin Integration with Collaborative Services

## Overview
This document explains how the **Custom Duration for Booknetic** plugin has been integrated into the **Booknetic Collaborative Services** plugin to allow users to select custom durations for each service when booking multiple services in a single session.

---

## Architecture & Implementation

### 1. **How the Custom Duration Plugin Works (Backend)**

The Custom Duration plugin (`booknetic-custom-duration`) operates as follows:

#### Key Components:
- **Listener.php**: Handles backend logic for saving/loading custom durations per service
- **Frontend/Ajax.php**: AJAX endpoint that responds to `get_custom_duration` requests
- **Frontend/view/durations-popup.php**: HTML template for the duration selection popup
- **assets/frontend/js/init.js**: Frontend logic that shows the popup after service selection

#### Key Hooks:
```php
// In CustomDurationAddon.php initFrontend():
add_filter('bkntc_set_service_duration_frontend', [...])  // Set duration on cart item
add_filter('bkntc_booking_panel_assets', [...])          // Load JS/CSS on frontend
```

#### Global Data:
```javascript
window.servicesWithCustomDuration = [
    { service_id: "1", duration: "30 - 90 minutes" },
    // ... array of all services with custom durations
]
```

This array is injected into the booking panel on page load and contains all services that have custom durations enabled.

---

### 2. **Integration with Collaborative Services**

The integration leverages the **checkbox-based multi-select system** already in place. When a user checks a service checkbox in collaborative mode:

#### Flow Diagram:
```
User checks checkbox
    ↓
Checkbox change event fires
    ↓
Check: Does this service have custom durations?
    ├─ YES: Call showCustomDurationPopup()
    │   ↓
    │   AJAX call: get_custom_duration
    │   ↓
    │   Custom Duration popup displayed
    │   ↓
    │   User selects duration or skips
    │   ↓
    │   Store duration in collaborativeService.selectedServices
    │
    └─ NO: Continue without popup
        ↓
        User stays on service selection page
```

#### Key Code Changes:

**File**: `step_service_collaborative.js`

**Location 1 - Checkbox Change Handler (Line ~1000):**
```javascript
card.find('.booknetic_collab_service_checkbox input').off('change').on('change', function (e) {
    e.stopPropagation();
    e.preventDefault();

    if ($(this).is(':checked')) {
        card.addClass('booknetic_card_selected');
        card.find('.booknetic_collab_assignment').slideDown(200);

        // NEW: Check if service has custom durations
        var hasCustomDuration = window.servicesWithCustomDuration && 
                                window.servicesWithCustomDuration.some(function(row) {
                                    return row.service_id === serviceId.toString();
                                });

        if (hasCustomDuration) {
            // NEW: Show custom duration popup for this service
            showCustomDurationPopup(booknetic, serviceId, card);
        }
    } else {
        card.removeClass('booknetic_card_selected');
        card.find('.booknetic_collab_assignment').slideUp(200);
    }

    updateSelectedCount(panel);
});
```

**Location 2 - New Function: `showCustomDurationPopup()` (Line ~1150):**
```javascript
function showCustomDurationPopup(booknetic, serviceId, card) {
    console.log('Showing custom duration popup for service:', serviceId);

    // Temporarily set the service in cart for AJAX
    var originalService = booknetic.cartArr[booknetic.cartCurrentIndex].service;
    booknetic.cartArr[booknetic.cartCurrentIndex].service = serviceId;

    // Add overlay if not exists
    if (!booknetic.panel_js.find('.booknetic_appointment_container').find('.popup-overlay').length > 0) {
        booknetic.panel_js.find('.booknetic_appointment_container').append($('<div>', {
            class: 'popup-overlay',
        }));
    }

    // Call AJAX to get custom duration popup
    booknetic.ajax('get_custom_duration', booknetic.ajaxParameters(), function (result) {
        booknetic.panel_js.find('.popup-overlay').addClass('enter');
        
        // Append the popup HTML
        $(result['custom-durations']).appendTo(booknetic.panel_js.find('.booknetic_appointment_container'));
        
        // Add event handlers to popup elements
        // ... Handle duration selection, close, and continue buttons
        
        // Store selected duration in collaborativeService.selectedServices
        collaborativeService.selectedServices.push({
            service_id: serviceId,
            assigned_to: card.find('input[name="assign_to_' + serviceId + '"]:checked').val() || 'me',
            category_id: card.data('category'),
            custom_duration: durationId
        });
    }, false);

    // Restore original service
    booknetic.cartArr[booknetic.cartCurrentIndex].service = originalService;
}
```

---

## Data Flow & Storage

### 1. **Service Selection Data Structure**

When a service is selected with a custom duration, the following object is created:

```javascript
collaborativeService.selectedServices = [
    {
        service_id: 1,           // Booknetic service ID
        assigned_to: "me",       // "me" or "guest"
        category_id: 5,          // Category ID for validation
        custom_duration: 3       // Custom Duration ID (from popup selection)
    },
    {
        service_id: 2,
        assigned_to: "guest",
        category_id: 5,
        custom_duration: 7
    }
]
```

### 2. **Cart Expansion with Custom Duration**

When the booking proceeds to the next step (`expandCartForMultiService()`), each selected service becomes a separate cart item with the custom duration metadata:

```javascript
booknetic.cartArr = [
    {
        service: 1,
        assigned_to: "me",
        is_collaborative_booking: true,
        custom_duration: 3,
        // ... other fields
    },
    {
        service: 2,
        assigned_to: "guest",
        is_collaborative_booking: true,
        custom_duration: 7,
        // ... other fields
    }
]
```

### 3. **AJAX Data Transmission**

When booking is confirmed, custom duration data is passed to the backend:

```javascript
// In 'appointment_ajax_data' filter:
if (booknetic.cartArr[i].custom_duration) {
    data.cart[i].custom_duration = booknetic.cartArr[i].custom_duration;
}
```

---

## Implementation Methods Summary

### **Method 1: Inline Popup (Currently Implemented)** ✓
**Advantages:**
- Non-blocking modal dialog
- User stays on service page
- Reuses existing custom duration popup UI
- No page navigation

**How it works:**
1. User checks service checkbox
2. Popup overlays current page with duration options
3. User selects duration or skips
4. Popup closes, user remains on service page
5. Data stored in memory for later submission

**Code Location**: `showCustomDurationPopup()` function

---

### **Method 2: Inline Form (Alternative)**
**If you prefer inline selection on the service card itself:**

```javascript
// Add duration selector directly below assignment section
if (hasCustomDuration) {
    var durationOptionsHtml = '<div class="booknetic_custom_duration_inline">' +
        '<label>Duration:</label>' +
        '<select data-service-id="' + serviceId + '">' +
        '<option value="">--Select Duration--</option>' +
        '<!-- Populate from window.servicesWithCustomDuration -->' +
        '</select>' +
        '</div>';
    
    card.find('.booknetic_collab_assignment').after(durationOptionsHtml);
}
```

**Advantages:**
- Always visible on the card
- No popup blocking
- User can see all durations at once

**Disadvantages:**
- Requires rendering all duration options (may be many)
- Card height increases

---

### **Method 3: AJAX with Inline UI (Alternative)**
**Fetch and render duration options dynamically:**

```javascript
$.ajax({
    url: BookneticCollabFrontend.ajaxurl,
    data: { 
        action: 'bkntc_get_custom_durations',
        service_id: serviceId
    },
    success: function(response) {
        // Render response HTML into card
        card.append(response.html);
    }
});
```

---

## Validation & Error Handling

### Service Validation
The existing `step_validation_service` filter already validates:
- Service quantity limits
- Min/max services per person
- Same category requirement

**Custom Duration additions:**
```javascript
// Check if custom duration is required but not selected
if (categorySettings.settings.require_custom_duration) {
    for (var i = 0; i < selectedServices.length; i++) {
        if (!selectedServices[i].custom_duration) {
            return {
                status: false,
                errorMsg: 'Please select a custom duration for all services.'
            };
        }
    }
}
```

### Backend Processing
The Custom Duration plugin's `Listener::apply_custom_duration_properties()` applies custom duration pricing to each appointment.

---

## User Experience

### Flow Diagram (Multi-Service with Custom Durations):

```
1. SERVICE SELECTION STEP
   ├─ Display services with checkboxes and "Me/Guest" assignment
   ├─ User checks: Service A (me)
   │   └─ Custom Duration popup appears
   │       ├─ Display: "30 min", "60 min", "90 min"
   │       └─ User selects "60 min"
   ├─ User checks: Service B (guest)
   │   └─ Custom Duration popup appears
   │       └─ User selects "90 min"
   └─ Click NEXT

2. INFORMATION STEP
   └─ Cart is internally expanded (hidden)

3. CART STEP
   ├─ Display: Service A (60 min) - me
   ├─ Display: Service B (90 min) - guest
   └─ Show combined price

4. CONFIRMATION
   └─ User confirms
       └─ Two separate appointments created with custom durations
```

---

## Testing Checklist

- [ ] Service without custom duration: Checkbox works, no popup
- [ ] Service with custom duration: Checkbox triggers popup
- [ ] Popup close button: Closes without selecting duration
- [ ] Duration selection: Selects and closes popup
- [ ] Continue button: Skips duration and continues
- [ ] Multiple services: Each has own popup when checked
- [ ] Unchecking service: Removes from selection
- [ ] Back navigation: Previous selections restored
- [ ] Cart view: Shows correct durations
- [ ] Backend: Appointments saved with correct custom duration IDs

---

## Backend Integration Points

### CustomDurationAddon Hooks (Already Implemented):

```php
// In Listener.php:
apply_filters('bkntc_booking_panel_render_custom_durations_info', 
              ['customDurations' => [...], 'serviceInf' => ...])

// In Frontend/Ajax.php::get_custom_duration():
// Returns: ['custom-durations' => HTML string]

// Appointment validation:
apply_action('bkntc_appointment_request_before_data_validate',
             CartRequest $appointmentRequest)
```

The custom duration is stored in the `appointments` table as `custom_duration_id`.

---

## Performance Considerations

1. **AJAX Calls**: One AJAX call per service selection (acceptable for typical 2-4 services)
2. **Data Storage**: Custom duration data stored in JavaScript memory only until submission
3. **Popup Rendering**: Uses existing custom duration template (no extra load)
4. **Client-side Filtering**: `window.servicesWithCustomDuration` lookup is O(n) where n = total services with custom durations

---

## Future Enhancements

1. **Batch Duration Selection**: UI to set same duration for multiple services at once
2. **Quick Duration Presets**: Common durations (30, 60, 90 min) as quick-select buttons
3. **Duration-Based Validation**: Min/max total duration across all selected services
4. **Staff-Specific Durations**: If using staff assignments, show staff-specific duration pricing
5. **Duration Preview**: Show price change when duration selected in real-time

---

## Troubleshooting

### Issue: Popup not appearing when service checked

**Solution**:
1. Verify `window.servicesWithCustomDuration` exists (check browser console)
2. Verify service ID is in the array
3. Check browser console for JavaScript errors
4. Verify custom duration plugin is active in WordPress

### Issue: Duration not saved to cart

**Solution**:
1. Check that `collaborativeService.selectedServices` is populated (console.log)
2. Verify `custom_duration` field is included in validation object
3. Check cart expansion function includes custom duration data

### Issue: Popup styling incorrect

**Solution**:
1. Verify custom duration CSS is loaded: `booknetic-custom-duration` stylesheet
2. Check for CSS conflicts from other plugins
3. Clear browser cache

---

## File References

- **Integration File**: `wp-content/plugins/booknetic-collaborative-services/assets/js/steps/step_service_collaborative.js`
- **Custom Duration Plugin**: `wp-content/plugins/booknetic-custom-duration/`
- **Key Functions**:
  - `checkCategoryMultiSelect()` - Enables multi-select per category
  - `convertServiceToMultiSelect()` - Converts service cards to checkboxes
  - `showCustomDurationPopup()` - Triggers custom duration interaction
  - `expandCartForMultiService()` - Expands cart with custom duration metadata

---

## API Reference

### Global Objects

```javascript
// Collaborative Service Data
window.collaborativeService = {
    categorySettings: {},           // Per-category config
    multiSelectCategories: {},      // Which categories allow multi-select
    selectedServices: [             // Selected services with custom durations
        { service_id, assigned_to, category_id, custom_duration }
    ],
    isMultiSelectMode: boolean
}

// Custom Duration Services
window.servicesWithCustomDuration = [
    { service_id: "1", duration: "30 - 90 mins" },
    ...
]
```

### Booknetic Hooks Used

```javascript
bookneticHooks.addAction('loaded_step', ...)    // After service step loads
bookneticHooks.addFilter('step_validation_service', ...) // Validate selection
bookneticHooks.addFilter('appointment_ajax_data', ...)   // Before submission
bookneticHooks.addAction('step_end_information', ...)    // After customer info
```

---

## Version History

- **v2.1.0** (Current): Initial custom duration integration with collaborative services
  - Added `showCustomDurationPopup()` function
  - Checkbox triggers popup for services with custom durations
  - Custom duration stored in selected services data structure
  - Popup overlay prevents page navigation

---

**Last Updated**: February 19, 2026
**Compatibility**: Booknetic 2.x, Custom Duration Plugin 2.2.0+
