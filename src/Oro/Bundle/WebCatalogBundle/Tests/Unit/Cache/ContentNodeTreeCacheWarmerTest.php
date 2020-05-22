<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\Cache;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\WebCatalogBundle\Async\Topics;
use Oro\Bundle\WebCatalogBundle\Cache\ContentNodeTreeCacheWarmer;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\Testing\Unit\EntityTrait;

class ContentNodeTreeCacheWarmerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /**
     * @var MessageProducerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $messageProducer;

    /**
     * @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject
     */
    private $doctrineHelper;

    /**
     * @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject
     */
    private $configManager;

    /**
     * @var ContentNodeTreeCacheWarmer
     */
    private $warmer;

    /**
     * {@inheritdoc}
     */
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

    public function testIsOptional()
    {
        $this->assertTrue($this->warmer->isOptional());
    }

    public function testWarmUp()
    {
        $website1 = $this->getEntity(Website::class, ['id' => 1]);
        $website2 = $this->getEntity(Website::class, ['id' => 2]);

        $repository = $this->createMock(EntityRepository::class);
        $repository->expects($this->once())
            ->method('findAll')
            ->willReturn([$website1, $website2]);
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepository')
            ->with(Website::class)
            ->willReturn($repository);

        $this->configManager->expects($this->once())
            ->method('getValues')
            ->with('oro_web_catalog.web_catalog', [$website1, $website2])
            ->willReturn([1 => 3, 2 => 4]);

        $this->messageProducer->expects($this->exactly(2))
            ->method('send')
            ->withConsecutive(
                [Topics::CALCULATE_WEB_CATALOG_CACHE, ['webCatalogId' => 3]],
                [Topics::CALCULATE_WEB_CATALOG_CACHE, ['webCatalogId' => 4]]
            );

        $this->warmer->warmUp(__DIR__);
    }
}
