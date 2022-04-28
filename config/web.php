<?php

$params = require __DIR__ . '/params.php';
$db = require __DIR__ . '/db.php';

$config = [
    'id' => 'basic',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log'],
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm'   => '@vendor/npm-asset',
    ],
    'components' => [
        'request' => [
            // !!! insert a secret key in the following (if it is empty) - this is required by cookie validation
            'cookieValidationKey' => 'pChpKOuaF2ZdmRuNknBP095OPh96XbdE',
            'parsers' => [
                'application/json' => 'yii\web\JsonParser',
//                'multipart/form-data' => 'yii\web\MultipartFormDataParser'
            ]
        ],
        'cache' => [
            'class' => 'yii\caching\FileCache',
        ],
        'user' => [
            'identityClass' => 'app\models\User',
            'enableAutoLogin' => true,
        ],
        'errorHandler' => [
            'errorAction' => 'site/error',
        ],

        'i18n' => [
            'translations' => [
                '*' => [
                    'class' => 'yii\i18n\PhpMessageSource',
                    'basePath' => '@app/messages', // if advanced application, set @frontend/messages
                    'sourceLanguage' => 'es_ES',
                    'fileMap' => [
                        //'main' => 'main.php',
                    ],
                ],
            ],
            
        ],
        /************** Componente interoperable *************/
        'lugar'=> [
            'class' => $params['servicioLugar'],//'app\components\ServicioLugar'
        ],
        'registral'=> [
            'class' => $params['servicioRegistral'],//'app\components\ServicioLugar'
        ],
        /************* Fin Componente interoperable *************/
        'mailer' => [
            'class' => 'yii\swiftmailer\Mailer',
            // send all mails to a file by default. You have to set
            // 'useFileTransport' to false and configure a transport
            // for the mailer to send real emails.
            'useFileTransport' => true,
        ],
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                ],
            ],
        ],
        'db' => $db,
        
        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'enableStrictParsing' => false,
            'rules' => [
                [   #Permiso
                    'class' => 'yii\rest\UrlRule',
                    'controller' => 'api/permiso', 
                ],
                [   
                    'class' => 'yii\rest\UrlRule',
                    'controller' => 'api/categoria',
                    'extraPatterns' => [
                        'PUT set-activo/{id}' => 'set-activo',
                        'OPTIONS set-activo/{id}' => 'set-activo',
                    ],
                ],
                [   
                    'class' => 'yii\rest\UrlRule',
                    'controller' => 'api/comprobante',
                    'extraPatterns' => [
                        'PUT editar-observacion/{id}' => 'editar-observacion',
                        'OPTIONS editar-observacion/{id}' => 'editar-observacion',
                        'PUT registrar-producto-faltante/{id}' => 'registrar-producto-faltante',
                        'OPTIONS registrar-producto-faltante/{id}' => 'registrar-producto-faltante',
                        'PUT set-producto-faltante/{id}' => 'set-producto-faltante',
                        'OPTIONS set-producto-faltante/{id}' => 'set-producto-faltante',
                        'PUT aprobar/{id}' => 'aprobar',
                        'OPTIONS aprobar/{id}' => 'aprobar',
                    ], 
                ],
                [   
                    'class' => 'yii\rest\UrlRule',
                    'controller' => 'api/deposito'
                ],
                [   
                    'class' => 'yii\rest\UrlRule',
                    'controller' => 'api/egreso'
                ],
                [   
                    'class' => 'yii\rest\UrlRule',
                    'controller' => 'api/tipo-egreso'
                ],
                [   
                    'class' => 'yii\rest\UrlRule',
                    'controller' => 'api/inventario',
                    'extraPatterns' => [
                        'POST set-defectuoso' => 'set-defectuoso',
                        'OPTIONS set-defectuoso' => 'set-defectuoso'
                    ], 
                ],
                [   
                    'class' => 'yii\rest\UrlRule',
                    'controller' => 'api/marca'
                ],
                [   
                    'class' => 'yii\rest\UrlRule',
                    'controller' => 'api/producto',
                    'extraPatterns' => [
                        'PUT set-activo/{id}' => 'set-activo',
                        'OPTIONS set-activo/{id}' => 'set-activo',
                    ],
                ],
                [   
                    'class' => 'yii\rest\UrlRule',
                    'controller' => 'api/proveedor',
                    'extraPatterns' => [
                        'PUT set-activo/{id}' => 'set-activo',
                        'OPTIONS set-activo/{id}' => 'set-activo',
                    ],
                ],
                [   
                    'class' => 'yii\rest\UrlRule',
                    'controller' => 'api/unidad-medida',
                    'extraPatterns' => [
                        'PUT set-activo/{id}' => 'set-activo',
                        'OPTIONS set-activo/{id}' => 'set-activo',
                    ],
                ],
                [   #Permiso
                    'class' => 'yii\rest\UrlRule',
                    'controller' => 'api/permiso', 
                ],
                [   #Rol
                    'class' => 'yii\rest\UrlRule',
                    'controller' => 'api/rol', 
                ],
                /****** USUARIOS *******/
                [   #Usuario
                    'class' => 'yii\rest\UrlRule',
                    'controller' => 'api/usuario',   
                    'extraPatterns' => [
                        'POST login' => 'login',
                        'OPTIONS login' => 'options',
                        'OPTIONS listar-asignacion/{id}' => 'listar-asignacion',
                        'GET listar-asignacion/{id}' => 'listar-asignacion',
                        'OPTIONS crear-asignacion' => 'crear-asignacion',
                        'POST crear-asignacion' => 'crear-asignacion',
                        'OPTIONS borrar-asignacion' => 'borrar-asignacion',
                        'POST borrar-asignacion' => 'borrar-asignacion',
                        'OPTIONS baja/{id}' => 'baja',
                        'PUT baja/{id}' => 'baja',
                        'OPTIONS buscar-persona-por-cuil/{cuil}' => 'buscar-persona-por-cuil',
                        'GET buscar-persona-por-cuil/{cuil}' => 'buscar-persona-por-cuil',
                    ],
                    'tokens' => ['{id}'=>'<id:\\w+>', '{cuil}'=>'<cuil:\\w+>'],                       
                ],  
                ##### Interoperabilidad con Lugar#####
                [   #Localidad
                    'class' => 'yii\rest\UrlRule',
                    'controller' => 'api/localidad', 
                ],
                 ##### Interoperabilidad con Registral #####
                [   #persona
                    'class' => 'yii\rest\UrlRule',
                    'controller' => 'api/persona', 
                    'extraPatterns' => [
                        'GET buscar-por-documento/{nro_documento}' => 'buscar-por-documento',
                        'OPTIONS buscar-por-documento/{nro_documento}' => 'buscar-por-documento',
                        'PUT contacto/{id}' => 'contacto',
                        'OPTIONS contacto/{id}' => 'contacto',
                    ],
                    'tokens' => [ '{id}' => '<id:\\w+>', '{nro_documento}'=>'<nro_documento:\\w+>' ],
                ],
            ]
        ],
        
    ],
    'params' => $params,

    #Modules
    'modules'=>[
        'api' => [
            'class' => 'app\modules\api\Api',
        ],
        "audit"=>[
            "class"=>"bedezign\yii2\audit\Audit",
            "ignoreActions" =>['audit/*', 'debug/*'],
            'userIdentifierCallback' => ['app\components\ServicioUsuarios', 'userIdentifierCallback'],
            'userFilterCallback' => ['app\components\ServicioUsuarios', 'userFilterCallback'],
            'accessIps'=>null,
            'accessUsers'=>null,
            'accessRoles'=>['admin']
        ],
        'user' => [
            'class' => 'dektrium\user\Module',
            'enableConfirmation'=> false,
            'enableRegistration'=> false,
            'enablePasswordRecovery'=> false,
            'admins'=>['admin']
        ],
        'rbac' => 'dektrium\rbac\RbacWebModule',
    ],
];

if (YII_ENV_DEV) {
    // configuration adjustments for 'dev' environment
    $config['bootstrap'][] = 'debug';
    $config['modules']['debug'] = [
        'class' => 'yii\debug\Module',
        // uncomment the following to add your IP if you are not connecting from localhost.
        //'allowedIPs' => ['127.0.0.1', '::1'],
    ];

    $config['bootstrap'][] = 'gii';
    $config['modules']['gii'] = [
        'class' => 'yii\gii\Module',
        'allowedIPs' => ['*'],
        // uncomment the following to add your IP if you are not connecting from localhost.
        //'allowedIPs' => ['127.0.0.1', '::1'],
    ];
}

return $config;
