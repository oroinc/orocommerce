<?php

namespace Oro\Bundle\FrontendLocalizationBundle\Api\Processor;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\LocaleBundle\DependencyInjection\Configuration;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Component\DoctrineUtils\ORM\QueryBuilderUtil;

/**
 * Modifies the query for Localization entity to filter not enabled localizations
 * and sort localizations by ID.
 */
class UpdateLocalizationQuery implements ProcessorInterface
{
    private ConfigManager $configManager;

    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var Context $context */

        $query = $context->getQuery();
        if (!$query instanceof QueryBuilder) {
            // unexpected query type
            return;
        }

        $idField = sprintf('%s.id', QueryBuilderUtil::getSingleRootAlias($query));
        $query
            ->andWhere($idField . ' IN (:enabledLocalizationIds)')
            ->setParameter('enabledLocalizationIds', $this->getEnabledLocalizationIds())
            ->orderBy($idField);
    }

    /**
     * @return int[]
     */
    private function getEnabledLocalizationIds(): array
    {
        return $this->configManager->get(Configuration::getConfigKeyByName(Configuration::ENABLED_LOCALIZATIONS));
    }
}
