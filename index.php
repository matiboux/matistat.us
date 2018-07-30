<?php
/*\
|*|  MATIBOUX STATUS API
|*|  
|*|  The API that powers https://matistat.us/
|*|  
|*|  Author: Matiboux <matiboux@gmail.com>
|*|            → Website: https://matiboux.com/
|*|            → Github: https://github.com/matiboux/
|*|  
|*|  License: MIT License
|*|  Copyright: 2018 Matiboux
|*|  
|*|  Open Source project published on Github:
|*|  https://github.com/matiboux/matistat.us
\*/


/** --- --- --- */
/**  I. Config  */
/** --- --- --- */

/** The API base URL
    Change if your website isn't hosted on the (sub)domain root directory.
    Format: "matiboux.com/status/" (no protocol, and note the ending slash) */
$baseurl = null; // Like $baseurl = 'matiboux.com/';
// $baseurl = 'matiboux.com/status/';

/** Timeout for a request on service */
$timeout = 10;


/** ---- ---- ---- */
/**  II. Services  */
/** ---- ---- ---- */

// List of supported Services in this API:
// - Domains:  - 
//             - 
// - Apps: - 
//         - 

/** List of supported services, and the url to ping */
$supportedServices = array(
	'matiboux.com' => 'https://matiboux.com/',
	'matistat.us' => 'https://matistat.us/',
	'imgshot.eu' => 'https://imgshot.eu/',
	'urwebst.it' => 'https://urwebst.it/',
	'olifw.net' => 'https://olifw.net/',
	'ljv.fr' => 'https://ljv.fr/'
);

/** Aliases, and Groups */
$aliases = array(
	'domains' => [
		'matiboux.com',
		'matistat.us',
		'imgshot.eu',
		'urwebst.it',
		'olifw.net',
		'ljv.fr'
	],
	'apps' => [
		
	]
);


/** --- -- --- */
/**  III. API  */
/** --- -- --- */

/** Is there a script error? */
$success = false;

/** The services specifically requested
    Values: "all" or list of services (array). */
$services = null;

/** The resulted status for all services */
$statuses = [];

/** The JSON response of the API */
$response = array(
	'success' => &$success, // Might change if an error occurs.
	'services' => &$services, // $services value
	'statuses' => &$statuses // All services status
);


/** The raw URL parameter for specific services request */
$urlParam = null;

// If $baseurl isn't empty, it must match and start with HTTP_HOST.
if(!empty($baseurl) AND strpos($baseurl, $_SERVER['HTTP_HOST']) === 0) {
	// Fetch the parameter from the URL.
	$urlParam = substr($_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'], strlen($baseurl));
	
	// If an error occurred, unset $urlParam.
	// (most likely $baseurl didn't match the actual URL)
	if($urlParam === false) $urlParam = null;
}

// If $urlParam is null, rely on the (sub)domain root directory and remove the starting "/".
if($urlParam === null) $urlParam = substr($_SERVER['REQUEST_URI'], 1) ?: null;

if(empty($urlParam) OR $urlParam == 'all') $services = array_keys($supportedServices);
else {
	// Set the requested services.
	$services = json_decode($urlParam, true) ?: $urlParam;
	
	// Convert into an array, if not already.
	if(!is_array($services)) {
		if(!empty($aliases[$services])) $services = $aliases[$services];
		else $services = [$services];
	}
}


/** cURL Request Options */
$options = array(
	CURLOPT_FRESH_CONNECT  => true, // New connection, no cache.
	CURLOPT_FOLLOWLOCATION => true, // Follow Redirects
	CURLOPT_USERAGENT      => 'MatibouxStatus', // Agent Identity
	CURLOPT_CONNECTTIMEOUT => $timeout, // Connection Timeout
	CURLOPT_TIMEOUT        => $timeout, // Response Timeout
	CURLOPT_MAXREDIRS      => 5, // Stop after 5 redirection
	CURLOPT_RETURNTRANSFER => true, // Return Request Content
	CURLOPT_NOBODY         => true, // Ignore Page Content
	CURLOPT_SSL_VERIFYPEER => false, // Disable SSL certification checks
);

// Fetch services to test, and their data
$listServices = array_intersect_key($supportedServices, array_flip($services));

if(!empty($listServices)) {
	$success = true;
	foreach($listServices as $name => $url) {
		// Give PHP some more time to execute
		set_time_limit($timeout <= 10 ? $timeout * 2 : $timeout + 10);
		
		// Create the resource and Set options
		$resource = curl_init($url);
		curl_setopt_array($resource, $options);
		
		$content = curl_exec($resource); // Request Returned Content
		$errno = curl_errno($resource); // Request Error Code
		// $errmsg = curl_error($resource); // Request Error Message
		$header = curl_getinfo($resource); // Request Returned Headers
		
		$statuses[$name] = array(
			'success' => $errno === 0,
			'url' => $header['url'],
			'ip' => $header['primary_ip'],
			'port' => $header['primary_port'],
			'content' => $content,
			'up' => $header['http_code'] >= 200 AND $header['http_code'] < 300,
			'http_code' => $header['http_code'],
			'time' => array(
				'total' => $header['total_time'],
				'namelookup' => $header['namelookup_time'],
				'connect' => $header['connect_time'],
				'pretransfer' => $header['pretransfer_time'],
				'starttransfer' => $header['starttransfer_time'],
				'redirect' => $header['redirect_time']
			)
		);
		
		// Close the resource
		curl_close($resource);
	}
} else $success = false;

/** Return API Response */
echo json_encode($response);
exit;
?>