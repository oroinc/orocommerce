<?php

namespace Oro\Bundle\PaymentTermBundle\Form\Extension;

use Oro\Bundle\PaymentTermBundle\Provider\PaymentTermAssociationProvider;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class PaymentTermAclExtension extends AbstractTypeExtension
{
    /** @var string */
    private $extendedType;

    /** @var string ACL resource to disable override */
    private $aclResource;

    /** @var AuthorizationCheckerInterface */
    private $authorizationChecker;

    /** @var PaymentTermAssociationProvider */
    private $paymentTermAssociationProvider;

    /**
     * @param PaymentTermAssociationProvider $paymentTermAssociationProvider
     * @param AuthorizationCheckerInterface $authorizationChecker
     */
    public function __construct(
        PaymentTermAssociationProvider $paymentTermAssociationProvider,
        AuthorizationCheckerInterface $authorizationChecker
    ) {
        $this->paymentTermAssociationProvider = $paymentTermAssociationProvider;
        $this->authorizationChecker = $authorizationChecker;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if (!$this->aclResource) {
            throw new \InvalidArgumentException('ACL resource not configured');
        }

        if (!$this->authorizationChecker->isGranted($this->aclResource)) {
            $associationNames = $this->paymentTermAssociationProvider->getAssociationNames($options['data_class']);
            foreach ($associationNames as $associationName) {
                if ($builder->has($associationName)) {
                    $builder->remove($associationName);
                }
            }
        }
    }

    /**
     * {@inheritdoc}
     * @throws \InvalidArgumentException If extendedType not set
     */
    public function getExtendedType()
    {
        if (!$this->extendedType) {
            throw new \InvalidArgumentException('Extended Type not configured');
        }

        return $this->extendedType;
    }

    /**
     * @param string $extendedType
     */
    public function setExtendedType($extendedType)
    {
        $this->extendedType = (string)$extendedType;
    }

    /**
     * @param string $aclResource
     */
    public function setAclResource($aclResource)
    {
        $this->aclResource = (string)$aclResource;
    }
}
