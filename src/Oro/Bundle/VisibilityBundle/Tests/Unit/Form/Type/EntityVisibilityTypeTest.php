<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\Validator\Validation;

use Oro\Bundle\FormBundle\Form\Type\DataChangesetType;
use Oro\Bundle\FormBundle\Form\Type\EntityChangesetType;
use Oro\Bundle\VisibilityBundle\Form\EventListener\VisibilityPostSetDataListener;
use Oro\Bundle\VisibilityBundle\Form\Type\EntityVisibilityType;
use Oro\Bundle\VisibilityBundle\Provider\VisibilityChoicesProvider;

use Oro\Bundle\AccountBundle\Tests\Unit\Form\Type\Stub\DataChangesetTypeStub;
use Oro\Bundle\AccountBundle\Tests\Unit\Form\Type\Stub\EntityChangesetTypeStub;

class EntityVisibilityTypeTest extends FormIntegrationTestCase
{
    const ACCOUNT_CLASS = 'Oro\Bundle\AccountBundle\Entity\Account';
    const ACCOUNT_GROUP_CLASS = 'Oro\Bundle\AccountBundle\Entity\AccountGroup';

    /**
     * @var EntityVisibilityType
     */
    protected $formType;

    /** @var \PHPUnit_Framework_MockObject_MockObject|VisibilityPostSetDataListener */
    protected $visibilityPostSetDataListener;

    /** @var \PHPUnit_Framework_MockObject_MockObject|VisibilityChoicesProvider */
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
        $this->formType->setAccountGroupClass(self::ACCOUNT_GROUP_CLASS);
        $this->formType->setAccountClass(self::ACCOUNT_CLASS);
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
            new ValidatorExtension(Validation::createValidator()),
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
        $this->visibilityChoicesProvider->expects($this->once())->method('getFormattedChoices')->willReturn([]);

        $options = [
            'targetEntityField' => '',
            'allClass' => '',
            'accountGroupClass' => '',
            'accountClass' => '',
        ];

        $form = $this->factory->create($this->formType, [], $options);

        $this->assertTrue($form->has('all'));
        $this->assertTrue($form->has('account'));
        $this->assertTrue($form->has('accountGroup'));
    }
}
