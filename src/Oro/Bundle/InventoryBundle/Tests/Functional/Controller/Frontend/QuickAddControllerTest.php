<?php

namespace Oro\Bundle\InventoryBundle\Tests\Functional\Controller\Frontend;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\EntityBundle\Entity\EntityFieldFallbackValue;
use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadAccountUserData;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadCombinedProductPrices;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductUnitPrecisions;
use Oro\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingLists;
use Oro\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingListLineItems;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolation
 */
class QuickAddControllerTest extends WebTestCase
{
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

    public function setUp()
    {
        $this->initClient(
            [],
            $this->generateBasicAuthHeader(LoadAccountUserData::AUTH_USER, LoadAccountUserData::AUTH_PW)
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
        $this->assertContains($errorMessage, $this->client->getResponse()->getContent());

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
        $this->assertContains(
            $this->getUrl('oro_rfp_frontend_request_create'),
            $this->client->getResponse()->headers->get('location')
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
