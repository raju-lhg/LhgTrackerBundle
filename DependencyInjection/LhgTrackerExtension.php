<?php 

namespace KimaiPlugin\LhgTrackerBundle\DependencyInjection;

use Doctrine\ORM\Query\Parser;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class LhgTrackerExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new Loader\YamlFileLoader(
            $container, 
            new FileLocator(__DIR__ . '/../Resources/config')
        );

        $loader->load('services.yaml');
        // $loader->load('twig.yaml');
    }

    // public function prepend(ContainerBuilder $container): void
    // {
    //     $yamlParser = new Parser();

    //     // load the entity serialization config (mainly for the API)
    //     $serializerConfig = file_get_contents(__DIR__ . '/../Resources/config/jms_serializer.yaml');
    //     if ($serializerConfig === false) {
    //         throw new \Exception('Could not read serializer configuration');
    //     }
    //     $config = $yamlParser->parse($serializerConfig);
    //     $container->prependExtensionConfig('jms_serializer', $config['jms_serializer']); 
    // }
}