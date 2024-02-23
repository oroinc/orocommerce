<?php

declare(strict_types=1);

namespace Oro\Bundle\RFPBundle\Tests\Functional\Form\Type;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\RFPBundle\Entity\RequestProduct;
use Oro\Bundle\RFPBundle\Entity\RequestProductItem;
use Oro\Bundle\RFPBundle\Form\Type\RequestProductItemType;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class RequestProductItemTypeTest extends WebTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->initClient();

        $this->loadFixtures([
            '@OroRFPBundle/Tests/Functional/Form/Type/DataFixtures/RequestProductItemType.yml',
        ]);
    }

    public function testCreateCompactDefault(): void
    {
        $formFactory = self::getContainer()->get('form.factory');
        $form = $formFactory->create(RequestProductItemType::class, null, ['csrf_protection' => false]);

        self::assertArrayIntersectEquals(
            [
                'data_class' => RequestProductItem::class,
                'compact_units' => false,
                'csrf_token_id' => 'rfp_request_product_item',
            ],
            $form->getConfig()->getOptions()
        );

        self::assertTrue($form->has('productUnit'));
        self::assertArrayIntersectEquals(
            [
                'required' => false,
                'compact' => false,
            ],
            $form->get('productUnit')->getConfig()->getOptions()
        );
    }

    public function testCreate(): void
    {
        $formFactory = self::getContainer()->get('form.factory');
        $form = $formFactory->create(
            RequestProductItemType::class,
            null,
            ['csrf_protection' => false, 'compact_units' => true]
        );

        self::assertArrayIntersectEquals(
            [
                'data_class' => RequestProductItem::class,
                'compact_units' => true,
                'csrf_token_id' => 'rfp_request_product_item',
            ],
            $form->getConfig()->getOptions()
        );

        self::assertTrue($form->has('price'));
        self::assertArrayIntersectEquals(
            [
                'required' => true,
                'currency_empty_value' => null,
                'validation_groups' => ['Optional'],
            ],
            $form->get('price')->getConfig()->getOptions()
        );

        self::assertTrue($form->has('productUnit'));
        self::assertArrayIntersectEquals(
            [
                'required' => false,
                'compact' => true,
            ],
            $form->get('productUnit')->getConfig()->getOptions()
        );

        self::assertTrue($form->has('quantity'));
        self::assertArrayIntersectEquals(
            [
                'required' => false,
                'default_data' => 1,
                'useInputTypeNumberValueFormat' => true,
            ],
            $form->get('quantity')->getConfig()->getOptions()
        );

        $formView = $form->createView();
        self::assertContains('oro_rfp_request_product_item', $formView->vars['block_prefixes']);
    }

    public function testSubmitNew(): void
    {
        /** @var ProductUnit $productUnitItem */
        $productUnitItem = $this->getReference('item');

        $formFactory = self::getContainer()->get('form.factory');
        $form = $formFactory->create(
            RequestProductItemType::class,
            null,
            ['csrf_protection' => false, 'validation_groups' => false]
        );

        $form->submit([
            'price' => [
                'value' => 123.4567,
                'currency' => 'USD',
            ],
            'productUnit' => $productUnitItem->getCode(),
            'quantity' => 42.1234,
        ]);

        self::assertTrue($form->isValid(), (string)$form->getErrors(true));
        self::assertTrue($form->isSynchronized());

        self::assertInstanceOf(RequestProductItem::class, $form->getData());

        self::assertEquals(
            (new RequestProductItem())
                ->setPrice(Price::create(123.4567, 'USD'))
                ->setProductUnit($productUnitItem)
                ->setQuantity(42.1234),
            $form->getData()
        );
    }

    public function testSubmitExisting(): void
    {
        /** @var Product $productKit1 */
        $productKit1 = $this->getReference('product_kit1');
        /** @var ProductUnit $productUnitItem */
        $productUnitItem = $this->getReference('item');
        /** @var ProductUnit $productUnitEach */
        $productUnitEach = $this->getReference('each');
        $requestProduct = (new RequestProduct())
            ->setProduct($productKit1);
        $requestProductItem = (new RequestProductItem())
            ->setPrice(Price::create(123.4567, 'USD'))
            ->setProductUnit($productUnitItem)
            ->setRequestProduct($requestProduct)
            ->setQuantity(42.1234);

        $formFactory = self::getContainer()->get('form.factory');
        $form = $formFactory->create(
            RequestProductItemType::class,
            $requestProductItem,
            ['csrf_protection' => false, 'validation_groups' => false]
        );

        $form->submit([
            'price' => [
                'value' => 34.5678,
                'currency' => 'USD',
            ],
            'productUnit' => $productUnitEach->getCode(),
            'quantity' => 45.6789,
        ]);

        self::assertTrue($form->isValid(), (string)$form->getErrors(true));
        self::assertTrue($form->isSynchronized());

        self::assertInstanceOf(RequestProductItem::class, $form->getData());

        self::assertEquals(
            (new RequestProductItem())
                ->setPrice(Price::create(34.5678, 'USD'))
                ->setProductUnit($productUnitEach)
                ->setQuantity(45.6789)
                ->setRequestProduct($requestProduct),
            $form->getData()
        );
    }
}
