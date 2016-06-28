<?php

namespace OroB2B\Bundle\WebsiteBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;

use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Bundle\SearchBundle\Tests\Unit\Fixture\Entity\Product;

use OroB2B\Bundle\WebsiteBundle\Provider\WebsiteProviderInterface;
use OroB2B\Bundle\WebsiteBundle\Form\Type\WebsiteScopedDataType;
use OroB2B\Bundle\AccountBundle\Tests\Unit\Form\Type\Stub\EntityVisibilityType;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;

class WebsiteScopedDataTypeTest extends FormIntegrationTestCase
{
    use EntityTrait;

    const WEBSITE_ID = 42;

    /**
     * @var WebsiteScopedDataType
     */
    protected $formType;

    /**
     * {@inheritdoc}
     */
    protected function getExtensions()
    {
        $entityVisibilityType = new EntityVisibilityType();

        return [
            new PreloadedExtension(
                [
                    EntityVisibilityType::NAME => $entityVisibilityType,
                ],
                []
            )
        ];
    }

    protected function setUp()
    {
        parent::setUp();

        $website = $this->getEntity(Website::class, ['id' => self::WEBSITE_ID]);

        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $em->expects($this->any())
            ->method('getReference')
            ->with('TestWebsiteClass', self::WEBSITE_ID)
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
            ->with('TestWebsiteClass')
            ->willReturn($repository);

        $registry->expects($this->any())
            ->method('getManagerForClass')
            ->with('TestWebsiteClass')
            ->willReturn($em);

        /** @var WebsiteProviderInterface|\PHPUnit_Framework_MockObject_MockObject $websiteProvider */
        $websiteProvider = $this->getMock('OroB2B\Bundle\WebsiteBundle\Provider\WebsiteProviderInterface');
        $websiteProvider->expects($this->any())
            ->method('getWebsites')
            ->willReturn([$website]);

        $this->formType = new WebsiteScopedDataType($registry, $websiteProvider);
        $this->formType->setWebsiteClass('TestWebsiteClass');
    }

    /**
     * @dataProvider submitDataProvider
     * @param Product $defaultData
     * @param array $options
     * @param array $submittedData
     * @param array $expectedData
     */
    public function testSubmit($defaultData, array $options, array $submittedData, array $expectedData)
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
            [
                'defaultData'   => [],
                'options' => [
                    'preloaded_websites' => [],
                    'type' => new EntityVisibilityType()
                ],
                'submittedData' => [
                    self::WEBSITE_ID => [],
                ],
                'expectedData'  => [
                    self::WEBSITE_ID => [],
                ],
            ],
        ];
    }

    public function testBuildView()
    {
        $view = new FormView();

        /** @var FormInterface|\PHPUnit_Framework_MockObject_MockObject $form */
        $form = $this->getMock('Symfony\Component\Form\FormInterface');
        $this->formType->buildView($view, $form, ['region_route' => 'test']);

        $this->assertArrayHasKey('websites', $view->vars);

        $websiteIds = array_map(
            function (Website $website) {
                return $website->getId();
            },
            $view->vars['websites']
        );

        $this->assertEquals([self::WEBSITE_ID], $websiteIds);
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
        $this->assertEquals(WebsiteScopedDataType::NAME, $this->formType->getName());
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
