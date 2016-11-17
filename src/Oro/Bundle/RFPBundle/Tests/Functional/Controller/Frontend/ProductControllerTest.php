<?php

namespace Oro\Bundle\RFPBundle\Tests\Functional\Controller\Frontend;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadAccountUserData;
use Oro\Bundle\FrontendTestFrameworkBundle\Test\Client;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Symfony\Bundle\FrameworkBundle\Translation\Translator;

/**
 * @dbIsolation
 */
class ProductControllerTest extends WebTestCase
{
    const RFP_PRODUCT_VISIBILITY_KEY = 'oro_rfp.frontend_product_visibility';

    /** @var Client */
    protected $client;

    /** @var Translator $translator*/
    protected $translator;

    /** @var ConfigManager $configManager */
    protected $configManager;

    protected function setUp()
    {
        $this->initClient(
            [],
            $this->generateBasicAuthHeader(LoadAccountUserData::AUTH_USER, LoadAccountUserData::AUTH_PW)
        );

        $this->loadFixtures([LoadProductData::class]);

        $this->translator = $this->getContainer()->get('translator');

        $this->configManager = $this->getContainer()->get('oro_config.manager');
        $this->configManager->set(self::RFP_PRODUCT_VISIBILITY_KEY, [Product::INVENTORY_STATUS_OUT_OF_STOCK]);
        $this->configManager->flush();
    }

    public function testViewProductWithRequestQuoteAvailable()
    {
        $this->assertContains(
            $this->translator->trans('oro.frontend.product.view.request_a_quote'),
            $this->viewProduct()->getContent()
        );
    }

    public function testViewProductWithoutRequestQuoteAvailable()
    {
        $this->configManager = $this->getContainer()->get('oro_config.global');
        $this->configManager->set(self::RFP_PRODUCT_VISIBILITY_KEY, [Product::INVENTORY_STATUS_IN_STOCK]);
        $this->configManager->flush();

        $this->assertNotContains(
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
        $this->assertContains($product->getSku(), $result->getContent());
        $this->assertContains($product->getDefaultName()->getString(), $result->getContent());

        return $result;
    }

    protected function tearDown()
    {
        unset($this->translator, $this->client);

        $this->configManager->reset(self::RFP_PRODUCT_VISIBILITY_KEY);
        $this->configManager->flush();
    }
}
