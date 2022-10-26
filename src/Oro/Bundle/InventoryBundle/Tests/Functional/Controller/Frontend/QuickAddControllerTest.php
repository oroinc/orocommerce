<?php

namespace Oro\Bundle\InventoryBundle\Tests\Functional\Controller\Frontend;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\ConfigBundle\Tests\Functional\Traits\ConfigManagerAwareTestTrait;
use Oro\Bundle\EntityBundle\Entity\EntityFieldFallbackValue;
use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadCustomerUserData;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadCombinedProductPrices;
use Oro\Bundle\ProductBundle\DependencyInjection\Configuration;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Storage\ProductDataStorage;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductUnitPrecisions;
use Oro\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingListLineItems;
use Oro\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingLists;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;

class QuickAddControllerTest extends WebTestCase
{
    use ConfigManagerAwareTestTrait;

    /**
     * @var EntityManagerInterface
     */
    protected $emProduct;

    /**
     * @var EntityManagerInterface
     */
    protected $emFallback;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    protected function setUp(): void
    {
        $this->initClient(
            [],
            $this->generateBasicAuthHeader(LoadCustomerUserData::AUTH_USER, LoadCustomerUserData::AUTH_PW)
        );
        $this->loadFixtures(
            [
                LoadProductUnitPrecisions::class,
                LoadShoppingLists::class,
                LoadShoppingListLineItems::class,
                LoadCombinedProductPrices::class,
            ]
        );
        $this->emProduct = $this->getContainer()->get('oro_entity.doctrine_helper')->getEntityManager(Product::class);
        $this->emFallback = $this->getContainer()->get('oro_entity.doctrine_helper')->getEntityManager(
            EntityFieldFallbackValue::class
        );
        $this->translator = $this->getContainer()->get('translator');
    }

    protected function tearDown(): void
    {
        self::getConfigManager()->set(
            Configuration::getConfigKeyByName(Configuration::ENABLE_QUICK_ORDER_FORM_OPTIMIZED),
            false
        );
        self::getConfigManager()->flush();
    }

    /**
     * @param int $quantity
     * @param int $minLimit
     * @param int $maxLimit
     * @param string $errorMessage
     * @param int $errorLimit
     *
     * @dataProvider getQuickAddData
     */
    public function testQuickAddReturnsErrorIfQuantityOutOfBound(
        $quantity,
        $minLimit,
        $maxLimit,
        $errorMessage,
        $errorLimit
    ) {
        /** @var Product $product */
        $product = $this->getReference(LoadProductData::PRODUCT_3);
        $this->setProductLimits($product, $minLimit, $maxLimit);

        $crawler = $this->client->request('GET', $this->getUrl('oro_product_frontend_quick_add'));

        $form = $crawler->filter('form[name="oro_product_quick_add"]')->form();
        $processor = $this->getContainer()->get('oro_shopping_list.processor.quick_add');
        $this->client->followRedirects(true);

        $this->client->request(
            $form->getMethod(),
            $form->getUri(),
            [
                'oro_product_quick_add' => [
                    '_token' => $form['oro_product_quick_add[_token]']->getValue(),
                    'products' => [
                        [
                            'productSku' => $product->getSku(),
                            'productQuantity' => $quantity,
                        ],
                    ],
                    'component' => $processor->getName(),
                    'additional' => null,
                ],
            ]
        );

        $errorMessage = $this->translator->trans(
            $errorMessage,
            ['%limit%' => $errorLimit, '%sku%' => $product->getSku(), '%product_name%' => $product->getName()]
        );
        static::assertStringContainsString($errorMessage, $this->client->getResponse()->getContent());
    }

    /**
     * @param int $quantity
     * @param int $minLimit
     * @param int $maxLimit
     * @param string $errorMessage
     * @param int $errorLimit
     *
     * @dataProvider getQuickAddData
     */
    public function testQuickAddReturnsErrorIfQuantityOutOfBoundWhenIsOptimized(
        int $quantity,
        int $minLimit,
        int $maxLimit,
        string $errorMessage,
        int $errorLimit
    ): void {
        self::getConfigManager()->set(
            Configuration::getConfigKeyByName(Configuration::ENABLE_QUICK_ORDER_FORM_OPTIMIZED),
            true
        );
        self::getConfigManager()->flush();

        /** @var Product $product */
        $product = $this->getReference(LoadProductData::PRODUCT_3);
        $this->setProductLimits($product, $minLimit, $maxLimit);

        $crawler = $this->client->request('GET', $this->getUrl('oro_product_frontend_quick_add'));

        $form = $crawler->filter('form[name="oro_product_quick_add"]')->form();
        $processor = self::getContainer()->get('oro_shopping_list.processor.quick_add');

        $this->client->request(
            $form->getMethod(),
            $form->getUri(),
            [
                'oro_product_quick_add' => [
                    '_token' => $form['oro_product_quick_add[_token]']->getValue(),
                    'products' => json_encode([
                        [
                            ProductDataStorage::PRODUCT_SKU_KEY => $product->getSku(),
                            ProductDataStorage::PRODUCT_QUANTITY_KEY => $quantity,
                        ],
                    ]),
                    'component' => $processor->getName(),
                    'additional' => null,
                ],
            ]
        );

        $errorMessage = $this->translator->trans(
            $errorMessage,
            ['%limit%' => $errorLimit, '%sku%' => $product->getSku(), '%product_name%' => $product->getName()]
        );
        $response = $this->client->getResponse();
        $responseData = self::getJsonResponseContent($response, 200);
        self::assertStringContainsString(
            $errorMessage,
            $responseData['collection']['items'][0]['errors'][0]['message']
        );
    }

    /**
     * @return array
     */
    public function getQuickAddData()
    {
        return [
            [2, 3, 5, 'oro.inventory.product.error.quantity_below_min_limit', 3],
            [6, 3, 5, 'oro.inventory.product.error.quantity_over_max_limit', 5],
        ];
    }

    public function testRFQExcludesQuantityLimitValidation()
    {
        /** @var Product $product */
        $product = $this->getReference(LoadProductData::PRODUCT_3);
        $quantity = 1;
        $minLimit = 3;
        $maxLimit = 5;
        $this->setProductLimits($product, $minLimit, $maxLimit);
        $crawler = $this->client->request('GET', $this->getUrl('oro_product_frontend_quick_add'));

        $form = $crawler->filter('form[name="oro_product_quick_add"]')->form();
        $processor = $this->getContainer()->get('oro_rfp.processor.quick_add');
        $this->client->followRedirects(false);

        $this->client->request(
            $form->getMethod(),
            $form->getUri(),
            [
                'oro_product_quick_add' => [
                    '_token' => $form['oro_product_quick_add[_token]']->getValue(),
                    'products' => [
                        [
                            'productSku' => $product->getSku(),
                            'productQuantity' => $quantity,
                        ],
                    ],
                    'component' => $processor->getName(),
                    'additional' => null,
                ],
            ]
        );
        $this->assertEquals(Response::HTTP_FOUND, $this->client->getResponse()->getStatusCode());
        static::assertStringContainsString(
            $this->getUrl('oro_rfp_frontend_request_create'),
            $this->client->getResponse()->headers->get('location')
        );
    }

    public function testRFQExcludesQuantityLimitValidationWhenInOptimized()
    {
        self::getConfigManager()->set(
            Configuration::getConfigKeyByName(Configuration::ENABLE_QUICK_ORDER_FORM_OPTIMIZED),
            true
        );
        self::getConfigManager()->flush();

        /** @var Product $product */
        $product = $this->getReference(LoadProductData::PRODUCT_3);
        $quantity = 1;
        $minLimit = 3;
        $maxLimit = 5;
        $this->setProductLimits($product, $minLimit, $maxLimit);
        $crawler = $this->client->request('GET', $this->getUrl('oro_product_frontend_quick_add'));

        $form = $crawler->filter('form[name="oro_product_quick_add"]')->form();
        $processor = $this->getContainer()->get('oro_rfp.processor.quick_add');
        $this->client->followRedirects(false);

        $this->client->request(
            $form->getMethod(),
            $form->getUri(),
            [
                'oro_product_quick_add' => [
                    '_token' => $form['oro_product_quick_add[_token]']->getValue(),
                    'products' => json_encode([
                        [
                            ProductDataStorage::PRODUCT_SKU_KEY => $product->getSku(),
                            ProductDataStorage::PRODUCT_QUANTITY_KEY => $quantity,
                        ],
                    ]),
                    'component' => $processor->getName(),
                    'additional' => null,
                ],
            ]
        );

        $response = $this->client->getResponse();
        $responseData = self::getJsonResponseContent($response, 200);
        self::assertStringContainsString(
            $this->getUrl('oro_rfp_frontend_request_create'),
            $responseData['redirectUrl']
        );
    }

    /**
     * @param Product $product
     * @param int $minLimit
     * @param int $maxLimit
     */
    protected function setProductLimits(Product $product, $minLimit, $maxLimit)
    {
        $entityFallback = new EntityFieldFallbackValue();
        $entityFallback->setScalarValue($minLimit);
        $entityFallback2 = new EntityFieldFallbackValue();
        $entityFallback2->setScalarValue($maxLimit);
        $this->emFallback->persist($entityFallback);
        $this->emFallback->persist($entityFallback2);
        $product->setMinimumQuantityToOrder($entityFallback);
        $product->setMaximumQuantityToOrder($entityFallback2);
        $this->emProduct->flush();
        $this->emFallback->flush();
    }
}
