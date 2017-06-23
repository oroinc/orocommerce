<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Processor\Create;

use Oro\Bundle\ApiBundle\Provider\ConfigProvider;
use Oro\Bundle\ApiBundle\Provider\MetadataProvider;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\FormContextStub;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\ProductBundle\Processor\Create\ProcessUnitPrecisionsCreate;
use Oro\Bundle\ProductBundle\Processor\Shared\ProcessPrecisionsAfterValidation;

class ProcessUnitPrecisionsCreateTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var DoctrineHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $doctrineHelper;


    /**
     * @var ProcessUnitPrecisionsCreate
     */
    protected $processPrecisionsAfterValidation;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->doctrineHelper = $this->getMockBuilder(DoctrineHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->processPrecisionsAfterValidation = new ProcessUnitPrecisionsCreate($this->doctrineHelper);
    }

}
