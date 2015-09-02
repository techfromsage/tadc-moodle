tadc-moodle
===========

A Moodle module for creating and displaying digitisation requests from Talis Aspire Digitised Content

Installation
------------

$ git clone git://github.com/talis/tadc-moodle.git

$ cp -r tadc-moodle /{path}/{to}/{moodle}/mod/tadc

### Setting up TADC:

1. Log into your TADC instance and under the 'Admin' menu, select 'Settings'
2. In the 'Services' tab, set the VLE Brand to 'Moodle' and the VLE location to base url of your Moodle instance and save your settings
3. Under the 'Admin' menu, select 'Integrations'.
4. Add an access key and take note of the 'API key' and 'shared secret'.

### Setting up Moodle to submit requests to TADC and create resources within the course:

0. You may need to click 'Notifications' on the left which will prompt you to update the database for the new plugin.
1. Add the TADC module via Site administration -> Plugins -> Plugins overview -> Check for available updates
2.  Go to Site administration -> Plugins -> Plugins overview page, make sure 'Course readings' (mod_tadc) is enabled
    1. Click on settings
    2. Add your TADC tenant code (e.g. https://content.talisaspire.com/{something})
    3. Add the TADC location (probably https://content.talisaspire.com/ unless you are using a CNAME)
    4. Enter your course details to translate from Moodle to TADC.
    5. Add your TADC API key.
    6. Add your shared secret passphrase.

In theory, the module should now be set up.  'Digitisation Request' should now appear as an option under 'Add an activity or resource'

TADC should now be set up to accept requests from Moodle.

### Enabling 'Restrict and Download via VLE'
1. Follow the instructions for *Setting up Moodle to submit requests to TADC and create resources within the course* (ignoring steps 2.4-2.6), select *Allow TADC downloads from Moodle* under the *Specify the TADC services you wish to enable from Moodle* section and save.
2. In TADC, click on the 'Admin' menu and select 'Settings'
3. In the 'Bundles' tab, select 'VLE'
4. The print button should now be enabled in the player, which links to page within Moodle for authorisation.
