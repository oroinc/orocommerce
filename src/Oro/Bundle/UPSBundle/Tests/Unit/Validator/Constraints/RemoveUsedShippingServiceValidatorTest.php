<?php

namespace Oro\Bundle\UPSBundle\Tests\Unit\Validator\Constraints;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\ShippingBundle\Entity\ShippingRuleMethodConfig;
use Oro\Bundle\ShippingBundle\Entity\ShippingRuleMethodTypeConfig;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodRegistry;
use Oro\Bundle\UPSBundle\Entity\ShippingService;
use Oro\Bundle\UPSBundle\Entity\UPSTransport;
use Oro\Bundle\UPSBundle\Method\UPSShippingMethod;
use Oro\Bundle\UPSBundle\Validator\Constraints\RemoveUsedShippingService;
use Oro\Bundle\UPSBundle\Validator\Constraints\RemoveUsedShippingServiceValidator;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class RemoveUsedShippingServiceValidatorTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;
    
    /**
     * @var ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $doctrine;

    /**
     * @var ShippingMethodRegistry|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $registry;

    /**
     * @var RemoveUsedShippingService
     */
    protected $constraint;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ExecutionContextInterface
     */
    protected $context;

    /**
     * @var RemoveUsedShippingServiceValidator
     */
    protected $validator;

    protected function setUp()
    {
        $this->doctrine = $this->getMock(ManagerRegistry::class);
        $this->registry = $this->getMock(ShippingMethodRegistry::class);

        $this->constraint = new RemoveUsedShippingService();
        $this->context = $this->getMock(ExecutionContextInterface::class);

        $this->validator =
            new RemoveUsedShippingServiceValidator($this->doctrine, $this->registry);
        $this->validator->initialize($this->context);
    }

    public function testConfiguration()
    {
        static::assertEquals(
            RemoveUsedShippingServiceValidator::ALIAS,
            $this->constraint->validatedBy()
        );
        static::assertEquals(Constraint::PROPERTY_CONSTRAINT, $this->constraint->getTargets());
    }

    /**
     * @param array $configured
     * @param array $submitted
     * @param int $violations
     *
     * @dataProvider validateDataProvider
     */
    public function testValidate($configured, $submitted, $violations)
    {
        $value = $this->createShippingServices($submitted);
        /** @var Constraint|\PHPUnit_Framework_MockObject_MockObject $constraint **/
        $constraint = $this->getMockBuilder(Constraint::class)
            ->disableOriginalConstructor()
            ->getMock();

        $country = new Country('US');
        $transport = (new UPSTransport())->setCountry($country);
        $transportForm = $this->createForm($transport, 'upstransport');
        $channel = $this->getEntity(Channel::class, ['id' => 1]);
        $channelForm = $this->createForm($channel, 'upstransport');
        
        $transportForm->expects(static::once())
            ->method('getParent')
            ->willReturn($channelForm);

        $this->context->expects(static::any())
            ->method('getPropertyPath')
            ->willReturn('[upstransport]');
        $this->context->expects(static::any())
            ->method('getRoot')
            ->willReturn($transportForm);

        $upsShippingMethod = $this
            ->getMockBuilder(UPSShippingMethod::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->registry->expects(static::once())
            ->method('getShippingMethod')
            ->willReturn($upsShippingMethod);

        $repository1 = $this
            ->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $repository1->expects(static::any())
            ->method('findBy')
            ->willReturn([$this->createShippingRuleMethod($configured)]);

        $enabledTypes = [];
        foreach ($configured as $v) {
            if ($v['enabled'] === true) {
                $enabledTypes[] = $v['code'];
            }
        }
        $repository2 = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $repository2->expects(static::any())
            ->method('findBy')
            ->willReturn($this->createShippingServices(array_diff($enabledTypes, $submitted), true));

        $manager = $this->getMock('Doctrine\ORM\EntityManagerInterface');
        $manager->expects(static::any())
            ->method('getRepository')
            ->willReturnMap([
                ['OroShippingBundle:ShippingRuleMethodConfig', $repository1],
                ['OroUPSBundle:ShippingService', $repository2],
            ]);

        $this->doctrine->expects(static::any())
            ->method('getManagerForClass')
            ->willReturn($manager);
        
        $this->context->expects(static::exactly($violations))
            ->method('addViolation');

        $this->validator->validate($value, $constraint);
    }

    /**
     * @return array
     */
    public function validateDataProvider()
    {
        return [
            'NotValid' => [
                'configured' => [
                    ['code' => '01', 'enabled' =>false],
                    ['code' => '02', 'enabled' =>true],
                    ['code' => '03', 'enabled' =>true],
                ],
                'submitted' => ['01', '02'],
                'violations' => 1,
            ],
            'Valid' => [
                'configured' => [
                    ['code' => '01', 'enabled' =>false],
                    ['code' => '02', 'enabled' =>true],
                    ['code' => '03', 'enabled' =>true],
                ],
                'submitted' => ['02', '03'],
                'violations' => 0
            ]
        ];
    }

    /**
     * @param array $codes
     * @param bool $toArray
     * @return ArrayCollection
     */
    protected function createShippingServices($codes, $toArray = false)
    {
        $collection = new ArrayCollection();
        foreach ($codes as $code) {
            $service = (new ShippingService())->setCode($code);
            $collection->add($service);
        }
        if ($toArray) {
            return $collection->toArray();
        } else {
            return $collection;
        }
    }

    /**
     * @param array $codes
     * @return ShippingRuleMethodConfig
     */
    protected function createShippingRuleMethod($codes)
    {
        $method = new ShippingRuleMethodConfig();
        foreach ($codes as $code) {
            $type = (new ShippingRuleMethodTypeConfig())->setType($code['code'])->setEnabled($code['enabled']);
            $method->addTypeConfig($type);
        }
        return $method;
    }

    /**
     * @param object $data
     * @param string $path
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function createForm($data, $path)
    {
        $form = $this->getMock('Symfony\Component\Form\FormInterface');
        $form->expects(static::any())
            ->method('offsetExists')
            ->with($path)
            ->willReturn(true);

        $form->expects(static::any())
            ->method('offsetGet')
            ->with($path)
            ->willReturn($form);
        $form->expects(static::any())->method('getData')->willReturn($data);
        return $form;
    }
}
