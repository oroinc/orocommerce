<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\ContentBlock\Model;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\CMSBundle\ContentBlock\Model\ContentBlockView;

class ContentBlockViewTest extends \PHPUnit\Framework\TestCase
{
    public function testGetters()
    {
        /** @var Collection|\PHPUnit\Framework\MockObject\MockObject $titles */
        $titles = $this->createMock(Collection::class);
        $view = new ContentBlockView('test_alias', $titles, true, 'test_content', 'h1 {color: #fff}');

        $this->assertEquals('test_alias', $view->getAlias());
        $this->assertEquals($titles, $view->getTitles());
        $this->assertTrue($view->isEnabled());
        $this->assertEquals('test_content', $view->getContent());
        $this->assertEquals('h1 {color: #fff}', $view->getContentStyle());
    }
}
