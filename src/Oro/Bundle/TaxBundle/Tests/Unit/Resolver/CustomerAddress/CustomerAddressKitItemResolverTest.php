<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\Resolver\CustomerAddress;

use Oro\Bundle\OrderBundle\Entity\OrderAddress;
use Oro\Bundle\TaxBundle\Model\Taxable;
use Oro\Bundle\TaxBundle\Resolver\AbstractItemResolver;
use Oro\Bundle\TaxBundle\Resolver\CustomerAddress\CustomerAddressKitItemResolver;
use Oro\Bundle\TaxBundle\Tests\Unit\Resolver\AbstractItemResolverTestCase;

class CustomerAddressKitItemResolverTest extends AbstractItemResolverTestCase
{
    protected function createResolver(): AbstractItemResolver
    {
        return new CustomerAddressKitItemResolver(
            $this->unitResolver,
            $this->rowTotalResolver,
            $this->matcher
        );
    }

    public function testIsApplicable(): void
    {
        $taxable = new Taxable();
        $this->resolver->resolve($taxable);

        $this->assertFalse($taxable->getResult()->isResultLocked());

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
        $taxable->setPrice('19.99');
        $taxable->setTaxationAddress(new OrderAddress());

        $taxable->getResult()->lockResult();

        $this->resolver->resolve($taxable);

        $this->assertTrue($taxable->getResult()->isResultLocked());
        $this->assertEmpty($taxable->getResult()->getUnit()->getExcludingTax());
        $this->assertEmpty($taxable->getResult()->getRow()->getExcludingTax());
    }

    /**
     * @dataProvider resolveDataProvider
     */
    public function testResolve(string $taxableAmount, array $taxRules): void
    {
        $taxable = new Taxable();
        $taxable->setPrice($taxableAmount);
        $taxable->setQuantity(3);
        $taxable->setAmount($taxableAmount);
        $taxable->setTaxationAddress(new OrderAddress());
        $taxable->getContext()->offsetSet(Taxable::PRODUCT_TAX_CODE, 'prod_tax_code');
        $taxable->getContext()->offsetSet(Taxable::ACCOUNT_TAX_CODE, 'acc_tax_code');

        $this->matcher->expects(self::once())
            ->method('match')
            ->willReturn($taxRules);

        $this->unitResolver->expects($this->once())
            ->method('resolveUnitPrice')
            ->with($taxable->getResult(), $taxRules, $taxable->getPrice());

        $this->rowTotalResolver->expects($this->once())
            ->method('resolveRowTotal')
            ->with($taxable->getResult(), $taxRules, $taxable->getPrice(), $taxable->getQuantity());

        $this->resolver->resolve($taxable);
    }

    public function resolveDataProvider(): array
    {
        return [
            [
                '19.99',
                [],
            ],
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
