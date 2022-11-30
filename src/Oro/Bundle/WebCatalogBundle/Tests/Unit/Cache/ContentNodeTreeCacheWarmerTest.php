<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\Cache;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\WebCatalogBundle\Async\Topic\WebCatalogCalculateCacheTopic;
use Oro\Bundle\WebCatalogBundle\Cache\ContentNodeTreeCacheWarmer;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\Testing\Unit\EntityTrait;

class ContentNodeTreeCacheWarmerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    private MessageProducerInterface|\PHPUnit\Framework\MockObject\MockObject $messageProducer;

    private DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject $doctrineHelper;

    private ConfigManager|\PHPUnit\Framework\MockObject\MockObject $configManager;

    private ContentNodeTreeCacheWarmer $warmer;

    protected function setUp(): void
    {
        $this->messageProducer = $this->createMock(MessageProducerInterface::class);
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->warmer = new ContentNodeTreeCacheWarmer(
            $this->messageProducer,
            $this->doctrineHelper,
            $this->configManager
        );
    }

    public function testIsOptional(): void
    {
        self::assertTrue($this->warmer->isOptional());
    }

    public function testWarmUp(): void
    {
        $website1 = $this->getEntity(Website::class, ['id' => 1]);
        $website2 = $this->getEntity(Website::class, ['id' => 2]);

        $repository = $this->createMock(EntityRepository::class);
        $repository->expects(self::once())
            ->method('findAll')
            ->willReturn([$website1, $website2]);
        $this->doctrineHelper->expects(self::once())
            ->method('getEntityRepository')
            ->with(Website::class)
            ->willReturn($repository);

        $this->configManager->expects(self::once())
            ->method('getValues')
            ->with('oro_web_catalog.web_catalog', [$website1, $website2])
            ->willReturn([1 => 3, 2 => 4]);

        $this->messageProducer->expects(self::exactly(2))
            ->method('send')
            ->withConsecutive(
                [WebCatalogCalculateCacheTopic::getName(), [WebCatalogCalculateCacheTopic::WEB_CATALOG_ID => 3]],
                [WebCatalogCalculateCacheTopic::getName(), [WebCatalogCalculateCacheTopic::WEB_CATALOG_ID => 4]]
            );

        $this->warmer->warmUp(__DIR__);
    }
}
