<?php

namespace OroB2B\Bundle\UserAdminBundle\Tests\Unit\Form\Type;

use Doctrine\ORM\Mapping\ClassMetadataInfo;

use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;

use Oro\Bundle\FormBundle\Form\Extension\TooltipFormExtension;
use Oro\Bundle\FormBundle\Form\Type\EntityIdentifierType;

use OroB2B\Bundle\UserAdminBundle\Entity\Group;
use OroB2B\Bundle\UserAdminBundle\Form\Type\FrontendRolesType;
use OroB2B\Bundle\UserAdminBundle\Form\Type\GroupType;

class GroupTypeTest extends FormIntegrationTestCase
{
    /**
     * @var GroupType
     */
    protected $formType;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Doctrine\ORM\EntityManager
     */
    protected $objectManager;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Doctrine\Common\Persistence\ManagerRegistry
     */
    protected $registry;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Doctrine\ORM\QueryBuilder
     */
    protected $queryBuilder;

    /**
     * @var array
     */
    protected $ids = [];

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->formType = new GroupType();

        $metadata = new ClassMetadataInfo('OroB2BUserAdminBundle:User');
        $metadata->identifier[] = 'id';

        $this->objectManager = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->objectManager->expects($this->any())
            ->method('getClassMetadata')
            ->willReturnMap([['OroB2BUserAdminBundle:User', $metadata]]);

        $this->registry = $this->getMock('Doctrine\Common\Persistence\ManagerRegistry');
        $this->registry->expects($this->any())
            ->method('getManagerForClass')
            ->willReturnMap([['OroB2BUserAdminBundle:User', $this->objectManager]]);

        parent::setUp();
    }

    /**
     * @return array
     */
    protected function getExtensions()
    {
        $types = [
            FrontendRolesType::NAME => new FrontendRolesType(
                [
                    'TEST_ROLE_01' => [
                        'label' => 'Test 1',
                        'description' => 'Test 1 description',
                    ],
                    'TEST_ROLE_02' => [
                        'label' => 'Test 2',
                        'description' => 'Test 2 description',
                    ],
                ]
            ),
            EntityIdentifierType::NAME => new EntityIdentifierType($this->registry),
        ];

        $extensions = [
            'form' => [
                new TooltipFormExtension()
            ]
        ];

        return [
            new PreloadedExtension($types, $extensions)
        ];
    }

    public function testBuildForm()
    {
        $form = $this->factory->create($this->formType);

        $this->assertTrue($form->has('name'));
        $this->assertTrue($form->has('roles'));
        $this->assertTrue($form->has('appendUsers'));
        $this->assertTrue($form->has('removeUsers'));
    }

    /**
     * @param array $submitted
     * @param Group $expected
     * @param array $expectedAppendUsers
     * @param array $expectedRemoveUsers
     * @dataProvider submitDataProvider
     */
    public function testSubmit(
        array $submitted,
        Group $expected,
        array $expectedAppendUsers,
        array $expectedRemoveUsers
    ) {
        $query = $this->getMockBuilder('Doctrine\ORM\AbstractQuery')
            ->disableOriginalConstructor()
            ->setMethods(['execute', 'getSQL', '_doExecute'])
            ->getMock();
        $query->expects($this->any())
            ->method('execute')
            ->willReturnCallback(
                function () {
                    return $this->createMockEntityList('id', $this->ids);
                }
            );

        $this->queryBuilder = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()
            ->setMethods(['where', 'setParameter', 'getQuery'])
            ->getMock();
        $this->queryBuilder->expects($this->any())
            ->method('where')
            ->with('e.id IN (:ids)')
            ->willReturn($this->queryBuilder);
        $this->queryBuilder->expects($this->any())
            ->method('setParameter')
            ->with('ids')
            ->willReturnCallback(
                function ($param, $ids) {
                    $this->ids = $ids;

                    return $this->queryBuilder;
                }
            );
        $this->queryBuilder->expects($this->any())
            ->method('getQuery')
            ->willReturn($query);

        $repository = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->setMethods(['createQueryBuilder'])
            ->getMock();
        $repository->expects($this->any())
            ->method('createQueryBuilder')
            ->with('e')
            ->willReturn($this->queryBuilder);

        $this->objectManager->expects($this->any())
            ->method('getRepository')
            ->with('OroB2BUserAdminBundle:User')
            ->willReturn($repository);

        $entity = new Group('');
        $form = $this->factory->create($this->formType, $entity);

        $this->assertEquals($entity, $form->getData());
        $form->submit($submitted);
        $this->assertTrue($form->isValid());

        $this->assertEquals($expected, $form->getData());
        $this->assertEquals($expectedAppendUsers, $form->get('appendUsers')->getData());
        $this->assertEquals($expectedRemoveUsers, $form->get('removeUsers')->getData());
    }

    /**
     * Create list of mocked entities by id property name and values
     *
     * @param string $property
     * @param array $values
     * @return \PHPUnit_Framework_MockObject_MockObject[]
     */
    protected function createMockEntityList($property, array $values)
    {
        $list = [];
        foreach ($values as $value) {
            $getter = 'get' . ucfirst($property);
            $result = $this->getMock('MockEntity', [$getter]);
            $result->expects($this->any())
                ->method($getter)
                ->will($this->returnValue($value));

            $list[] = $result;
        }

        return $list;
    }

    /**
     * @return array
     */
    public function submitDataProvider()
    {
        $name = 'Test Group Name';
        $role = 'TEST_ROLE_02';

        $group = new Group($name);
        $group->addRole($role);

        return [
            'group without roles and users' => [
                'submitted' => [
                    'name' => $name,
                    'roles' => [],
                ],
                'expected' => new Group($name),
                'expectedAppendUsers' => [],
                'expectedRemoveUsers' => [],
            ],
            'group with roles and without users' => [
                'submitted' => [
                    'name' => $name,
                    'roles' => [$role],
                ],
                'expected' => $group,
                'expectedAppendUsers' => [],
                'expectedRemoveUsers' => [],
            ],
            'group with roles and users' => [
                'submitted' => [
                    'name' => $name,
                    'roles' => [$role],
                    'appendUsers' => [1],
                    'removeUsers' => [2],
                ],
                'expected' => $group,
                'expectedAppendUsers' => $this->createMockEntityList('id', [1]),
                'expectedRemoveUsers' => $this->createMockEntityList('id', [2]),
            ]
        ];
    }

    public function testGetName()
    {
        $this->assertEquals(GroupType::NAME, $this->formType->getName());
    }

    public function testSetDefaultOptions()
    {
        $expectedDefaults = [
            'data_class' => 'OroB2B\Bundle\UserAdminBundle\Entity\Group',
            'intention' => 'group',
        ];

        $resolver = $this->getMock('Symfony\Component\OptionsResolver\OptionsResolverInterface');
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with($expectedDefaults);

        $this->formType->setDefaultOptions($resolver);
    }
}
