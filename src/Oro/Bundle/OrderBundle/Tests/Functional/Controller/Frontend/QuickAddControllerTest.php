<?php

namespace Oro\Bundle\OrderBundle\Tests\Functional\Controller\Frontend;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\ConfigBundle\Tests\Functional\Traits\ConfigManagerAwareTestTrait;
use Oro\Bundle\EntityBundle\Entity\EntityFieldFallbackValue;
use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadCustomerUserData;
use Oro\Bundle\InventoryBundle\Tests\Functional\DataFixtures\LoadProductRelatedFallbackValuesData;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadCombinedProductPrices;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Model\QuickAddRow;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductUnitPrecisions;
use Oro\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingListLineItems;
use Oro\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingLists;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

final class QuickAddControllerTest extends WebTestCase
{
    use ConfigManagerAwareTestTrait;

    private EntityManagerInterface $emProduct;

    private EntityManagerInterface $emFallback;

    private TranslatorInterface $translator;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient(
            [],
            self::generateBasicAuthHeader(LoadCustomerUserData::AUTH_USER, LoadCustomerUserData::AUTH_PW)
        );
        $this->loadFixtures(
            [
                LoadProductData::class,
                LoadProductUnitPrecisions::class,
                LoadShoppingLists::class,
                LoadShoppingListLineItems::class,
                LoadCombinedProductPrices::class,
                LoadProductRelatedFallbackValuesData::class,
            ]
        );
        $this->emProduct = self::getContainer()->get('oro_entity.doctrine_helper')->getEntityManager(Product::class);
        $this->emFallback = self::getContainer()->get('oro_entity.doctrine_helper')->getEntityManager(
            EntityFieldFallbackValue::class
        );
        $this->translator = self::getContainer()->get('translator');
    }

    public function testQuickAddReturnsErrorIfInventoryStatusNotSupported(): void
    {
        /** @var Product $product */
        $product = $this->getReference(LoadProductData::PRODUCT_1);

        self::getConfigManager()->set(
            'oro_order.frontend_product_visibility',
            ['prod_inventory_status.in_stock']
        );
        self::getConfigManager()->flush();

        $enumRepository = $this->emProduct->getRepository('Oro\Bundle\EntityExtendBundle\Entity\EnumOption');
        $outOfStockStatus = $enumRepository->findOneBy([
            'id' => 'prod_inventory_status.out_of_stock',
            'enumCode' => 'prod_inventory_status'
        ]);

        $product->setInventoryStatus($outOfStockStatus);
        $this->emProduct->flush();

        $crawler = $this->client->request('GET', $this->getUrl('oro_product_frontend_quick_add'));

        $form = $crawler->filter('form[name="oro_product_quick_add"]')->form();

        $this->client->request(
            $form->getMethod(),
            $form->getUri(),
            [
                'oro_product_quick_add' => [
                    '_token' => $form['oro_product_quick_add[_token]']->getValue(),
                    'products' => json_encode([
                        [
                            QuickAddRow::SKU => $product->getSku(),
                            QuickAddRow::QUANTITY => 1,
                        ],
                    ], JSON_THROW_ON_ERROR),
                    'component' => 'oro_shopping_list_to_checkout_quick_add_processor',
                    'additional' => null,
                ],
            ]
        );

        $collectionErrorMessage = $this->translator->trans(
            sprintf(
                'oro.product.frontend.quick_add.validation.component.%s.error',
                'oro_shopping_list_to_checkout_quick_add_processor'
            ),
            [],
            'validators'
        );

        $errorMessage = $this->translator->trans(
            'oro.shoppinglist.lineitem.inventory_status.checkout_not_supported',
            [],
            'validators'
        );
        $response = $this->client->getResponse();
        $responseData = self::getJsonResponseContent($response, 200);

        self::assertArrayHasKey('collection', $responseData);
        self::assertStringContainsString(
            $collectionErrorMessage,
            $responseData['collection']['errors'][0]['message']
        );
        self::assertStringContainsString(
            $errorMessage,
            $responseData['collection']['items'][0]['errors'][0]['message']
        );
    }
}
