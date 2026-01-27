(function($)
{
    "use strict"

    const fetchView = async (bookneticShortCode)=>{

        let data = new FormData();

        data.append('shortcode',bookneticShortCode.text().trim())

        let req = await fetch(decodeURIComponent(bookneticSaaSElementor.url) + '/?bkntc_saas_preview=1',{
            method:'POST',
            body:data
        });
        let res = await req.text();
        $(bookneticShortCode).html(res).css('pointer-events', 'none');
    }

    $(document).ready(function()
    {
        fetchView( $('#bookneticSaaSForgotPasswordElementorContainer') );
    });

})(jQuery)