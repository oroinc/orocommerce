<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Functional\ImportExport\Writer;

use Akeneo\Bundle\BatchBundle\Entity\JobExecution;
use Akeneo\Bundle\BatchBundle\Entity\JobInstance;
use Akeneo\Bundle\BatchBundle\Entity\StepExecution;

use Oro\Bundle\ImportExportBundle\Context\ContextRegistry;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\PricingBundle\ImportExport\Strategy\ProductPriceImportStrategy;
use OroB2B\Bundle\PricingBundle\ImportExport\Writer\ProductPriceWriter;

class ProductPriceWriterTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
    }

    public function testHashArrayClearsOnWrite()
    {
        $jobExecution = new JobExecution();
        $jobExecution->setJobInstance(new JobInstance());
        $stepExecution = new StepExecution('step', $jobExecution);

        /** @var ContextRegistry $contextRegistry */
        $contextRegistry = $this->getContainer()->get('oro_importexport.context_registry');
        $context = $contextRegistry->getByStepExecution($stepExecution);
        $context->setValue(ProductPriceImportStrategy::PROCESSED_ENTITIES_HASH, [md5('hash1'), md5('hash2')]);

        /** @var ProductPriceWriter $writer */
        $writer = $this->getContainer()->get('orob2b_pricing.importexport.writer.product_price');
        $writer->setStepExecution($stepExecution);

        $writer->write([]);

        $this->assertEmpty($context->getValue(ProductPriceImportStrategy::PROCESSED_ENTITIES_HASH));
    }
}
