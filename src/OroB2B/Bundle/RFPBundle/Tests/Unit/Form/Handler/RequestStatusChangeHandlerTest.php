<?php

namespace OroB2B\Bundle\RFPBundle\Tests\Unit\Form\Handler;

use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;

use Oro\Bundle\NoteBundle\Entity\Note;
use Oro\Component\Testing\Unit\FormHandlerTestCase;

use OroB2B\Bundle\RFPBundle\Entity\Request as RFPRequest;
use OroB2B\Bundle\RFPBundle\Entity\RequestStatus;
use OroB2B\Bundle\RFPBundle\Form\Handler\RequestStatusChangeHandler;

class RequestStatusChangeHandlerTest extends FormHandlerTestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|EngineInterface
     */
    protected $templating;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->entity = new RFPRequest();
        $this->templating = $this->getMock('Symfony\Bundle\FrameworkBundle\Templating\EngineInterface');
        $this->handler = new RequestStatusChangeHandler($this->form, $this->request, $this->manager, $this->templating);
    }

    /**
     * @dataProvider supportedMethods
     * @param string $method
     * @param boolean $isValid
     * @param boolean $isProcessed
     */
    public function testProcessSupportedRequest($method, $isValid, $isProcessed)
    {
        $this->request->setMethod($method);

        $this->form->expects($this->any())
            ->method('isValid')
            ->will($this->returnValue($isValid));

        if ($isValid) {
            $this->assertRequestStatusChange();
        }

        $this->assertEquals($isProcessed, $this->handler->process($this->entity));
    }

    /**
     * {@inheritdoc}
     */
    public function testProcessUnsupportedRequest()
    {
        $this->request->setMethod('GET');

        $this->form->expects($this->never())
            ->method('submit');

        $this->assertFalse($this->handler->process($this->entity));
    }

    /**
     * {@inheritdoc}
     */
    public function testProcessValidData()
    {
        $this->request->setMethod('POST');

        $this->form->expects($this->once())
            ->method('isValid')
            ->will($this->returnValue(true));

        $this->assertRequestStatusChange();

        $this->manager->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(new Note()));

        $this->manager->expects($this->once())
            ->method('flush');

        $this->assertTrue($this->handler->process($this->entity));
    }

    /**
     * {@inheritdoc}
     */
    public function supportedMethods()
    {
        return [
            'post valid' => [
                'method' => 'POST',
                'isValid' => true,
                'isProcessed' => true
            ],
            'post invalid' => [
                'method' => 'POST',
                'isValid' => false,
                'isProcessed' => false
            ],
        ];
    }

    protected function assertRequestStatusChange()
    {
        $requestStatusEntity = new RequestStatus();
        $requestStatusEntity->setLabel('StatusLabel');

        $status = $this->getMock('Symfony\Component\Form\FormInterface');
        $status->expects($this->once())
            ->method('getData')
            ->will($this->returnValue($requestStatusEntity));

        $note = $this->getMock('Symfony\Component\Form\FormInterface');
        $note->expects($this->once())
            ->method('getData')
            ->will($this->returnValue('note'));

        $this->form->expects($this->exactly(2))
            ->method('get')
            ->with(
                $this->logicalOr(
                    $this->equalTo('status'),
                    $this->equalTo('note')
                )
            )
            ->will(
                $this->returnCallback(
                    function ($param) use ($status, $note) {
                        $data = null;

                        switch ($param) {
                            case 'status':
                                $data = $status;
                                break;
                            case 'note':
                                $data = $note;
                                break;
                            default:
                                break;
                        }

                        return $data;
                    }
                )
            );
        $this->form->expects($this->once())
            ->method('submit')
            ->with($this->request);

        $this->templating->expects($this->once())
            ->method('render')
            ->with(
                'OroB2BRFPBundle:Request:note.html.twig',
                [
                    'status' => $requestStatusEntity->getLabel(),
                    'note' => 'note',
                ]
            )
            ->will($this->returnValue('message'));
    }
}
