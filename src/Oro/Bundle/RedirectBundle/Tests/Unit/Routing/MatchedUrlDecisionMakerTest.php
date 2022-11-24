<?php

namespace Oro\Bundle\RedirectBundle\Tests\Unit\Routing;

use Oro\Bundle\FrontendBundle\Request\FrontendHelper;
use Oro\Bundle\RedirectBundle\Routing\MatchedUrlDecisionMaker;

class MatchedUrlDecisionMakerTest extends \PHPUnit\Framework\TestCase
{
    /** @var FrontendHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $frontendHelper;

    protected function setUp(): void
    {
        $this->frontendHelper = $this->createMock(FrontendHelper::class);
    }

    /**
     * @dataProvider urlDataProvider
     */
    public function testMatches(bool $isFrontend, array $skippedUrlPatterns, string $url, bool $expected)
    {
        $maker = new MatchedUrlDecisionMaker($skippedUrlPatterns, $this->frontendHelper);
        $this->frontendHelper->expects($this->any())
            ->method('isFrontendUrl')
            ->with($url)
            ->willReturn($isFrontend);
        $this->assertEquals($expected, $maker->matches($url));
    }

    public function urlDataProvider(): array
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

    public function testShouldResetInternalCacheWhenNewPatternIsAdded()
    {
        $this->frontendHelper->expects($this->any())
            ->method('isFrontendUrl')
            ->willReturn(true);

        $maker = new MatchedUrlDecisionMaker(['/folder1/'], $this->frontendHelper);
        $this->assertFalse($maker->matches('/folder1/file.html'));
        $this->assertTrue($maker->matches('/folder2/file.html'));
        $this->assertTrue($maker->matches('/folder3/file.html'));

        $maker->addSkippedUrlPattern('/folder2/');
        $this->assertFalse($maker->matches('/folder1/file.html'));
        $this->assertFalse($maker->matches('/folder2/file.html'));
        $this->assertTrue($maker->matches('/folder3/file.html'));
    }
}
