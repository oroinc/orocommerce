<?php

namespace Oro\Bundle\RFPBundle\Tests\Functional\Controller;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductKitData;
use Oro\Bundle\RFPBundle\Form\Type\RequestType;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class AjaxRequestProductKitItemLineItemControllerTest extends WebTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->initClient([], self::generateBasicAuthHeader());
        $this->client->useHashNavigation(true);

        $this->loadFixtures([
            LoadProductKitData::class,
        ]);
    }

    public function testEntryPointActionProductNotFound(): void
    {
        $this->client->followRedirects();
        $this->client->request(
            'GET',
            $this->getUrl(
                'oro_rfp_request_product_kit_item_line_item_entry_point',
                ['id' => \PHP_INT_MAX]
            )
        );

        self::assertResponseStatusCodeEquals($this->client->getResponse(), 404);
    }

    public function testEntryPointActionNotProductKit(): void
    {
        $this->client->request(
            'GET',
            $this->getUrl(
                'oro_rfp_request_product_kit_item_line_item_entry_point',
                ['id' => $this->getReference(LoadProductData::PRODUCT_1)->getId()]
            )
        );

        $response = $this->client->getResponse();

        self::assertJsonResponseStatusCodeEquals($response, 200);
        $jsonContent = json_decode($response->getContent(), true);
        self::assertEmpty($jsonContent);
    }

    public function testEntryPointActionProductKitAndNoRequestProducts(): void
    {
        /** @var Product $productKit1 */
        $productKit1 = $this->getReference(LoadProductKitData::PRODUCT_KIT_1);
        $this->client->request(
            'GET',
            $this->getUrl(
                'oro_rfp_request_product_kit_item_line_item_entry_point',
                ['id' => $productKit1->getId()]
            )
        );

        $response = $this->client->getResponse();

        self::assertJsonResponseStatusCodeEquals($response, 200);
        $content = json_decode($response->getContent(), true);
        self::assertEmpty($content);
    }

    public function testEntryPointActionProductKitWithRequestProducts(): void
    {
        /** @var Product $productKit1 */
        $productKit1 = $this->getReference(LoadProductKitData::PRODUCT_KIT_1);
        $requestProductKey = 4;
        $this->client->request(
            'GET',
            $this->getUrl(
                'oro_rfp_request_product_kit_item_line_item_entry_point',
                ['id' => $productKit1->getId()]
            ),
            [
                RequestType::NAME => [
                    'requestProducts' => [
                        $requestProductKey => [
                            'product' => $productKit1->getId(),
                        ],
                    ],
                ],
            ]
        );

        $response = $this->client->getResponse();

        self::assertJsonResponseStatusCodeEquals($response, 200);
        $content = json_decode($response->getContent(), true);
        self::assertNotEmpty($content);

        foreach ($productKit1->getKitItems() as $kitItem) {
            self::assertStringContainsString(
                sprintf(
                    'name="oro_rfp_request[requestProducts][%d][kitItemLineItems][%d][product]"',
                    $requestProductKey,
                    $kitItem->getId()
                ),
                $content
            );
            self::assertStringContainsString(
                sprintf(
                    'name="oro_rfp_request[requestProducts][%d][kitItemLineItems][%d][quantity]"',
                    $requestProductKey,
                    $kitItem->getId()
                ),
                $content
            );
        }
    }
}
