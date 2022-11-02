<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Datagrid;

use Doctrine\ORM\QueryBuilder;
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
    private $tokenStorage;

    /** @var FrontendProductListModifier|\PHPUnit\Framework\MockObject\MockObject */
    private $productListModifier;

    /** @var Request|\PHPUnit\Framework\MockObject\MockObject */
    private $request;

    /** @var ProductSelectionGridExtension */
    private $extension;

    protected function setUp(): void
    {
        $this->tokenStorage = $this->createMock(TokenStorageInterface::class);
        $this->productListModifier = $this->createMock(FrontendProductListModifier::class);

        $this->request = $this->createMock(Request::class);
        $requestStack = $this->createMock(RequestStack::class);
        $requestStack->expects($this->any())
            ->method('getCurrentRequest')
            ->willReturn($this->request);

        $this->extension = new ProductSelectionGridExtension(
            $requestStack,
            $this->tokenStorage,
            $this->productListModifier
        );
        $this->extension->setParameters(new ParameterBag());
    }

    /**
     * @dataProvider applicableDataProvider
     */
    public function testIsApplicable(string $gridName, ?object $token, bool $expected)
    {
        $config = $this->createMock(DatagridConfiguration::class);
        $config->expects($this->once())
            ->method('getName')
            ->willReturn($gridName);
        $this->tokenStorage->expects($this->any())
            ->method('getToken')
            ->willReturn($token);

        $this->assertEquals($expected, $this->extension->isApplicable($config));
    }

    public function applicableDataProvider(): array
    {
        return [
            ['test', null, false],
            ['products-select-grid-frontend', $this->getToken(false), false],
            ['test', $this->getToken(true), false],
            ['products-select-grid-frontend', $this->getToken(true), true],
        ];
    }

    private function getToken(bool $getUser): TokenInterface
    {
        $token = $this->createMock(TokenInterface::class);
        if ($getUser) {
            $token->expects($this->any())
                ->method('getUser')
                ->willReturn(new CustomerUser());
        }

        return $token;
    }

    /**
     * @dataProvider visitDatasourceDataProvider
     */
    public function testVisitDatasource(?string $currency)
    {
        if ($currency) {
            $this->request->expects($this->once())
                ->method('get')
                ->with(ProductSelectionGridExtension::CURRENCY_KEY)
                ->willReturn($currency);
        }

        $gridName = 'products-select-grid-frontend';

        $token = $this->createMock(TokenInterface::class);
        $token->expects($this->any())
            ->method('getUser')
            ->willReturn(new CustomerUser());
        $config = $this->createMock(DatagridConfiguration::class);
        $config->expects($this->once())
            ->method('getName')
            ->willReturn($gridName);
        $this->tokenStorage->expects($this->any())
            ->method('getToken')
            ->willReturn($token);

        $qb = $this->createMock(QueryBuilder::class);

        $dataSource = $this->createMock(OrmDatasource::class);
        $dataSource->expects($this->once())
            ->method('getQueryBuilder')
            ->willReturn($qb);

        $this->productListModifier->expects($this->once())
            ->method('applyPriceListLimitations')
            ->with($qb);

        $this->extension->visitDatasource($config, $dataSource);

        // Check that limits are applied only once
        $this->extension->visitDatasource($config, $dataSource);
    }

    public function visitDatasourceDataProvider(): array
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
