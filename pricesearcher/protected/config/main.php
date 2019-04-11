<?php

// uncomment the following to define a path alias
// Yii::setPathOfAlias('local','path/to/local-folder');

// This is the main Web application configuration. Any writable
// CWebApplication properties can be configured here.
return array(
	'basePath'=>dirname(__FILE__).DIRECTORY_SEPARATOR.'..',
	'name'=>'Pricesearcher',

	// preloading 'log' component
	'preload'=>array('log'),

	// autoloading model and component classes
	'import'=>array(
		'application.models.*',
		'application.components.*',
	),

	'modules'=>array(
		'gii'=>array(
			'class'=>'system.gii.GiiModule',
			'password'=>'Nak3d1adygii',
			// If removed, Gii defaults to localhost only. Edit carefully to taste.
			'ipFilters'=>array('127.0.0.1','::1'),
		),
        'api',
	),

	// application components
	'components'=>array(
        'user'=>array(
            'class' => 'WebUser',
        ),

		// uncomment the following to enable URLs in path-format
		'urlManager'=>array(
			'urlFormat'=>'path',
			'rules'=>array(
				'<controller:\w+>/<id:\d+>'=>'<controller>/view',
				'<controller:\w+>/<action:\w+>/<id:\d+>'=>'<controller>/<action>',
				'<controller:\w+>/<action:\w+>'=>'<controller>/<action>',
			),
			'showScriptName'=>false,
		),

        /*
        'db'=>array(
                'connectionString' => 'mysql:host=10.16.16.4;dbname=willo-eig-u-171187',
                'emulatePrepare' => true,
                'enableProfiling'=>true,
                'enableParamLogging' => true,
                'username' => 'willo-eig-u-171187',
                'password' => 'NB/rtzzCw',
                'charset' => 'utf8',
            ),
        */

		'db'=>array(
	            'connectionString' => 'mysql:host=127.0.0.1;dbname=shareling',
	            'emulatePrepare' => true,
	            'enableProfiling'=>true,
	            'enableParamLogging' => true,
	            'username' => 'root',
	            'password' => '',
	            'charset' => 'utf8',
	        ),

		'errorHandler'=>array(
			// use 'site/error' action to display errors
			'errorAction'=>YII_DEBUG ? null : 'site/error',
		),

		'log'=>array(
			'class'=>'CLogRouter',
			'routes'=>array(
				array(
					'class'=>'CFileLogRoute',
					'levels'=>'error, warning',
				),
				// uncomment the following to show log messages on web pages
				/*
				array(
					'class'=>'CWebLogRoute',
				),
				*/
			),
		),

	),

	// application-level parameters that can be accessed
	// using Yii::app()->params['paramName']
	'params'=>array(
		// this is used in contact page
		'adminEmail'=>'webmaster@example.com',
	),
);
