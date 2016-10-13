<?php

namespace Oro\Bundle\ScopeBundle\Tests\Unit\Form\Type;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\ScopeBundle\Form\Type\ScopedDataType;
use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

class ScopedDataTypeTest extends FormIntegrationTestCase
{
    use EntityTrait;

    /**
     * @var ScopedDataType
     */
    protected $formType;

    /**
     * {@inheritdoc}
     */
    protected function getExtensions()
    {
//        $entityVisibilityType = new EntityVisibilityType();
//
//        return [
//            new PreloadedExtension(
//                [
//                    EntityVisibilityType::NAME => $entityVisibilityType,
//                ],
//                []
//            )
//        ];
        return [];
    }

    protected function setUp()
    {
        parent::setUp();

        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $em->expects($this->any())
            ->method('getReference')
            ->with('TestWebsiteClass', self::WEBSITE_ID)
            ->willReturn($website);

        $repository = $this->getMockBuilder('Oro\Bundle\WebsiteBundle\Entity\Repository\WebsiteRepository')
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
            ->with('TestWebsiteClass')
            ->willReturn($repository);
        $registry->expects($this->any())
            ->method('getManagerForClass')
            ->with('TestWebsiteClass')
            ->willReturn($em);

        $this->formType = new ScopedDataType($registry);
    }

    public function testSubmit()
    {
        $this->factory->createBuilder()
        $form = $this->factory->create($this->formType);

        $this->assertEquals($defaultData, $form->getData());
        $form->submit($submittedData);
        $this->assertTrue($form->isValid());

        $data = $form->getData();

        $this->assertEquals($expectedData, $data);
    }

    /**
     * @return array
     */
//    public function submitDataProvider()
//    {
//        return [
//            [
//                'defaultData'   => [],
//                'options' => [
//                    'preloaded_websites' => [],
//                    'type' => new EntityVisibilityType()
//                ],
//                'submittedData' => [
//                    self::WEBSITE_ID => [],
//                ],
//                'expectedData'  => [
//                    self::WEBSITE_ID => [],
//                ],
//            ],
//        ];
//    }

    public function testBuildView()
    {
        $view = new FormView();

        /** @var FormInterface|\PHPUnit_Framework_MockObject_MockObject $form */
        $form = $this->getMock('Symfony\Component\Form\FormInterface');
        $this->formType->buildView($view, $form, ['region_route' => 'test']);

        $this->assertArrayHasKey('websites', $view->vars);

//        $websiteIds = array_map(
//            function (Website $website) {
//                return $website->getId();
//            },
//            $view->vars['websites']
//        );

//        $this->assertEquals([self::WEBSITE_ID], $websiteIds);
    }

    /**
     * @return array
     */
    public function finishViewDataProvider()
    {
        return [
            [
                'children' => ['1' => 'test'],
                'expected' => []
            ],
            [
                'children' => ['1' => 'test', 'not_int' => 'test'],
                'expected' => ['not_int' => 'test']
            ],
            [
                'children' => ['1' => 'test', 'not_int' => 'test'],
                'expected' => ['1' => 'test', 'not_int' => 'test']
            ],
        ];
    }

    public function testGetName()
    {
        $this->assertEquals(ScopedDataType::NAME, $this->formType->getName());
    }

    /**
     * @param FormView $formView
     * @param array $children
     * @return FormView
     */
    protected function setFormViewChildren(FormView $formView, array $children)
    {
        $childrenReflection = new \ReflectionProperty($formView, 'children');
        $childrenReflection->setAccessible(true);
        $childrenReflection->setValue($formView, $children);

        return $formView;
    }
}
