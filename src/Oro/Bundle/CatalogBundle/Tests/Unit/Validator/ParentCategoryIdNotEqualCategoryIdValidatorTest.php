<?php

namespace Oro\Bundle\CatalogBundle\Tests\Unit\Validator;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Validator\Constraints\ParentCategoryIdNotEqualCategoryId;
use Oro\Bundle\CatalogBundle\Validator\Constraints\ParentCategoryIdNotEqualCategoryIdValidator;
use Oro\Component\Testing\ReflectionUtil;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidatorInterface;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class ParentCategoryIdNotEqualCategoryIdValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator(): ConstraintValidatorInterface
    {
        return new ParentCategoryIdNotEqualCategoryIdValidator();
    }

    public function testGetTargets(): void
    {
        $constraint = new ParentCategoryIdNotEqualCategoryId();
        self::assertEquals(Constraint::CLASS_CONSTRAINT, $constraint->getTargets());
    }

    public function testValidateWithInvalidData(): void
    {
        $category = $this->getCategory([
            'id' => 2,
            'parentCategoryId' => 2,
        ]);
        $constraint = new ParentCategoryIdNotEqualCategoryId();
        $this->validator->validate($category, $constraint);
        $this
            ->buildViolation('oro.catalog.category.parent_category.parent_category_id_same_as_id')
            ->atPath('property.path.parentCategory')
            ->assertRaised();
    }

    /**
     * @dataProvider validateValidDataProvider
     */
    public function testValidateWithValidData(array $data): void
    {
        $category = $this->getCategory($data);

        $constraint = new ParentCategoryIdNotEqualCategoryId();
        $this->validator->validate($category, $constraint);

        $this->assertNoViolation();
    }

    public function validateValidDataProvider(): array
    {
        return [
            'New Category without Id and Parent Id' => [
                'data' => [
                    'id' => null,
                    'parentCategoryId' => null,
                ],
            ],
            'Category without Parent Id' => [
                'data' => [
                    'id' => 1,
                    'parentCategoryId' => null,
                ],
            ],
            'New Category without Id' => [
                'data' => [
                    'id' => null,
                    'parentCategoryId' => 1,
                ],
            ],
            'New Category with not equal Id and Parent Id' => [
                'data' => [
                    'id' => 2,
                    'parentCategoryId' => 1,
                ],
            ],
        ];
    }

    private function getCategory(array $data): Category
    {
        $category = new Category();
        ReflectionUtil::setId($category, $data['id']);

        if ($data['parentCategoryId']) {
            $parentCategory = new Category();
            ReflectionUtil::setId($parentCategory, $data['parentCategoryId']);
            $category->setParentCategory($parentCategory);
        }

        return $category;
    }
}
