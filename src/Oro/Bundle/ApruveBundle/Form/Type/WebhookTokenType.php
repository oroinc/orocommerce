<?php

namespace Oro\Bundle\ApruveBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\ApruveBundle\TokenGenerator\TokenGeneratorInterface;

class WebhookTokenType extends AbstractType
{
    const BLOCK_PREFIX = 'oro_apruve_webhook_token';

    /**
     * @var TokenGeneratorInterface
     */
    protected $generator;

    /**
     * @param TokenGeneratorInterface $tokenGenerator
     */
    public function __construct(TokenGeneratorInterface $tokenGenerator)
    {
        $this->generator = $tokenGenerator;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'empty_data' => function () {
                return $this->generator->generateToken();
            },
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function getBlockPrefix()
    {
        return self::BLOCK_PREFIX;
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return HiddenType::class;
    }
}
