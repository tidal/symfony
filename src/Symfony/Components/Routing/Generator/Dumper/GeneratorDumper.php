<?php

namespace Symfony\Components\Routing\Generator\Dumper;

use Symfony\Components\Routing\RouteCollection;
use Symfony\Components\Routing\Route;

/*
 * This file is part of the Symfony framework.
 *
 * (c) Fabien Potencier <fabien.potencier@symfony-project.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

/**
 * GeneratorDumper is the base class for all built-in generator dumpers.
 *
 * @package    Symfony
 * @subpackage Components_Routing
 * @author     Fabien Potencier <fabien.potencier@symfony-project.com>
 */
abstract class GeneratorDumper implements GeneratorDumperInterface
{
  protected $routes;

  /**
   * Constructor.
   *
   * @param RouteCollection $routes The RouteCollection to dump
   */
  public function __construct(RouteCollection $routes)
  {
    $this->routes = $routes;
  }

  /**
   * Dumps the routing.
   *
   * @param  array  $options An array of options
   *
   * @return string The representation of the routing
   *
   * @throws \LogicException When this abstrass class is not implemented
   */
  public function dump(array $options = array())
  {
    throw new \LogicException('You must extend this abstract class and implement the dump() method.');
  }
}
