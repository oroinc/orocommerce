<?php

namespace OroB2B\Bundle\UserAdminBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;

use Oro\Bundle\FormBundle\Form\Extension\TooltipFormExtension;

use OroB2B\Bundle\UserAdminBundle\Form\Type\FrontendRolesType;

class FrontendRolesTypeTest extends FormIntegrationTestCase
{
    /**
     * @var FrontendRolesType
     */
    protected $formType;

    /**
     * @var array
     */
    protected $frontendRoles = [
        'TEST_ROLE_01' => [
            'label' => 'Test 1',
            'description' => 'Test 1 description',
        ],
        'TEST_ROLE_02' => [
            'label' => 'Test 2',
            'description' => 'Test 2 description',
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

        /** @var \Symfony\Component\Form\FormInterface $child */
        foreach ($form as $child) {
            $this->assertTrue($child->getConfig()->hasOption('tooltip'));
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
                    'TEST_ROLE_01',
                    'TEST_ROLE_02'
                ],
                'expected' => [
                    'TEST_ROLE_01',
                    'TEST_ROLE_02'
                ]
            ],
            'existing role' => [
                'submittedData' => [
                    'TEST_ROLE_01',
                ],
                'expected' => [
                    'TEST_ROLE_01',
                ]
            ],
            'not existing roles' => [
                'submittedData' => [
                    'TEST1',
                    'TEST2'
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
