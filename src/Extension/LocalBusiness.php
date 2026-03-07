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
     * The name of the schema form.
     *
     * @var   string
     */
    protected $pluginName = 'LocalBusiness';

    /**
     * Returns an array of events this subscriber will listen to.
     *
     * @return  array
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'onSchemaPrepareForm'       => 'onSchemaPrepareForm',
            'onSchemaBeforeCompileHead' => ['onSchemaBeforeCompileHead', Priority::BELOW_NORMAL],
        ];
    }

    /**
     * Cleanup and process LocalBusiness schema data before it is rendered.
     *
     * @param   BeforeCompileHeadEvent  $event  The event object.
     *
     * @return  void
     */
    public function onSchemaBeforeCompileHead(BeforeCompileHeadEvent $event): void
    {
        $schema = $event->getSchema();
        $graph  = $schema->get('@graph');

        foreach ($graph as &$entry) {
            if (!isset($entry['@type']) || $entry['@type'] !== 'LocalBusiness') {
                continue;
            }

            // Fix for Google Validator: isPartOf is not valid for LocalBusiness
            if (isset($entry['isPartOf'])) {
                $entry['mainEntityOfPage'] = $entry['isPartOf'];
                unset($entry['isPartOf']);
            }

            // Process image and logo - ensure absolute URLs
            if (!empty($entry['image'])) {
                $entry['image'] = $this->ensureAbsoluteUrl($this->prepareImage($entry['image']));
            }

            if (!empty($entry['logo'])) {
                $entry['logo'] = $this->ensureAbsoluteUrl($this->prepareImage($entry['logo']));
            }

            // Process sameAs (Social Media)
            if (!empty($entry['sameAs']) && is_array($entry['sameAs'])) {
                $urls = [];
                foreach ($entry['sameAs'] as $social) {
                    if (!empty($social['url'])) {
                        $urls[] = $social['url'];
                    }
                }
                $entry['sameAs'] = $urls;
            }

            // Process opening hours
            if (isset($entry['openingHours']) && is_string($entry['openingHours'])) {
                $hours = explode("\n", str_replace("\r", "", $entry['openingHours']));
                $entry['openingHours'] = array_values(array_filter(array_map('trim', $hours)));
            }

            // Ensure coordinates in geo subform are floats
            if (!empty($entry['geo']) && is_array($entry['geo'])) {
                if (isset($entry['geo']['latitude'])) {
                    $entry['geo']['latitude'] = (float) $entry['geo']['latitude'];
                }
                if (isset($entry['geo']['longitude'])) {
                    $entry['geo']['longitude'] = (float) $entry['geo']['longitude'];
                }
            }

            // Convert booleans
            $boolFields = ['hasDriveThroughService', 'publicAccess', 'smokingAllowed'];
            foreach ($boolFields as $field) {
                if (isset($entry[$field])) {
                    $entry[$field] = (bool) $entry[$field];
                }
            }

            // Process AggregateRating
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

    /**
     * Helper to ensure a URL is absolute.
     */
    private function ensureAbsoluteUrl($url)
    {
        if (empty($url)) {
            return $url;
        }

        if (!preg_match('#^(https?:)?//#i', $url)) {
            return Uri::root() . ltrim($url, '/');
        }

        return $url;
    }
}
