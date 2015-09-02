<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Datagrid;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;

use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\PricingBundle\Datagrid\ProductSelectionGridExtension;
use OroB2B\Bundle\PricingBundle\Model\FrontendProductListModifier;

class ProductSelectionGridExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|TokenStorageInterface
     */
    protected $tokenStorage;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|FrontendProductListModifier
     */
    protected $productListModifier;

    /**
     * @var ProductSelectionGridExtension
     */
    protected $extension;

    protected function setUp()
    {
        $this->tokenStorage = $this
            ->getMock('Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface');
        $this->productListModifier = $this
            ->getMockBuilder('OroB2B\Bundle\PricingBundle\Model\FrontendProductListModifier')
            ->disableOriginalConstructor()
            ->getMock();

        $this->extension = new ProductSelectionGridExtension($this->tokenStorage, $this->productListModifier);
    }

    /**
     * @dataProvider applicableDataProvider
     * @param string $gridName
     * @param object|null $token
     * @param bool $expected
     */
    public function testIsApplicable($gridName, $token, $expected)
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|DatagridConfiguration $config */
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
        $emptyToken = $this->getMock('Symfony\Component\Security\Core\Authentication\Token\TokenInterface');
        $expectedToken = $this->getMock('Symfony\Component\Security\Core\Authentication\Token\TokenInterface');
        $expectedToken->expects($this->any())
            ->method('getUser')
            ->will($this->returnValue(new AccountUser()));

        return [
            ['test', null, false],
            ['products-select-grid-frontend', $emptyToken, false],
            ['test', $expectedToken, false],
            ['products-select-grid-frontend', $expectedToken, true],
        ];
    }

    /**
     * @dataProvider visitDatasourceDataProvider
     * @param string $currency
     */
    public function testVisitDatasource($currency)
    {
        if ($currency) {
            /** @var \PHPUnit_Framework_MockObject_MockObject|Request $request */
            $request = $this->getMock('Symfony\Component\HttpFoundation\Request');
            $this->extension->setRequest($request);

            $request->expects($this->once())
                ->method('get')
                ->with(ProductSelectionGridExtension::CURRENCY_KEY)
                ->willReturn($currency);
        }

        $gridName = 'products-select-grid-frontend';

        $token = $this->getMock('Symfony\Component\Security\Core\Authentication\Token\TokenInterface');
        $token->expects($this->any())
            ->method('getUser')
            ->will($this->returnValue(new AccountUser()));
        /** @var \PHPUnit_Framework_MockObject_MockObject|DatagridConfiguration $config */
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

        /** @var \PHPUnit_Framework_MockObject_MockObject|OrmDatasource $dataSource */
        $dataSource = $this->getMockBuilder('Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource')
            ->disableOriginalConstructor()
            ->getMock();
        $dataSource->expects($this->once())
            ->method('getQueryBuilder')
            ->will($this->returnValue($qb, $currency));

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
