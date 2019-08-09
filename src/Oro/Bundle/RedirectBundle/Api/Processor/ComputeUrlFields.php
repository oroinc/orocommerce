<?php

namespace Oro\Bundle\RedirectBundle\Api\Processor;

use Doctrine\ORM\Query\Expr\Join;
use Oro\Bundle\ApiBundle\Processor\CustomizeLoadedData\CustomizeLoadedDataContext;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\LocaleBundle\DependencyInjection\Configuration;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\RedirectBundle\Entity\Slug;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Computes values of "url" and "urls" fields for a sluggable entity.
 */
class ComputeUrlFields implements ProcessorInterface
{
    private const URL_FIELD  = 'url';
    private const URLS_FIELD = 'urls';

    /** @var DoctrineHelper */
    private $doctrineHelper;

    /** @var LocalizationHelper */
    private $localizationHelper;

    /** @var ConfigManager */
    private $configManager;

    /**
     * @param DoctrineHelper     $doctrineHelper
     * @param LocalizationHelper $localizationHelper
     * @param ConfigManager      $configManager
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        LocalizationHelper $localizationHelper,
        ConfigManager $configManager
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->localizationHelper = $localizationHelper;
        $this->configManager = $configManager;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var CustomizeLoadedDataContext $context */

        $data = $context->getData();

        $isUrlFieldRequested = $context->isFieldRequestedForCollection(self::URL_FIELD, $data);
        $isUrlsFieldRequested = $context->isFieldRequestedForCollection(self::URLS_FIELD, $data);
        if (!$isUrlFieldRequested && !$isUrlsFieldRequested) {
            return;
        }

        $currentLocalizationId = $this->localizationHelper->getCurrentLocalization()->getId();
        $enabledLocalizationIds = $this->getEnabledLocalizationIds();
        $ownerEntityClass = $this->doctrineHelper->getManageableEntityClass(
            $context->getClassName(),
            $context->getConfig()
        );
        $ownerEntityIdFieldName = $this->doctrineHelper->getSingleEntityIdentifierFieldName($ownerEntityClass);
        $ownerIdFieldName = $context->getResultFieldName($ownerEntityIdFieldName);

        $urls = $this->loadUrls(
            $ownerEntityClass,
            $ownerEntityIdFieldName,
            $context->getIdentifierValues($data, $ownerIdFieldName),
            $enabledLocalizationIds
        );
        foreach ($data as $key => $item) {
            $ownerId = $item[$ownerIdFieldName];
            if (empty($urls[$ownerId])) {
                $currentUrl = null;
                $otherUrls = [];
            } else {
                list($currentUrl, $otherUrls) = $this->getUrlFieldsData(
                    $urls[$ownerId],
                    $enabledLocalizationIds,
                    $currentLocalizationId
                );
            }

            if ($isUrlFieldRequested) {
                $data[$key][self::URL_FIELD] = $currentUrl;
            }
            if ($isUrlsFieldRequested) {
                $data[$key][self::URLS_FIELD] = $otherUrls;
            }
        }

        $context->setData($data);
    }

    /**
     * @return int[]
     */
    private function getEnabledLocalizationIds(): array
    {
        return $this->configManager->get(Configuration::getConfigKeyByName(Configuration::ENABLED_LOCALIZATIONS));
    }

    /**
     * @param array $urls [[url, localization id], ...]
     * @param int[] $localizationIds
     * @param int   $currentLocalizationId
     *
     * @return array [url for current localization, urls for other localizations]
     */
    private function getUrlFieldsData(array $urls, array $localizationIds, int $currentLocalizationId): array
    {
        $currentUrl = null;
        $otherUrls = [];

        $notLocalizedUrl = null;
        $foundLocalizations = [];
        foreach ($urls as list($url, $localizationId)) {
            if (null === $localizationId) {
                $notLocalizedUrl = $url;
            } else {
                $foundLocalizations[$localizationId] = true;
                if ($localizationId === $currentLocalizationId) {
                    $currentUrl = $url;
                } else {
                    $otherUrls[] = $this->getUrlData($url, $localizationId);
                }
            }
        }
        if (!isset($foundLocalizations[$currentLocalizationId])) {
            $foundLocalizations[$currentLocalizationId] = true;
        }
        if ($notLocalizedUrl) {
            foreach ($localizationIds as $localizationId) {
                if (!isset($foundLocalizations[$localizationId])) {
                    $otherUrls[] = $this->getUrlData($notLocalizedUrl, $localizationId);
                }
            }
        }

        return [$currentUrl ?? $notLocalizedUrl, $otherUrls];
    }

    /**
     * @param string $url
     * @param int    $localizationId
     *
     * @return array
     */
    private function getUrlData(string $url, int $localizationId): array
    {
        return [
            'url'    => $url,
            'localizationId' => (string)$localizationId
        ];
    }

    /**
     * @param string $ownerEntityClass
     * @param string $ownerEntityIdFieldName
     * @param array  $ownerIds
     * @param array  $localizationIds
     *
     * @return array [owner id => [[url, localization id], ...], ...]
     */
    private function loadUrls(
        string $ownerEntityClass,
        string $ownerEntityIdFieldName,
        array $ownerIds,
        array $localizationIds
    ): array {
        $rows = $this->doctrineHelper
            ->createQueryBuilder(Slug::class, 's')
            ->select(sprintf(
                's.url, IDENTITY(s.localization) AS locId, owner.%s AS ownerId',
                $ownerEntityIdFieldName
            ))
            ->innerJoin($ownerEntityClass, 'owner', Join::WITH, 's MEMBER OF owner.slugs')
            ->where('owner IN (:ownerIds) AND (s.localization IN (:locIds) OR s.localization IS NULL)')
            ->setParameter('ownerIds', $ownerIds)
            ->setParameter('locIds', $localizationIds)
            ->getQuery()
            ->getArrayResult();

        $result = [];
        foreach ($rows as $row) {
            $localizationId = $row['locId'];
            if (null !== $localizationId) {
                $localizationId = (int)$localizationId;
            }
            $result[$row['ownerId']][] = [$row['url'], $localizationId];
        }

        return $result;
    }
}
