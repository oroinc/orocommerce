<?php

namespace Oro\Bundle\RFPBundle\Tests\Functional\Controller\Frontend;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadAccountUserData;
use Oro\Bundle\ProductBundle\ComponentProcessor\DataStorageAwareComponentProcessor;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolation
 */
class QuickAddControllerNotificationTest extends WebTestCase
{
    const RFP_PRODUCT_VISIBILITY_KEY = 'oro_rfp.frontend_product_visibility';

    /** @var ConfigManager $configManager */
    protected $configManager;

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

        $inventoryStatusClassName = ExtendHelper::buildEnumValueClassName('prod_inventory_status');
        $availableInventoryStatuses = [
            $this->getContainer()->get('doctrine')->getRepository($inventoryStatusClassName)
                ->find(Product::INVENTORY_STATUS_IN_STOCK)->getId()
        ];

        $this->configManager = $this->getContainer()->get('oro_config.manager');
        $this->configManager->set(self::RFP_PRODUCT_VISIBILITY_KEY, $availableInventoryStatuses);
        $this->configManager->flush();
    }

    /**
     * @dataProvider productProvider
     *
     * @param string $processorName
     * @param string $expectedMessage
     * @param array $products
     */
    public function testNotification(
        $processorName,
        $expectedMessage,
        array $products
    ) {
        /** @var DataStorageAwareComponentProcessor $processor */
        $processor = $this->getContainer()->get($processorName);

        $crawler = $this->client->request('GET', $this->getUrl('oro_product_frontend_quick_add'));

        $this->assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);

        $form = $crawler->filter('form[name="oro_product_quick_add"]')->form();

        $this->client->followRedirects(true);
        $this->client->request(
            $form->getMethod(),
            $form->getUri(),
            [
                'oro_product_quick_add' => [
                    '_token' => $form['oro_product_quick_add[_token]']->getValue(),
                    'products' => $products,
                    'component' => $processor->getName(),
                ],
            ]
        );

        $this->assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);

        $this->assertContains(
            $this->getContainer()->get('translator')->trans($expectedMessage),
            $this->client->getResponse()->getContent()
        );
    }

    /**
     * @return array
     */
    public function productProvider()
    {
        return [
            'no_products_be_added_to_rfq' => [
                'processorName' => 'oro_rfp.processor.quick_add',
                'expectedMessage' => 'oro.frontend.rfp.data_storage.no_products_be_added_to_rfq',
                'products' => [
                    [
                        'productSku' => 'product.3',
                        'productQuantity' => 1,
                    ],
                ],
            ],
            'cannot_be_added_to_rfq' => [
                'processorName' => 'oro_rfp.processor.quick_add',
                'expectedMessage' => 'oro.frontend.rfp.data_storage.cannot_be_added_to_rfq',
                'products' => [
                    [
                        'productSku' => 'product.2',
                        'productQuantity' => 1,
                    ],
                    [
                        'productSku' => 'product.3',
                        'productQuantity' => 1,
                    ],
                ],
            ],
        ];
    }

    protected function tearDown()
    {
        $this->configManager->reset(self::RFP_PRODUCT_VISIBILITY_KEY);
        $this->configManager->flush();
    }
}
