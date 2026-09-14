<?php

declare(strict_types=1);

namespace Oro\Bundle\RedirectBundle\Tests\Unit\Api\Processor;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\CustomizeLoadedData\CustomizeLoadedDataProcessorTestCase;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\RedirectBundle\Api\Processor\ComputeCanonicalUrlField;
use Oro\Bundle\RedirectBundle\Generator\BatchCanonicalUrlGeneratorInterface;
use Oro\Bundle\RedirectBundle\Tests\Unit\Entity\SluggableEntityStub;
use PHPUnit\Framework\MockObject\MockObject;

class ComputeCanonicalUrlFieldTest extends CustomizeLoadedDataProcessorTestCase
{
    private DoctrineHelper&MockObject $doctrineHelper;
    private BatchCanonicalUrlGeneratorInterface&MockObject $canonicalUrlGenerator;
    private ComputeCanonicalUrlField $processor;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->canonicalUrlGenerator = $this->createMock(BatchCanonicalUrlGeneratorInterface::class);

        $this->processor = new ComputeCanonicalUrlField($this->doctrineHelper, $this->canonicalUrlGenerator);
    }

    public function testProcessWhenFieldIsNotRequested(): void
    {
        $config = new EntityDefinitionConfig();
        $config->addField('id');
        $config->addField('canonicalUrl')->setExcluded(true);

        $this->doctrineHelper->expects(self::never())
            ->method(self::anything());
        $this->canonicalUrlGenerator->expects(self::never())
            ->method(self::anything());

        $this->context->setClassName(SluggableEntityStub::class);
        $this->context->setConfig($config);
        $this->context->setData([['id' => 1]]);
        $this->processor->process($this->context);

        self::assertSame([['id' => 1]], $this->context->getData());
    }

    public function testProcess(): void
    {
        $config = new EntityDefinitionConfig();
        $config->addField('id');
        $config->addField('canonicalUrl');

        $this->doctrineHelper->expects(self::once())
            ->method('getManageableEntityClass')
            ->with(SluggableEntityStub::class, $config)
            ->willReturn(SluggableEntityStub::class);
        $this->doctrineHelper->expects(self::once())
            ->method('getSingleEntityIdentifierFieldName')
            ->with(SluggableEntityStub::class)
            ->willReturn('id');
        // neither the localization nor the website is passed, the generator resolves them
        $this->canonicalUrlGenerator->expects(self::once())
            ->method('getUrls')
            ->with(SluggableEntityStub::class, [1, 2])
            ->willReturn([1 => 'http://example.com/one', 2 => 'http://example.com/two']);

        $this->context->setClassName(SluggableEntityStub::class);
        $this->context->setConfig($config);
        $this->context->setData([['id' => 1], ['id' => 2]]);
        $this->processor->process($this->context);

        self::assertSame(
            [
                ['id' => 1, 'canonicalUrl' => 'http://example.com/one'],
                ['id' => 2, 'canonicalUrl' => 'http://example.com/two'],
            ],
            $this->context->getData()
        );
    }
}
