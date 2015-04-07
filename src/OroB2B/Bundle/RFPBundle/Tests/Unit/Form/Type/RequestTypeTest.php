<?php

namespace OroB2B\Bundle\RFPBundle\Tests\Unit\Form\Type;

use Symfony\Component\Validator\Constraints\NotBlank;

use OroB2B\Bundle\RFPBundle\Form\Type\RequestStatusSelectType;
use OroB2B\Bundle\RFPBundle\Form\Type\RequestType;

class RequestTypeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var RequestType
     */
    protected $formType;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->formType = new RequestType();
    }

    /**
     * Test getName
     */
    public function testGetName()
    {
        $this->assertEquals(RequestType::NAME, $this->formType->getName());
    }

    public function testBuildForm()
    {
        $builder = $this->getMockBuilder('Symfony\Component\Form\FormBuilder')
            ->disableOriginalConstructor()
            ->getMock();
        $builder->expects($this->at(0))
            ->method('add')
            ->with(
                'status',
                RequestStatusSelectType::NAME,
                [
                    'label'       => 'orob2b.rfp.request.status.label',
                    'required'    => true,
                    'empty_value' => '',
                    'constraints' => [
                        new NotBlank(),
                    ],
                ]
            )
            ->will($this->returnSelf());
        $builder->expects($this->at(1))
            ->method('add')
            ->with(
                'note',
                'oro_rich_text',
                [
                    'label'    => 'oro.note.entity_label',
                    'required' => false,
                ]
            )
            ->will($this->returnSelf());

        $this->formType->buildForm($builder, []);
    }
}
