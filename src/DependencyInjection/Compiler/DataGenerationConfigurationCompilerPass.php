<?php

namespace App\DependencyInjection\Compiler;

use App\Command\PopulateDBCommand;
use App\DependencyInjection\DataGenerationConfiguration;
use App\Interface\service\FakeDataGeneratorInterface;
use App\Service\Generator\FakeDataGenerator;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Yaml\Yaml;

class DataGenerationConfigurationCompilerPass implements CompilerPassInterface
{

    public function process(ContainerBuilder $container)
    {
        $processor = new Processor();

        $config = Yaml::parse(file_get_contents(__DIR__.'/../../../config/data_generation_configuration.yaml'));
        $config = $processor->processConfiguration(new DataGenerationConfiguration(), $config);

        $fakeDataGenerator = $container->getDefinition(FakeDataGeneratorInterface::class);
        $fakeDataGenerator->setArgument('$languages', $config['languages']);

        $populateDBCommand = $container->getDefinition(PopulateDBCommand::class);
        $populateDBCommand->setArgument('$languages', $config['languages']);
    }
}