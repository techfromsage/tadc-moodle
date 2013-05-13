M.mod_tadc = {};
M.mod_tadc.form_init = function(Y, args)
{
    Y.use('cssbutton');
}

M.mod_tadc.resize_iframe = function(Y, args)
{
    function resizeIframe()
    {
        // Set the iFrame height
        //Y.one('#tadc-bundle-viewer').setStyle('height', Y.DOM.winHeight() - (Y.one('#tadc-bundle-viewer').getX() + 25));
        Y.one('#tadc-bundle-viewer').setStyle('height', Y.DOM.winHeight());
    }
    Y.on("domready", resizeIframe, Y, "Resizing the iFrame");
}