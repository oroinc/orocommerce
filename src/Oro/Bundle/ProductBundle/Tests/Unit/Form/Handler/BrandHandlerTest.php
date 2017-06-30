<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Form\Handler;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Component\Testing\Unit\FormHandlerTestCase;
use Oro\Bundle\ProductBundle\Form\Handler\BrandHandler;
use Oro\Bundle\ProductBundle\Entity\Brand;

class BrandHandlerTest extends FormHandlerTestCase
{
    /**
     * @var Brand
     */
    protected $entity;

    protected function setUp()
    {
        parent::setUp();
        $this->entity = $this->createMock(Brand::class);
        $translator= $this->createMock(TranslatorInterface::class);

        $this->handler = new BrandHandler($this->form, $this->request, $this->manager, $translator);
    }

    public function testProcessValidData()
    {
        $this->form->expects($this->once())
            ->method('setData')
            ->with($this->entity);

        $this->request->setMethod('POST');

        $this->form->expects($this->once())
            ->method('submit')
            ->with($this->request);

        $this->form->expects($this->once())
            ->method('isValid')
            ->will($this->returnValue(true));

        $this->manager->expects($this->any())
            ->method('persist');

        $this->manager->expects($this->any())
            ->method('flush');

        $this->assertTrue($this->handler->process($this->entity));
    }
}
