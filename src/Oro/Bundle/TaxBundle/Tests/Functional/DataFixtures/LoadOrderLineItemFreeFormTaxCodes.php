<?php

namespace Oro\Bundle\TaxBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\TaxBundle\Entity\ProductTaxCode;

class LoadOrderLineItemFreeFormTaxCodes extends AbstractFixture implements DependentFixtureInterface
{
    public const string ORDER_LINE_ITEM_WITH_TAX_CODE = 'order_line_item.1';

    #[\Override]
    public function getDependencies(): array
    {
        return [
            LoadProductTaxCodes::class,
            '@OroOrderBundle/Tests/Functional/DataFixtures/order_line_items.yml'
        ];
    }

    #[\Override]
    public function load(ObjectManager $manager): void
    {
        /** @var OrderLineItem $lineItemWithTaxCode */
        $lineItemWithTaxCode = $this->getReference(self::ORDER_LINE_ITEM_WITH_TAX_CODE);
        /** @var ProductTaxCode $taxCode */
        $taxCode = $this->getReference(LoadProductTaxCodes::REFERENCE_PREFIX . '.' . LoadProductTaxCodes::TAX_1);

        $lineItemWithTaxCode->setFreeFormTaxCode($taxCode);

        $manager->flush();
    }
}
