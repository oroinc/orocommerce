<?php

namespace Oro\Bundle\CatalogBundle\Tests\Unit\Form\Type;

use Oro\Bundle\CatalogBundle\ContentVariantType\CategoryPageContentVariantType;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Entity\CategoryTitle;
use Oro\Bundle\CatalogBundle\Form\Type\CategoryPageVariantType;
use Oro\Bundle\CatalogBundle\Form\Type\CategoryTreeType;
use Oro\Bundle\FormBundle\Form\Type\EntityIdentifierType;
use Oro\Bundle\FormBundle\Tests\Unit\Stub\TooltipFormExtensionStub;
use Oro\Bundle\LocaleBundle\Validator\Constraints\NotBlankDefaultLocalizedFallbackValueValidator;
use Oro\Component\Testing\ReflectionUtil;
use Oro\Component\Testing\Unit\Form\Type\Stub\EntityTypeStub;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Oro\Component\Tree\Handler\AbstractTreeHandler;
use Symfony\Component\Form\Extension\Core\Type\FormType;

class CategoryPageVariantTypeTest extends FormIntegrationTestCase
{
    /** @var Category[] */
    private static $categories = [];

    /**
     * {@inheritDoc}
     */
    protected function getExtensions(): array
    {
        $treeHandler = $this->createMock(AbstractTreeHandler::class);
        $treeHandler->expects($this->any())
            ->method('createTree')
            ->willReturn([
                ['id' => 1001, 'parent' => '#', 'text' => 'Parent category', 'state' => []],
                ['id' => 2002, 'parent' => 1001, 'text' => 'Sub category', 'state' => []]
            ]);

        return [
            new PreloadedExtension(
                [
                    new CategoryTreeType($treeHandler),
                    EntityIdentifierType::class => new EntityTypeStub([
                        1001 => $this->getCategory(1001),
                        2002 => $this->getCategory(2002),
                    ])
                ],
                [
                    FormType::class => [new TooltipFormExtensionStub($this)]
                ]
            ),
            $this->getValidatorExtension(true)
        ];
    }

    /**
     * {@inheritDoc}
     */
    protected function getValidators(): array
    {
        return array_merge(parent::getValidators(), [
            'oro_locale.default_localized_fallback_value.not_blank' =>
                new NotBlankDefaultLocalizedFallbackValueValidator()
        ]);
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

    private function getCategory(int $id): Category
    {
        if (!array_key_exists($id, self::$categories)) {
            $category = new Category();
            ReflectionUtil::setId($category, $id);
            $category->addTitle((new CategoryTitle())->setString('Category ' . $id));
            self::$categories[$id] = $category;
        }

        return self::$categories[$id];
    }

    public function testBuildForm()
    {
        $form = $this->factory->create(CategoryPageVariantType::class);

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
}
