<?php

namespace Oro\Bundle\OrderBundle\Tests\Functional\Controller\Frontend;

use Oro\Bundle\FrontendTestFrameworkBundle\Test\FrontendWebTestCase;
use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrderLineItemData;
use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrders;
use Oro\Bundle\OrderBundle\Tests\Functional\EventListener\ORM\PreviouslyPurchasedFeatureTrait;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\WebsiteSearchBundle\Tests\Functional\WebsiteSearchExtensionTrait;

class ProductControllerAnonymousTest extends FrontendWebTestCase
{
    use WebsiteSearchExtensionTrait;
    use PreviouslyPurchasedFeatureTrait;

    /** {@inheritdoc} */
    public function setUp()
    {
        /** login as anonymous user */
        $this->initClient();

        $this->loadFixtures(
            [
                LoadProductData::class,
                LoadOrders::class,
                LoadOrderLineItemData::class,
            ]
        );

        $this->enablePreviouslyPurchasedFeature($this->getReference('defaultWebsite'));

        $this->reindexProductData();
    }

    public function testPreviouslyPurchasedGridIfUserNonAuth()
    {
        $gridName = ProductControllerTest::FRONTEND_GRID_NAME;
        $response = $this->client->requestFrontendGrid($gridName, [], true);

        $this->assertResponseStatusCodeEquals(
            $response,
            401,
            sprintf('Please check acl for "%s"', $gridName)
        );
    }
}
