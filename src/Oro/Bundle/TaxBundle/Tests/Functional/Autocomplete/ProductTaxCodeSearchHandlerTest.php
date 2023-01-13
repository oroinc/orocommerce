<?php

namespace Oro\Bundle\TaxBundle\Tests\Functional\Autocomplete;

use Oro\Bundle\TaxBundle\Autocomplete\TaxCodeSearchHandler;
use Oro\Bundle\TaxBundle\Entity\ProductTaxCode;
use Oro\Bundle\TaxBundle\Tests\Functional\DataFixtures\LoadProductTaxCodes;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class ProductTaxCodeSearchHandlerTest extends WebTestCase
{
    private TaxCodeSearchHandler $searchHandler;

    protected function setUp(): void
    {
        $this->initClient([], $this->generateWsseAuthHeader());
        $this->loadFixtures([LoadProductTaxCodes::class]);

        $this->searchHandler = $this->getContainer()->get('oro_tax.form.autocomplete.product_tax_code.search_handler');
    }

    private function getProductTaxCode(string $code): ProductTaxCode
    {
        return $this->getReference(LoadProductTaxCodes::REFERENCE_PREFIX . '.' . $code);
    }

    public function testSearchEntities(): void
    {
        $taxCode1 = $this->getProductTaxCode(LoadProductTaxCodes::TAX_1);
        $taxCode2 = $this->getProductTaxCode(LoadProductTaxCodes::TAX_2);
        $taxCode3 = $this->getProductTaxCode(LoadProductTaxCodes::TAX_3);

        $result = $this->searchHandler->search('', 1, 10);
        $this->assertEquals(
            [
                ['id' => $taxCode1->getId(), 'code' => $taxCode1->getCode()],
                ['id' => $taxCode2->getId(), 'code' => $taxCode2->getCode()],
                ['id' => $taxCode3->getId(), 'code' => $taxCode3->getCode()]
            ],
            $result['results']
        );
    }

    public function testSearchEntitiesLowerCase(): void
    {
        $taxCode1 = $this->getProductTaxCode(LoadProductTaxCodes::TAX_1);

        $result = $this->searchHandler->search('ax1', 1, 10);
        $this->assertEquals(
            [
                ['id' => $taxCode1->getId(), 'code' => $taxCode1->getCode()]
            ],
            $result['results']
        );
    }

    public function testSearchEntitiesUpperCase(): void
    {
        $taxCode1 = $this->getProductTaxCode(LoadProductTaxCodes::TAX_1);

        $result = $this->searchHandler->search('AX1', 1, 10);
        $this->assertEquals(
            [
                ['id' => $taxCode1->getId(), 'code' => $taxCode1->getCode()]
            ],
            $result['results']
        );
    }
}
