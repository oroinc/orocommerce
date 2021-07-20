<?php

namespace Oro\Bundle\RFPBundle\Tests\Unit\Form\DataTransformer;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\RFPBundle\Form\DataTransformer\UserIdToEmailTransformer;
use Oro\Bundle\UserBundle\Entity\User;

class UserIdToEmailTransformerTest extends \PHPUnit\Framework\TestCase
{
    const USER_ID = 42;
    const USER_EMAIL = 'box42@example.com';

    /**
     * @return User|\PHPUnit\Framework\MockObject\MockObject
     */
    protected function getExistingUserMock()
    {
        $user = $this->getMockBuilder('Oro\Bundle\UserBundle\Entity\User')
            ->disableOriginalConstructor()
            ->getMock();
        $user->expects($this->any())
            ->method('getId')
            ->willReturn(self::USER_ID);
        $user->expects($this->any())
            ->method('getEmail')
            ->willReturn(self::USER_EMAIL);

        return $user;
    }

    /**
     * @param array $findMap
     * @param array $findOneByMap
     * @return ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject
     */
    protected function createRegistryMock(array $findMap = [], array $findOneByMap = [])
    {
        $repository = $this->getMockBuilder('Doctrine\Persistence\ObjectRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $repository->expects($this->any())
            ->method('find')
            ->willReturnMap($findMap);
        $repository->expects($this->any())
            ->method('findOneBy')
            ->willReturnMap($findOneByMap);

        $manager = $this->getMockBuilder('Doctrine\Persistence\ObjectManager')
            ->disableOriginalConstructor()
            ->getMock();
        $manager->expects($this->any())
            ->method('getRepository')
            ->with('OroUserBundle:User')
            ->willReturn($repository);

        $registry = $this->getMockBuilder('Doctrine\Persistence\ManagerRegistry')
            ->disableOriginalConstructor()
            ->getMock();
        $registry->expects($this->any())
            ->method('getManagerForClass')
            ->with('OroUserBundle:User')
            ->willReturn($manager);

        return $registry;
    }

    /**
     * @return array
     */
    public function transformDataProvider()
    {
        return [
            'null' => [
                'input' => null,
                'expected' => null,
            ],
            'empty string' => [
                'input' => '',
                'expected' => null,
            ],
            'existing email' => [
                'input' => self::USER_EMAIL,
                'expected' => self::USER_ID,
            ]
        ];
    }

    /**
     * @param mixed $input
     * @param mixed $expected
     * @dataProvider transformDataProvider
     */
    public function testTransform($input, $expected)
    {
        $findOneByMap = [];
        if ($expected) {
            $findOneByMap[] = [['email' => $input], $this->getExistingUserMock()];
        }

        $transformer = new UserIdToEmailTransformer($this->createRegistryMock([], $findOneByMap));
        $this->assertEquals($expected, $transformer->transform($input));
    }

    public function testTransformException()
    {
        $this->expectException(\Symfony\Component\Form\Exception\TransformationFailedException::class);
        $this->expectExceptionMessage('User with email "unknown_email@example.com" does not exist');

        $transformer = new UserIdToEmailTransformer($this->createRegistryMock());
        $transformer->transform('unknown_email@example.com');
    }

    /**
     * @return array
     */
    public function reverseTransformDataProvider()
    {
        return [
            'null' => [
                'input' => null,
                'expected' => null,
            ],
            'empty string' => [
                'input' => '',
                'expected' => null,
            ],
            'existing email' => [
                'input' => self::USER_EMAIL,
                'expected' => self::USER_EMAIL,
            ],
            'existing id' => [
                'input' => self::USER_ID,
                'expected' => self::USER_EMAIL,
            ]
        ];
    }

    /**
     * @param mixed $input
     * @param mixed $expected
     * @dataProvider reverseTransformDataProvider
     */
    public function testReverseTransform($input, $expected)
    {
        $findMap = [];
        if ($expected) {
            $findMap[] = [$input, $this->getExistingUserMock()];
        }

        $transformer = new UserIdToEmailTransformer($this->createRegistryMock($findMap));
        $this->assertEquals($expected, $transformer->reverseTransform($input));
    }

    public function testReverseTransformException()
    {
        $this->expectException(\Symfony\Component\Form\Exception\TransformationFailedException::class);
        $this->expectExceptionMessage('User with ID "100500" does not exist');

        $unknownEmailId = 100500;

        $transformer = new UserIdToEmailTransformer($this->createRegistryMock());
        $transformer->reverseTransform($unknownEmailId);
    }
}
