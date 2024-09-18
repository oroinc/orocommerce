<?php

namespace Oro\Bundle\CMSBundle\Layout\DataProvider;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CMSBundle\ContentBlock\ContentBlockResolver;
use Oro\Bundle\CMSBundle\ContentBlock\Model\ContentBlockView;
use Oro\Bundle\CMSBundle\Entity\ContentBlock;
use Oro\Bundle\LayoutBundle\Layout\Extension\ThemeConfiguration;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\ThemeBundle\Provider\ThemeConfigurationProvider;
use Psr\Log\LoggerInterface;

/**
 * Layout data provider for Content Blocks.
 * Add possibility to get appropriate `Oro\Bundle\CMSBundle\ContentBlock\Model\ContentBlockView`
 * according Content Block alias.
 */
class ContentBlockDataProvider
{
    public function __construct(
        private ContentBlockResolver $contentBlockResolver,
        private ManagerRegistry $doctrine,
        private ScopeManager $scopeManager,
        private ThemeConfigurationProvider $themeConfigurationProvider,
        private AclHelper $aclHelper,
        private LoggerInterface $logger,
        private string $scopeType
    ) {
    }

    public function getPromotionalBlockAlias(): string
    {
        $configValue = $this->themeConfigurationProvider->getThemeConfigurationOption(
            ThemeConfiguration::buildOptionKey('header', 'promotional_content')
        );
        if (!$configValue) {
            return '';
        }

        return $this->doctrine->getRepository(ContentBlock::class)
            ->getContentBlockAliasById($configValue, $this->aclHelper) ?? '';
    }

    public function hasContentBlockView(string $alias): bool
    {
        return null !== $this->getContentBlock($alias);
    }

    public function getContentBlockView(string $alias): ?ContentBlockView
    {
        $contentBlock = $this->getContentBlock($alias);
        if (null === $contentBlock) {
            return null;
        }

        $contentBlockView = $this->contentBlockResolver->getContentBlockViewByCriteria(
            $contentBlock,
            $this->scopeManager->getCriteria($this->scopeType)
        );
        if (!$contentBlockView) {
            $this->logger->notice('Content block with alias "{alias}" is not visible to user.', ['alias' => $alias]);

            return null;
        }

        return $contentBlockView;
    }

    private function getContentBlock(string $alias): ?ContentBlock
    {
        return $this->doctrine->getRepository(ContentBlock::class)->findOneBy(['alias' => $alias]);
    }
}
