<?php

namespace OroB2B\Bundle\WarehouseBundle\Tests\Unit\Form\Handler;

use Symfony\Component\HttpFoundation\Request;

use Oro\Component\Testing\Unit\FormHandlerTestCase;

use OroB2B\Bundle\WarehouseBundle\Entity\Warehouse;
use OroB2B\Bundle\WarehouseBundle\Form\Handler\WarehouseHandler;

class WarehouseHandlerTest extends FormHandlerTestCase
{
    public function setUp()
    {
        $this->form = $this->getMockBuilder('Symfony\Component\Form\Form')
            ->disableOriginalConstructor()
            ->getMock();

        $this->request = new Request();

        $this->manager = $this->getMockBuilder('Doctrine\Common\Persistence\ObjectManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->entity = new Warehouse();

        $this->handler = new WarehouseHandler(
            $this->form,
            $this->request,
            $this->manager
        );
    }
}
