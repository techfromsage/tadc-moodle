tadc-moodle
===========

A Moodle module for creating and displaying digitisation requests from Talis Aspire Digitised Content

Installation
------------

$ git clone git://github.com/talis/tadc-moodle.git
$ cp -r tadc-moodle /{path}/{to}/{moodle}/mod/tadc

### Setting up Moodle to submit requests to TADC and create resources within the course:

1. Add the TADC module via Site administration -> Plugins -> Plugins overview -> Check for available updates
2. Enable REST in webservices, if it's not already enabled:
    a. Site administration -> Plugins -> Webservices -> Overview
    b. Enable web services - set to 'yes'
    c. Enable protocols: rest
    d. Create a user to perform the webservices calls (e.g. talisaspire)
    e. Check user capability (must have at least the following):
        i. webservice/rest:use
        ii. mod/tadc:updateinstance
    f. Go to 'Select a service'
        i. Make sure External service: trackback from plugin: mod_tadc is listed under "Built-in services"
        ii. Click the 'Functions' link and check that the tadc_trackback function is listed
        iii. Click on 'Authorised users' and add the user you created in 2.d. to the Authorised users list
        iv. Click on 'Edit' and make sure that 'Enabled' is checked (and that it generally makes sense)
    e. Go to Site administration -> Plugins -> Webservices -> Manage tokens
        i. Click 'Add'
        ii. Select the user your created in 2.d.
        iii. For 'Service' select 'trackback'
        iv. Do not add any restrictions.
        v. Click 'Save changes'
        vi. Note the saved token
3.  Go to Site administration -> Plugins -> Plugins overview page, make sure 'Course readings' (mod_tadc) is enabled
    a. Click on settings
    b. Add your TADC tenant code (e.g. http://content.talisaspire.com/{something})
    c. Add the TADC location (probably http://content.talisaspire.com/ unless you are using a CNAME)
    d. Add your shared secret passphrase.  Make this as long and unique a phrase as possible.
    e. Your trackback location should be: http(s)://{your_moodle_hostname}/webservice/rest/server.php?wstoken={token_string_from_2.e.vi}&wsfunction=tadc_trackback

In theory, the module should now be set up.  'Digitisation request' should now appear as an option under 'Add an activity or resource'

### Setting up TADC:

1. Log into your TADC instance and under the 'Admin' menu, select 'Settings'
2. In the 'Services' tab, set the VLE Brand to 'Moodle' and the VLE location to base url of your Moodle instance.
3. In the 'Authentication' tab, set the 'Access Passphrase' to whatever you chose for the 'shared secret passphrase' above.
4. Save your settings.

TADC should now be set up to accept requests from Moodle.

### Enabling 'Restrict and Download via VLE'
1. You can ignore the 'REST webservices' section (2.a - 2.e), but follow all of the other instructions (except 3.e) if you do not wish to enable requests from Moodle
2. In TADC, click on the 'Admin' menu and select 'Settings'
3. In the 'Bundles' tab, select 'VLE'
4. The print button should now be enabled in the player, which links to page within Moodle for authorisation.
