<?php

namespace OroB2B\Bundle\FrontendBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Component\Layout\Extension\Theme\Model\Theme;
use Oro\Component\Layout\Extension\Theme\Model\ThemeManager;

use OroB2B\Bundle\FrontendBundle\Form\Type\ThemeSelectType;

class ThemeSelectTypeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ThemeManager
     */
    protected $themeManager;

    /**
     * @var ThemeSelectType
     */
    protected $type;

    protected function setUp()
    {
        $this->themeManager = $this->getMockBuilder('Oro\Component\Layout\Extension\Theme\Model\ThemeManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->type = new ThemeSelectType($this->themeManager);
    }

    protected function tearDown()
    {
        unset($this->type, $this->themeManager);
    }

    public function testGetName()
    {
        $this->assertEquals('orob2b_frontend_theme_select', $this->type->getName());
    }

    public function testGetParent()
    {
        $this->assertEquals('choice', $this->type->getParent());
    }

    public function testConfigureOptions()
    {
        $themes = [
            $this->getTheme('theme1', 'label1', 'icon1', 'logo1', 'screenshot1', 'description1'),
            $this->getTheme('theme2', 'label2', 'icon2', 'logo2', 'screenshot2', 'description2')
        ];

        $expectedChoices = [
            'theme1' => 'label1',
            'theme2' => 'label2'
        ];

        $this->themeManager->expects($this->once())
            ->method('getAllThemes')
            ->with('commerce')
            ->will($this->returnValue($themes));

        /** @var \PHPUnit_Framework_MockObject_MockObject|OptionsResolver $resolver */
        $resolver = $this->getMockBuilder('Symfony\Component\OptionsResolver\OptionsResolver')
            ->disableOriginalConstructor()
            ->getMock();
        $resolver->expects($this->once())
            ->method('setDefault')
            ->with('choices', $expectedChoices);

        $this->type->configureOptions($resolver);
    }

    public function testFinishView()
    {
        $themes = [
            $this->getTheme('theme1', 'label1', 'icon1', 'logo1', 'screenshot1', 'description1'),
            $this->getTheme('theme2', 'label2', 'icon2', 'logo2', 'screenshot2', 'description2')
        ];

        $this->themeManager->expects($this->once())
            ->method('getAllThemes')
            ->with('commerce')
            ->will($this->returnValue($themes));

        $view = new FormView();
        /** @var \PHPUnit_Framework_MockObject_MockObject|FormInterface $form */
        $form = $this->getMock('Symfony\Component\Form\FormInterface');
        $options = [];

        $this->type->finishView($view, $form, $options);

        $expectedMetadata = [
            'theme1' => [
                'icon' => 'icon1',
                'logo' => 'logo1',
                'screenshot' => 'screenshot1',
                'description' => 'description1'
            ],
            'theme2' => [
                'icon' => 'icon2',
                'logo' => 'logo2',
                'screenshot' => 'screenshot2',
                'description' => 'description2'
            ]
        ];

        $this->assertArrayHasKey('themes-metadata', $view->vars);
        $this->assertEquals($expectedMetadata, $view->vars['themes-metadata']);
    }

    /**
     * @param string $name
     * @param string $label
     * @param string $icon
     * @param string $logo
     * @param string $screenshot
     * @param string $description
     * @return Theme
     */
    protected function getTheme($name, $label, $icon, $logo, $screenshot, $description)
    {
        $theme = new Theme($name);
        $theme->setLabel($label);
        $theme->setIcon($icon);
        $theme->setLogo($logo);
        $theme->setScreenshot($screenshot);
        $theme->setDescription($description);

        return $theme;
    }
}
