<?php

namespace Mundipagg\Core\Kernel\Abstractions;

use Mundipagg\Core\Kernel\Aggregates\Configuration;
use Mundipagg\Core\Kernel\Repositories\ConfigurationRepository;
use ReflectionClass;

abstract class AbstractModuleCoreSetup
{
    const CONCRETE_MODULE_CORE_SETUP_CLASS = 0;

    const CONCRETE_CART_DECORATOR_CLASS = 10;
    const CONCRETE_DATABASE_DECORATOR_CLASS = 11;
    const CONCRETE_PLATFORM_ORDER_DECORATOR_CLASS = 12;
    const CONCRETE_PLATFORM_INVOICE_DECORATOR_CLASS = 13;
    const CONCRETE_PLATFORM_CREDITMEMO_DECORATOR_CLASS = 14;
    const CONCRETE_PRODUCT_DECORATOR_CLASS = 15;

    const CONCRETE_DATA_SERVICE = 100;

    const CONCRETE_FORMAT_SERVICE = 1000;

    protected static $moduleVersion;
    protected static $platformVersion;
    protected static $logPath;
    protected static $instance;
    protected static $config;
    protected static $platformRoot;
    protected static $moduleConcreteDir;
    /**
     *
     * @var Configuration
     */
    protected static $moduleConfig;
    /**
     *
     * @var string
     */
    protected static $dashboardLanguage;
    /**
     *
     * @var string
     */
    protected static $storeLanguage;

    /**
     *
     * @return mixed
     */
    public static function getPlatformRoot()
    {
        return static::$platformRoot;
    }

    /**
     *
     * @param  mixed $platformRoot
     * @throws \Exception
     */
    public static function bootstrap($platformRoot = null)
    {
        if (static::$instance === null) {
            static::$instance = new static();
            static::$instance->setConfig();
            static::$instance->setModuleVersion();
            static::$instance->setPlatformVersion();
            static::$instance->setLogPath();
            static::$config[self::CONCRETE_MODULE_CORE_SETUP_CLASS] = static::class;

            static::$platformRoot = $platformRoot;

            static::updateModuleConfiguration();
        }
    }

    protected static function updateModuleConfiguration()
    {
        $configurationRepository = new ConfigurationRepository;

        static::$instance->loadModuleConfiguration();

        if (static::$moduleConfig->getId() !== null) {
            return true;
        }

        if (self::getDefaultConfigSaved() === null) {
            static::$moduleConfig->setStoreId(static::getDefaultStoreId());
            $configurationRepository->save(static::$moduleConfig);
            static::$moduleConfig->setStoreId(static::getCurrentStoreId());
        }

        if(static::$moduleConfig->getStoreId() != static::getDefaultStoreId()) {
            static::$moduleConfig->setParentConfiguration(self::getDefaultConfigSaved());
            static::$moduleConfig->setId(null);
        }

        $configurationRepository->save(static::$moduleConfig);
    }

    /**
     * @return Configuration|null
     */
    private static function getDefaultConfigSaved()
    {
        $configurationRepository = new ConfigurationRepository;

        return $configurationRepository->findByStore(
            static::getDefaultStoreId()
        );
    }

    /**
     *
     * @return Configuration
     */
    public static function getModuleConfiguration()
    {
        return static::$moduleConfig;
    }

    public static function setModuleConfiguration(Configuration $moduleConfig)
    {
        static::$moduleConfig = $moduleConfig;
    }

    public static function get($configId)
    {
        self::bootstrap();

        if (!isset(static::$config[$configId])) {
            throw new Exception("Configuration $configId wasn't set!");
        }

        return static::$config[$configId];
    }

    public static function getAll()
    {
        self::bootstrap();

        return static::$config;
    }

    public static function getHubAppPublicAppKey()
    {
        $moduleCoreSetupClass = self::get(self::CONCRETE_MODULE_CORE_SETUP_CLASS);
        return $moduleCoreSetupClass::getPlatformHubAppPublicAppKey();
    }

    public static function getDatabaseAccessDecorator()
    {
        $concreteCoreSetupClass = self::get(self::CONCRETE_MODULE_CORE_SETUP_CLASS);
        $DBDecoratorClass = $concreteCoreSetupClass::get(self::CONCRETE_DATABASE_DECORATOR_CLASS);

        return new $DBDecoratorClass($concreteCoreSetupClass::getDatabaseAccessObject());
    }

    public static function getModuleVersion()
    {
        return self::$moduleVersion;
    }

    public static function getPlatformVersion()
    {
        return self::$platformVersion;
    }

    public static function getLogPath()
    {
        return self::$logPath;
    }

    public static function getDashboardLanguage()
    {
        return self::$instance->_getDashboardLanguage();
    }
    public static function getStoreLanguage()
    {
        return self::$instance->_getStoreLanguage();
    }

    public static function formatToCurrency($price)
    {
        return self::$instance->_formatToCurrency($price);
    }

    public static function getModuleConcreteDir()
    {
        if (isset(self::$moduleConcreteDir)) {
            return self::$moduleConcreteDir;
        }

        $concretePlatformCoreSetupClass = self::get(self::CONCRETE_MODULE_CORE_SETUP_CLASS);

        $moduleCoreSetupReflection = new ReflectionClass($concretePlatformCoreSetupClass);
        $concreteCoreSetupFilename = $moduleCoreSetupReflection->getFileName();
        $concreteDir = explode(DIRECTORY_SEPARATOR, $concreteCoreSetupFilename);
        array_pop($concreteDir);

        self::$moduleConcreteDir = implode(DIRECTORY_SEPARATOR, $concreteDir);

        return self::$moduleConcreteDir;
    }

    public static function setModuleConcreteDir($concreteModuleDir)
    {
        if(!isset(self::$moduleConcreteDir)) {
            self::$moduleConcreteDir = $concreteModuleDir;
        }
    }

    abstract protected static function setConfig();
    abstract protected static function loadModuleConfiguration();
    abstract protected static function setModuleVersion();
    abstract protected static function setPlatformVersion();
    abstract protected static function setLogPath();
    abstract public static function getDatabaseAccessObject();
    /**
     *
     * @return string
     **/
    abstract protected static function getPlatformHubAppPublicAppKey();
    abstract protected static function _getDashboardLanguage();
    abstract protected static function _getStoreLanguage();
    abstract protected static function _formatToCurrency($price);

    /**
     * @since 1.5.1
     */
    abstract protected static function getCurrentStoreId();

    /**
     * @since 1.5.1
     */
    abstract public static function getDefaultStoreId();
}

