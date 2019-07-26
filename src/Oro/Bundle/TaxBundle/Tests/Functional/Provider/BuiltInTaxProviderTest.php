<?php

namespace Oro\Bundle\TaxBundle\Tests\Functional\Provider;

use Oro\Bundle\TaxBundle\Entity\TaxValue;
use Oro\Bundle\TaxBundle\Model\Result;
use Oro\Bundle\TaxBundle\Provider\BuiltInTaxProvider;
use Oro\Bundle\TaxBundle\Tests\Functional\Provider\Stub\TestProductMapper;
use Oro\Bundle\TaxBundle\Tests\Functional\Traits\OrderTaxHelperTrait;
use Oro\Bundle\TestFrameworkBundle\Entity\Product as TestProduct;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolationPerTest
 */
class BuiltInTaxProviderTest extends WebTestCase
{
    use OrderTaxHelperTrait;

    /** @var BuiltInTaxProvider */
    private $taxProvider;

    protected function setUp()
    {
        $this->initClient();
        $this->client->useHashNavigation(true);

        $taxFactory = $this->getContainer()->get('oro_tax.factory.tax');
        $taxFactory->addMapper(new TestProductMapper());

        $taxValueTransformer = $this->getContainer()->get('oro_tax.tests.alias.transformer.tax_value');

        $taxManager = $this->getContainer()->get('oro_tax.manager.tax_manager');
        $taxManager->addTransformer(TestProduct::class, $taxValueTransformer);

        $this->taxProvider = new BuiltInTaxProvider($taxManager);
    }

    public function testSaveTaxReallySaveToDB()
    {
        $taxValueRepository = $this->getTaxValueEntityManager()->getRepository(TaxValue::class);

        // Tax value table before test should be empty
        $this->assertEmpty($taxValueRepository->findAll());

        $testProduct = new TestProduct();
        $testProduct->setName('my_name');
        $em = $this->getDoctrine()->getManagerForClass(TestProduct::class);
        $em->persist($testProduct);
        $em->flush();

        $result = $this->taxProvider->saveTax($testProduct);
        $this->assertInstanceOf(Result::class, $result);

        // saveTax must save tax information to DB
        $this->assertNotEmpty($taxValueRepository->findAll());
    }
}
