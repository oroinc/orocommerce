<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\Provider;

use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomers;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomerUserAddressACLData;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteriaRequestHandler;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadCombinedProductPrices;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository;
use Oro\Bundle\ProductBundle\Event\QuickAddRowsCollectionReadyEvent;
use Oro\Bundle\ProductBundle\Model\QuickAddField;
use Oro\Bundle\ProductBundle\Model\QuickAddRow;
use Oro\Bundle\ProductBundle\Model\QuickAddRowCollection;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;

class QuickAddCollectionPriceProviderTest extends WebTestCase
{
    private ProductRepository $productRepository;

    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([LoadCombinedProductPrices::class]);

        $request = Request::createFromGlobals();
        $this->loginUser(LoadCustomerUserAddressACLData::USER_ACCOUNT_1_ROLE_DEEP);
        $this->updateUserSecurityToken(self::AUTH_USER);

        $request->query->set(
            ProductPriceScopeCriteriaRequestHandler::CUSTOMER_ID_KEY,
            $this->getReference(LoadCustomers::CUSTOMER_LEVEL_1_1)->getId()
        );

        self::getContainer()->get('request_stack')->push($request);

        $this->productRepository = self::getContainer()->get('oro_product.repository.product');
    }

    public function testIfCorrectPricesAreBeingAddedToRowItems(): void
    {
        $collection = $this->getValidCollection();
        $event = new QuickAddRowsCollectionReadyEvent($collection);

        self::getContainer()->get('event_dispatcher')->dispatch($event, QuickAddRowsCollectionReadyEvent::NAME);

        $expectedResults = [
            new QuickAddField('prices', [
                'bottle' => [['price' => 13.1, 'currency' => 'USD', 'quantity' => 1.0, 'unit' => 'bottle']],
                'liter' => [
                    ['price' => 10.0, 'currency' => 'USD', 'quantity' => 1.0, 'unit' => 'liter'],
                    ['price' => 12.2, 'currency' => 'USD', 'quantity' => 10.0, 'unit' => 'liter'],
                ],
                'milliliter' => [['price' => 0, 'currency' => 'USD', 'quantity' => 1.0, 'unit' => 'milliliter']],
            ]),
            new QuickAddField('prices', [
                'liter' => [
                    ['price' => 20.0, 'currency' => 'USD', 'quantity' => 1.0, 'unit' => 'liter'],
                    ['price' => 12.2, 'currency' => 'USD', 'quantity' => 12.0, 'unit' => 'liter'],
                ],
                'milliliter' => [['price' => 0, 'currency' => 'USD', 'quantity' => 1.0, 'unit' => 'milliliter']],
            ]),
            new QuickAddField('prices', [
                'bottle' => [['price' => 200.5, 'currency' => 'USD', 'quantity' => 10.0, 'unit' => 'bottle']],
                'milliliter' => [['price' => 0, 'currency' => 'USD', 'quantity' => 1.0, 'unit' => 'milliliter']],
            ]),
        ];

        foreach ($collection as $i => $quickAddRow) {
            self::assertEquals($expectedResults[$i], $quickAddRow->getAdditionalField('prices'));
        }
    }

    public function testIfPriceIsNullIfCollectionHasNoRows(): void
    {
        $collection = new QuickAddRowCollection();

        $event = new QuickAddRowsCollectionReadyEvent($collection);

        self::getContainer()->get('event_dispatcher')->dispatch($event, QuickAddRowsCollectionReadyEvent::NAME);

        self::assertEquals(null, $collection->getAdditionalField('prices'));
    }

    private function getValidCollection(): QuickAddRowCollection
    {
        $collection = new QuickAddRowCollection();
        $lineNumber = 0;

        $quickAddRow1 = new QuickAddRow(++$lineNumber, 'product-1', 1, 'bottle');
        $quickAddRow1->setProduct($this->productRepository->findOneBySku('product-1'));

        $quickAddRow2 = new QuickAddRow(++$lineNumber, 'product-2', 1, 'liter');
        $quickAddRow2->setProduct($this->productRepository->findOneBySku('product-2'));

        $quickAddRow3 = new QuickAddRow(++$lineNumber, 'product-4', 1, 'bottle');
        $quickAddRow3->setProduct($this->productRepository->findOneBySku('product-4'));

        $collection->add($quickAddRow1);
        $collection->add($quickAddRow2);
        $collection->add($quickAddRow3);

        return $collection;
    }
}
