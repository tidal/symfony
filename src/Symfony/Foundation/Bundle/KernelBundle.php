<?php

namespace Symfony\Foundation\Bundle;

use Symfony\Foundation\Bundle\Bundle;
use Symfony\Foundation\ClassCollectionLoader;
use Symfony\Components\DependencyInjection\ContainerInterface;
use Symfony\Components\DependencyInjection\Loader\Loader;
use Symfony\Components\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Components\DependencyInjection\BuilderConfiguration;

/*
 * This file is part of the Symfony framework.
 *
 * (c) Fabien Potencier <fabien.potencier@symfony-project.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

/**
 * KernelBundle.
 *
 * @package    Symfony
 * @subpackage Foundation
 * @author     Fabien Potencier <fabien.potencier@symfony-project.com>
 */
class KernelBundle extends Bundle
{
  public function buildContainer(ContainerInterface $container)
  {
    Loader::registerExtension(new KernelExtension());

    $configuration = new BuilderConfiguration();

    $loader = new XmlFileLoader(array(__DIR__.'/../Resources/config', __DIR__.'/Resources/config'));
    $configuration->merge($loader->load('services.xml'));

    if ($container->getParameter('kernel.debug'))
    {
      $configuration->merge($loader->load('debug.xml'));
      $configuration->setDefinition('event_dispatcher', $configuration->findDefinition('debug.event_dispatcher'));
    }

    return $configuration;
  }

  public function boot(ContainerInterface $container)
  {
    $container->getErrorHandlerService();

    // load core classes
    ClassCollectionLoader::load($container->getParameter('kernel.compiled_classes'), $container->getParameter('kernel.cache_dir'), 'classes', $container->getParameter('kernel.debug'));
  }
}
