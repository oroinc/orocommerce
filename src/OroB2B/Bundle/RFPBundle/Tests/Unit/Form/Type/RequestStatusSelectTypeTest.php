<?php

namespace OroB2B\Bundle\RFPBundle\Tests\Unit\Form\Type;

use OroB2B\Bundle\RFPBundle\Form\Type\RequestStatusSelectType;

class RequestStatusSelectTypeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var RequestStatusSelectType
     */
    protected $formType;

    /**
     * @var array
     */
    protected $choices = [
        1 => 'Opened',
        2 => 'Closed'
    ];

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $repository = $this->getMockBuilder('OroB2B\Bundle\RFPBundle\Entity\Repository\RequestStatusRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $repository->expects($this->any())
            ->method('getNotDeletedStatuses')
            ->willReturn($this->choices);

        $registry = $this->getMockBuilder('Doctrine\Common\Persistence\ManagerRegistry')
            ->disableOriginalConstructor()
            ->getMock();

        $registry->expects($this->any())
            ->method('getRepository')
            ->with('OroB2BRFPBundle:RequestStatus')
            ->willReturn($repository);

        $this->formType = new RequestStatusSelectType($registry);
    }

    /**
     * Test setDefaultOptions
     */
    public function testSetDefaultOptions()
    {
        $resolver = $this->getMockBuilder('Symfony\Component\OptionsResolver\OptionsResolverInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with([
                'class'   => 'OroB2B\Bundle\RFPBundle\Entity\RequestStatus',
                'choices' => $this->choices,
            ]);

        $this->formType->setDefaultOptions($resolver);
    }

    /**
     * Test getName
     */
    public function testGetName()
    {
        $this->assertEquals(RequestStatusSelectType::NAME, $this->formType->getName());
    }

    /**
     * Test getParent
     */
    public function testGetParent()
    {
        $this->assertEquals('entity', $this->formType->getParent());
    }
}
