<?php

namespace Oro\Bundle\TaxBundle\Tests\Functional\Autocomplete;

use Oro\Bundle\TaxBundle\Autocomplete\SearchHandler;
use Oro\Bundle\TaxBundle\Tests\Functional\DataFixtures\LoadProductTaxCodes;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class SearchHandlerTest extends WebTestCase
{
    /** @var SearchHandler */
    private $searchHandler;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->initClient([], $this->generateWsseAuthHeader());
        $this->loadFixtures([LoadProductTaxCodes::class]);

        $this->searchHandler = $this->getContainer()->get(
            'oro_tax.form.autocomplete.product_tax_code.entity_search_handler'
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown(): void
    {
        unset($this->searchHandler);
    }

    public function testSearchEntities()
    {
        $page = 1;
        $perPage = 10;

        $taxCode1 = $this->getReference(LoadProductTaxCodes::REFERENCE_PREFIX . '.' . LoadProductTaxCodes::TAX_1);
        $taxCode2 = $this->getReference(LoadProductTaxCodes::REFERENCE_PREFIX . '.' . LoadProductTaxCodes::TAX_2);
        $taxCode3 = $this->getReference(LoadProductTaxCodes::REFERENCE_PREFIX . '.' . LoadProductTaxCodes::TAX_3);

        $result = $this->searchHandler->search('', $page, $perPage);
        $expected = [
            [
                'id' => $taxCode1->getId(),
                'code' => $taxCode1->getCode()
            ],
            [
                'id' => $taxCode2->getId(),
                'code' => $taxCode2->getCode()
            ],
            [
                'id' => $taxCode3->getId(),
                'code' => $taxCode3->getCode()
            ]
        ];

        $this->assertEquals($expected, $result['results']);
    }

    public function testSearchEntitiesLowerCase()
    {
        $page = 1;
        $perPage = 10;

        $taxCode1 = $this->getReference(LoadProductTaxCodes::REFERENCE_PREFIX . '.' . LoadProductTaxCodes::TAX_1);
        $result = $this->searchHandler->search('ax1', $page, $perPage);
        $expected = [
            [
                'id' => $taxCode1->getId(),
                'code' => $taxCode1->getCode()
            ]
        ];

        $this->assertEquals($expected, $result['results']);
    }

    public function testSearchEntitiesUpperCase()
    {
        $page = 1;
        $perPage = 10;

        $taxCode1 = $this->getReference(LoadProductTaxCodes::REFERENCE_PREFIX . '.' . LoadProductTaxCodes::TAX_1);
        $result = $this->searchHandler->search('AX1', $page, $perPage);
        $expected = [
            [
                'id' => $taxCode1->getId(),
                'code' => $taxCode1->getCode()
            ]
        ];

        $this->assertEquals($expected, $result['results']);
    }
}
