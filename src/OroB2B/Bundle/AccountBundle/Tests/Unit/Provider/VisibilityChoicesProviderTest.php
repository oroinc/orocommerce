<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Unit\Formatter;

use Symfony\Bundle\FrameworkBundle\Tests\Templating\Helper\Fixtures\StubTranslator;

use OroB2B\Bundle\AccountBundle\Provider\VisibilityChoicesProvider;

class VisibilityChoicesProviderTest extends \PHPUnit_Framework_TestCase
{
    const VISIBILITY_CLASS = '\OroB2B\Bundle\AccountBundle\Entity\Visibility\CategoryVisibility';
    /**
     * @var VisibilityChoicesProvider
     */
    protected $formatter;

    public function setUp()
    {
        $translator = new StubTranslator();
        $this->formatter = new VisibilityChoicesProvider($translator);
    }

    public function testGetFormattedChoices()
    {
        $actual = $this->formatter->getFormattedChoices(self::VISIBILITY_CLASS);
        $expected = [
            'parent_category' => '[trans]orob2b.account.visibility.categoryvisibility.choice.parent_category[/trans]',
            'config' =>'[trans]orob2b.account.visibility.categoryvisibility.choice.config[/trans]',
            'hidden' => '[trans]orob2b.account.visibility.categoryvisibility.choice.hidden[/trans]',
            'visible' =>'[trans]orob2b.account.visibility.categoryvisibility.choice.visible[/trans]',
        ];
        $this->assertEquals($expected, $actual);
    }

    public function testGetChoices()
    {
        $actual = $this->formatter->getChoices(self::VISIBILITY_CLASS);
        $expected = [
            'parent_category',
            'config',
            'hidden',
            'visible',
        ];
        $this->assertEquals($expected, $actual);
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
}
