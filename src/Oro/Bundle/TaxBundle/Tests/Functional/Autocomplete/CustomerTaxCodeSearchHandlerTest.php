<?php

namespace Oro\Bundle\TaxBundle\Tests\Functional\Autocomplete;

use Oro\Bundle\TaxBundle\Autocomplete\TaxCodeSearchHandler;
use Oro\Bundle\TaxBundle\Entity\CustomerTaxCode;
use Oro\Bundle\TaxBundle\Tests\Functional\DataFixtures\LoadCustomerTaxCodes;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class CustomerTaxCodeSearchHandlerTest extends WebTestCase
{
    private TaxCodeSearchHandler $searchHandler;

    protected function setUp(): void
    {
        $this->initClient([], $this->generateWsseAuthHeader());
        $this->loadFixtures([LoadCustomerTaxCodes::class]);

        $this->searchHandler = $this->getContainer()->get('oro_tax.form.autocomplete.customer_tax_code.search_handler');
    }

    private function getCustomerTaxCode(string $code): CustomerTaxCode
    {
        return $this->getReference(LoadCustomerTaxCodes::REFERENCE_PREFIX . '.' . $code);
    }

    public function testSearchEntities(): void
    {
        $taxCode1 = $this->getCustomerTaxCode(LoadCustomerTaxCodes::TAX_1);
        $taxCode2 = $this->getCustomerTaxCode(LoadCustomerTaxCodes::TAX_2);
        $taxCode3 = $this->getCustomerTaxCode(LoadCustomerTaxCodes::TAX_3);
        $taxCode4 = $this->getCustomerTaxCode(LoadCustomerTaxCodes::TAX_4);

        $result = $this->searchHandler->search('', 1, 10);
        $this->assertEquals(
            [
                ['id' => $taxCode1->getId(), 'code' => $taxCode1->getCode()],
                ['id' => $taxCode2->getId(), 'code' => $taxCode2->getCode()],
                ['id' => $taxCode3->getId(), 'code' => $taxCode3->getCode()],
                ['id' => $taxCode4->getId(), 'code' => $taxCode4->getCode()]
            ],
            $result['results']
        );
    }

    public function testSearchEntitiesLowerCase(): void
    {
        $taxCode1 = $this->getCustomerTaxCode(LoadCustomerTaxCodes::TAX_1);

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
        $taxCode1 = $this->getCustomerTaxCode(LoadCustomerTaxCodes::TAX_1);

        $result = $this->searchHandler->search('AX1', 1, 10);
        $this->assertEquals(
            [
                ['id' => $taxCode1->getId(), 'code' => $taxCode1->getCode()]
            ],
            $result['results']
        );
    }
}
