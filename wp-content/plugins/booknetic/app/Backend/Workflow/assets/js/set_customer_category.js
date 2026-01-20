( function ( $ ) {
    'use strict';

    $( document ).ready( function () {
        $( '.fs-modal' ).on( 'click', '#saveWorkflowActionBtn', function () {
            let data	= new FormData();
            let category_id = $('#input_customer_set_category').val();
            let run_workflows = $('#input_run_workflows').is(':checked');
            let is_active = $('#input_is_active').is(':checked') ? 1 : 0;

            data.append('id', workflow_action_id );
            data.append('is_active', is_active);
            data.append('category_id', category_id);
            data.append('run_workflows', run_workflows ? 1 : 0);

            booknetic.ajax( 'workflow_actions.set_customer_category_save', data, function () {
                booknetic.modalHide( $( '.fs-modal' ) );
                booknetic.reloadActionList();
            } );
        });

        $( '#input_customer_set_category' ).select2( {
            theme: 'bootstrap',
            placeholder: booknetic.__('select'),
            allowClear: false,
            tags: false
        });


    } );
})(jQuery);