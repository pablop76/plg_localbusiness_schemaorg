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
use Joomla\Plugin\Schemaorg\LocalBusiness\Extension\LocalBusiness;

return new class () implements ServiceProviderInterface {
    /**
     * Registers the service provider with a DI container.
     *
     * @param   Container  $container  The DI container.
     *
     * @return  void
     */
    public function register(Container $container)
    {
        $container->set(
            PluginInterface::class,
            function (Container $container) {
                // Return the plugin instance
                $plugin = new LocalBusiness(
                    (array) PluginHelper::getPlugin('schemaorg', 'plg_localbusiness_schemaorg')
                );

                $plugin->setApplication(Factory::getApplication());

                return $plugin;
            }
        );
    }
};
