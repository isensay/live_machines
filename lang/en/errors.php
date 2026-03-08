<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Http exaption pages language lines
    |--------------------------------------------------------------------------
    |
    */

    'pre_title'      => 'Error',

    'button_to_main' => 'Return Home',

    'support_line'   => 'Contact support',

    'errors' =>
    [

	    401 =>
	    [
		    'title' => 'Unauthorized',
		    'text'  => 'Oops! Something went wrong. Don\'t worry...<br />Authorization is required to access this resource.<br />If you are already logged in, you may not have the necessary rights.',
		],

		403 =>
	    [
		    'title' => 'Forbidden',
		    'text'  => 'It\'s looking like you may have taken a wrong turn. Don\'t worry... it happens to the best of us.<br />Access to this page is denied!<br />Try to go back to the main page',
		],

		404 =>
	    [
		    'title' => 'Page Not Found',
		    'text'  => 'It\'s looking like you may have taken a wrong turn. Don\'t worry... it happens to the best of us.<br />The page you requested may be unavailable or may have moved to a new address.',
		],

		419 =>
	    [
		    'title' => 'Page expired',
		    'text'  => 'It\'s looking like you may have taken a wrong turn. Don\'t worry... it happens to the best of us.<br />The resource you are requesting may be out of date.',
		],

		429 =>
	    [
		    'title' => 'Too Many Sessions',
		    'text'  => 'We\'ve noticed you have multiple active sessions. For security reasons, we limit each IP address to simultaneous sessions within a 24-hour period.',
			'button'=> 'Try again',
		],

		500 =>
	    [
		    'title' => 'Internal Server Error',
		    'text'  => 'Oops! Something went wrong. Don\'t worry...<br />We are already working on fixing the error.<br />Please try again later.',
		],

		503 =>
	    [
		    'title' => 'Service Temporarily Unavailable',
		    'text'  => 'Oops! Something went wrong. Don\'t worry...<br />We are already working on fixing the error.<br />Please try again later.',
		],

		503 => // For maintenance
	    [
		    'title'        => 'Service Temporarily Unavailable',
		    'page_title'   => 'We are currently performing maintenance',
		    'text'         => 'We\'re making the system more awesome. We\'ll be back shortly.',
		    'descriptions' =>
		    [
		    	'left' =>
		    	[
		    		'title' => 'Why is the site down?',
		    		'text'  => 'There are many variations of passages of Lorem Ipsum available, but the majority have suffered alteration.',
		    	],
		    	'center' =>
		    	[
		    		'title' => 'What is the downtime?',
		    		'text'  => 'Contrary to popular belief, Lorem Ipsum is not simply random text. It has roots in a piece of classical but the majority.',
		    	],
		    	'right' =>
		    	[
		    		'title' => 'Do you need support?',
		    		'text'  => 'If you are going to use a passage of Lorem Ipsum, you need to be sure there isn\'t anything embar.. <a href="mailto:#">support@adoxa.ru</a>',
		    	],
		    ],
		],

	]

];
