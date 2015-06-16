<?php

namespace OroB2B\Bundle\RFPAdminBundle\Tests\Unit\Form\Type;

use OroB2B\Bundle\RFPAdminBundle\Form\Type\RequestStatusSelectType;

class RequestStatusSelectTypeTest extends \PHPUnit_Framework_TestCase
{
    const ENTITY_CLASS = 'OroB2B\Bundle\RFPAdminBundle\Entity\RequestStatus';

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
        $repository = $this->getMockBuilder('OroB2B\Bundle\RFPAdminBundle\Entity\Repository\RequestStatusRepository')
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
            ->with(self::ENTITY_CLASS)
            ->willReturn($repository);

        $this->formType = new RequestStatusSelectType($registry);
        $this->formType->setEntityClass(self::ENTITY_CLASS);
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
                'class'   => self::ENTITY_CLASS,
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
        $this->assertEquals('translatable_entity', $this->formType->getParent());
    }
}
