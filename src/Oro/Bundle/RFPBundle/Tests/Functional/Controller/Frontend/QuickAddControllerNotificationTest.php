<?php

namespace Oro\Bundle\RFPBundle\Tests\Functional\Controller\Frontend;

use Oro\Bundle\ConfigBundle\Tests\Functional\Traits\ConfigManagerAwareTestTrait;
use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadCustomerUserData;
use Oro\Bundle\ProductBundle\ComponentProcessor\DataStorageAwareComponentProcessor;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadFrontendProductData;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;

class QuickAddControllerNotificationTest extends WebTestCase
{
    use ConfigManagerAwareTestTrait;

    private const RFP_PRODUCT_VISIBILITY_KEY = 'oro_rfp.frontend_product_visibility';

    protected function setUp(): void
    {
        $this->initClient(
            [],
            $this->generateBasicAuthHeader(LoadCustomerUserData::AUTH_USER, LoadCustomerUserData::AUTH_PW)
        );

        $this->getContainer()->get('request_stack')->push(Request::create(''));
        $this->loadFixtures([LoadFrontendProductData::class]);

        $configManager = self::getConfigManager();
        $configManager->set(self::RFP_PRODUCT_VISIBILITY_KEY, [Product::INVENTORY_STATUS_IN_STOCK]);
        $configManager->flush();
    }

    /**
     * @dataProvider productProvider
     */
    public function testNotification(
        string $processorName,
        array $products,
        string $expectedMessage
    ) {
        $this->markTestSkipped(
            'Waiting for new quick order page to be finished'
        );

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

        self::assertStringContainsString(
            $this->getContainer()->get('translator')->trans($expectedMessage),
            $this->client->getResponse()->getContent()
        );
    }

    public function productProvider(): array
    {
        return [
            'no_products_be_added_to_rfq' => [
                'processorName' => 'oro_rfp.processor.quick_add',
                'products' => [
                    [
                        'productSku' => LoadProductData::PRODUCT_3,
                        'productQuantity' => 1,
                        'productUnit' => 'item'
                    ],
                ],
                'expectedMessage' => 'oro.frontend.rfp.data_storage.no_products_be_added_to_rfq',
            ],
            'cannot_be_added_to_rfq' => [
                'processorName' => 'oro_rfp.processor.quick_add',
                'products' => [
                    [
                        'productSku' => LoadProductData::PRODUCT_2,
                        'productQuantity' => 1,
                        'productUnit' => 'item'
                    ],
                    [
                        'productSku' => LoadProductData::PRODUCT_3,
                        'productQuantity' => 1,
                        'productUnit' => 'item'
                    ],
                ],
                'expectedMessage' => 'oro.frontend.rfp.data_storage.cannot_be_added_to_rfq',
            ],
        ];
    }
}
