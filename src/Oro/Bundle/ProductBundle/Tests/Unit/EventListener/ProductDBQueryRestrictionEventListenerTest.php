<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\EventListener;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\FrontendBundle\Request\FrontendHelper;
use Oro\Bundle\ProductBundle\Event\ProductDBQueryRestrictionEvent;
use Oro\Bundle\ProductBundle\EventListener\ProductDBQueryRestrictionEventListener;
use Oro\Bundle\ProductBundle\Model\ProductVisibilityQueryBuilderModifier;

class ProductDBQueryRestrictionEventListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    protected $configManager;

    /** @var ProductVisibilityQueryBuilderModifier|\PHPUnit\Framework\MockObject\MockObject */
    protected $modifier;

    /** @var ProductDBQueryRestrictionEvent|\PHPUnit\Framework\MockObject\MockObject */
    protected $event;

    /** @var QueryBuilder|\PHPUnit\Framework\MockObject\MockObject */
    protected $queryBuilder;

    /** @var FrontendHelper|\PHPUnit\Framework\MockObject\MockObject */
    protected $frontendHelper;

    /** @var ProductDBQueryRestrictionEventListener */
    protected $listener;

    protected function setUp(): void
    {
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->modifier = $this->createMock(ProductVisibilityQueryBuilderModifier::class);
        $this->event = $this->createMock(ProductDBQueryRestrictionEvent::class);
        $this->queryBuilder = $this->createMock(QueryBuilder::class);
        $this->frontendHelper = $this->createMock(FrontendHelper::class);

        $this->listener = $this->createListener();
    }

    /**
     * @return ProductDBQueryRestrictionEventListener
     */
    protected function createListener()
    {
        return new ProductDBQueryRestrictionEventListener(
            $this->configManager,
            $this->modifier,
            $this->frontendHelper
        );
    }

    /**
     * @dataProvider onQueryDataProvider
     */
    public function testOnQuery(bool $isFrontend, ?string $frontendPath, ?string $backendPath)
    {
        $statuses = [
            'status1',
            'status2',
        ];

        $this->frontendHelper->expects($this->any())
            ->method('isFrontendRequest')
            ->willReturn($isFrontend);

        $this->event->expects($this->any())
            ->method('getQueryBuilder')
            ->willReturn($this->queryBuilder);

        if ($isFrontend && $frontendPath) {
            $this->configManager->expects($this->once())
                ->method('get')
                ->with($frontendPath)
                ->willReturn($statuses);

            $this->modifier->expects($this->once())
                ->method('modifyByInventoryStatus')
                ->with($this->queryBuilder, $statuses);
        } elseif (!$isFrontend && $backendPath) {
            $this->configManager->expects($this->once())
                ->method('get')
                ->with($backendPath)
                ->willReturn($statuses);

            $this->modifier->expects($this->once())
                ->method('modifyByInventoryStatus')
                ->with($this->queryBuilder, $statuses);
        } else {
            $this->modifier->expects($this->never())
                ->method('modifyByInventoryStatus')
                ->with($this->queryBuilder, $statuses);
        }

        $this->listener->setFrontendSystemConfigurationPath($frontendPath);
        $this->listener->setBackendSystemConfigurationPath($backendPath);

        $this->listener->onDBQuery($this->event);
    }

    public function onQueryDataProvider(): array
    {
        return [
            [
                'isFrontend' => false,
                'frontendPath' => 'frontend_path',
                'backendPath' => 'backend_path',
            ],
            [
                'isFrontend' => false,
                'frontendPath' => null,
                'backendPath' => 'backend_path',
            ],
            [
                'isFrontend' => true,
                'frontendPath' => 'frontend_path',
                'backendPath' => 'backend_path',
            ],
            [
                'isFrontend' => true,
                'frontendPath' => 'frontend_path',
                'backendPath' => null,
            ],
            [
                'isFrontend' => false,
                'frontendPath' => 'frontend_path',
                'backendPath' => null,
            ],
            [
                'isFrontend' => true,
                'frontendPath' => null,
                'backendPath' => 'backend_path',
            ]
        ];
    }

    public function testSystemConfigurationPathEmpty()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(
            'SystemConfigurationPath not configured for ProductDBQueryRestrictionEventListener'
        );

        $this->listener->setFrontendSystemConfigurationPath(null);
        $this->listener->setBackendSystemConfigurationPath(null);

        $this->listener->onDBQuery($this->event);
    }
}
