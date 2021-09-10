<?php

namespace Oro\Bundle\PaymentTermBundle\Form\Extension;

use Oro\Bundle\PaymentTermBundle\Provider\PaymentTermAssociationProvider;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

abstract class AbstractPaymentTermAclExtension extends AbstractTypeExtension
{
    /** @var string ACL resource to disable override */
    private $aclResource;

    /** @var AuthorizationCheckerInterface */
    private $authorizationChecker;

    /** @var PaymentTermAssociationProvider */
    private $paymentTermAssociationProvider;

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
     * @param string $aclResource
     */
    public function setAclResource($aclResource)
    {
        $this->aclResource = (string)$aclResource;
    }
}
