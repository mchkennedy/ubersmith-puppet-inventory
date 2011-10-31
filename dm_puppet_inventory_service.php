<?php

/**
 * Puppet Inventory Service Module
 * 
 * https://github.com/jasongill/ubersmith-puppet-inventory
 * 
 * This module will issue a query to the Puppet Inventory Service for the 
 * configured device and return a list of information in the summary.
 *
 * Place this file in to
 * include/device_modules/
 * on your Ubersmith "frontend" server, then activate from "Device Types"
 *
 * @author Matt Kennedy <mkennedy@jumpline.com>
 * @author Jason Gill <jasongill@gmail.com>
 * @package default
 **/

class dm_puppet_inventory_service extends device_module
{
	/**
	 * Device Module Title
	 *
	 * This function returns the title of your device module. It will be included
	 * in the light blue title bar on the Device Manager page for your device,
	 * as well as in the configuration drop down in the Device Types section of
	 * 'setup & admin'.
	 *
	 * @return string
	 **/
	function title()
	{
		return 'Puppet Inventory Service';
	}
	
	/**
	 * Device Module Initialization
	 *
	 * This function will perform some basic initialization routines. In this 
	 * example, custom data for the device is retrieved. Other useful calls
	 * could be included in this step, if needed.
	 * 
	 * @return void
	 */
	function init($request = array())
	{
		if (isset($this->device)) {
			$this->metas = device_metadata($this->device);
		}
	}
	
	/**
	 * Device Module Summary
	 *
	 * This function returns a string (usually of HTML) that will be displayed
	 * in the Device Module's 'box' on the device's Device Manager page
	 * 
	 * This can be used to display retrieved information, or provide a link
	 * to a utility outside of Ubersmith, or to call some functionality within
	 * Ubersmith.
	 * 
	 * @return string
	 **/
	function summary($request = array())
	{
		$this->init($request);
		
		$url = sprintf("%s/%s", $this->config('pis_baseurl'), $this->device['label']);
		
		// Get the optional fact list
		$factstring = $this->config('pis_facts');
		$factlist = array();
		if($factstring) {
			foreach(explode(',', $factstring) as $fact) {
				array_push($factlist, trim($fact));
			}
		}
		$fcount = count($factlist);
		
		// Get the optional fact exclusion list
		$factstring = $this->config('pis_factexclude');
		$excludelist = array();
		foreach(explode(',', $factstring) as $fact) {
			array_push($excludelist, trim($fact));
		}
		
		// Put the cert into a tmp file
		$tmpfname = tempnam("/tmp", "dm_puppent_inventory_service");
		$handle = fopen($tmpfname, "w");
		fwrite($handle, $this->config('pis_certificate'));
		fclose($handle);

		$curl = curl_init($url);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURLOPT_SSLCERT, $tmpfname);
		curl_setopt($curl, CURLOPT_HEADER, 0);
		$extraHeaders = array(
                              'Accept: yaml',
                              );
		curl_setopt($curl, CURLOPT_HTTPHEADER, $extraHeaders);
		
		$response = curl_exec($curl);
		unlink($tmpfname);
		
		if(curl_errno($curl)) {
			return ("API Server connection error: " . curl_error($curl));
		}
		curl_close($curl);
		
		// Parse response into a key/value array of facts
		$lines = explode("\n", $response);
		$data = array();
		
		foreach($lines as $line) {
			if(preg_match('/^\s+([\w_]+):\s*(.*)$/', $line, $matches)) {
				if(!$fcount or in_array($matches[1], $factlist)) {
					$data[$matches[1]] = $matches[2];
				}
			}
		}
		
		// Delete anything from the exclusion list
		foreach($excludelist as $exclude) {
			unset($data[$exclude]);
		}
		
		if(!count($data)) {
			return $response;
		}
		// Sort keys and render results into an HTML table
		$keys = array_keys($data);
		sort($keys);
		$returnval = "<table>";
		foreach($keys as $key) {
			$output = trim($data[$key], "\"");
			$output = wordwrap($output, 50, "<br />\n", true);
			$returnval .= "<tr onMouseOver=\"this.bgColor='#eeeeee'\" onMouseOut=\"this.bgColor='white'\"><td>$key</td><td style=\"font-family: monospace; padding-left: 15px;\">$output</td></tr>";
		}
		$returnval .= "</table>";
		return $returnval;
	}
	
	/**
	 * Device Module Configuration Items
	 *
	 * This function returns an array of configuration options that will be 
	 * displayed when the module is configured in the Device Types section of 
	 * setup & admin.
	 *
	 * @return array
	 **/
	function config_items()
	{
		return array(
			'pis_baseurl'	=> array(
				'label' => uber_i18n('Puppet Inventory Service URL'),
				'type' => 'text',
				'size' => 35,
				'default' => 'https://puppet:8140/production/facts',
			),
			'pis_facts' => array(
				'label' => uber_i18n('Fact Names to Display (Comma separated names, blank for all)'),
				'type' => 'text',
				'size' => 35,
				'default' => '',
			),
			'pis_factexclude' => array(
				'label' => uber_i18n('Fact Names to Exclude (Comma separated names, blank for none)'),
				'type' => 'text',
				'size' => 35,
				'default' => '',
			),
			'pis_certificate' => array(
				'label' => uber_i18n('Certificate'),
				'type' => 'textarea',
				'rows' => 20,
				'cols' => 35,
				'default' => '',
			),
		);
	}

}

// end of script