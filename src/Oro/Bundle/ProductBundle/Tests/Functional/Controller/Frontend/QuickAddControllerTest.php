<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\Controller\Frontend;

use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadCustomerUserData;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadFrontendProductData;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductUnitPrecisions;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\VisibilityBundle\Tests\Functional\DataFixtures\LoadFrontendProductVisibilityData;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\DomCrawler\Form;

class QuickAddControllerTest extends WebTestCase
{
    const VALIDATION_RESULT_SELECTOR        = 'div.validation-info table tbody tr';
    const VALIDATION_ERRORS_SELECTOR        = 'div.import-errors ol li';

    protected function setUp(): void
    {
        $this->initClient(
            [],
            $this->generateBasicAuthHeader(LoadCustomerUserData::AUTH_USER, LoadCustomerUserData::AUTH_PW)
        );

        $this->loadFixtures(
            [
                LoadFrontendProductData::class,
                LoadFrontendProductVisibilityData::class,
                LoadProductUnitPrecisions::class
            ]
        );
    }

    /**
     * @param string      $file
     * @param null|array  $expectedValidationResult
     * @param null|string $formErrorMessage
     *
     * @dataProvider importFromFileProvider
     */
    public function testImportFromFileAction($file, $expectedValidationResult, $formErrorMessage = null)
    {
        $crawler = $this->client->request('GET', $this->getUrl('oro_product_frontend_quick_add'));
        $response = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($response, 200);
        static::assertStringContainsString('Import Excel .CSV File', $response->getContent());

        $this->assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);

        $form = $crawler->filter('form[name="oro_product_quick_add_import_from_file"] input[type="submit"]')->form();

        $this->updateFormActionToDialog($form);

        if (file_exists($file)) {
            $form['oro_product_quick_add_import_from_file[file]']->upload($file);
        }

        $crawler = $this->client->submit($form);

        $this->assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);

        if ($formErrorMessage) {
            static::assertStringContainsString(htmlentities($formErrorMessage), $crawler->html());
        } else {
            $this->assertEquals($expectedValidationResult, $this->parseValidationResult($crawler));
        }
    }

    /**
     * @return array
     */
    public function importFromFileProvider()
    {
        $dir         = __DIR__ . '/files/';
        $correctCSV  = $dir . 'quick-order.csv';
        $correctXLSX = $dir . 'quick-order.xlsx';
        $correctODS  = $dir . 'quick-order.ods';
        $invalidDOC  = $dir . 'quick-order.doc';
        $emptyCSV    = $dir . 'quick-order-empty.csv';

        $expectedValidationResult = [
            'product-1 - product-1.names.default' => 1,
            'product-3 - product-3.names.default' => 3
        ];

        return [
            'valid CSV'    => [
                'file'                     => $correctCSV,
                'expectedValidationResult' => $expectedValidationResult
            ],
            'valid XLSX'   => [
                'file'                     => $correctXLSX,
                'expectedValidationResult' => $expectedValidationResult
            ],
            'valid ODS'    => [
                'file'                     => $correctODS,
                'expectedValidationResult' => $expectedValidationResult
            ],
            'empty CSV'    => [
                'file'                     => $emptyCSV,
                'expectedValidationResult' => null,
                'formErrorMessage'         =>
                    'We have not been able to identify any product references in the uploaded file'
            ],
            'invalid DOC'  => [
                'file'                     => $invalidDOC,
                'expectedValidationResult' => null,
                'formErrorMessage'         =>
                    'We have not been able to identify any product references in the uploaded file'
            ],
            'without file' => [
                'file'                     => null,
                'expectedValidationResult' => null,
                'formErrorMessage'         =>
                    'We have not been able to identify any product references in the uploaded file'
            ],
        ];
    }

    /**
     * @param Crawler $crawler
     * @return array
     */
    private function parseValidationResult(Crawler $crawler)
    {
        $result = [];
        $crawler->filter(self::VALIDATION_RESULT_SELECTOR)->each(
            function (Crawler $node) use (&$result) {
                $result[trim($node->children()->eq(0)->text())] = (int)$node->children()->eq(1)->text();
            }
        );

        $crawler->filter(self::VALIDATION_ERRORS_SELECTOR)->each(
            function (Crawler $node) use (&$result) {
                $result[self::VALIDATION_ERRORS][] = trim($node->text());
            }
        );

        return $result;
    }

    protected function updateFormActionToDialog(Form $form)
    {
        $form->getFormNode()->setAttribute(
            'action',
            $form->getFormNode()->getAttribute('action') . '?_widgetContainer=dialog'
        );
    }
}
