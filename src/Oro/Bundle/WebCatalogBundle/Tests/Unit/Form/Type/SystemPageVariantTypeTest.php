<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\Form\Type;

use Oro\Bundle\NavigationBundle\Form\Type\RouteChoiceType;
use Oro\Bundle\NavigationBundle\Tests\Unit\Form\Type\Stub\RouteChoiceTypeStub;
use Oro\Bundle\ScopeBundle\Form\Type\ScopeCollectionType;
use Oro\Bundle\ScopeBundle\Tests\Unit\Form\Type\Stub\ScopeCollectionTypeStub;
use Oro\Bundle\WebCatalogBundle\ContentVariantType\SystemPageContentVariantType;
use Oro\Bundle\WebCatalogBundle\Entity\ContentVariant;
use Oro\Bundle\WebCatalogBundle\Entity\WebCatalog;
use Oro\Bundle\WebCatalogBundle\Form\Extension\PageVariantTypeExtension;
use Oro\Bundle\WebCatalogBundle\Form\Type\SystemPageVariantType;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;

class SystemPageVariantTypeTest extends FormIntegrationTestCase
{
    /**
     * @var SystemPageVariantType
     */
    protected $type;

    /**
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        $this->type = new SystemPageVariantType();
        parent::setUp();
    }

    /**
     * {@inheritDoc}
     */
    protected function tearDown(): void
    {
        unset($this->type);
    }

    /**
     * @return array
     */
    protected function getExtensions()
    {
        return [
            new PreloadedExtension(
                [
                    SystemPageVariantType::class => $this->type,
                    ScopeCollectionType::class => new ScopeCollectionTypeStub(),
                    RouteChoiceType::class => new RouteChoiceTypeStub(
                        [
                            'some_route' => 'some_route',
                            'other_route' => 'other_route'
                        ]
                    )
                ],
                [
                    SystemPageVariantType::class => [new PageVariantTypeExtension()],
                ]
            )
        ];
    }

    public function testBuildForm()
    {
        $form = $this->factory->create(SystemPageVariantType::class, null, ['web_catalog' => null]);

        $this->assertTrue($form->has('systemPageRoute'));
        $this->assertTrue($form->has('scopes'));
        $this->assertTrue($form->has('type'));
    }

    public function testGetBlockPrefix()
    {
        $type = new SystemPageVariantType();
        $this->assertEquals(SystemPageVariantType::NAME, $type->getBlockPrefix());
    }

    /**
     * @dataProvider submitDataProvider
     *
     * @param WebCatalog $existingData
     * @param array $submittedData
     * @param WebCatalog $expectedData
     */
    public function testSubmit($existingData, $submittedData, $expectedData)
    {
        $form = $this->factory->create(SystemPageVariantType::class, $existingData, ['web_catalog' => null]);

        $this->assertEquals($existingData, $form->getData());

        $form->submit($submittedData);
        $this->assertTrue($form->isValid());
        $this->assertTrue($form->isSynchronized());

        $this->assertEquals($expectedData, $form->getData());
    }

    /**
     * @return array
     */
    public function submitDataProvider()
    {
        return [
            'new entity' => [
                new ContentVariant(),
                [
                    'systemPageRoute' => 'some_route'
                ],
                (new ContentVariant())
                    ->setSystemPageRoute('some_route')
                    ->setType(SystemPageContentVariantType::TYPE)
            ],
            'existing entity' => [
                (new ContentVariant())
                    ->setSystemPageRoute('some_route')
                    ->setType(SystemPageContentVariantType::TYPE),
                [
                    'systemPageRoute' => 'other_route',
                    'type' => 'fakeType',
                    'default' => true
                ],
                (new ContentVariant())
                    ->setSystemPageRoute('other_route')
                    ->setType(SystemPageContentVariantType::TYPE)
                    ->setDefault(true)
            ],
        ];
    }
}
