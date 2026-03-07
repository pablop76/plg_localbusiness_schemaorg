<?php

/**
 * @package     Joomla.Plugin
 * @subpackage  Schemaorg.localbusiness
 */

namespace Joomla\Plugin\Schemaorg\LocalBusiness\Extension;

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
     * Ensure the LocalBusiness option exists and load its form.
     */
    public function onSchemaPrepareForm(PrepareFormEvent $event): void
    {
        $form = $event->getForm();
        
        // This context is usually com_content.article
        if (!$this->isSupported($form->getName())) {
            return;
        }

        $this->loadLanguage();

        // Add to dropdown list manually
        $schemaType = $form->getField('schemaType', 'schema');
        if ($schemaType) {
            $schemaType->addOption(Text::_('PLG_SCHEMAORG_LOCALBUSINESS_LABEL'), ['value' => 'LocalBusiness']);
        }

        // Direct path for stability, as Joomla's trait might look for a different folder
        $xmlPath = JPATH_PLUGINS . '/schemaorg/localbusiness/forms/schemaorg.xml';
        if (is_file($xmlPath)) {
            $form->loadFile($xmlPath);
        }
    }

    /**
     * Output data generation.
     */
    public function onSchemaBeforeCompileHead(BeforeCompileHeadEvent $event): void
    {
        $schema = $event->getSchema();
        $graph  = $schema->get('@graph');

        foreach ($graph as &$entry) {
            if (!isset($entry['@type']) || $entry['@type'] !== 'LocalBusiness') {
                continue;
            }

            // Google Validator Fix
            if (isset($entry['isPartOf'])) {
                $entry['mainEntityOfPage'] = $entry['isPartOf'];
                unset($entry['isPartOf']);
            }

            // Absolute URLs for images
            if (!empty($entry['image'])) {
                $entry['image'] = $this->ensureAbsoluteUrl($this->prepareImage($entry['image']));
            }
            if (!empty($entry['logo'])) {
                $entry['logo'] = $this->ensureAbsoluteUrl($this->prepareImage($entry['logo']));
            }

            // Social Media
            if (!empty($entry['sameAs']) && is_array($entry['sameAs'])) {
                $urls = [];
                foreach ($entry['sameAs'] as $social) {
                    if (!empty($social['url'])) {
                        $urls[] = $social['url'];
                    }
                }
                $entry['sameAs'] = $urls;
            }

            // Opening hours
            if (isset($entry['openingHours']) && is_string($entry['openingHours'])) {
                $hours = explode("\n", str_replace("\r", "", $entry['openingHours']));
                $entry['openingHours'] = array_values(array_filter(array_map('trim', $hours)));
            }

            // Coordinates
            if (!empty($entry['geo']) && is_array($entry['geo'])) {
                if (isset($entry['geo']['latitude'])) {
                    $entry['geo']['latitude'] = (float) $entry['geo']['latitude'];
                }
                if (isset($entry['geo']['longitude'])) {
                    $entry['geo']['longitude'] = (float) $entry['geo']['longitude'];
                }
            }

            // Booleans
            $boolFields = ['hasDriveThroughService', 'publicAccess', 'smokingAllowed'];
            foreach ($boolFields as $field) {
                if (isset($entry[$field])) {
                    $entry[$field] = (bool) $entry[$field];
                }
            }

            // AggregateRating
            if (!empty($entry['aggregateRating']) && is_array($entry['aggregateRating'])) {
                if (empty($entry['aggregateRating']['ratingValue']) && empty($entry['aggregateRating']['ratingCount'])) {
                    unset($entry['aggregateRating']);
                } else {
                    if (isset($entry['aggregateRating']['ratingValue'])) {
                        $entry['aggregateRating']['ratingValue'] = (float) $entry['aggregateRating']['ratingValue'];
                    }
                    if (isset($entry['aggregateRating']['bestRating'])) {
                        $entry['aggregateRating']['bestRating'] = (float) $entry['aggregateRating']['bestRating'];
                    }
                    if (isset($entry['aggregateRating']['ratingCount'])) {
                        $entry['aggregateRating']['ratingCount'] = (int) $entry['aggregateRating']['ratingCount'];
                    }
                }
            }
        }

        $schema->set('@graph', $graph);
    }

    private function ensureAbsoluteUrl($url)
    {
        if (empty($url)) return $url;
        if (!preg_match('#^(https?:)?//#i', $url)) {
            return Uri::root() . ltrim($url, '/');
        }
        return $url;
    }
}
