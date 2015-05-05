<?php

namespace OroB2B\Bundle\UserAdminBundle\Tests\Unit\Form\Handler;

use Oro\Component\Testing\Unit\FormHandlerTestCase;

use OroB2B\Bundle\UserAdminBundle\Entity\User;
use OroB2B\Bundle\UserAdminBundle\Form\Handler\UserHandler;

class UserHandlerTest extends FormHandlerTestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->entity = new User();
        $this->handler = new UserHandler($this->form, $this->request, $this->manager);
    }
}
