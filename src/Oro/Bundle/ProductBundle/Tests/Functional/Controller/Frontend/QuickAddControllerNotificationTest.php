<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\Controller\Frontend;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadAccountUserData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\ProductBundle\ComponentProcessor\DataStorageAwareComponentProcessor;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;

use Symfony\Bundle\FrameworkBundle\Translation\Translator;

/**
 * @dbIsolation
 */
class QuickAddControllerNotificationTest extends WebTestCase
{
    const RFP_PRODUCT_VISIBILITY_KEY = 'oro_b2b_rfp.frontend_product_visibility';

    /** @var ConfigManager $configManager */
    protected $configManager;

    /** @var Translator $translator */
    protected $translator;

    /** @var Product $productInStock */
    protected $productInStock;

    /** @var Product $productOutOfStock */
    protected $productOutOfStock;

    /** @var array $productGroups */
    protected $productGroups;

    /** @var array $productGroups */
    protected $expectedMessageGroup;

    protected function setUp()
    {
        $this->initClient(
            [],
            $this->generateBasicAuthHeader(LoadAccountUserData::AUTH_USER, LoadAccountUserData::AUTH_PW)
        );

        $this->loadFixtures(
            [
                'Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData'
            ]
        );

        $this->productInStock = $this->getReference(LoadProductData::PRODUCT_2);
        $this->productOutOfStock = $this->getReference(LoadProductData::PRODUCT_3);

        $this->translator = $this->getContainer()->get('translator');

        $inventoryStatusClassName = ExtendHelper::buildEnumValueClassName('prod_inventory_status');
        $availableInventoryStatuses = [
            $this->getContainer()->get('doctrine')->getRepository($inventoryStatusClassName)
                ->find(Product::INVENTORY_STATUS_IN_STOCK)->getId()
        ];
        $this->configManager = $this->getContainer()->get('oro_config.manager');
        $this->configManager->set(self::RFP_PRODUCT_VISIBILITY_KEY, $availableInventoryStatuses);
        $this->configManager->flush();

        $this->productGroups = [
            'no_products_be_added_to_rfq' => [
                [
                    'productSku' => $this->productOutOfStock->getSku(),
                    'productQuantity' => 1,
                ],
            ],
            'cannot_be_added_to_rfq' => [
                [
                    'productSku' => $this->productInStock->getSku(),
                    'productQuantity' => 1,
                ],
                [
                    'productSku' => $this->productOutOfStock->getSku(),
                    'productQuantity' => 1,
                ],
            ]
        ];

        $this->expectedMessageGroup = [
            'no_products_be_added_to_rfq' => $this->translator->trans(
                'oro.frontend.rfp.data_storage.no_products_be_added_to_rfq'
            ),
            'cannot_be_added_to_rfq' => $this->translator->trans(
                'oro.frontend.rfp.data_storage.cannot_be_added_to_rfq'
            )
        ];
    }

    /**
     * @param string $group
     * @dataProvider  productProvider
     */
    public function testNotificationForMultiplyProductsNotAllowedRFQ($group)
    {
        $crawler = $this->client->request('GET', $this->getUrl('orob2b_product_frontend_quick_add'));

        $this->assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);

        $form = $crawler->filter('form[name="orob2b_product_quick_add"]')->form();

        /** @var DataStorageAwareComponentProcessor $processor */
        $processor = $this->getContainer()->get('orob2b_rfp.processor.quick_add');

        $this->client->followRedirects(true);
        $this->client->request(
            $form->getMethod(),
            $form->getUri(),
            [
                'orob2b_product_quick_add' => [
                    '_token' => $form['orob2b_product_quick_add[_token]']->getValue(),
                    'products' => $this->productGroups[$group],
                    'component' => $processor->getName(),
                ],
            ]
        );

        $this->assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);

        $content = $this->client->getResponse()->getContent();

        $this->assertContains(
            $this->expectedMessageGroup[$group],
            $content
        );

        $this->assertContains(
            $this->productOutOfStock->getSku(),
            $content
        );
    }

    /**
     * @return array
     */
    public function productProvider()
    {
        return [
            'no_products_be_added_to_rfq' => [
                'group' => 'no_products_be_added_to_rfq'
            ],
            'cannot_be_added_to_rfq' => [
                'group' => 'cannot_be_added_to_rfq'
            ],
        ];
    }

    protected function tearDown()
    {
        $this->configManager->reset(self::RFP_PRODUCT_VISIBILITY_KEY);
        $this->configManager->flush();

        unset(
            $this->translator,
            $this->productInStock,
            $this->productOutOfStock,
            $this->productGroups,
            $this->expectedMessageGroup
        );
    }
}
