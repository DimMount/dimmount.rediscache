<?php
/**
 * Copyright (c) 2017. Dmitry Kozlov. https://github.com/DimMount
 */

IncludeModuleLangFile(__FILE__);

if (class_exists('dimmount_rediscache')) {
    return;
}

class dimmount_rediscache extends CModule
{
    public $MODULE_ID = 'dimmount.rediscache';

    public $MODULE_VERSION;

    public $MODULE_VERSION_DATE;

    public $MODULE_NAME;

    public $MODULE_DESCRIPTION;

    public $PARTNER_NAME;

    public $PARTNER_URI;

    public function __construct()
    {
        $arModuleVersion = [];

        include __DIR__ . '/version.php';

        $this->MODULE_VERSION = $arModuleVersion['VERSION'];
        $this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'];

        $this->MODULE_NAME = GetMessage('DIMMOUNT_REDISCACHE_INSTALL_NAME');
        $this->MODULE_DESCRIPTION = GetMessage('DIMMOUNT_REDISCACHE_INSTALL_DESCRIPTION');
        $this->PARTNER_NAME = GetMessage('DIMMOUNT_REDISCACHE_PARTNER');
        $this->PARTNER_URI = GetMessage('DIMMOUNT_REDISCACHE_PARTNER_URI');
    }

    public function DoInstall()
    {
        global $APPLICATION;
        RegisterModule($this->MODULE_ID);

        $cacheConfig = [
            'type' => [
                'class_name'    => 'CPHPCacheRedis',
                'required_file' => 'modules/' . $this->MODULE_ID . '/include.php'
            ]
        ];

        $config = \Bitrix\Main\Config\Configuration::getInstance();
        $config->addReadonly('cache', $cacheConfig);
        $config->saveConfiguration();

        $APPLICATION->IncludeAdminFile(GetMessage('BARS46_REDISCACHE_INSTALL_MODULE'), __DIR__ . '/step.php');
    }

    public function DoUninstall()
    {
        global $APPLICATION;

        $cacheConfig = [
            'type' => 'files'
        ];

        $config = \Bitrix\Main\Config\Configuration::getInstance();
        $config->addReadonly('cache', $cacheConfig);
        $config->saveConfiguration();

        UnRegisterModule($this->MODULE_ID);
        $APPLICATION->IncludeAdminFile(GetMessage('BARS46_REDISCACHE_UNINSTALL_MODULE'), __DIR__ . '/unstep.php');
    }
}
