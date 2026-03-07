<?php

/**
 * @package     Joomla.Plugin
 * @subpackage  Schemaorg.localbusiness
 */

namespace Joomla\Plugin\Schemaorg\PlgLocalbusinessSchemaorg\Extension;

use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Schemaorg\SchemaorgPluginTrait;
use Joomla\CMS\Schemaorg\SchemaorgPrepareImageTrait;
use Joomla\CMS\Event\Plugin\System\Schemaorg\BeforeCompileHeadEvent;
use Joomla\CMS\Event\Plugin\System\Schemaorg\PrepareFormEvent;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
use Joomla\Event\Priority;
use Joomla\Event\SubscriberInterface;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Schemaorg LocalBusiness Plugin
 */
final class LocalBusiness extends CMSPlugin implements SubscriberInterface
{
    use SchemaorgPluginTrait;
    use SchemaorgPrepareImageTrait;

    protected $autoloadLanguage = true;

    /**
     * The name of the schema type.
     */
    protected $pluginName = 'LocalBusiness';

    /**
     * Returns an array of events this subscriber will listen to.
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'onSchemaPrepareForm'       => 'onSchemaPrepareForm',
            'onSchemaBeforeCompileHead' => ['onSchemaBeforeCompileHead', Priority::BELOW_NORMAL],
        ];
    }

    /**
     * Added extra logging/direct insertion to ensure field visibility.
     */
    public function onSchemaPrepareForm(PrepareFormEvent $event): void
    {
        $form = $event->getForm();
        
        // Load language for translations
        $this->loadLanguage();
        
        // FOR DEBUG: We skip isSupported check to ensure it at least appears
        // if (!$this->isSupported($form->getName())) { return; }

        // 1. Manually add the option to the Schema Type list
        $schemaType = $form->getField('schemaType', 'schema');
        if ($schemaType) {
            $schemaType->addOption(Text::_('PLG_SCHEMAORG_LOCALBUSINESS_LABEL'), ['value' => 'LocalBusiness']);
        }

        // 2. Load the form fields from the XML file
        $xmlPath = dirname(__DIR__, 2) . '/forms/schemaorg.xml';
        if (is_file($xmlPath)) {
            $form->loadFile($xmlPath, true);
        }
    }

    /**
     * Handle the output generation.
     */
    public function onSchemaBeforeCompileHead(BeforeCompileHeadEvent $event): void
    {
        $schema = $event->getSchema();
        $graph  = $schema->get('@graph');

        foreach ($graph as &$entry) {
            if (!isset($entry['@type']) || $entry['@type'] !== 'LocalBusiness') {
                continue;
            }

            if (isset($entry['isPartOf'])) {
                $entry['mainEntityOfPage'] = $entry['isPartOf'];
                unset($entry['isPartOf']);
            }

            if (!empty($entry['image'])) {
                $entry['image'] = $this->ensureAbsoluteUrl($this->prepareImage($entry['image']));
            }

            if (!empty($entry['logo'])) {
                $entry['logo'] = $this->ensureAbsoluteUrl($this->prepareImage($entry['logo']));
            }

            if (!empty($entry['sameAs']) && is_array($entry['sameAs'])) {
                $urls = [];
                foreach ($entry['sameAs'] as $social) {
                    if (!empty($social['url'])) {
                        $urls[] = $social['url'];
                    }
                }
                $entry['sameAs'] = $urls;
            }

            if (isset($entry['openingHours']) && is_string($entry['openingHours'])) {
                $hours = explode("\n", str_replace("\r", "", $entry['openingHours']));
                $entry['openingHours'] = array_values(array_filter(array_map('trim', $hours)));
            }
        }

        $schema->set('@graph', $graph);
    }

    private function ensureAbsoluteUrl($url)
    {
        if (empty($url)) { return $url; }
        if (!preg_match('#^(https?:)?//#i', $url)) {
            return Uri::root() . ltrim($url, '/');
        }
        return $url;
    }
}
