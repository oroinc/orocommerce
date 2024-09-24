<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\Resolver\CustomerAddress;

use Oro\Bundle\OrderBundle\Entity\OrderAddress;
use Oro\Bundle\TaxBundle\Model\Taxable;
use Oro\Bundle\TaxBundle\Resolver\AbstractItemResolver;
use Oro\Bundle\TaxBundle\Resolver\CustomerAddress\CustomerAddressItemResolver;
use Oro\Bundle\TaxBundle\Resolver\CustomerAddress\CustomerAddressKitItemResolver;
use Oro\Bundle\TaxBundle\Tests\Unit\Resolver\AbstractItemResolverTestCase;

class CustomerAddressItemResolverTest extends AbstractItemResolverTestCase
{
    #[\Override]
    protected function createResolver(): AbstractItemResolver
    {
        return new CustomerAddressItemResolver(
            $this->unitResolver,
            $this->rowTotalResolver,
            $this->matcher,
            new CustomerAddressKitItemResolver(
                $this->unitResolver,
                $this->rowTotalResolver,
                $this->matcher
            )
        );
    }

    public function testIsApplicable(): void
    {
        $taxable = new Taxable();
        $this->resolver->resolve($taxable);

        $this->assertFalse($taxable->getResult()->isResultLocked());

        $taxable->setKitTaxable(true);
        $this->resolver->resolve($taxable);

        $this->assertFalse($taxable->getResult()->isResultLocked());

        $taxable->setPrice('19.99');
        $this->resolver->resolve($taxable);

        $this->assertFalse($taxable->getResult()->isResultLocked());

        $taxable->addItem(new Taxable());
        $this->resolver->resolve($taxable);

        $this->assertFalse($taxable->getResult()->isResultLocked());

        $taxable->setTaxationAddress(new OrderAddress());
        $this->resolver->resolve($taxable);

        $this->assertTrue($taxable->getResult()->isResultLocked());
    }

    public function testResultLocked(): void
    {
        $taxable = new Taxable();
        $taxable->setKitTaxable(true);
        $taxable->setPrice('19.99');
        $taxable->setTaxationAddress(new OrderAddress());

        $taxable->getResult()->lockResult();

        $this->resolver->resolve($taxable);

        $this->assertTrue($taxable->getResult()->isResultLocked());
        $this->assertEmpty($taxable->getResult()->getUnit()->getExcludingTax());
        $this->assertEmpty($taxable->getResult()->getRow()->getExcludingTax());
    }

    public function testEmptyRules(): void
    {
        $taxable = new Taxable();
        $taxable->setTaxationAddress(new OrderAddress());
        $taxable->setPrice('1');
        $taxable->setAmount('1');

        $this->matcher->expects($this->once())
            ->method('match')
            ->willReturn([]);

        $this->unitResolver->expects($this->once())
            ->method('resolveUnitPrice')
            ->with($taxable->getResult(), [], $taxable->getPrice());

        $this->rowTotalResolver->expects($this->once())
            ->method('resolveRowTotal')
            ->with($taxable->getResult(), [], $taxable->getPrice(), $taxable->getQuantity());

        $this->resolver->resolve($taxable);

        $this->assertEquals([], $taxable->getResult()->getTaxes());
    }

    /**
     * @dataProvider rulesDataProvider
     */
    public function testRules(string $taxableAmount, array $taxRules)
    {
        $taxableItem = new Taxable();
        $taxableItem->setPrice(10);
        $taxableItem->setQuantity(2);
        $taxableItem->setTaxationAddress(new OrderAddress());

        $taxable = new Taxable();
        $taxable->setPrice($taxableAmount);
        $taxable->setKitTaxable(true);
        $taxable->setQuantity(3);
        $taxable->setAmount($taxableAmount);
        $taxable->setTaxationAddress(new OrderAddress());
        $taxable->getContext()->offsetSet(Taxable::PRODUCT_TAX_CODE, 'prod_tax_code');
        $taxable->getContext()->offsetSet(Taxable::ACCOUNT_TAX_CODE, 'acc_tax_code');
        $taxable->addItem($taxableItem);

        $this->matcher->expects(self::exactly(2))
            ->method('match')
            ->willReturn($taxRules);

        $this->unitResolver->expects(self::exactly(2))
            ->method('resolveUnitPrice')
            ->withConsecutive(
                [$taxableItem->getResult(), $taxRules, $taxableItem->getPrice()],
                [$taxable->getResult(), $taxRules, $taxable->getPrice()],
            );

        $this->rowTotalResolver->expects(self::exactly(2))
            ->method('resolveRowTotal')
            ->withConsecutive(
                [$taxableItem->getResult(), $taxRules, $taxableItem->getPrice(), $taxableItem->getQuantity()],
                [$taxable->getResult(), $taxRules, $taxable->getPrice(), $taxable->getQuantity()],
            );

        $this->resolver->resolve($taxable);
    }

    public function rulesDataProvider(): array
    {
        return [
            [
                '19.99',
                [$this->getTaxRule('city', '0.08')],
            ],
            [
                '19.99',
                [
                    $this->getTaxRule('city', '0.08'),
                    $this->getTaxRule('region', '0.07'),
                ],
            ],
        ];
    }
}
