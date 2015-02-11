<?php

namespace OroB2B\Bundle\AttributeBundle\Tests\Unit\Form\Handler;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

use Doctrine\Common\Persistence\ObjectManager;

use OroB2B\Bundle\AttributeBundle\Entity\Attribute;
use OroB2B\Bundle\AttributeBundle\Form\Handler\UpdateAttributeHandler;
use OroB2B\Bundle\AttributeBundle\Form\Type\UpdateAttributeType;

class UpdateAttributeHandlerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|FormInterface
     */
    protected $form;

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ObjectManager
     */
    protected $manager;

    /**
     * @var UpdateAttributeHandler
     */
    protected $handler;

    /**
     * @var Attribute
     */
    protected $attribute;

    protected function setUp()
    {
        $this->form = $this->getMockBuilder('Symfony\Component\Form\Form')
            ->disableOriginalConstructor()
            ->getMock();
        $this->request = new Request();
        $this->manager = $this->getMockBuilder('Doctrine\Common\Persistence\ObjectManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->attribute = new Attribute();
        $this->handler = new UpdateAttributeHandler($this->form, $this->request, $this->manager);
    }

    public function testProcessUnsupportedRequest()
    {
        $this->form->expects($this->once())
            ->method('setData')
            ->with($this->attribute);

        $this->request->setMethod('GET');

        $this->form->expects($this->never())
            ->method('submit');

        $this->assertFalse($this->handler->process($this->attribute));
    }

    /**
     * @dataProvider supportedMethods
     * @param array $request
     * @param string $method
     * @param boolean $isValid
     * @param boolean $isProcessed
     */
    public function testProcessSupportedRequest(array $request, $method, $isValid, $isProcessed)
    {
        $this->request->request->add($request);

        $this->form->expects($this->once())
            ->method('setData')
            ->with($this->attribute);

        $this->form->expects($this->any())
            ->method('isValid')
            ->will($this->returnValue($isValid));

        $this->request->setMethod($method);

        $this->form->expects($this->any())
            ->method('submit')
            ->with($this->request);

        $this->assertEquals($isProcessed, $this->handler->process($this->attribute));
    }

    /**
     * @return array
     */
    public function supportedMethods()
    {
        return [
            'post valid' => [
                'request' => [UpdateAttributeType::NAME => []],
                'method' => 'POST',
                'isValid' => true,
                'isProcessed' => true
            ],
            'put valid' => [
                'request' => [UpdateAttributeType::NAME => []],
                'method' => 'PUT',
                'isValid' => true,
                'isProcessed' => true
            ],
            'invalid' => [
                'request' => [UpdateAttributeType::NAME => []],
                'method' => 'POST',
                'isValid' => false,
                'isProcessed' => false
            ],
            'no request' => [
                'request' => [],
                'method' => 'POST',
                'isValid' => true,
                'isProcessed' => false
            ],
        ];
    }

    public function testProcessValidData()
    {
        $this->form->expects($this->once())
            ->method('setData')
            ->with($this->attribute);

        $this->request->request->set(UpdateAttributeType::NAME, []);
        $this->request->setMethod('POST');

        $this->form->expects($this->once())
            ->method('submit')
            ->with($this->request);

        $this->form->expects($this->once())
            ->method('isValid')
            ->will($this->returnValue(true));

        $this->manager->expects($this->once())
            ->method('persist')
            ->with($this->attribute);

        $this->manager->expects($this->once())
            ->method('flush');

        $this->assertTrue($this->handler->process($this->attribute));
    }
}
