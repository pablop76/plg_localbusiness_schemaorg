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
use function array_filter;
use function array_keys;
use function array_map;
use function array_values;
use function explode;
use function in_array;
use function is_array;
use function is_string;
use function preg_match;
use function str_contains;
use function str_replace;
use function strlen;
use function trim;
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

        // Direct path for stability
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
            if (isset($entry['@type']) && $entry['@type'] === 'BreadcrumbList') {
                $entry = $this->sanitizeBreadcrumbList($entry);
            }

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

            if (isset($entry['openingHours']) && is_string($entry['openingHours'])) {
                $hours = explode("\n", str_replace("\r", "", $entry['openingHours']));
                $entry['openingHours'] = array_values(array_filter(array_map('trim', $hours)));
            }

            if (isset($entry['sameAs'])) {
                $sameAs = $this->normalizeSameAs($entry['sameAs']);

                if (empty($sameAs)) {
                    unset($entry['sameAs']);
                } else {
                    $entry['sameAs'] = $sameAs;
                }
            }
        }

        $schema->set('@graph', $graph);
    }

    /**
     * Remove invalid breadcrumb items and enforce sequential positions.
     */
    private function sanitizeBreadcrumbList(array $entry): array
    {
        if (empty($entry['itemListElement']) || !is_array($entry['itemListElement'])) {
            return $entry;
        }

        $items    = [];
        $position = 0;

        foreach ($entry['itemListElement'] as $listItem) {
            if (!is_array($listItem) || empty($listItem['item']) || !is_array($listItem['item'])) {
                continue;
            }

            $item = $listItem['item'];
            $name = trim((string) ($item['name'] ?? ''));

            if ($name === '') {
                continue;
            }

            $normalizedItem = ['name' => $name];

            if (!empty($item['@id']) && is_string($item['@id'])) {
                $normalizedItem['@id'] = trim($item['@id']);
            }

            $items[] = [
                '@type'    => 'ListItem',
                'position' => ++$position,
                'item'     => $normalizedItem,
            ];
        }

        if (empty($items)) {
            unset($entry['itemListElement']);

            return $entry;
        }

        $entry['itemListElement'] = $items;

        return $entry;
    }

    private function ensureAbsoluteUrl($url)
    {
        if (empty($url)) return $url;
        if (!preg_match('#^(https?:)?//#i', $url)) {
            return Uri::root() . ltrim($url, '/');
        }
        return $url;
    }

    /**
     * Convert various form payload formats into a valid sameAs URL list.
     */
    private function normalizeSameAs($sameAs): array
    {
        if (is_string($sameAs)) {
            $sameAs = explode("\n", str_replace("\r", '', $sameAs));
        }

        if (!is_array($sameAs)) {
            return [];
        }

        $urls = [];

        foreach ($sameAs as $value) {
            if (is_string($value)) {
                $this->appendSameAsUrl($urls, $value);
                continue;
            }

            if (!is_array($value)) {
                continue;
            }

            if (!empty($value['url']) && is_string($value['url'])) {
                $this->appendSameAsUrl($urls, $value['url']);
                continue;
            }

            // Some repeatable subform payloads may include control keys (e.g. cancel/@type).
            foreach ($value as $key => $candidate) {
                if (!is_string($candidate)) {
                    continue;
                }

                if (in_array($key, ['cancel', '@type'], true) || str_contains((string) $key, 'sameAs')) {
                    continue;
                }

                $this->appendSameAsUrl($urls, $candidate);
            }
        }

        return array_values(array_keys($urls));
    }

    private function appendSameAsUrl(array &$urls, string $candidate): void
    {
        $candidate = trim($candidate);

        if ($candidate === '' || strlen($candidate) < 8) {
            return;
        }

        if (!preg_match('#^https?://#i', $candidate)) {
            return;
        }

        $urls[$candidate] = true;
    }
}
