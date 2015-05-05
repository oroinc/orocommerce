<?php

namespace OroB2B\Bundle\UserAdminBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;

use Oro\Bundle\FormBundle\Form\Extension\TooltipFormExtension;

use OroB2B\Bundle\UserAdminBundle\Form\Type\FrontendRolesType;

class FrontendRolesTypeTest extends FormIntegrationTestCase
{
    const TEST_ROLE_01 = 'TEST_ROLE_01';
    const TEST_ROLE_02 = 'TEST_ROLE_02';

    const TEST_LABEL_01 = 'Test 1';
    const TEST_LABEL_02 = 'Test 2';

    const TEST_DESCRIPTION_01 = 'Test 1 description';
    const TEST_DESCRIPTION_02 = 'Test 2 description';

    /**
     * @var FrontendRolesType
     */
    protected $formType;

    /**
     * @var array
     */
    protected $frontendRoles = [
        'TEST_ROLE_01' => [
            'label' => self::TEST_LABEL_01,
            'description' => self::TEST_DESCRIPTION_01,
        ],
        'TEST_ROLE_02' => [
            'label' => self::TEST_LABEL_02,
            'description' => self::TEST_DESCRIPTION_02,
        ],
    ];

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->formType = new FrontendRolesType($this->frontendRoles);
    }

    /**
     * @param array $submittedData
     * @param array $expected
     * @dataProvider submitDataProvider
     */
    public function testSubmit(array $submittedData, array $expected = null)
    {
        $form = $this->factory->create($this->formType, null, []);

        $labels = [self::TEST_LABEL_01, self::TEST_LABEL_02];
        $descriptions = [self::TEST_DESCRIPTION_01, self::TEST_DESCRIPTION_02];

        /** @var \Symfony\Component\Form\FormInterface $child */
        foreach ($form as $child) {
            $this->assertContains(
                $child->getConfig()->getOption('label'),
                array_shift($labels)
            );
            $this->assertContains(
                $child->getConfig()->getOption('tooltip'),
                array_shift($descriptions)
            );
        }

        $this->assertNull($form->getData());
        $form->submit($submittedData);
        $this->assertTrue($form->isValid());
        $this->assertEquals($expected, $form->getData());
    }

    /**
     * @return array
     */
    protected function getExtensions()
    {
        $extensions = [
            'form' => [
                new TooltipFormExtension()
            ]
        ];

        return [
            new PreloadedExtension([], $extensions)
        ];
    }

    /**
     * @return array
     */
    public function submitDataProvider()
    {
        return [
            'existing roles' => [
                'submittedData' => [
                    self::TEST_ROLE_01,
                    self::TEST_ROLE_02
                ],
                'expected' => [
                    self::TEST_ROLE_01,
                    self::TEST_ROLE_02
                ]
            ],
            'existing role' => [
                'submittedData' => [
                    self::TEST_ROLE_01
                ],
                'expected' => [
                    self::TEST_ROLE_01
                ]
            ],
            'not existing roles' => [
                'submittedData' => [
                    'NOT_EXISTING_ROLE_01',
                    'NOT_EXISTING_ROLE_02'
                ],
                'expected' => null
            ]
        ];
    }

    public function testGetName()
    {
        $this->assertEquals(FrontendRolesType::NAME, $this->formType->getName());
    }

    public function testGetParent()
    {
        $this->assertEquals('choice', $this->formType->getParent());
    }
}
