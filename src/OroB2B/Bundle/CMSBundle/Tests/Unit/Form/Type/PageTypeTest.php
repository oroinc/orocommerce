<?php

namespace OroB2B\Bundle\CMSBundle\Tests\Unit\Form\Type;

use Symfony\Component\Validator\Constraints\NotBlank;

use Oro\Bundle\FormBundle\Form\Type\EntityIdentifierType;
use Oro\Bundle\FormBundle\Form\Type\OroRichTextType;

use OroB2B\Bundle\CMSBundle\Form\Type\PageType;

class PageTypeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var PageType
     */
    protected $type;

    protected function setUp()
    {
        $this->type = new PageType();
    }

    public function testBuildForm()
    {
        $builder = $this->getMockBuilder('Symfony\Component\Form\FormBuilder')
            ->disableOriginalConstructor()
            ->getMock();
        $builder->expects($this->at(0))
            ->method('add')
            ->with(
                'parentPage',
                EntityIdentifierType::NAME,
                [
                    'class' => 'OroB2B\Bundle\CMSBundle\Entity\Page',
                    'multiple' => false
                ]
            )
            ->will($this->returnSelf());
        $builder->expects($this->at(1))
            ->method('add')
            ->with(
                'title',
                'text',
                [
                    'label' => 'orob2b.cms.page.title.label',
                    'required' => true,
                    'constraints' => [new NotBlank()],
                ]
            )
            ->will($this->returnSelf());
        $builder->expects($this->at(2))
            ->method('add')
            ->with(
                'content',
                OroRichTextType::NAME,
                [
                    'label' => 'orob2b.cms.page.content.label',
                    'required' => false,
                ]
            )
            ->will($this->returnSelf());

        $this->type->buildForm($builder, []);
    }

    public function testSetDefaultOptions()
    {
        $resolver = $this->getMock('Symfony\Component\OptionsResolver\OptionsResolverInterface');
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with(
                [
                    'data_class' => 'OroB2B\Bundle\CMSBundle\Entity\Page',
                    'intention' => 'page',
                    'extra_fields_message' => 'This form should not contain extra fields: "{{ extra_fields }}"'
                ]
            );

        $this->type->setDefaultOptions($resolver);
    }

    public function testGetName()
    {
        $this->assertEquals(PageType::NAME, $this->type->getName());
    }
}
