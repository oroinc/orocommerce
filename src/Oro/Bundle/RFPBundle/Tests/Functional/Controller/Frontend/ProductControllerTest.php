<?php

namespace Oro\Bundle\RFPBundle\Tests\Functional\Controller\Frontend;

use Oro\Bundle\ConfigBundle\Tests\Functional\Traits\ConfigManagerAwareTestTrait;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadCustomerUserData;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;

class ProductControllerTest extends WebTestCase
{
    use ConfigManagerAwareTestTrait;

    #[\Override]
    protected function setUp(): void
    {
        self::markTestSkipped('Will be fixed in BAP-22773');
        $this->initClient(
            [],
            self::generateBasicAuthHeader(LoadCustomerUserData::AUTH_USER, LoadCustomerUserData::AUTH_PW)
        );
        $this->loadFixtures([LoadProductData::class]);
    }

    private function getTranslator(): TranslatorInterface
    {
        return self::getContainer()->get('translator');
    }

    public function testViewProductWithRequestQuoteAvailable()
    {
        $configManager = self::getConfigManager();
        $initialRfpProductVisibility = $configManager->get('oro_rfp.frontend_product_visibility');
        $configManager->set('oro_rfp.frontend_product_visibility', [
            ExtendHelper::buildEnumOptionId(Product::INVENTORY_STATUS_ENUM_CODE, Product::INVENTORY_STATUS_OUT_OF_STOCK)
        ]);
        $configManager->flush();
        try {
            self::assertStringContainsString(
                $this->getTranslator()->trans('oro.frontend.product.view.request_a_quote'),
                $this->viewProduct()->getContent()
            );
        } finally {
            $configManager->set('oro_rfp.frontend_product_visibility', $initialRfpProductVisibility);
            $configManager->flush();
        }
    }

    public function testViewProductWithoutRequestQuoteAvailable()
    {
        $configManager = self::getConfigManager();
        $initialRfpProductVisibility = $configManager->get('oro_rfp.frontend_product_visibility');
        $configManager->set('oro_rfp.frontend_product_visibility', [Product::INVENTORY_STATUS_IN_STOCK]);
        $configManager->flush();
        try {
            self::assertStringNotContainsString(
                $this->getTranslator()->trans('oro.frontend.product.view.request_a_quote'),
                $this->viewProduct()->getContent()
            );
        } finally {
            $configManager->set('oro_rfp.frontend_product_visibility', $initialRfpProductVisibility);
            $configManager->flush();
        }
    }

    private function viewProduct(): Response
    {
        $product = $this->getReference(LoadProductData::PRODUCT_3);

        $this->assertInstanceOf(Product::class, $product);

        $this->client->request(
            'GET',
            $this->getUrl('oro_product_frontend_product_view', ['id' => $product->getId()])
        );
        $result = $this->client->getResponse();

        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        self::assertStringContainsString($product->getSku(), $result->getContent());
        self::assertStringContainsString($product->getDefaultName()->getString(), $result->getContent());

        return $result;
    }
}
