<?php

namespace OroB2B\Bundle\FrontendBundle\Tests\Unit\Request;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

use OroB2B\Bundle\FrontendBundle\Request\LayoutHelper;


class LayoutHelperTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @param array $attributes
     * @param bool $isLayout
     * @dataProvider isLayoutRequestDataProvider
     */
    public function testIsLayoutRequest(array $attributes, $isLayout)
    {
        /** @var RequestStack|\PHPUnit_Framework_MockObject_MockObject $requestStack1 */
        $requestStack1 = $this->getMock('Symfony\Component\HttpFoundation\RequestStack');
        $requestStack1->expects($this->once())->method('getCurrentRequest')->willReturn(
            new Request([], [], $attributes)
        );
        $requestStack2 = clone $requestStack1;
        $requestStack2->expects($this->never())->method('getCurrentRequest');

        $helper = new LayoutHelper($requestStack2);
        $this->assertSame($isLayout, $helper->isLayoutRequest(new Request([], [], $attributes)));

        $helper = new LayoutHelper($requestStack1);
        $this->assertSame($isLayout, $helper->isLayoutRequest());
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\BadRequestHttpException
     * @expectedExceptionMessage Request is not defined
     */
    public function testException()
    {
        /** @var RequestStack|\PHPUnit_Framework_MockObject_MockObject $requestStack */
        $requestStack = $this->getMock('Symfony\Component\HttpFoundation\RequestStack');
        $requestStack->expects($this->once())->method('getCurrentRequest')->willReturn(null);
        $helper = new LayoutHelper($requestStack);
        $helper->isLayoutRequest();
    }

    /**
     * @return array
     */
    public function isLayoutRequestDataProvider()
    {
        return [
            'backend' => [
                'attributes' => [],
                'isLayout' => false,
            ],
            'frontend' => [
                'attributes' => ['_layout' => 'value'],
                'isLayout' => true,
            ],
        ];
    }
}
