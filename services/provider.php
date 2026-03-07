<?php

/**
 * @package     Joomla.Plugin
 * @subpackage  Schemaorg.localbusiness
 */

\defined('_JEXEC') or die;

use Joomla\CMS\Extension\PluginInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;

// Try to resolve file path for manual loading
$jpathFile = JPATH_PLUGINS . '/schemaorg/localbusiness/src/Extension/LocalBusiness.php';
if (!file_exists($jpathFile)) {
    $jpathFile = JPATH_PLUGINS . '/schemaorg/plg_localbusiness_schemaorg/src/Extension/LocalBusiness.php';
}

if (file_exists($jpathFile)) {
    require_once $jpathFile;
}

// Ensure the class name used is exact match to what's in LocalBusiness.php
use Joomla\Plugin\Schemaorg\LocalBusiness\Extension\LocalBusiness;

return new class () implements ServiceProviderInterface {
    public function register(Container $container)
    {
        $container->set(
            PluginInterface::class,
            function (Container $container) {
                // Return an instance of the class
                $element = 'localbusiness';
                $pluginData = (array) PluginHelper::getPlugin('schemaorg', $element);
                
                if (empty($pluginData)) {
                    $element = 'plg_localbusiness_schemaorg';
                    $pluginData = (array) PluginHelper::getPlugin('schemaorg', $element);
                }

                $plugin = new LocalBusiness($pluginData);
                $plugin->setApplication(Factory::getApplication());

                return $plugin;
            }
        );
    }
};
