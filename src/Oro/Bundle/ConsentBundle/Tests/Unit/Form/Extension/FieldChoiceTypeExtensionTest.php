<?php

namespace Oro\Bundle\ConsentBundle\Tests\Unit\Form\Extension;

use Oro\Bundle\ConsentBundle\Form\Extension\FieldChoiceTypeExtension;
use Oro\Bundle\QueryDesignerBundle\Form\Type\FieldChoiceType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

class FieldChoiceTypeExtensionTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var FieldChoiceTypeExtension
     */
    private $extension;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->extension = new FieldChoiceTypeExtension();
    }

    public function testGetExtendTypes()
    {
        $this->assertSame([FieldChoiceType::class], FieldChoiceTypeExtension::getExtendedTypes());
    }

    public function testBuildView()
    {
        $formView = new FormView();

        /**
         * @var FormInterface|\PHPUnit\Framework\MockObject\MockObject
         */
        $formMock = $this->createMock(FormInterface::class);
        $this->extension->buildView($formView, $formMock, []);

        $this->assertSame([['name' => 'acceptedConsents']], $formView->vars['page_component_options']['exclude']);
    }
}
