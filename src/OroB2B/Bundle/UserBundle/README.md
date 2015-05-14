OroB2B\Bundle\UserBundle\OroB2BUserBundle
=========================================

Description:
------------

This bundle provides a way for frontend users handle its account. It based on FOSUserBundle and extends its functionality.

Bundle responsibilities:
------------------------

Frontend User registration.
Frontend User login/logut.
Change and reset password.
Frontend User profile.

Expected dependencies:
----------------------

Doctrine\ORM\EntityManager
Doctrine\ORM\Mapping
FOS\UserBundle
JMS\AopBundle\JMSAopBundle
JMS\DiExtraBundle\JMSDiExtraBundle
JMS\SecurityExtraBundle\JMSSecurityExtraBundle
Oro\Bundle\ApplicationBundle\Config\ConfigManager
Oro\Bundle\TestFrameworkBundle\Test\DependencyInjection\ExtensionTestCase
OroB2B\Bundle\FrontendBundle\Test\WebTestCase
Symfony\Bundle\FrameworkBundle\Templating\EngineInterface
Symfony\Component\Config\FileLocator
Symfony\Component\DependencyInjection\ContainerBuilder
Symfony\Component\DependencyInjection\Loader
Symfony\Component\Form\Extension\Validator\Type\FormTypeValidatorExtension
Symfony\Component\Form\Extension\Validator\ValidatorExtension
Symfony\Component\Form\FormBuilderInterface
Symfony\Component\Form\Forms
Symfony\Component\Form\Test\FormIntegrationTestCase
Symfony\Component\HttpKernel
Symfony\Component\Routing\RouterInterface
Symfony\Component\Validator
