<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\EventListener;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\EventListener\ProductSelectPriceListAwareListener;
use Oro\Bundle\PricingBundle\Model\FrontendProductListModifier;
use Oro\Bundle\ProductBundle\Event\ProductDBQueryRestrictionEvent;
use Symfony\Component\HttpFoundation\ParameterBag;

class ProductSelectPriceListAwareListenerTest extends \PHPUnit\Framework\TestCase
{
    private const PRICE_LIST_ID = 42;

    /** @var FrontendProductListModifier|\PHPUnit\Framework\MockObject\MockObject */
    private $modifier;

    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrine;

    /** @var ProductSelectPriceListAwareListener */
    private $listener;

    protected function setUp(): void
    {
        $this->modifier = $this->createMock(FrontendProductListModifier::class);
        $this->doctrine = $this->createMock(ManagerRegistry::class);

        $this->listener = new ProductSelectPriceListAwareListener($this->modifier, $this->doctrine);
    }

    /**
     * @dataProvider onDBQueryDataProvider
     */
    public function testOnDBQuery(bool $applicable, array $parameters = [], bool $withPriceList = false)
    {
        $queryBuilder = $this->createMock(QueryBuilder::class);

        if ($applicable) {
            if ($withPriceList) {
                $repository = $this->createMock(EntityRepository::class);
                $repository->expects($this->once())
                    ->method('find')
                    ->with(self::PRICE_LIST_ID)
                    ->willReturn(new PriceList());

                $this->doctrine->expects($this->once())
                    ->method('getRepository')
                    ->with(PriceList::class)
                    ->willReturn($repository);
            } else {
                $this->modifier->expects($this->once())
                    ->method('applyPriceListLimitations')
                    ->with($queryBuilder);
            }
        } else {
            $this->modifier->expects($this->never())
                ->method('applyPriceListLimitations');
        }

        $event = new ProductDBQueryRestrictionEvent($queryBuilder, new ParameterBag($parameters));
        $this->listener->onDBQuery($event);
    }

    public function onDBQueryDataProvider(): array
    {
        return [
            'applicable default customer user' => [
                'applicable' => true,
                'parameters' => ['price_list' => ProductSelectPriceListAwareListener::DEFAULT_ACCOUNT_USER],
                'withPriceList' => false
            ],
            'applicable with price list' => [
                'applicable' => true,
                'parameters' => ['price_list' => self::PRICE_LIST_ID],
                'withPriceList' => true
            ],
            'not applicable without parameters' => [
                'applicable' => false
            ],
            'not applicable' => [
                'applicable' => false,
                'parameters' => ['another' => 123]
            ]
        ];
    }
}
