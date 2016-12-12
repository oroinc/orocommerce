<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\Provider;

use Oro\Bundle\CMSBundle\Entity\Page;
use Oro\Bundle\CMSBundle\Provider\PageRoutingInformationProvider;
use Oro\Bundle\RedirectBundle\Provider\RoutingInformationProviderInterface;
use Oro\Component\Routing\RouteData;
use Oro\Component\Testing\Unit\EntityTrait;

class PageRoutingInformationProviderTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var RoutingInformationProviderInterface
     */
    protected $provider;

    protected function setUp()
    {
        $this->provider = new PageRoutingInformationProvider();
    }

    public function testIsSupported()
    {
        $this->assertTrue($this->provider->isSupported(new Page()));
    }

    public function testIsNotSupported()
    {
        $this->assertFalse($this->provider->isSupported(new \DateTime()));
    }

    public function testGetUrlPrefix()
    {
        $this->assertSame('', $this->provider->getUrlPrefix(new Page()));
    }

    public function testGetRouteData()
    {
        $this->assertEquals(
            new RouteData('oro_cms_frontend_page_view', ['id' => 42]),
            $this->provider->getRouteData($this->getEntity(Page::class, ['id' => 42]))
        );
    }
}
