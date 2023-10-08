<?php

// SSLCommerz configuration

$apiDomain = env('SSLCZ_TESTMODE') ? "https://sandbox.sslcommerz.com" : "https://securepay.sslcommerz.com";
return [
	'apiCredentials' => [
		'store_id' => env("SSLCZ_STORE_ID"),
		'store_password' => env("SSLCZ_STORE_PASSWORD"),
	],
	'apiUrl' => [
		'make_payment' => "/gwprocess/v4/api.php",
		'transaction_status' => "/validator/api/merchantTransIDvalidationAPI.php",
		'order_validate' => "/validator/api/validationserverAPI.php",
		'refund_payment' => "/validator/api/merchantTransIDvalidationAPI.php",
		'refund_status' => "/validator/api/merchantTransIDvalidationAPI.php",
	],
	'apiDomain' => $apiDomain,
	'connect_from_localhost' => env("IS_LOCALHOST", false), // For Sandbox, use "true", For Live, use "false"
	'success_url' => '/payment-response/success',
	'failed_url' => '/payment-response/fail',
	'cancel_url' => '/payment-response/cancel',
	'ipn_url' => '/payment-response/ipn',
	'application_success_url' => env('SSLCZ_SUCCESS_URL'),
	'application_failed_url' => env('SSLCZ_FAILED_URL'),
	'application_cancel_url' => env('SSLCZ_CANCEL_URL'),
];
