<?php

namespace Oro\Bundle\CatalogBundle\Tests\Unit\Form\Type\Filter;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Form\Type\Filter\SubcategoryFilterType;
use Oro\Bundle\FilterBundle\Form\Type\Filter\FilterType;
use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Component\Testing\Unit\Form\Type\Stub\EntityType;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatorInterface;

class SubcategoryFilterTypeTest extends FormIntegrationTestCase
{
    use EntityTrait;

    /** @var SubcategoryFilterType */
    protected $type;

    /** @var array|Category[] */
    protected static $categories = [];

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->type = new SubcategoryFilterType();
    }

    public function testConfigureOptions()
    {
        /* @var $resolver OptionsResolver|\PHPUnit_Framework_MockObject_MockObject */
        $resolver = $this->createMock(OptionsResolver::class);
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with(
                [
                    'field_type' => 'entity',
                    'field_options' => [
                        'multiple' => true,
                        'class' => Category::class,
                    ],
                    'categories' => [],
                ]
            );

        $this->type->configureOptions($resolver);
    }

    public function testSubmitValidData()
    {
        $category1 = $this->getCategory(100);
        $category2 = $this->getCategory(200);

        $form = $this->factory->create($this->type, null, ['categories' => [$category1, $category2]]);
        $form->submit(['type' => null, 'value' => [$category2->getId()]]);

        $this->assertTrue($form->isValid());
        $this->assertEquals(
            ['type' => null, 'value' => [$category2]],
            $form->getData()
        );
    }

    /**
     * {@inheritDoc}
     */
    protected function getExtensions()
    {
        /** @var TranslatorInterface $translator */
        $translator = $this->createMock(TranslatorInterface::class);

        $filterType = new FilterType($translator);

        $entityType = new EntityType(
            [
                100 => $this->getCategory(100),
                200 => $this->getCategory(200),
            ]
        );

        $preLoadedExtension = new PreloadedExtension(
            [
                $filterType->getName() => $filterType,
                $entityType->getName() => $entityType,
            ],
            []
        );

        return array_merge(parent::getExtensions(), [$preLoadedExtension]);
    }

    public function testGetParent()
    {
        $this->assertEquals(FilterType::NAME, $this->type->getParent());
    }

    public function testGetName()
    {
        $this->assertEquals(SubcategoryFilterType::NAME, $this->type->getName());
    }

    public function testGetBlockPrefix()
    {
        $this->assertEquals(SubcategoryFilterType::NAME, $this->type->getBlockPrefix());
    }

    /**
     * @param int $id
     * @return Category
     */
    protected function getCategory($id)
    {
        if (!array_key_exists($id, self::$categories)) {
            self::$categories[$id] = $this->getEntity(Category::class, ['id' => $id]);
        }

        return self::$categories[$id];
    }
}
