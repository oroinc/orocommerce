<?php

namespace OroB2B\Bundle\AccountBundle\Layout\Block\Type;

use Symfony\Component\HttpKernel\Fragment\FragmentHandler;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

use Oro\Component\Layout\Block\OptionsResolver\OptionsResolver;
use Oro\Component\Layout\Block\Type\AbstractType;
use Oro\Component\Layout\BlockInterface;
use Oro\Component\Layout\BlockView;

use OroB2B\Bundle\AccountBundle\Entity\AccountUser;

class AddressBookType extends AbstractType
{
    const NAME = 'address_book';

    /**
     * @var UrlGeneratorInterface
     */
    protected $router;

    /**
     * @var FragmentHandler
     */
    protected $fragmentHandler;

    /**
     * @param UrlGeneratorInterface $router
     * @param FragmentHandler $fragmentHandler
     */
    public function __construct(UrlGeneratorInterface $router, FragmentHandler $fragmentHandler)
    {
        $this->router = $router;
        $this->fragmentHandler = $fragmentHandler;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'addressCreateAclResource' => null,
                'addressUpdateAclResource' => null,
                'useFormDialog' => false
            ]
        );

        $resolver->setRequired(['entity', 'addressListRouteName', 'addressCreateRouteName', 'addressUpdateRouteName']);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function finishView(BlockView $view, BlockInterface $block, array $options)
    {
        $entity = $options['entity'];

        if (!$entity instanceof AccountUser) {
            throw new \RuntimeException(
                sprintf(
                    'Expected instance of type "%s", "%s" given',
                    'OroB2B\Bundle\AccountBundle\Entity\AccountUser',
                    is_object($entity) ? get_class($entity) : gettype($entity)
                )
            );
        }

        $view->vars['item'] = $entity;
        $view->vars['addressCreateAclResource'] = $options['addressCreateAclResource'];
        $view->vars['addressUpdateAclResource'] = $options['addressUpdateAclResource'];
        $view->vars['componentOptions'] = $this->getAddressBookOptions($options['entity'], $options);
    }

    /**
     * @param AccountUser $entity
     * @param array $options
     * @return array
     */
    protected function getAddressBookOptions(AccountUser $entity, array $options)
    {
        $addressListUrl = $this->router->generate($options['addressListRouteName'], ['entityId' => $entity->getId()]);
        $addressCreateUrl = $this->router->generate(
            $options['addressCreateRouteName'],
            ['entityId' => $entity->getId()]
        );

        return [
            'entityId' => $entity->getId(),
            'addressListUrl' => $addressListUrl,
            'addressCreateUrl' => $addressCreateUrl,
            'addressUpdateRouteName' => $options['addressUpdateRouteName'],
            'currentAddresses' => $this->fragmentHandler->render($addressListUrl),
            'useFormDialog' => $options['useFormDialog']
        ];
    }
}
