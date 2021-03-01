<?php

namespace Oro\Bundle\CatalogBundle\Tests\Unit\Form\Type;

use Oro\Bundle\CatalogBundle\ContentVariantType\CategoryPageContentVariantType;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Entity\CategoryTitle;
use Oro\Bundle\CatalogBundle\Form\Type\CategoryPageVariantType;
use Oro\Bundle\CatalogBundle\Form\Type\CategoryTreeType;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\FormBundle\Form\Extension\TooltipFormExtension;
use Oro\Bundle\FormBundle\Form\Type\EntityIdentifierType;
use Oro\Bundle\LocaleBundle\Validator\Constraints\NotBlankDefaultLocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Validator\Constraints\NotBlankDefaultLocalizedFallbackValueValidator;
use Oro\Bundle\TranslationBundle\Translation\Translator;
use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Component\Testing\Unit\Form\Type\Stub\EntityType;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Oro\Component\Tree\Handler\AbstractTreeHandler;
use Symfony\Component\Form\Extension\Core\Type\FormType;

class CategoryPageVariantTypeTest extends FormIntegrationTestCase
{
    use EntityTrait;

    /** @var array|Category[] */
    protected static $categories = [];

    /**
     * @return array
     */
    protected function getExtensions()
    {
        /** @var ConfigProvider|\PHPUnit\Framework\MockObject\MockObject $configProvider */
        $configProvider = $this->createMock(ConfigProvider::class);

        /** @var Translator|\PHPUnit\Framework\MockObject\MockObject $translator */
        $translator = $this->createMock(Translator::class);

        /** @var AbstractTreeHandler|\PHPUnit\Framework\MockObject\MockObject $treeHandler */
        $treeHandler = $this->createMock(AbstractTreeHandler::class);
        $treeHandler->expects($this->any())
            ->method('createTree')
            ->willReturn(
                [
                    [
                        'id' => 1001,
                        'parent' => '#',
                        'text' => 'Parent category',
                        'state' => []
                    ],
                    [
                        'id' => 2002,
                        'parent' => 1001,
                        'text' => 'Sub category',
                        'state' => []
                    ]
                ]
            );

        return [
            new PreloadedExtension(
                [
                    CategoryTreeType::class => new CategoryTreeType($treeHandler),
                    EntityIdentifierType::class => new EntityType(
                        [
                            1001 => $this->getCategory(1001),
                            2002 => $this->getCategory(2002),
                        ]
                    )
                ],
                [
                    FormType::class => [
                        new TooltipFormExtension($configProvider, $translator),
                    ],
                ]
            ),
            $this->getValidatorExtension(true)
        ];
    }

    public function testSubmit()
    {
        $form = $this->factory->create(CategoryPageVariantType::class, [], []);
        $form->submit(
            [
                'excludeSubcategories' => true,
                'categoryPageCategory' => 2002,
                'overrideVariantConfiguration' => false
            ]
        );

        $this->assertTrue($form->isSynchronized(), 'Form isSynchronized');
        $this->assertTrue($form->isValid(), 'Form isValid');

        $this->assertEquals(
            [
                'excludeSubcategories' => true,
                'categoryPageCategory' => $this->getCategory(2002),
                'overrideVariantConfiguration' => false
            ],
            $form->getData()
        );
    }

    /**
     * @param int $id
     * @return Category
     */
    protected function getCategory($id)
    {
        if (!array_key_exists($id, self::$categories)) {
            /** @var Category $category */
            $category = $this->getEntity(Category::class, ['id' => $id]);
            $category->addTitle((new CategoryTitle())->setString('Category ' . $id));
            self::$categories[$id] = $category;
        }

        return self::$categories[$id];
    }

    public function testBuildForm()
    {
        $form = $this->factory->create(CategoryPageVariantType::class, null);

        $this->assertTrue($form->has('excludeSubcategories'));
        $this->assertTrue($form->has('categoryPageCategory'));
        $this->assertEquals(
            CategoryPageContentVariantType::TYPE,
            $form->getConfig()->getOption('content_variant_type')
        );
    }

    public function testGetName()
    {
        $type = new CategoryPageVariantType();
        $this->assertEquals(CategoryPageVariantType::NAME, $type->getName());
    }

    public function testGetBlockPrefix()
    {
        $type = new CategoryPageVariantType();
        $this->assertEquals(CategoryPageVariantType::NAME, $type->getBlockPrefix());
    }

    /**
     * {@inheritDoc}
     */
    protected function getValidators()
    {
        $validators = [
            NotBlankDefaultLocalizedFallbackValue::class => new NotBlankDefaultLocalizedFallbackValue(),
            'oro_locale.default_localized_fallback_value.not_blank'
            => new NotBlankDefaultLocalizedFallbackValueValidator()
        ];

        return array_merge(parent::getValidators(), $validators);
    }
}
