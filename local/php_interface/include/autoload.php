<?php

use Bitrix\Main\Loader;

// Автозагрузка классов
Loader::registerAutoLoadClasses(null, [
    'lib\Catalog\CColor' => APP_CLASS_FOLDER . 'Catalog/CSomething.php',
]);

Loader::registerAutoLoadClasses(null, [
    'lib\Catalog\CColor' => APP_CLASS_FOLDER . 'Catalog/CSadovikowClass.php',
]);
