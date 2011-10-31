ubersmith-puppet-inventory
==========================

This is a device module which adds support for the Puppet Inventory Service to Ubersmith. Once installed, you will be able to see an output of all facts associated with a device inside of the device manager. This makes inventorying servers easy - just compare your internal record of serial numbers and configurations with the output of factor, right from the Ubersmith web interface.

Installation
------------

You'll need to have Puppet installed, and activate the Puppet Inventory Service. It's very easy to do - learn more at http://docs.puppetlabs.com/guides/inventory_service.html

To set up the inventory service quickly, just add:

    path /facts
    auth yes
    method find, search
    allow ubersmith

to /etc/puppet/auth.conf on your Puppet Master, then generate a key called "ubersmith" by running `puppet cert --generate ubersmith`

To install this module, just add the dm\_puppet\_inventory\_service.php file to the includes/device_modules/ directory on your Ubersmith "frontend" machine (inside of the webroot for Ubersmith).

After installing the file, go to the "Setup & Admin" area of Ubersmith and click "Device Types". Now, click the "Modules" link for a device type, then "Add Module" and select "Puppet Inventory Service".

On the Config tab, you'll need to enter the full URL to your Puppet Inventory Service server, which is normally https://puppet:8140/production/facts
You can optionally enter a comma separated list of fact names that you'd like to include or exclude (to cut down on un-needed information).

In the Certificate field, add the output of `cat /var/lib/puppet/ssl/private_key/ubersmith.pem /var/lib/puppet/ssl/certs/ubersmith.pem` from the Puppet Master server

Once you save your changes, you can browse your devices under Device Manager and you should see a new section labeled "Puppet Inventory Service" which contains a nicely formatted list of facts for the device. If the device is not found, an error will appear in the box. Note that the module uses the value of the "label" field to query the Puppet Inventory Service - this same field is used by the built in Ubersmith Puppet Node Control module.