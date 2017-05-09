<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\ContentBlockView\Model;

use Doctrine\Common\Collections\Collection;

use Oro\Bundle\CMSBundle\ContentBlock\Model\ContentBlockView;

class ContentBlockViewViewTest extends \PHPUnit_Framework_TestCase
{
    public function testGetters()
    {
        $titles = $this->createMock(Collection::class);
        $view = new ContentBlockView('test_alias', $titles, true, 'test_content');

        $this->assertEquals('test_alias', $view->getAlias());
        $this->assertEquals($titles, $view->getTitles());
        $this->assertTrue($view->isEnabled());
        $this->assertEquals('test_content', $view->getContent());
    }
}
