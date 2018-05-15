<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit\Helper;

use Oro\Bundle\WebsiteSearchBundle\Helper\PlaceholderHelper;
use Oro\Bundle\WebsiteSearchBundle\Placeholder\AbstractPlaceholder;
use Oro\Bundle\WebsiteSearchBundle\Placeholder\PlaceholderRegistry;

class PlaceholderHelperTest extends \PHPUnit_Framework_TestCase
{
    /** @var PlaceholderRegistry|\PHPUnit_Framework_MockObject_MockObject */
    private $placeholderRegistry;

    /** @var PlaceholderHelper */
    private $helper;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->placeholderRegistry = $this->getMockBuilder(PlaceholderRegistry::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->helper = new PlaceholderHelper($this->placeholderRegistry);
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        unset($this->placeholderRegistry, $this->helper);
    }

    /**
     * @dataProvider isNameMatchDataProvider
     *
     * @param string $name
     * @param string $nameValue
     * @param bool $expected
     */
    public function testIsNameMatch($name, $nameValue, $expected)
    {
        $placeholder = $this->getMockBuilder(AbstractPlaceholder::class)->getMock();

        $placeholder
            ->expects($this->any())
            ->method('getPlaceholder')
            ->willReturn('WEBSITE_ID');

        $placeholder
            ->expects($this->any())
            ->method('getDefaultValue')
            ->willReturn('[^_]+');

        $this->placeholderRegistry
            ->expects($this->once())
            ->method('getPlaceholders')
            ->willReturn([$placeholder]);

        $this->assertEquals($expected, $this->helper->isNameMatch($name, $nameValue));
    }

    public function testIsNameMatchWithoutPlaceholder()
    {
        $name = 'no placeholders';
        $expected = false;

        $this->placeholderRegistry
            ->expects($this->never())
            ->method('getPlaceholders');

        $this->assertEquals($expected, $this->helper->isNameMatch($name, ''));
    }

    /**
     * @return array
     */
    public function isNameMatchDataProvider()
    {
        return [
            'with placeholder' => [
                'name' => 'oro_test_WEBSITE_ID',
                'nameValue' => 'oro_test_1a',
                'expected' => true
            ]
        ];
    }
}
