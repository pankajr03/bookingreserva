( function ( $ ) {
    'use strict';

    $( document ).ready( function () {
        $( '.fs-modal' ).on( 'click', '#saveWorkflowActionBtn', function () {
            let data	= new FormData();
            let to = $('#input_to').val();
            let title = $('#input_title').val();
            let message = $('#input_message').val();
            let status = $('#input_status').val();
            let run_workflows = $('#input_run_workflows').is(':checked');
            let is_active = $('#input_is_active').is(':checked') ? 1 : 0;

            data.append('id', workflow_action_id );
            data.append('to', to);
            data.append('title', title);
            data.append('message', message);
            data.append('status', status);
            data.append('is_active', is_active);
            data.append('run_workflows', run_workflows ? 1 : 0);

            booknetic.ajax( 'workflow_actions.in_app_notification_save', data, function () {
                booknetic.modalHide( $( '.fs-modal' ) );
                booknetic.reloadActionList();
            } );
        });

        $( '#input_to' ).select2( {
            theme: 'bootstrap',
            placeholder: booknetic.__('select'),
            allowClear: false,
            tags: false
        });

        booknetic.initKeywordsInput( $( '#input_title' ), workflow_in_app_notification_action_all_shortcodes_obj );
        booknetic.summernote(
            $('#input_message'),
            [
                ['style', ['style']],
                ['style', ['bold', 'italic', 'underline', 'clear']],
                ['fontsize', ['fontsize']],
                ['color', ['color']],
                ['para', ['ul', 'ol', 'paragraph']],
                ['table', ['table']],
                ['insert', ['link', 'picture']],
                ['view', ['codeview']],
                ['height', ['height']],
            ],
            workflow_in_app_notification_action_all_shortcodes_obj
        );
    } );
})(jQuery);