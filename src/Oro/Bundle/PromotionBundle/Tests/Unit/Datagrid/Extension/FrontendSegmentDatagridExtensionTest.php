<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Datagrid\Extension;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\FrontendBundle\Datagrid\Extension\FrontendDatagridExtension;
use Oro\Bundle\FrontendBundle\Request\FrontendHelper;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\PromotionBundle\Datagrid\Extension\FrontendSegmentDatagridExtension;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class FrontendSegmentDatagridExtensionTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var TokenStorageInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $tokenStorage;

    /**
     * @var FrontendHelper|\PHPUnit\Framework\MockObject\MockObject
     */
    private $frontendHelper;

    /**
     * @var FrontendSegmentDatagridExtension
     */
    private $extension;

    protected function setUp(): void
    {
        $this->tokenStorage = $this->createMock(TokenStorageInterface::class);
        $this->frontendHelper = $this->createMock(FrontendHelper::class);
        $this->extension = new FrontendSegmentDatagridExtension(
            $this->tokenStorage,
            $this->frontendHelper
        );
    }

    public function testIsApplicableIfNotSegmentGrid()
    {
        $datagridConfig = DatagridConfiguration::createNamed('test_grid', []);

        self::assertFalse($this->extension->isApplicable($datagridConfig));
    }

    public function testIsApplicableIfSegmentGridButNoProductQuery()
    {
        $datagridConfig = DatagridConfiguration::createNamed(Segment::GRID_PREFIX . 777, []);

        self::assertFalse($this->extension->isApplicable($datagridConfig));
    }

    public function testIsApplicableIfSegmentGrid()
    {
        $datagridConfig = DatagridConfiguration::createNamed(
            Segment::GRID_PREFIX . 777,
            [
                'source' => [
                    'type' => OrmDatasource::TYPE,
                    'query' => [
                        'from' => [
                            [
                                'table' => Product::class,
                                'alias' => 'alias'
                            ]
                        ]
                    ]
                ]
            ]
        );

        self::assertTrue($this->extension->isApplicable($datagridConfig));
    }

    public function testProcessConfigsWhenNoFrontendRequest()
    {
        $datagridConfig = DatagridConfiguration::createNamed(Segment::GRID_PREFIX . 777, []);
        $this->frontendHelper->expects($this->once())
            ->method('isFrontendRequest')
            ->willReturn(false);

        $this->extension->processConfigs($datagridConfig);
        self::assertNull($datagridConfig->offsetGetByPath(FrontendDatagridExtension::FRONTEND_OPTION_PATH));
    }

    public function testProcessConfigsWhenNoFrontendRequestAndNoFrontendGrid()
    {
        $datagridConfig = DatagridConfiguration::createNamed(
            Segment::GRID_PREFIX . 777,
            ['options' => ['frontend' => false]]
        );
        $this->frontendHelper->expects($this->once())
            ->method('isFrontendRequest')
            ->willReturn(false);

        $this->extension->processConfigs($datagridConfig);
        self::assertFalse($datagridConfig->offsetGetByPath(FrontendDatagridExtension::FRONTEND_OPTION_PATH));
    }

    public function testProcessConfigsWhenFrontendRequest()
    {
        $datagridConfig = DatagridConfiguration::createNamed(Segment::GRID_PREFIX . 777, []);
        $this->frontendHelper->expects($this->once())
            ->method('isFrontendRequest')
            ->willReturn(true);

        $this->extension->processConfigs($datagridConfig);
        self::assertTrue($datagridConfig->offsetGetByPath(FrontendDatagridExtension::FRONTEND_OPTION_PATH));
    }
}
