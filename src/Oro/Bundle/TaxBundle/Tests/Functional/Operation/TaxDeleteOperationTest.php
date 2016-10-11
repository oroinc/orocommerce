<?php

namespace Oro\Bundle\TaxBundle\Tests\Functional\Operation;

use Oro\Bundle\ActionBundle\Tests\Functional\ActionTestCase;
use Oro\Bundle\TaxBundle\Tests\Functional\DataFixtures\LoadTaxes;

/**
 * @dbIsolation
 */
class TaxDeleteOperationTest extends ActionTestCase
{
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->client->useHashNavigation(true);

        $this->loadFixtures(
            [
                'Oro\Bundle\TaxBundle\Tests\Functional\DataFixtures\LoadTaxes'
            ]
        );
    }

    public function testDelete()
    {
        $tax = $this->getReference(LoadTaxes::REFERENCE_PREFIX . '.' . LoadTaxes::TAX_1);

        $this->assertDeleteOperation($tax->getId(), 'oro_tax.entity.tax.class', 'oro_tax_index');
    }
}
