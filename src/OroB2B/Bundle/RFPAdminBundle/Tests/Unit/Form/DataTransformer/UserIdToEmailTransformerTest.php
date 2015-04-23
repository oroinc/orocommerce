<?php

namespace OroB2B\Bundle\RFPAdminBundle\Tests\Unit\Form\DataTransformer;

use OroB2B\Bundle\RFPAdminBundle\Form\DataTransformer\UserIdToEmailTransformer;

class UserIdToEmailTransformerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var UserIdToEmailTransformer
     */
    protected $transformer;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $user = $this->getMockBuilder('Oro\Bundle\UserBundle\Entity\User')
            ->disableOriginalConstructor()
            ->getMock();

        $user->expects($this->any())
            ->method('getId')
            ->willReturn(42);

        $user->expects($this->any())
            ->method('getEmail')
            ->willReturn('box42@example.com');

        $repository = $this->getMockBuilder('Doctrine\Common\Persistence\ObjectRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $repository->expects($this->any())
            ->method('findOneBy')
            ->will($this->returnCallback(function ($arg) use ($user) {
                $map = [
                    'box42@example.com' => $user,
                    'box100500@example.com' => null
                ];

                return $map[$arg['email']];
            }));

        $repository->expects($this->any())
            ->method('find')
            ->will($this->returnCallback(function ($arg) use ($user) {
                $map = [
                    '42' => $user,
                    '100500' => null
                ];

                return $map[$arg];
            }));

        $manager = $this->getMockBuilder('Doctrine\Common\Persistence\ObjectManager')
            ->disableOriginalConstructor()
            ->getMock();

        $manager->expects($this->any())
            ->method('getRepository')
            ->with('OroUserBundle:User')
            ->willReturn($repository);

        $registry = $this->getMockBuilder('Doctrine\Common\Persistence\ManagerRegistry')
            ->disableOriginalConstructor()
            ->getMock();

        $registry->expects($this->any())
            ->method('getManagerForClass')
            ->with('OroUserBundle:User')
            ->willReturn($manager);

        $this->transformer = new UserIdToEmailTransformer($registry);
    }

    /**
     * Test transform
     */
    public function testTransform()
    {
        $this->assertNull($this->transformer->transform(null));
        $this->assertEquals('42', $this->transformer->transform('box42@example.com'));
    }

    /**
     * Test transform fail
     */
    public function testTransformFail()
    {
        $this->setExpectedException('Symfony\Component\Form\Exception\TransformationFailedException');
        $this->transformer->transform('box100500@example.com');
    }

    /**
     * Test reverseTransform
     */
    public function testReverseTransform()
    {
        $this->assertNull($this->transformer->reverseTransform(null));
        $this->assertEquals('box42@example.com', $this->transformer->reverseTransform(42));
    }

    /**
     * Test reverseTransform fail
     */
    public function testReverseTransformFail()
    {
        $this->setExpectedException('Symfony\Component\Form\Exception\TransformationFailedException');
        $this->transformer->reverseTransform(100500);
    }
}
