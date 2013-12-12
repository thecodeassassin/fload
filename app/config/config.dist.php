<?php
return array(
    'app' => array(
        // if you set this to false, the handler will no longer pick up on errors
        'debug' => false,
        'log.level' => \Slim\Log::DEBUG
    ),

    // settings for the logger
    'log' => array(
        'path' =>  dirname(__FILE__) . '/../log/',
        'name_format' => 'Y-m-d',
        'extension' => 'log',
        'message_format' => '%label% - %date% - %message%'
    ),

    // if you implemented another dataprovider, set it here
    'dataProvider' => array(
        'type' => 'Mongo',
        'host' => '127.0.0.1',
        'port' => 27017,
        'db' => 'fload',
        'options' => array(
            'connectTimeoutMS' => 500,
            'username' => '',
            'password' => ''
        )
    ),

    'tmpDir' => '/tmp',
    'uploadDir' => dirname(__FILE__) . '/../uploads',

    'publicMaxFileSize' => 5242880, // size in bytes

    'secretKey' => 'CHANGEME' // The main secret key (can be added by doing /addkey/<thiskey>)
);
