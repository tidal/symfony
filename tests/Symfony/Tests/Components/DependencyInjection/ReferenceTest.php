<?php

/*
 * This file is part of the symfony package.
 * (c) Fabien Potencier <fabien.potencier@symfony-project.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Tests\Components\DependencyInjection;

use Symfony\Components\DependencyInjection\Reference;

class ReferenceTest extends \PHPUnit_Framework_TestCase
{
  public function testConstructor()
  {
    $ref = new Reference('foo');
    $this->assertEquals('foo', (string) $ref, '__construct() sets the id of the reference, which is used for the __toString() method');
  }
}
