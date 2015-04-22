<?php

namespace OroB2B\Bundle\RFPAdminBundle\Tests\Unit\Form\Type;

use Symfony\Component\Validator\Constraints\NotBlank;

use Oro\Bundle\FormBundle\Form\Type\OroRichTextType;

use OroB2B\Bundle\RFPAdminBundle\Form\Type\RequestStatusSelectType;
use OroB2B\Bundle\RFPAdminBundle\Form\Type\RequestChangeStatusType;

class RequestChangeStatusTypeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var RequestChangeStatusType
     */
    protected $formType;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->formType = new RequestChangeStatusType();
    }

    /**
     * Test getName
     */
    public function testGetName()
    {
        $this->assertEquals(RequestChangeStatusType::NAME, $this->formType->getName());
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
                    'label'       => 'orob2b.rfpadmin.request.status.label',
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
                OroRichTextType::NAME,
                [
                    'label'    => 'oro.note.entity_label',
                    'required' => false,
                ]
            )
            ->will($this->returnSelf());

        $this->formType->buildForm($builder, []);
    }
}
