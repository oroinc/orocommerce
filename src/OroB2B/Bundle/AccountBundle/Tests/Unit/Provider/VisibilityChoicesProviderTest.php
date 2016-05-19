<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Unit\Provider;

use Symfony\Bundle\FrameworkBundle\Tests\Templating\Helper\Fixtures\StubTranslator;

use Doctrine\Bundle\DoctrineBundle\Registry;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\CatalogBundle\Entity\Category;
use OroB2B\Bundle\AccountBundle\Provider\VisibilityChoicesProvider;

class VisibilityChoicesProviderTest extends \PHPUnit_Framework_TestCase
{
    const VISIBILITY_CLASS = 'OroB2B\Bundle\AccountBundle\Entity\Visibility\CategoryVisibility';

    /**
     * @var VisibilityChoicesProvider
     */
    protected $formatter;

    /**
     * @var Registry|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $registry;

    public function setUp()
    {
        $translator = new StubTranslator();
        $this->registry = $this->getMockBuilder('Doctrine\Bundle\DoctrineBundle\Registry')
            ->disableOriginalConstructor()
            ->getMock();
        $this->formatter = new VisibilityChoicesProvider($translator, $this->registry);
    }

    public function testGetFormattedChoices()
    {
        $actual = $this->formatter->getFormattedChoices(self::VISIBILITY_CLASS, $this->createCategory());
        $expected = [
            'parent_category' => '[trans]orob2b.account.visibility.categoryvisibility.choice.parent_category[/trans]',
            'config' => '[trans]orob2b.account.visibility.categoryvisibility.choice.config[/trans]',
            'hidden' => '[trans]orob2b.account.visibility.categoryvisibility.choice.hidden[/trans]',
            'visible' => '[trans]orob2b.account.visibility.categoryvisibility.choice.visible[/trans]',
        ];
        $this->assertEquals($expected, $actual);
    }

    /**
     * @dataProvider getChoicesForCategoryDataProvider
     * @param array $expected
     */
    public function testGetChoicesForCategory(array $expected)
    {
        $actual = $this->formatter->getChoices(self::VISIBILITY_CLASS, $this->createCategory());
        $this->assertEquals($expected, $actual);
    }

    /**
     * @return array
     */
    public function getChoicesForCategoryDataProvider()
    {
        return [
           'target category' => [
                'expected' => [
                    'parent_category',
                    'config',
                    'hidden',
                    'visible',
                ]
            ]
        ];
    }

    /**
     * @dataProvider getChoicesForProductDataProvider
     * @param Category|null $productCategory
     * @param array $expected
     */
    public function testGetChoicesForProduct($productCategory, array $expected)
    {
        $repository = $this->getMockBuilder('OroB2B\Bundle\CatalogBundle\Entity\Repository\CategoryRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $repository->expects($this->any())
            ->method('findOneByProduct')
            ->willReturn($productCategory);

        $em = $this->getMock('Doctrine\Common\Persistence\ObjectManager');
        $em->expects($this->any())
            ->method('getRepository')
            ->willReturn($repository);

        $this->registry->expects($this->any())
            ->method('getManagerForClass')
            ->willReturn($em);

        $actual = $this->formatter->getChoices(
            'OroB2B\Bundle\AccountBundle\Entity\Visibility\ProductVisibility',
            new Product()
        );
        $this->assertEquals($expected, $actual);
    }

    /**
     * @return array
     */
    public function getChoicesForProductDataProvider()
    {
        return [
            'target product with category' => [
                'productCategory' => new Category(),
                'expected' => [
                    'category',
                    'config',
                    'hidden',
                    'visible',
                ]
            ],
            'target product without category' => [
                'productCategory' => null,
                'expected' => [
                    'config',
                    'hidden',
                    'visible',
                ]
            ]
        ];
    }

    public function testFormatChoices()
    {
        $actual = $this->formatter->formatChoices('test.%s', ['test_1', 'test_2']);
        $expected = [
            'test_1' => '[trans]test.test_1[/trans]',
            'test_2' => '[trans]test.test_2[/trans]'
        ];
        $this->assertEquals($expected, $actual);
    }

    public function testFormat()
    {
        $actual = $this->formatter->format('test.%s', 'test_1');
        $this->assertEquals('[trans]test.test_1[/trans]', $actual);
    }

    /**
     * @return $this
     */
    protected function createCategory()
    {
        return (new Category())->setParentCategory(new Category());
    }
}
