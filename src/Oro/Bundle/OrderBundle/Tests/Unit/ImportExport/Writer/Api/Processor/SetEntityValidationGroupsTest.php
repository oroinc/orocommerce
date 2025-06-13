<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\ImportExport\Writer\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\FormContext;
use Oro\Bundle\OrderBundle\ImportExport\Writer\Api\Processor\SetEntityValidationGroups;
use PHPUnit\Framework\TestCase;

class SetEntityValidationGroupsTest extends TestCase
{
    public function testProcess(): void
    {
        $context = $this->createMock(FormContext::class);
        $context->expects(self::once())
            ->method('setFormOptions')
            ->with(['validation_groups' => ['external_order_import', 'api']]);

        $processor = new SetEntityValidationGroups();
        $processor->process($context);
    }
}
