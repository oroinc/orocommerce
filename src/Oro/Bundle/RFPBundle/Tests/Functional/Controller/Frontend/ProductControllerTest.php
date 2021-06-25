<?php

namespace Oro\Bundle\RFPBundle\Tests\Functional\Controller\Frontend;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConfigBundle\Tests\Functional\Traits\ConfigManagerAwareTestTrait;
use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadCustomerUserData;
use Oro\Bundle\FrontendTestFrameworkBundle\Test\Client;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Symfony\Bundle\FrameworkBundle\Translation\Translator;

class ProductControllerTest extends WebTestCase
{
    use ConfigManagerAwareTestTrait;

    const RFP_PRODUCT_VISIBILITY_KEY = 'oro_rfp.frontend_product_visibility';

    /** @var Client */
    protected $client;

    /** @var Translator $translator*/
    protected $translator;

    /** @var ConfigManager $configManager */
    protected $configManager;

    /** @var ConfigManager $globalConfigManager */
    protected $globalConfigManager;

    protected function setUp(): void
    {
        $this->initClient(
            [],
            $this->generateBasicAuthHeader(LoadCustomerUserData::AUTH_USER, LoadCustomerUserData::AUTH_PW)
        );

        $this->loadFixtures([LoadProductData::class]);

        $this->translator = $this->getContainer()->get('translator');

        $this->configManager = self::getConfigManager('global');
        $this->configManager->set(self::RFP_PRODUCT_VISIBILITY_KEY, [Product::INVENTORY_STATUS_OUT_OF_STOCK]);
        $this->configManager->flush();
    }

    public function testViewProductWithRequestQuoteAvailable()
    {
        static::assertStringContainsString(
            $this->translator->trans('oro.frontend.product.view.request_a_quote'),
            $this->viewProduct()->getContent()
        );
    }

    public function testViewProductWithoutRequestQuoteAvailable()
    {
        $this->configManager = self::getConfigManager('global');
        $this->configManager->set(self::RFP_PRODUCT_VISIBILITY_KEY, [Product::INVENTORY_STATUS_IN_STOCK]);
        $this->configManager->flush();

        static::assertStringNotContainsString(
            $this->translator->trans('oro.frontend.product.view.request_a_quote'),
            $this->viewProduct()->getContent()
        );
    }

    /**
     * @return null|\Symfony\Component\HttpFoundation\Response
     */
    protected function viewProduct()
    {
        $product = $this->getReference(LoadProductData::PRODUCT_3);

        $this->assertInstanceOf('Oro\Bundle\ProductBundle\Entity\Product', $product);

        $this->client->request('GET', $this->getUrl('oro_product_frontend_product_view', ['id' => $product->getId()]));
        $result = $this->client->getResponse();

        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        static::assertStringContainsString($product->getSku(), $result->getContent());
        static::assertStringContainsString($product->getDefaultName()->getString(), $result->getContent());

        return $result;
    }
}
