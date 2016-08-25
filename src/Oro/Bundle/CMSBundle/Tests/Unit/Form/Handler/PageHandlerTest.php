<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\Form\Handler;

use Oro\Component\Testing\Unit\FormHandlerTestCase;
use Oro\Bundle\CMSBundle\Entity\Page;
use Oro\Bundle\CMSBundle\Form\Handler\PageHandler;
use Oro\Bundle\RedirectBundle\Manager\SlugManager;

class PageHandlerTest extends FormHandlerTestCase
{
    /**
     * @var SlugManager
     */
    protected $slugManager;

    protected function setUp()
    {
        parent::setUp();

        $this->slugManager = $this->getMockBuilder('Oro\Bundle\RedirectBundle\Manager\SlugManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->entity = new Page();
        $this->handler = new PageHandler($this->form, $this->request, $this->manager, $this->slugManager);
    }

    /**
     * @dataProvider supportedMethods
     * @param string $method
     * @param boolean $isValid
     * @param boolean $isProcessed
     */
    public function testProcessSupportedRequest($method, $isValid, $isProcessed)
    {

        if ($isValid) {
            $this->slugManager->expects($this->once())
                ->method('makeUrlUnique')
                ->with($this->entity->getCurrentSlug());
        } else {
            $this->slugManager->expects($this->never())
                ->method('makeUrlUnique')
                ->with($this->entity->getCurrentSlug());
        }

        parent::testProcessSupportedRequest($method, $isValid, $isProcessed);
    }
}
