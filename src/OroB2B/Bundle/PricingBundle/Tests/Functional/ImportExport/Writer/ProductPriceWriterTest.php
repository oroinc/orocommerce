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
        $this->assertNotEmpty($context->getValue(ProductPriceImportStrategy::PROCESSED_ENTITIES_HASH));

        $container = $this->getContainer();

        $writer = new ProductPriceWriter(
            $container->get('doctrine'),
            $container->get('event_dispatcher'),
            $container->get('oro_importexport.context_registry'),
            $container->get('oro_integration.logger.strategy')
        );
        $writer->setStepExecution($stepExecution);

        $writer->write([]);

        $this->assertEmpty($context->getValue(ProductPriceImportStrategy::PROCESSED_ENTITIES_HASH));
    }
}
