<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\EventListener;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\FrontendBundle\Request\FrontendHelper;
use Oro\Bundle\ProductBundle\Event\ProductSearchQueryRestrictionEvent;
use Oro\Bundle\ProductBundle\EventListener\ProductSearchQueryRestrictionEventListener;
use Oro\Bundle\ProductBundle\Model\ProductVisibilitySearchQueryModifier;
use Oro\Bundle\SearchBundle\Query\Query;

abstract class AbstractProductSearchQueryRestrictionEventListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    protected $configManager;

    /** @var string */
    protected $frontendConfigPath = '/front/end/cfg/path';

    /** @var ProductVisibilitySearchQueryModifier|\PHPUnit\Framework\MockObject\MockObject */
    protected $modifier;

    /** @var ProductSearchQueryRestrictionEventListener */
    protected $listener;

    /** @var array */
    protected $statuses = ['in_stock'];

    /** @var FrontendHelper|\PHPUnit\Framework\MockObject\MockObject */
    protected $frontendHelper;

    protected function setUp(): void
    {
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->modifier = $this->createMock(ProductVisibilitySearchQueryModifier::class);
        $this->frontendHelper = $this->createMock(FrontendHelper::class);

        $this->listener = $this->createListener();
    }

    protected function configureDependenciesForFrontend()
    {
        $this->frontendHelper->expects($this->atLeastOnce())
            ->method('isFrontendRequest')
            ->willReturn(true);

        $this->modifier->expects($this->once())
            ->method('modifyByInventoryStatus');

        $this->configManager->expects($this->once())
            ->method('get')
            ->with($this->frontendConfigPath)
            ->willReturn($this->statuses);
    }

    protected function configureDependenciesForBackend()
    {
        $this->frontendHelper->expects($this->atLeastOnce())
            ->method('isFrontendRequest')
            ->willReturn(false);

        $this->modifier->expects($this->never())
            ->method('modifyByInventoryStatus');

        $this->configManager->expects($this->never())
            ->method('get')
            ->with($this->frontendConfigPath)
            ->willReturn($this->statuses);
    }

    protected function getEvent(): ProductSearchQueryRestrictionEvent
    {
        return new ProductSearchQueryRestrictionEvent(new Query());
    }

    abstract protected function createListener(): ProductSearchQueryRestrictionEventListener;
}
