<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Unit\Form\Type;

use Oro\Bundle\AccountBundle\Tests\Unit\Form\Type\Stub\DataChangesetTypeStub;
use Oro\Bundle\AccountBundle\Tests\Unit\Form\Type\Stub\EntityChangesetTypeStub;
use Oro\Bundle\FormBundle\Form\Type\DataChangesetType;
use Oro\Bundle\FormBundle\Form\Type\EntityChangesetType;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\AccountGroupProductVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\AccountProductVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\ProductVisibility;
use Oro\Bundle\VisibilityBundle\Form\EventListener\VisibilityPostSetDataListener;
use Oro\Bundle\VisibilityBundle\Form\Type\EntityVisibilityType;
use Oro\Bundle\VisibilityBundle\Provider\VisibilityChoicesProvider;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Symfony\Component\Form\PreloadedExtension;

class EntityVisibilityTypeTest extends FormIntegrationTestCase
{
    const ACCOUNT_CLASS = 'Oro\Bundle\AccountBundle\Entity\Account';
    const ACCOUNT_GROUP_CLASS = 'Oro\Bundle\AccountBundle\Entity\AccountGroup';

    /**
     * @var EntityVisibilityType
     */
    protected $formType;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|VisibilityPostSetDataListener
     */
    protected $visibilityPostSetDataListener;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|VisibilityChoicesProvider
     */
    protected $visibilityChoicesProvider;

    protected function setUp()
    {
        parent::setUp();

        $this->visibilityPostSetDataListener = $this->getMockBuilder(
            'Oro\Bundle\VisibilityBundle\Form\EventListener\VisibilityPostSetDataListener'
        )
            ->disableOriginalConstructor()
            ->getMock();

        $this->visibilityChoicesProvider = $this
            ->getMockBuilder('Oro\Bundle\VisibilityBundle\Provider\VisibilityChoicesProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->formType = new EntityVisibilityType(
            $this->visibilityPostSetDataListener,
            $this->visibilityChoicesProvider
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function getExtensions()
    {
        return [
            new PreloadedExtension(
                [
                    DataChangesetType::NAME => new DataChangesetTypeStub(),
                    EntityChangesetType::NAME => new EntityChangesetTypeStub()
                ],
                []
            ),
            $this->getValidatorExtension(true)
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        unset(
            $this->formType,
            $this->visibilityPostSetDataListener,
            $this->visibilityPostSubmitListener,
            $this->visibilityChoicesProvider
        );
    }

    public function testGetName()
    {
        $this->assertEquals(EntityVisibilityType::NAME, $this->formType->getName());
    }

    public function testBuildForm()
    {
        $this->markTestIncomplete('BB-4710');
        $this->visibilityChoicesProvider->expects($this->once())->method('getFormattedChoices')->willReturn([]);

        $options = [
            'allClass' => ProductVisibility::class,
            'accountGroupClass' => AccountGroupProductVisibility::class,
            'accountClass' => AccountProductVisibility::class
        ];

        $form = $this->factory->create($this->formType, [], $options);

        $form->submit([

        ]);
        $this->assertTrue($form->isValid());
    }
}
