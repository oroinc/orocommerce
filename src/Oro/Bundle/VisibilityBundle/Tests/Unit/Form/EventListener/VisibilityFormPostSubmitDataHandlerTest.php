<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Unit\Form\EventListener;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\VisibilityBundle\Form\EventListener\VisibilityFormFieldDataProvider;
use Oro\Bundle\VisibilityBundle\Form\EventListener\VisibilityFormPostSubmitDataHandler;

class VisibilityFormPostSubmitDataHandlerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var VisibilityFormPostSubmitDataHandler
     */
    protected $dateHandler;

    /**
     * @var ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $registry;

    /**
     * @var VisibilityFormFieldDataProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $fieldDataProvider;

    protected function setUp()
    {
        $this->registry = $this->getMock(ManagerRegistry::class);

        $this->fieldDataProvider = $this->getMockBuilder(VisibilityFormFieldDataProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->dateHandler = new VisibilityFormPostSubmitDataHandler(
            $this->registry,
            $this->fieldDataProvider
        );
    }

    public function testSaveForm()
    {
        // todo
    }
}
