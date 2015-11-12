<?php

namespace OroB2B\Bundle\WebsiteBundle\Tests\Unit\Form\Handler;

use Symfony\Component\HttpFoundation\Request;

use Oro\Component\Testing\Unit\FormHandlerTestCase;

use OroB2B\Bundle\WebsiteBundle\Entity\Website;
use OroB2B\Bundle\WebsiteBundle\Form\Handler\WebsiteHandler;

class WebsiteHandlerTest extends FormHandlerTestCase
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

        $this->entity = new Website();

        $this->handler = new WebsiteHandler(
            $this->form,
            $this->request,
            $this->manager
        );
    }
}
