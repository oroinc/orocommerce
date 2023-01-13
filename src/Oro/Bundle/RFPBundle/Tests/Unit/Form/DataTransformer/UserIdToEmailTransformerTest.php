<?php

namespace Oro\Bundle\RFPBundle\Tests\Unit\Form\DataTransformer;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Persistence\ObjectRepository;
use Oro\Bundle\RFPBundle\Form\DataTransformer\UserIdToEmailTransformer;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\Form\Exception\TransformationFailedException;

class UserIdToEmailTransformerTest extends \PHPUnit\Framework\TestCase
{
    private const USER_ID = 42;
    private const USER_EMAIL = 'box42@example.com';

    private function getExistingUser(): User
    {
        $user = $this->createMock(User::class);
        $user->expects($this->any())
            ->method('getId')
            ->willReturn(self::USER_ID);
        $user->expects($this->any())
            ->method('getEmail')
            ->willReturn(self::USER_EMAIL);

        return $user;
    }

    private function getDoctrine(array $findMap = [], array $findOneByMap = []): ManagerRegistry
    {
        $repository = $this->createMock(ObjectRepository::class);
        $repository->expects($this->any())
            ->method('find')
            ->willReturnMap($findMap);
        $repository->expects($this->any())
            ->method('findOneBy')
            ->willReturnMap($findOneByMap);

        $manager = $this->createMock(ObjectManager::class);
        $manager->expects($this->any())
            ->method('getRepository')
            ->with('OroUserBundle:User')
            ->willReturn($repository);

        $registry = $this->createMock(ManagerRegistry::class);
        $registry->expects($this->any())
            ->method('getManagerForClass')
            ->with('OroUserBundle:User')
            ->willReturn($manager);

        return $registry;
    }

    public function transformDataProvider(): array
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
     * @dataProvider transformDataProvider
     */
    public function testTransform(mixed $input, mixed $expected)
    {
        $findOneByMap = [];
        if ($expected) {
            $findOneByMap[] = [['email' => $input], $this->getExistingUser()];
        }

        $transformer = new UserIdToEmailTransformer($this->getDoctrine([], $findOneByMap));
        $this->assertEquals($expected, $transformer->transform($input));
    }

    public function testTransformException()
    {
        $this->expectException(TransformationFailedException::class);
        $this->expectExceptionMessage('User with email "unknown_email@example.com" does not exist');

        $transformer = new UserIdToEmailTransformer($this->getDoctrine());
        $transformer->transform('unknown_email@example.com');
    }

    public function reverseTransformDataProvider(): array
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
     * @dataProvider reverseTransformDataProvider
     */
    public function testReverseTransform(mixed $input, mixed $expected)
    {
        $findMap = [];
        if ($expected) {
            $findMap[] = [$input, $this->getExistingUser()];
        }

        $transformer = new UserIdToEmailTransformer($this->getDoctrine($findMap));
        $this->assertEquals($expected, $transformer->reverseTransform($input));
    }

    public function testReverseTransformException()
    {
        $this->expectException(TransformationFailedException::class);
        $this->expectExceptionMessage('User with ID "100500" does not exist');

        $unknownEmailId = 100500;

        $transformer = new UserIdToEmailTransformer($this->getDoctrine());
        $transformer->reverseTransform($unknownEmailId);
    }
}
