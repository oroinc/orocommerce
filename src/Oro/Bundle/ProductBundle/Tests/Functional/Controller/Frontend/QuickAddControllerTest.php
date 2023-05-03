<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\Controller\Frontend;

use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadCustomerUserData;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadFrontendProductData;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductUnitPrecisions;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\VisibilityBundle\Tests\Functional\DataFixtures\LoadFrontendProductVisibilityData;

class QuickAddControllerTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient(
            [],
            self::generateBasicAuthHeader(LoadCustomerUserData::AUTH_USER, LoadCustomerUserData::AUTH_PW)
        );
        $this->loadFixtures([
            LoadFrontendProductData::class,
            LoadFrontendProductVisibilityData::class,
            LoadProductUnitPrecisions::class,
        ]);
    }

    /**
     * @dataProvider importFromFileProvider
     */
    public function testImportFromFileAction(
        string $file,
        ?array $expectedValidationResult,
        ?string $formErrorMessage = null
    ): void {
        $crawler = $this->client->request('GET', $this->getUrl('oro_product_frontend_quick_add'));
        $response = $this->client->getResponse();
        self::assertHtmlResponseStatusCodeEquals($response, 200);
        self::assertStringContainsString('Import Excel .CSV File', $response->getContent());

        self::assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);

        $form = $crawler->filter('form[name="oro_product_quick_add_import_from_file"] input[type="submit"]')->form();

        if (file_exists($file)) {
            $form['oro_product_quick_add_import_from_file[file]']->upload($file);
        }

        $this->client->submit($form);

        $response = $this->client->getResponse();
        $responseData = self::getJsonResponseContent($response, 200);

        if ($formErrorMessage) {
            self::assertStringContainsString($formErrorMessage, $responseData['messages']['error'][0]);
        } else {
            self::assertEquals($expectedValidationResult, $responseData);
        }
    }

    public function importFromFileProvider(): array
    {
        $dir = __DIR__ . '/files/';
        $expectedResponse = json_decode(
            file_get_contents(__DIR__ . '/files/expected-quick-order-import-response.json'),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        return [
            'valid CSV' => [
                'file' => $dir . 'quick-order.csv',
                'expectedResponse' => $expectedResponse,
            ],
            'valid XLSX' => [
                'file' => $dir . 'quick-order.xlsx',
                'expectedResponse' => $expectedResponse,
            ],
            'valid ODS' => [
                'file' => $dir . 'quick-order.ods',
                'expectedResponse' => $expectedResponse,
            ],
            'empty CSV' => [
                'file' => $dir . 'quick-order-empty.csv',
                'expectedResponse' => null,
                'formErrorMessage' => 'An empty file is not allowed.',
            ],
            'invalid CSV' => [
                'file' => $dir . 'quick-order-invalid.csv',
                'expectedResponse' => [
                    'success' => false,
                    'collection' => [
                        'errors' => [
                            [
                                'message' => 'We have not been able to identify any product references '
                                    . 'in the uploaded file.',
                            ],
                        ],
                        'items' => [],
                    ],
                ],
            ],
            'invalid DOC' => [
                'file' => $dir . 'quick-order.doc',
                'expectedResponse' => [
                    'success' => false,
                    'collection' => [
                        'errors' => [
                            [
                                'message' => 'This file type is not allowed',
                            ],
                        ],
                        'items' => [],
                    ],
                ],
                'formErrorMessage' => null,
            ],
            'without file' => [
                'file' => '',
                'expectedResponse' => null,
                'formErrorMessage' => 'We have not been able to identify any product references in the uploaded file.',
            ],
        ];
    }
}
