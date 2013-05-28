M.mod_tadc = {};
M.mod_tadc.form_init = function(Y, args)
{
    Y.use('cssbutton');
}

// Resize the iframe to the
M.mod_tadc.resize_iframe = function(Y, args)
{
    function resizeIframe()
    {
        Y.one('#tadc-bundle-viewer').setStyle('height', Y.DOM.winHeight());
    }
    Y.on("domready", resizeIframe, Y, "Resizing the iFrame");
}