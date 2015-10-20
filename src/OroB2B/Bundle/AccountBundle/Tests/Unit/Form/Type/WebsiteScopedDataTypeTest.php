<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\Test\FormIntegrationTestCase;

use Doctrine\Common\Persistence\ManagerRegistry;

use OroB2B\Bundle\AccountBundle\Form\Type\WebsiteScopedDataType;
use OroB2B\Bundle\AccountBundle\Tests\Unit\Form\Type\Stub\EntityVisibilityType;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;

class WebsiteScopedDataTypeTest extends FormIntegrationTestCase
{
    /**
     * @var WebsiteScopedDataType
     */
    protected $formType;

    protected function setUp()
    {
        parent::setUp();

        $website = new Website();
        $idReflection = new \ReflectionProperty($website, 'id');
        $idReflection->setAccessible(true);
        $idReflection->setValue($website, 42);

        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $em->expects($this->any())
            ->method('getReference')
            ->willReturn($website);

        $repository = $this->getMockBuilder('OroB2B\Bundle\WebsiteBundle\Entity\Repository\WebsiteRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $repository->expects($this->any())
            ->method('getAllWebsites')
            ->willReturn([$website]);

        /** @var ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject $registry */
        $registry = $this->getMockBuilder('\Doctrine\Common\Persistence\ManagerRegistry')
            ->disableOriginalConstructor()
            ->getMock();

        $registry->expects($this->any())
            ->method('getRepository')
            ->willReturn($repository);

        $registry->expects($this->any())
            ->method('getManagerForClass')
            ->willReturn($em);

        $this->formType = new WebsiteScopedDataType($registry);
    }

    /**
     * @dataProvider submitDataProvider
     * @param array $defaultData
     * @param array $options
     * @param array $submittedData
     * @param array $expectedData
     */
    public function testSubmit(array $defaultData, array $options, array $submittedData, array $expectedData)
    {
        $form = $this->factory->create($this->formType, $defaultData, $options);

        $this->assertEquals($defaultData, $form->getData());
        $form->submit($submittedData);
        $this->assertTrue($form->isValid());

        $data = $form->getData();

        $this->assertEquals($expectedData, $data);
    }

    /**
     * @return array
     */
    public function submitDataProvider()
    {
        return [
            'without default' => [
                'defaultData'   => [],
                'options' => [
                    'preloaded_websites' => [],
                    'type' => new EntityVisibilityType()
                ],
                'submittedData' => [
                    '42' => 'test'
                ],
                'expectedData'  => [],
            ],
        ];
    }

    public function testGetName()
    {
        $this->assertEquals(WebsiteScopedDataType::NAME, $this->formType->getName());
    }
}
