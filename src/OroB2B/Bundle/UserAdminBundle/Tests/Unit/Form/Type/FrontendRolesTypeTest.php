<?php

namespace OroB2B\Bundle\UserAdminBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\Test\FormIntegrationTestCase;

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
            'Description' => 'Test 1 description',
        ],
        'TEST_ROLE_02' => [
            'label' => 'Test 2',
            'Description' => 'Test 2 description',
        ],
    ];

    protected function setUp()
    {
        parent::setUp();

        $this->formType = new FrontendRolesType($this->frontendRoles);
    }

    /**
     * @param mixed $submittedData
     * @param array $expected
     * @dataProvider submitDataProvider
     */
    public function testSubmit(array $submittedData, array $expected = null)
    {
        $form = $this->factory->create($this->formType, null, []);

        $this->assertNull($form->getData());
        $form->submit($submittedData);
        $this->assertTrue($form->isValid());
        $this->assertEquals($expected, $form->getData());
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
