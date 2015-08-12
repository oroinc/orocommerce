<?php

namespace OroB2B\Bundle\AccountBundle\Form\DataTransformer;

use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

use Symfony\Component\Form\DataTransformerInterface;

class UserIdToEntityTransformer implements DataTransformerInterface
{
    /** @var ManagerRegistry */
    protected $registry;

    /** @var string */
    protected $userClass;

    /**
     * @param ManagerRegistry $registry
     * @param string $userClass
     */
    public function __construct(ManagerRegistry $registry, $userClass)
    {
        $this->registry = $registry;
        $this->userClass = $userClass;
    }

    /**
     * @param mixed $value
     * @return mixed
     */
    public function transform($value)
    {
        return $value;
    }

    /**
     * @param mixed $value
     * @return mixed
     */
    public function reverseTransform($value)
    {
        return $value;
    }

    /**
     * @return ObjectRepository
     */
    protected function getUserRepository()
    {
        return $this->registry->getManagerForClass($this->userClass)->getRepository($this->userClass);
    }
}
