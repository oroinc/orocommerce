<?php

namespace Oro\Bundle\RedirectBundle\Tests\Unit\Routing;

use Oro\Bundle\FrontendBundle\Request\FrontendHelper;
use Oro\Bundle\RedirectBundle\Routing\MatchedUrlDecisionMaker;

class MatchedUrlDecisionMakerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var FrontendHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $frontendHelper;

    protected function setUp()
    {
        $this->frontendHelper = $this->getMockBuilder(FrontendHelper::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @dataProvider urlDataProvider
     * @param bool $installed
     * @param bool $isFrontend
     * @param array $skippedUrl
     * @param string $url
     * @param bool $expected
     */
    public function testMatches($installed, $isFrontend, array $skippedUrl, $url, $expected)
    {
        $maker = new MatchedUrlDecisionMaker($this->frontendHelper, $installed);
        foreach ($skippedUrl as $urlPattern) {
            $maker->addSkippedUrlPattern($urlPattern);
        }
        $this->frontendHelper->expects($this->any())
            ->method('isFrontendUrl')
            ->with($url)
            ->willReturn($isFrontend);
        $this->assertEquals($expected, $maker->matches($url));
    }

    /**
     * @return array
     */
    public function urlDataProvider()
    {
        return [
            'allowed url' => [
                true,
                true,
                [],
                '/test',
                true
            ],
            'not installed' => [
                false,
                true,
                [],
                '/test',
                false
            ],
            'not frontend' => [
                true,
                false,
                [],
                '/test',
                false
            ],
            'skipped frontend' => [
                true,
                true,
                ['/api/'],
                '/api/test',
                false
            ],
        ];
    }
}
