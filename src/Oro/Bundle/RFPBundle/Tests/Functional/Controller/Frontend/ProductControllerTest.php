<?php

namespace Oro\Bundle\RFPBundle\Tests\Functional\Controller\Frontend;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Functional\Controller\Frontend\ProductControllerTest
    as BaseControllerTest
;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;

/**
 * @dbIsolation
 */
class ProductControllerTest extends BaseControllerTest
{
    const RFP_PRODUCT_VISIBILITY_KEY = 'oro_rfp.frontend_product_visibility';

    /** @var ConfigManager $configManager */
    protected $configManager;

    protected function setUp()
    {
        parent::setUp();

        $inventoryStatusClassName = ExtendHelper::buildEnumValueClassName('prod_inventory_status');

        $availableInventoryStatuses = [
            $this->getContainer()->get('doctrine')->getRepository($inventoryStatusClassName)
                ->find(Product::INVENTORY_STATUS_IN_STOCK)->getId()
        ];

        $this->configManager = $this->getContainer()->get('oro_config.manager');
        $this->configManager->set(self::RFP_PRODUCT_VISIBILITY_KEY, $availableInventoryStatuses);
        $this->configManager->flush();
    }

    public function testViewProductWithoutRequestQuoteAvailable()
    {
        $product = $this->getProduct(LoadProductData::PRODUCT_3);

        $this->assertInstanceOf('Oro\Bundle\ProductBundle\Entity\Product', $product);

        $this->client->request(
            'GET',
            $this->getUrl('oro_product_frontend_product_view', ['id' => $product->getId()])
        );
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertContains($product->getSku(), $result->getContent());
        $this->assertContains($product->getDefaultName()->getString(), $result->getContent());

        $this->assertNotContains(
            $this->translator->trans(
                'oro.frontend.product.view.request_a_quote'
            ),
            $result->getContent()
        );
    }
}
