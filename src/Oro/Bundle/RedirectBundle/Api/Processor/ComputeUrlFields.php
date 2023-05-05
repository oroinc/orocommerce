<?php

namespace Oro\Bundle\RedirectBundle\Api\Processor;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ApiBundle\Processor\CustomizeLoadedData\CustomizeLoadedDataContext;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\LocaleBundle\DependencyInjection\Configuration;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Component\Routing\UrlUtil;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Computes values of "url" and "urls" fields for a sluggable entity.
 */
class ComputeUrlFields implements ProcessorInterface
{
    protected DoctrineHelper $doctrineHelper;
    protected LocalizationHelper $localizationHelper;
    protected ConfigManager $configManager;
    protected UrlGeneratorInterface $urlGenerator;
    protected string $urlField;
    protected string $urlsField;

    public function __construct(
        DoctrineHelper $doctrineHelper,
        LocalizationHelper $localizationHelper,
        ConfigManager $configManager,
        UrlGeneratorInterface $urlGenerator,
        string $urlField = 'url',
        string $urlsField = 'urls'
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->localizationHelper = $localizationHelper;
        $this->configManager = $configManager;
        $this->urlGenerator = $urlGenerator;
        $this->urlField = $urlField;
        $this->urlsField = $urlsField;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var CustomizeLoadedDataContext $context */

        $data = $context->getData();

        $isUrlFieldRequested = $context->isFieldRequestedForCollection($this->urlField, $data);
        $isUrlsFieldRequested = $context->isFieldRequestedForCollection($this->urlsField, $data);
        if (!$isUrlFieldRequested && !$isUrlsFieldRequested) {
            return;
        }

        $config = $context->getConfig();
        $ownerEntityClass = $this->doctrineHelper->getManageableEntityClass($context->getClassName(), $config);
        $ownerEntityIdFieldName = $this->doctrineHelper->getSingleEntityIdentifierFieldName($ownerEntityClass);
        $ownerIdFieldName = $context->getResultFieldName($ownerEntityIdFieldName);

        $currentLocalizationId = $this->localizationHelper->getCurrentLocalization()->getId();
        $enabledLocalizationIds = $this->getEnabledLocalizationIds();
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
                [$currentUrl, $otherUrls] = $this->getUrlFieldsData(
                    $urls[$ownerId],
                    $enabledLocalizationIds,
                    $currentLocalizationId
                );
            }

            if ($isUrlFieldRequested) {
                $data[$key][$this->urlField] = $currentUrl;
            }
            if ($isUrlsFieldRequested) {
                $data[$key][$this->urlsField] = $otherUrls;
            }
        }

        $context->setData($data);
    }

    /**
     * @return int[]
     */
    protected function getEnabledLocalizationIds(): array
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
    protected function getUrlFieldsData(array $urls, array $localizationIds, int $currentLocalizationId): array
    {
        $currentUrl = null;
        $otherUrls = [];

        $notLocalizedUrl = null;
        $foundLocalizations = [];
        foreach ($urls as [$url, $localizationId]) {
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

    protected function getUrlData(string $url, int $localizationId): array
    {
        return [
            'url'            => $url,
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
    protected function loadUrls(
        string $ownerEntityClass,
        string $ownerEntityIdFieldName,
        array $ownerIds,
        array $localizationIds
    ): array {
        $qb = $this->getQueryForLoadUrls($ownerEntityClass, $ownerEntityIdFieldName, $ownerIds, $localizationIds);

        $result = [];
        $baseUrl = $this->urlGenerator->getContext()->getBaseUrl();
        $rows = $qb->getQuery()->getArrayResult();
        foreach ($rows as $row) {
            $localizationId = $row['localizationId'];
            if (null !== $localizationId) {
                $localizationId = (int)$localizationId;
            }
            $result[$row['ownerId']][] = [
                UrlUtil::getAbsolutePath((string)$row['url'], $baseUrl),
                $localizationId
            ];
        }

        return $result;
    }

    /**
     * The query should contain 3 fields: url, localizationId and ownerId.
     */
    protected function getQueryForLoadUrls(
        string $ownerEntityClass,
        string $ownerEntityIdFieldName,
        array $ownerIds,
        array $localizationIds
    ): QueryBuilder {
        return $this->doctrineHelper
            ->createQueryBuilder($ownerEntityClass, 'owner')
            ->select(sprintf(
                's.url, IDENTITY(s.localization) AS localizationId, owner.%s AS ownerId',
                $ownerEntityIdFieldName
            ))
            ->innerJoin('owner.slugs', 's')
            ->where('owner IN (:ownerIds) AND (s.localization IN (:localizationIds) OR s.localization IS NULL)')
            ->setParameter('ownerIds', $ownerIds)
            ->setParameter('localizationIds', $localizationIds);
    }
}
