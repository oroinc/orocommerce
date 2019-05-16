Customize Products SKU Validation
=================================

The `'/^[a-zA-Z0-9]*$/'` pattern is used for Product SKU validation by default.
This pattern provides the possibility to save alphanumeric symbols with additional dash and underscore symbols.
If you need to extend the default pattern for Product SKU Validation or make it stricter,
override the `oro_product.sku.regex_pattern` parameter and the translation for the validation message. 

### Override the `oro_product.sku.regex_pattern` parameter

There are 2 ways to override the `oro_product.sku.regex_pattern` parameter in your own bundle:

1. Add the `oro_product.sku.regex_pattern` parameter to the `Resources/config/services.yml` file in your bundle.
    
    ```yml
    # src/Acme/Bundle/DemoBundle/Resources/config/services.yml
    parameters:
        ...
        oro_product.sku.regex_pattern: '/^[a-z]*$/'
    ```
    
2. Write appropriate CompilerPass in your bundle

    ```php
    // src/Acme/DemoBundle/DependencyInjection/Compiler/OverrideProductSKUCompilerPass.php
    namespace Acme\DemoBundle\DependencyInjection\Compiler;
    
    use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
    use Symfony\Component\DependencyInjection\ContainerBuilder;
    
    class OverrideProductSKUCompilerPass implements CompilerPassInterface
    {
        public function process(ContainerBuilder $container)
        {  
            $container->setParameter('oro_product.sku.regex_pattern', '/^[a-z]*$/');
        }
    }
    ```
    
   and register this CompilerPass in the build() method of the bundle class
   
   ```php
   // src/Acme/DemoBundle/DemoBundle.php
   namespace DemoBundle;
   
   use Acme\DemoBundle\DependencyInjection\Compiler\OverrideProductSKUCompilerPass;
   use Symfony\Component\DependencyInjection\ContainerBuilder;
   use Symfony\Component\HttpKernel\Bundle\Bundle;
   
   class DemoBundle extends Bundle
   {
       public function build(ContainerBuilder $container)
       {
           parent::build($container);
   
           $container->addCompilerPass(new OverrideProductSKUCompilerPass());
       }
   }
   ```

### Override the validation message

If you need to change the default `'This vaule should contain only latin letters, numbers and symbols "-" or "_".'` validation message, override the `oro.product.sku.not_match_regex` translation key. To do that, add the appropriate translation to the `translations/validators.en.yml` file in your bundle:

```yml
# translations/validators.en.yaml
oro.product.sku.not_match_regex: This vaule should contain only latin letters in lower case.
```
