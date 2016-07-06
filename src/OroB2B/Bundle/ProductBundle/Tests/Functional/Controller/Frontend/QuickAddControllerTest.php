<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Functional\Controller\Frontend;

use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\DomCrawler\Form;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadAccountUserData;

use OroB2B\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;

abstract class QuickAddControllerTest extends WebTestCase
{
    const VALIDATION_TOTAL_ROWS = 'Total number of records';
    const VALIDATION_VALID_ROWS = 'Valid items';
    const VALIDATION_ERROR_ROWS = 'Records with errors';
    const VALIDATION_ERRORS = 'Errors';
    const VALIDATION_RESULT_SELECTOR = 'div.validation-info table tbody tr';
    const VALIDATION_ERRORS_SELECTOR = 'div.import-errors ol li';
    const VALIDATION_ERROR_NOT_FOUND = 'Item number %s does not found.';
    const VALIDATION_ERROR_MALFORMED = 'Row #%d has invalid format.';

    protected function setUp()
    {
        $this->initClient(
            [],
            $this->generateBasicAuthHeader(LoadAccountUserData::AUTH_USER, LoadAccountUserData::AUTH_PW)
        );

        $this->loadFixtures([
            'OroB2B\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData',
            'OroB2B\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductUnitPrecisions'
        ]);
    }

    /**
     * @param string $processorName
     * @param string $routerName
     * @param array $routerParams
     * @param string $expectedMessage
     *
     * @dataProvider validationResultProvider
     */
    public function testCopyPasteAction($processorName, $routerName, array $routerParams, $expectedMessage)
    {
        $example = [
            LoadProductData::PRODUCT_1 . ", 1",
            LoadProductData::PRODUCT_2 . ",     2",
            LoadProductData::PRODUCT_3 . "\t3",
            "not-existing-product\t  4",
        ];

        $expectedValidationResult = [
            self::VALIDATION_TOTAL_ROWS => 4,
            self::VALIDATION_VALID_ROWS => 3,
            self::VALIDATION_ERROR_ROWS => 1,
            self::VALIDATION_ERRORS => [
                sprintf(self::VALIDATION_ERROR_NOT_FOUND, 'not-existing-product'),
            ]
        ];

        $crawler = $this->client->request('GET', $this->getUrl('orob2b_product_frontend_quick_add'));
        $this->assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);
        $this->assertContains(htmlentities('Paste your order'), $crawler->html());

        $form = $crawler->selectButton('Verify Order')->form();
        $this->updateFormActionToDialog($form);
        $form['orob2b_product_quick_add_copy_paste[copyPaste]'] = implode(PHP_EOL, $example);

        $crawler = $this->client->submit($form);

        $this->assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);
        $this->assertEquals($expectedValidationResult, $this->parseValidationResult($crawler));

        //test result form actions (create rfp, create order, add to shopping list)
        $resultForm = $crawler->selectButton('Cancel')->form();
        $this->updateFormActionToDialog($resultForm);
        $resultForm['orob2b_product_quick_add[component]'] = $processorName;
        $this->client->submit($resultForm);
        $response = $this->client->getResponse();
        $targetUrl = $this->parseTargetUrl($response->getContent());

        $this->assertHtmlResponseStatusCodeEquals($response, 200);

        $expectedTargetUrl = $this->getUrl($routerName, $routerParams);
        $this->assertEquals($expectedTargetUrl, $targetUrl);

        $this->client->request('GET', $targetUrl);
        $response = $this->client->getResponse();

        $this->assertHtmlResponseStatusCodeEquals($response, 200);

        if ($expectedMessage) {
            $this->assertContains($expectedMessage, $this->client->getResponse()->getContent());
        }
    }

    /**
     * @param string $file
     * @param null|array $expectedValidationResult
     * @param null|string $formErrorMessage
     *
     * @dataProvider importFromFileProvider
     */
    public function testImportFromFileAction($file, $expectedValidationResult, $formErrorMessage = null)
    {
        $this->client->request('GET', $this->getUrl('orob2b_product_frontend_quick_add'));
        $response = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($response, 200);
        $this->assertContains('Import Excel .CSV File', $response->getContent());

        $crawler = $this->client->request(
            'GET',
            $this->getUrl(
                'orob2b_product_frontend_quick_add_import',
                ['_widgetContainer' => 'dialog']
            )
        );
        $this->assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);


        $form = $crawler->selectButton('Upload')->form();
        $this->updateFormActionToDialog($form);

        if (file_exists($file)) {
            $form['orob2b_product_quick_add_import_from_file[file]']->upload($file);
        }

        $crawler = $this->client->submit($form);

        $this->assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);

        if ($formErrorMessage) {
            $this->assertContains(htmlentities($formErrorMessage), $crawler->html());
        } else {
            $this->assertEquals($expectedValidationResult, $this->parseValidationResult($crawler));
        }
    }

    /**
     * @return array
     */
    abstract public function validationResultProvider();

    /**
     * @return array
     */
    public function importFromFileProvider()
    {
        $dir = __DIR__ . '/files/';
        $correctCSV = $dir . 'quick-order.csv';
        $correctXLSX = $dir . 'quick-order.xlsx';
        $correctODS = $dir . 'quick-order.ods';
        $invalidDOC = $dir . 'quick-order.doc';
        $emptyCSV = $dir . 'quick-order-empty.csv';

        $expectedValidationResult = [
            self::VALIDATION_TOTAL_ROWS => 6,
            self::VALIDATION_VALID_ROWS => 3,
            self::VALIDATION_ERROR_ROWS => 3,
            self::VALIDATION_ERRORS => [
                sprintf(self::VALIDATION_ERROR_NOT_FOUND, 'SKU1'),
                sprintf(self::VALIDATION_ERROR_MALFORMED, 5),
                sprintf(self::VALIDATION_ERROR_MALFORMED, 6)
            ]
        ];

        return [
            'valid CSV' => [
                'file' => $correctCSV,
                'expectedValidationResult' => $expectedValidationResult
            ],
            'valid XLSX' => [
                'file' => $correctXLSX,
                'expectedValidationResult' => $expectedValidationResult
            ],
            'valid ODS' => [
                'file' => $correctODS,
                'expectedValidationResult' => $expectedValidationResult
            ],
            'empty CSV' => [
                'file' => $emptyCSV,
                'expectedValidationResult' => null,
                'formErrorMessage' => 'An empty file is not allowed.'
            ],
            'invalid DOC' => [
                'file' => $invalidDOC,
                'expectedValidationResult' => null,
                'formErrorMessage' => 'This file type is not allowed'
            ],
            'without file' => [
                'file' => null,
                'expectedValidationResult' => null,
                'formErrorMessage' => 'This value should not be blank'
            ]
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
                $result[trim($node->children()->eq(0)->text())] = (int) $node->children()->eq(1)->text();
            }
        );

        $crawler->filter(self::VALIDATION_ERRORS_SELECTOR)->each(
            function (Crawler $node) use (&$result) {
                $result[self::VALIDATION_ERRORS][] = trim($node->text());
            }
        );

        return $result;
    }

    /**
     * @param string $content
     * @return string
     */
    private function parseTargetUrl($content)
    {
        $pattern = '/data-page-component-options\s*=\s*"([^"]+)"/';
        $this->assertRegExp($pattern, $content);
        preg_match($pattern, $content, $matches);

        $parsedOptions = json_decode(html_entity_decode($matches[1]), true);
        $this->assertArrayHasKey('targetUrl', $parsedOptions);

        return stripslashes($parsedOptions['targetUrl']);
    }

    /**
     * @param Form $form
     */
    protected function updateFormActionToDialog(Form $form)
    {
        /** TODO Change after BAP-1813 */
        $form->getFormNode()->setAttribute(
            'action',
            $form->getFormNode()->getAttribute('action') . '?_widgetContainer=dialog'
        );
    }
}
