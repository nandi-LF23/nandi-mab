<?php return array (
  'barryvdh/laravel-dompdf' => 
  array (
    'providers' => 
    array (
      0 => 'Barryvdh\\DomPDF\\ServiceProvider',
    ),
    'aliases' => 
    array (
      'PDF' => 'Barryvdh\\DomPDF\\Facade\\Pdf',
    ),
  ),
  'enlightn/laravel-security-checker' => 
  array (
    'providers' => 
    array (
      0 => 'Enlightn\\LaravelSecurityChecker\\ServiceProvider',
    ),
  ),
  'facade/ignition' => 
  array (
    'providers' => 
    array (
      0 => 'Facade\\Ignition\\IgnitionServiceProvider',
    ),
    'aliases' => 
    array (
      'Flare' => 'Facade\\Ignition\\Facades\\Flare',
    ),
  ),
  'fideloper/proxy' => 
  array (
    'providers' => 
    array (
      0 => 'Fideloper\\Proxy\\TrustedProxyServiceProvider',
    ),
  ),
  'fruitcake/laravel-cors' => 
  array (
    'providers' => 
    array (
      0 => 'Fruitcake\\Cors\\CorsServiceProvider',
    ),
  ),
  'laravel/passport' => 
  array (
    'providers' => 
    array (
      0 => 'Laravel\\Passport\\PassportServiceProvider',
    ),
  ),
  'laravel/tinker' => 
  array (
    'providers' => 
    array (
      0 => 'Laravel\\Tinker\\TinkerServiceProvider',
    ),
  ),
  'macsidigital/laravel-oauth2-client' => 
  array (
    'providers' => 
    array (
      0 => 'MacsiDigital\\OAuth2\\Providers\\OAuth2ServiceProvider',
    ),
    'aliases' => 
    array (
    ),
  ),
  'nesbot/carbon' => 
  array (
    'providers' => 
    array (
      0 => 'Carbon\\Laravel\\ServiceProvider',
    ),
  ),
  'nunomaduro/collision' => 
  array (
    'providers' => 
    array (
      0 => 'NunoMaduro\\Collision\\Adapters\\Laravel\\CollisionServiceProvider',
    ),
  ),
  'php-mqtt/laravel-client' => 
  array (
    'providers' => 
    array (
      0 => 'PhpMqtt\\Client\\MqttClientServiceProvider',
    ),
    'aliases' => 
    array (
      'MQTT' => 'PhpMqtt\\Client\\Facades\\MQTT',
    ),
  ),
  'rap2hpoutre/laravel-log-viewer' => 
  array (
    'providers' => 
    array (
      0 => 'Rap2hpoutre\\LaravelLogViewer\\LaravelLogViewerServiceProvider',
    ),
  ),
  'tormjens/eventy' => 
  array (
    'providers' => 
    array (
      0 => 'TorMorten\\Eventy\\EventServiceProvider',
      1 => 'TorMorten\\Eventy\\EventBladeServiceProvider',
    ),
    'aliases' => 
    array (
      'Eventy' => 'TorMorten\\Eventy\\Facades\\Events',
    ),
  ),
  'zanysoft/laravel-zip' => 
  array (
    'providers' => 
    array (
      0 => 'ZanySoft\\Zip\\ZipServiceProvider',
    ),
    'aliases' => 
    array (
      'Zip' => 'ZanySoft\\Zip\\ZipFacade',
    ),
  ),
);