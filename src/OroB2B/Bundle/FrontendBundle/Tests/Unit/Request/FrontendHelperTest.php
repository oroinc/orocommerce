<?php

namespace OroB2B\Bundle\FrontendBundle\Tests\Unit\Request;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

use OroB2B\Bundle\FrontendBundle\Request\FrontendHelper;

class FrontendHelperTest extends \PHPUnit_Framework_TestCase
{
    const BACKEND_PREFIX = '/admin';

    /**
     * @param string $path
     * @param bool $isFrontend
     * @dataProvider isFrontendRequestDataProvider
     */
    public function testIsFrontendRequest($path, $isFrontend)
    {
        $request = $path ? Request::create($path) : null;

        /** @var RequestStack|\PHPUnit_Framework_MockObject_MockObject $requestStack1 */
        $requestStack1 = $this->getMock('Symfony\Component\HttpFoundation\RequestStack');
        $requestStack1->expects($this->once())->method('getCurrentRequest')->willReturn($request);

        $helper = new FrontendHelper(self::BACKEND_PREFIX, $requestStack1);
        $this->assertSame($isFrontend, $helper->isFrontendRequest());

        /** @var RequestStack|\PHPUnit_Framework_MockObject_MockObject $requestStack2 */
        $requestStack2 = $this->getMock('Symfony\Component\HttpFoundation\RequestStack');
        $requestStack2->expects($request ? $this->never() : $this->once())->method('getCurrentRequest');

        $helper = new FrontendHelper(self::BACKEND_PREFIX, $requestStack2);
        $this->assertSame($isFrontend, $helper->isFrontendRequest($request));
    }

    /**
     * @return array
     */
    public function isFrontendRequestDataProvider()
    {
        return [
            'no request' => [
                'path' => null,
                'isFrontend' => false,
            ],
            'backend' => [
                'path' => self::BACKEND_PREFIX . '/backend',
                'isFrontend' => false,
            ],
            'frontend' => [
                'path' => '/frontend',
                'isFrontend' => true,
            ],
            'frontend with backend part' => [
                'path' => '/frontend' . self::BACKEND_PREFIX,
                'isFrontend' => true,
            ],
            'frontend with backend part and slug' => [
                'path' => '/frontend' . self::BACKEND_PREFIX . '/slug',
                'isFrontend' => true,
            ],
        ];
    }
}
