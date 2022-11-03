<?php

namespace Oro\Bundle\ProductBundle\Tests\Behat\Context;

use Behat\Gherkin\Node\TableNode;
use Oro\Bundle\TestFrameworkBundle\Behat\Client\FileDownloader;
use Oro\Bundle\TestFrameworkBundle\Behat\Context\OroFeatureContext;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\Element;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\OroPageObjectAware;
use Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context\PageObjectDictionary;

class QuickOrderFormContext extends OroFeatureContext implements OroPageObjectAware
{
    use PageObjectDictionary;

    /** @var string */
    private $template;

    /** @var string */
    private $importedFile;

    /**
     * @Given /^I download "([^"]*)"$/
     */
    public function iDownload($template)
    {
        $link = $this->getSession()->getPage()->findLink($template);

        self::assertNotNull($link);
        if (!$link->hasAttribute('href')) {
            throw new \InvalidArgumentException(sprintf(
                'Could not download template "%s"',
                $template
            ));
        }

        $url = $this->locatePath($link->getAttribute('href'));

        $this->template = tempnam(sys_get_temp_dir(), 'quick_order_template_') . '.csv';

        self::assertTrue((new FileDownloader())->download($url, $this->template, $this->getSession()));
    }

    /**
     * @Given /^I fill quick order template with data:$/
     */
    public function iFillQuickOrderTemplateWithData(TableNode $table)
    {
        $this->importedFile = tempnam(sys_get_temp_dir(), 'quick_order_import_') . '.csv';
        $fp = fopen($this->importedFile, 'w');
        $csv = array_map('str_getcsv', file($this->template));
        $headers = array_shift($csv);
        fputcsv($fp, $headers);

        foreach ($table as $row) {
            $values = [];
            foreach ($headers as $header) {
                $value = '';
                foreach ($row as $rowHeader => $rowValue) {
                    if (preg_match(sprintf('/^%s$/i', $rowHeader), $header)) {
                        $value = $rowValue;
                    }
                }

                $values[] = $value;
            }
            fputcsv($fp, $values);
        }

        fclose($fp);
    }

    /**
     * @When /^I import file for quick order$/
     */
    public function iImportFileForQuickOrder()
    {
        $this->createElement('Quick Order Import File Field')->attachFile($this->importedFile);
        $this->waitForAjax();
    }

    /**
     * @Given /^I wait for products to load$/
     */
    public function iWaitForProductsToLoad()
    {
        $this->waitForAjax(200);
    }

    /**
     * @Given /^I wait for "(?P<sku>.*)" price recalculation$/
     */
    public function iWaitForPriceRecalculation($sku)
    {
        $priceField = $this->getPriceField($sku);
        $initialValue = $priceField->getValue();

        $this->spin(function () use ($initialValue, $priceField) {
            return $initialValue != $priceField->getValue();
        }, 3);
    }

    /**
     * @Given /^"(?P<sku>.*)" product should has "(?P<price>.+)" value in price field$/
     */
    public function productShouldHasValueInPriceField($sku, $price)
    {
        $priceField = $this->getPriceField($sku);

        static::assertEquals($price, trim($priceField->getValue()));
    }

    /**
     * @param string $sku
     *
     * @return NodeElement|null
     */
    private function findProductRow($sku)
    {
        /** @var NodeElement[] $productRows */
        $productRows = $this->findAllElements('Quick Add Sku Field', $this->createElement('Quick Order Form'));

        foreach ($productRows as $skuField) {
            if ($skuField->getValue() === $sku) {
                return $skuField->find('xpath', 'ancestor::*[contains(@class, "quick-order-add__row-content")]');
            }
        }

        return null;
    }

    /**
     * @param string $sku
     * @return Element|null
     */
    private function getPriceField($sku)
    {
        return $this->createElement('Quick Add Price Field', $this->findProductRow($sku));
    }

    /**
     * @When /^I import unsupported file for quick order$/
     */
    public function iImportUnsupportedFileForQuickOrder()
    {
        $notSupportedFile = __DIR__ . '/../Features/Fixtures/files_import/000.jpg';

        $this->createElement('Quick Order Import File Field')->attachFile($notSupportedFile);
        $this->waitForAjax();
    }

    /**
     * @Given /^Request a Quote contains products$/
     */
    public function requestAQuoteContainsProducts(TableNode $table)
    {
        $requestAQuote = $this->createElement('RequestAQuoteProducts');

        foreach ($table->getRows() as $row) {
            $productFound = false;
            foreach ($requestAQuote->getElements('RequestAQuoteProductLine') as $productLine) {
                if ($this->matchProductLine($productLine, $row)) {
                    $productFound = true;
                    break;
                }
            }

            self::assertTrue($productFound, sprintf(
                'Product %s, QTY: %s %s has not been found',
                ...$row
            ));
        }
    }

    /**
     * @param Element $productLine
     * @param array   $row
     *
     * @return bool
     */
    private function matchProductLine(Element $productLine, array $row)
    {
        list($name, $quantity, $unit) = $row;

        try {
            self::assertEquals($name, $productLine->getElement('RequestAQuoteProductLineName')->getText());
            self::assertEquals($quantity, $productLine->getElement('RequestAQuoteProductLineQuantity')->getText());
            self::assertEquals($unit, $productLine->getElement('RequestAQuoteProductLineUnit')->getText());
        } catch (\Exception $exception) {
            return false;
        }

        return true;
    }
}
