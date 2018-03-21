<?php

namespace Oro\Bundle\SEOBundle\EventListener;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\WebsiteBundle\Provider\AbstractWebsiteLocalizationProvider;
use Oro\Bundle\WebsiteSearchBundle\Engine\IndexDataProvider;
use Oro\Bundle\WebsiteSearchBundle\Event\IndexEntityEvent;
use Oro\Bundle\WebsiteSearchBundle\Manager\WebsiteContextManager;
use Oro\Bundle\WebsiteSearchBundle\Placeholder\LocalizationIdPlaceholder;

abstract class AbstractSEOSearchIndexListener
{
    /**
     * @var DoctrineHelper
     */
    private $doctrineHelper;

    /**
     * @var AbstractWebsiteLocalizationProvider
     */
    private $websiteLocalizationProvider;

    /**
     * @var WebsiteContextManager
     */
    private $websiteContextManager;

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param AbstractWebsiteLocalizationProvider $websiteLocalizationProvider
     * @param WebsiteContextManager $websiteContextManager
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        AbstractWebsiteLocalizationProvider $websiteLocalizationProvider,
        WebsiteContextManager $websiteContextManager
    ) {
        $this->doctrineHelper              = $doctrineHelper;
        $this->websiteLocalizationProvider = $websiteLocalizationProvider;
        $this->websiteContextManager       = $websiteContextManager;
    }

    /**
     * @return DoctrineHelper
     */
    protected function getDoctrineHelper()
    {
        return $this->doctrineHelper;
    }

    /**
     * @param Product $entity
     * @param Localization $localization
     * @return array
     */
    protected function getMetaFieldsForEntity($entity, $localization)
    {
        return [
            $entity->getMetaTitle($localization),
            $entity->getMetaDescription($localization),
            $entity->getMetaKeyword($localization)
        ];
    }

    /**
     * @param IndexEntityEvent $event
     * @param int $entityId
     * @param string $metaField
     * @param int $localizationId
     */
    protected function addPlaceholderToEvent($event, $entityId, $metaField, $localizationId)
    {
        $metaField = $this->cleanUpString($metaField);
        $event->addPlaceholderField(
            $entityId,
            IndexDataProvider::ALL_TEXT_L10N_FIELD,
            $metaField,
            [LocalizationIdPlaceholder::NAME => $localizationId],
            true
        );
    }

    /**
     * @param IndexEntityEvent $event
     */
    public function onWebsiteSearchIndex(IndexEntityEvent $event)
    {
        $websiteId = $this->websiteContextManager->getWebsiteId($event->getContext());
        if (!$websiteId) {
            $event->stopPropagation();

            return;
        }

        $this->process($event, $this->websiteLocalizationProvider->getLocalizationsByWebsiteId($websiteId));
    }

    /**
     * @param IndexEntityEvent $event
     * @param array $localizations
     */
    abstract protected function process(IndexEntityEvent $event, array $localizations);

    /**
     * Cleans up a unicode string from control characters.
     *
     * @param string $string
     * @return string
     */
    private function cleanUpString($string)
    {
        return preg_replace('/[[:cntrl:]]/', '', $string);
    }
}
