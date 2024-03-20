<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\Form\Type;

use Oro\Bundle\NavigationBundle\Form\Type\RouteChoiceType;
use Oro\Bundle\NavigationBundle\Tests\Unit\Form\Type\Stub\RouteChoiceTypeStub;
use Oro\Bundle\ScopeBundle\Form\Type\ScopeCollectionType;
use Oro\Bundle\ScopeBundle\Tests\Unit\Form\Type\Stub\ScopeCollectionTypeStub;
use Oro\Bundle\WebCatalogBundle\ContentVariantType\SystemPageContentVariantType;
use Oro\Bundle\WebCatalogBundle\Entity\ContentVariant;
use Oro\Bundle\WebCatalogBundle\Form\Extension\PageVariantTypeExtension;
use Oro\Bundle\WebCatalogBundle\Form\Type\SystemPageVariantType;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;

class SystemPageVariantTypeTest extends FormIntegrationTestCase
{
    private SystemPageVariantType $type;

    protected function setUp(): void
    {
        $this->type = new SystemPageVariantType();
        parent::setUp();
    }

    protected function getExtensions(): array
    {
        return [
            new PreloadedExtension(
                [
                    SystemPageVariantType::class => $this->type,
                    ScopeCollectionType::class => new ScopeCollectionTypeStub(),
                    RouteChoiceType::class => new RouteChoiceTypeStub([
                        'some_route' => 'some_route',
                        'other_route' => 'other_route'
                    ])
                ],
                [
                    SystemPageVariantType::class => [new PageVariantTypeExtension()],
                ]
            )
        ];
    }

    public function testBuildForm(): void
    {
        $form = $this->factory->create(SystemPageVariantType::class, null, ['web_catalog' => null]);

        self::assertTrue($form->has('systemPageRoute'));
        self::assertTrue($form->has('scopes'));
        self::assertTrue($form->has('type'));

        self::assertFormOptionEqual(['frontend' => true], 'options_filter', $form->get('systemPageRoute'));
        self::assertFormOptionEqual('/^oro_\w+(?<!frontend_root)$/', 'name_filter', $form->get('systemPageRoute'));
        self::assertFormOptionEqual(SystemPageVariantType::MENU_NAME, 'menu_name', $form->get('systemPageRoute'));
    }

    public function testGetBlockPrefix(): void
    {
        $type = new SystemPageVariantType();
        self::assertEquals('oro_web_catalog_system_page_variant', $type->getBlockPrefix());
    }

    /**
     * @dataProvider submitDataProvider
     */
    public function testSubmit(ContentVariant $existingData, array $submittedData, ContentVariant $expectedData): void
    {
        $form = $this->factory->create(SystemPageVariantType::class, $existingData, ['web_catalog' => null]);

        self::assertEquals($existingData, $form->getData());

        $form->submit($submittedData);
        self::assertTrue($form->isValid());
        self::assertTrue($form->isSynchronized());

        self::assertEquals($expectedData, $form->getData());

        self::assertFormOptionEqual(['frontend' => true], 'options_filter', $form->get('systemPageRoute'));
        self::assertFormOptionEqual('/^oro_\w+(?<!frontend_root)$/', 'name_filter', $form->get('systemPageRoute'));
        self::assertFormOptionEqual(SystemPageVariantType::MENU_NAME, 'menu_name', $form->get('systemPageRoute'));
    }

    public function submitDataProvider(): array
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
