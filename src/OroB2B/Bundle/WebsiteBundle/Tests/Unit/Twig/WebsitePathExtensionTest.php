<?php

namespace OroB2B\Bundle\WebsiteBundle\Tests\Unit\Twig;

use Oro\Component\Testing\Unit\EntityTrait;

use OroB2B\Bundle\WebsiteBundle\Entity\Website;
use OroB2B\Bundle\WebsiteBundle\Resolver\WebsiteUrlResolver;
use OroB2B\Bundle\WebsiteBundle\Twig\WebsitePathExtension;

class WebsitePathExtensionTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var WebsiteUrlResolver|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $websiteUrlResolver;

    /**
     * @var WebsitePathExtension
     */
    protected $websitePathExtension;

    protected function setUp()
    {
        $this->websiteUrlResolver = $this->getMockBuilder(WebsiteUrlResolver::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->websitePathExtension = new WebsitePathExtension($this->websiteUrlResolver);
    }

    public function testGetName()
    {
        $this->assertEquals(WebsitePathExtension::NAME, $this->websitePathExtension->getName());
    }

    public function testGetFunctions()
    {
        /** @var \Twig_SimpleFunction[] $filters */
        $functions = $this->websitePathExtension->getFunctions();

        $this->assertCount(2, $functions);

        $availableFunctions = [
            'website_path',
            'website_secure_path'
        ];

        foreach ($functions as $name => $function) {
            $this->assertInstanceOf(\Twig_SimpleFunction::class, $function);
            $this->assertTrue(in_array($name, $availableFunctions, true));
        }
    }

    /**
     * @dataProvider testGetWebsitePathDataProvider
     *
     * @param string $rout
     * @param array $routeParams
     * @param string $expected
     */
    public function testGetWebsitePath($rout, array $routeParams, $expected)
    {
        /** @var Website $website **/
        $website = $this->getEntity(Website::class, ['id' => 42]);

        $this->websiteUrlResolver->expects($this->once())
            ->method('getWebsitePath')
            ->with($rout, $routeParams, $website)
            ->willReturn($expected);

        $actual = $this->websitePathExtension->getWebsitePath($rout, $routeParams, $website);

        $this->assertEquals($expected, $actual);
    }


    /**
     * @dataProvider testGetWebsitePathDataProvider
     *
     * @param string $rout
     * @param array $routeParams
     * @param string $expected
     */
    public function testGetWebsiteSecurePath($rout, array $routeParams, $expected)
    {
        /** @var Website $website **/
        $website = $this->getEntity(Website::class, ['id' => 42]);

        $this->websiteUrlResolver->expects($this->once())
            ->method('getWebsiteSecurePath')
            ->with($rout, $routeParams, $website)
            ->willReturn($expected);

        $actual = $this->websitePathExtension->getWebsiteSecurePath($rout, $routeParams, $website);

        $this->assertEquals($expected, $actual);
    }

    /**
     * @return array
     */
    public function testGetWebsitePathDataProvider()
    {
        return [
            [
                'route' => 'test',
                'routeParams' => ['param' => 123],
                'expected' => 'hhtp://website.com/test?param=123'
            ]
        ];
    }
}
