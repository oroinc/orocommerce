<?php

namespace Oro\Bundle\RedirectBundle\Tests\Unit\Routing;

use Oro\Bundle\FrontendBundle\Request\FrontendHelper;
use Oro\Bundle\RedirectBundle\Routing\MatchedUrlDecisionMaker;

class MatchedUrlDecisionMakerTest extends \PHPUnit\Framework\TestCase
{
    /** @var FrontendHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $frontendHelper;

    protected function setUp()
    {
        $this->frontendHelper = $this->getMockBuilder(FrontendHelper::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @dataProvider urlDataProvider
     */
    public function testMatches(bool $isFrontend, array $skippedUrl, string $url, bool $expected)
    {
        $maker = new MatchedUrlDecisionMaker($this->frontendHelper);
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
                [],
                '/test',
                true
            ],
            'not frontend' => [
                false,
                [],
                '/test',
                false
            ],
            'skipped frontend' => [
                true,
                ['/api/'],
                '/api/test',
                false
            ],
        ];
    }
}
