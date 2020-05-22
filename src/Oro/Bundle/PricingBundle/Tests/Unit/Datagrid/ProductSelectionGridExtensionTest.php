<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Datagrid;

use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\PricingBundle\Datagrid\ProductSelectionGridExtension;
use Oro\Bundle\PricingBundle\Model\FrontendProductListModifier;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class ProductSelectionGridExtensionTest extends \PHPUnit\Framework\TestCase
{
    /** @var TokenStorageInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $tokenStorage;

    /** @var FrontendProductListModifier|\PHPUnit\Framework\MockObject\MockObject */
    protected $productListModifier;

    /** @var ProductSelectionGridExtension */
    protected $extension;

    /** @var  Request|\PHPUnit\Framework\MockObject\MockObject */
    protected $request;

    protected function setUp(): void
    {
        $this->tokenStorage = $this
            ->createMock('Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface');
        $this->productListModifier = $this
            ->getMockBuilder('Oro\Bundle\PricingBundle\Model\FrontendProductListModifier')
            ->disableOriginalConstructor()
            ->getMock();

        $this->request = $this->createMock('Symfony\Component\HttpFoundation\Request');
        /** @var RequestStack|\PHPUnit\Framework\MockObject\MockObject $requestStack */
        $requestStack = $this->createMock('Symfony\Component\HttpFoundation\RequestStack');
        $requestStack->expects($this->any())->method('getCurrentRequest')->willReturn($this->request);

        $this->extension = new ProductSelectionGridExtension(
            $requestStack,
            $this->tokenStorage,
            $this->productListModifier
        );
        $this->extension->setParameters(new ParameterBag());
    }

    /**
     * @dataProvider applicableDataProvider
     * @param string $gridName
     * @param object|null $token
     * @param bool $expected
     */
    public function testIsApplicable($gridName, $token, $expected)
    {
        /** @var \PHPUnit\Framework\MockObject\MockObject|DatagridConfiguration $config */
        $config = $this->getMockBuilder('Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration')
            ->disableOriginalConstructor()
            ->getMock();
        $config->expects($this->once())
            ->method('getName')
            ->will($this->returnValue($gridName));
        $this->tokenStorage->expects($this->any())
            ->method('getToken')
            ->will($this->returnValue($token));

        $this->assertEquals($expected, $this->extension->isApplicable($config));
    }

    /**
     * @return array
     */
    public function applicableDataProvider()
    {
        return [
            ['test', null, false],
            ['products-select-grid-frontend', $this->getTockenMock(), false],
            ['test', $this->getTockenMock(true), false],
            ['products-select-grid-frontend', $this->getTockenMock(true), true],
        ];
    }

    /**
     * @param bool $getUser
     * @return \PHPUnit\Framework\MockObject\MockObject|TokenInterface
     */
    protected function getTockenMock($getUser = false)
    {
        $tokenMock = $this->createMock('Symfony\Component\Security\Core\Authentication\Token\TokenInterface');

        if ($getUser) {
            $tokenMock->expects($this->any())
                ->method('getUser')
                ->will($this->returnValue(new CustomerUser()));
        }

        return $tokenMock;
    }

    /**
     * @dataProvider visitDatasourceDataProvider
     * @param string $currency
     */
    public function testVisitDatasource($currency)
    {
        if ($currency) {
            $this->request->expects($this->once())
                ->method('get')
                ->with(ProductSelectionGridExtension::CURRENCY_KEY)
                ->willReturn($currency);
        }

        $gridName = 'products-select-grid-frontend';

        $token = $this->createMock('Symfony\Component\Security\Core\Authentication\Token\TokenInterface');
        $token->expects($this->any())
            ->method('getUser')
            ->will($this->returnValue(new CustomerUser()));
        /** @var \PHPUnit\Framework\MockObject\MockObject|DatagridConfiguration $config */
        $config = $this->getMockBuilder('Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration')
            ->disableOriginalConstructor()
            ->getMock();
        $config->expects($this->once())
            ->method('getName')
            ->will($this->returnValue($gridName));
        $this->tokenStorage->expects($this->any())
            ->method('getToken')
            ->will($this->returnValue($token));

        $qb = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()
            ->getMock();

        /** @var \PHPUnit\Framework\MockObject\MockObject|OrmDatasource $dataSource */
        $dataSource = $this->getMockBuilder('Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource')
            ->disableOriginalConstructor()
            ->getMock();
        $dataSource->expects($this->once())
            ->method('getQueryBuilder')
            ->will($this->returnValue($qb));

        $this->productListModifier->expects($this->once())
            ->method('applyPriceListLimitations')
            ->with($qb);

        $this->extension->visitDatasource($config, $dataSource);

        // Check that limits are applied only once
        $this->extension->visitDatasource($config, $dataSource);
    }

    /**
     * @return array
     */
    public function visitDatasourceDataProvider()
    {
        return [
            'with currency' => [
                'currency' => 'USD'
            ],
            'without currency' => [
                'currency' => null
            ]
        ];
    }
}
