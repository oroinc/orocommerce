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
        /** @var RequestStack|\PHPUnit_Framework_MockObject_MockObject $requestStack1 */
        $requestStack1 = $this->getMock('Symfony\Component\HttpFoundation\RequestStack');
        $requestStack1->expects($this->once())->method('getCurrentRequest')->willReturn(
            Request::create($path)
        );
        $requestStack2 = clone $requestStack1;
        $requestStack2->expects($this->never())->method('getCurrentRequest');

        $helper = new FrontendHelper(self::BACKEND_PREFIX, $requestStack2);
        $this->assertSame($isFrontend, $helper->isFrontendRequest(Request::create($path)));

        $helper = new FrontendHelper(self::BACKEND_PREFIX, $requestStack1);
        $this->assertSame($isFrontend, $helper->isFrontendRequest());
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\BadRequestHttpException
     * @expectedExceptionMessage Request is not define
     */
    public function testException()
    {
        /** @var RequestStack|\PHPUnit_Framework_MockObject_MockObject $requestStack */
        $requestStack = $this->getMock('Symfony\Component\HttpFoundation\RequestStack');
        $requestStack->expects($this->once())->method('getCurrentRequest')->willReturn(null);
        $helper = new FrontendHelper(self::BACKEND_PREFIX, $requestStack);
        $helper->isFrontendRequest();
    }

    /**
     * @return array
     */
    public function isFrontendRequestDataProvider()
    {
        return [
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
