<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Unit\Form\Extension;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\FormBundle\Form\Type\OroEntitySelectOrCreateInlineType;

use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\AccountBundle\Form\Extension\OroEntitySelectOrCreateInlineExtension;

class OroEntitySelectOrCreateInlineExtensionTest extends AbstractAccountUserAwareExtensionTest
{
    protected function setUp()
    {
        parent::setUp();

        $this->extension = new OroEntitySelectOrCreateInlineExtension($this->tokenStorage);
    }

    public function testGetExtendedType()
    {
        $this->assertEquals(OroEntitySelectOrCreateInlineType::NAME, $this->extension->getExtendedType());
    }

    public function testConfigureOptionsNonAccountUser()
    {
        $this->assertOptionsNotChangedForNonAccountUser();
    }

    public function testConfigureOptionsAccountUser()
    {
        $this->assertAccountUserTokenCall();

        /** @var \PHPUnit_Framework_MockObject_MockObject|OptionsResolver $resolver */
        $resolver = $this->getMockBuilder('Symfony\Component\OptionsResolver\OptionsResolver')
            ->disableOriginalConstructor()
            ->getMock();
        $resolver->expects($this->once())
            ->method('setDefault')
            ->with('grid_widget_route', 'orob2b_frontend_datagrid_widget');

        $this->extension->configureOptions($resolver);
    }

    /**
     * @dataProvider viewDataProvider
     * @param object $user
     * @param string $route
     * @param string $expectedRoute
     */
    public function testBuildView($user, $route, $expectedRoute)
    {
        $this->assertAccountUserTokenCall($user);

        $view = new FormView();
        /** @var FormInterface|\PHPUnit_Framework_MockObject_MockObject $form */
        $form = $this->getMock('Symfony\Component\Form\FormInterface');
        $options = [];

        $view->vars['configs']['route_name'] = $route;
        $this->extension->buildView($view, $form, $options);

        $this->assertEquals($expectedRoute, $view->vars['configs']['route_name']);
    }

    /**
     * @return array
     */
    public function viewDataProvider()
    {
        return [
            [new \stdClass(), 'oro_form_autocomplete_search', 'oro_form_autocomplete_search'],
            [new AccountUser(), 'custom_route', 'custom_route'],
            [new AccountUser(), 'oro_form_autocomplete_search', 'orob2b_frontend_autocomplete_search'],
        ];
    }
}
