(function ($)
{
    "use strict";

    $(document).ready(function ()
    {
        $(document).on( 'click', '#download_qr', function ()
        {
            fetch( $( '#qr_code' ).attr( 'src' ) )
                .then( response => response.blob() )
                .then( blob => {
                    const url = window.URL.createObjectURL( blob );
                    const a = document.createElement('a');

                    a.href = url;
                    a.download = 'QR.png';

                    document.body.appendChild(a);
                    a.click();
                    window.URL.revokeObjectURL(url);
                })
                .catch( error => console.error( 'Error downloading QR code:', error ) );
        });
    });

})(jQuery);