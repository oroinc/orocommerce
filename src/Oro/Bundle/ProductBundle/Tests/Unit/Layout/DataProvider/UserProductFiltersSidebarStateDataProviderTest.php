<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Layout\DataProvider;

use Oro\Bundle\ProductBundle\Layout\DataProvider\UserProductFiltersSidebarStateDataProvider;
use Oro\Bundle\ProductBundle\Manager\UserProductFiltersSidebarStateManager;

class UserProductFiltersSidebarStateDataProviderTest extends \PHPUnit\Framework\TestCase
{
    private UserProductFiltersSidebarStateManager|\PHPUnit\Framework\MockObject\MockObject
        $userProductFiltersSidebarStateManager;

    private UserProductFiltersSidebarStateDataProvider $dataProvider;

    protected function setUp(): void
    {
        $this->userProductFiltersSidebarStateManager = $this->createMock(
            UserProductFiltersSidebarStateManager::class
        );

        $this->dataProvider = new UserProductFiltersSidebarStateDataProvider(
            $this->userProductFiltersSidebarStateManager
        );
    }

    /**
     * @dataProvider getIsProductFiltersSidebarExpandedDataProvider
     */
    public function testIsProductFiltersSidebarExpanded(bool $isSidebarExpanded): void
    {
        $this->userProductFiltersSidebarStateManager
            ->expects(self::once())
            ->method('isProductFiltersSidebarExpanded')
            ->willReturn($isSidebarExpanded);

        self::assertEquals(
            $isSidebarExpanded,
            $this->dataProvider->isProductFiltersSidebarExpanded()
        );
    }

    /**
     * @dataProvider getIsProductFiltersSidebarExpandedDataProvider
     */
    public function testIsProductFiltersSidebarCollapsed(bool $isSidebarExpanded): void
    {
        $this->userProductFiltersSidebarStateManager
            ->expects(self::once())
            ->method('isProductFiltersSidebarExpanded')
            ->willReturn($isSidebarExpanded);

        self::assertEquals(
            !$isSidebarExpanded,
            $this->dataProvider->isProductFiltersSidebarCollapsed()
        );
    }

    public function getIsProductFiltersSidebarExpandedDataProvider(): array
    {
        return [
            [
                'isSidebarExpanded' => false,
            ],
            [
                'isSidebarExpanded' => true,
            ],
        ];
    }
}
