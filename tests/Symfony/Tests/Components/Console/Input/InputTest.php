<?php

/*
 * This file is part of the symfony package.
 * (c) Fabien Potencier <fabien.potencier@symfony-project.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Tests\Components\Console\Input;

use Symfony\Components\Console\Input\ArrayInput;
use Symfony\Components\Console\Input\InputDefinition;
use Symfony\Components\Console\Input\InputArgument;
use Symfony\Components\Console\Input\InputOption;

class InputTest extends \PHPUnit_Framework_TestCase
{
  public function testConstructor()
  {
    $input = new ArrayInput(array('name' => 'foo'), new InputDefinition(array(new InputArgument('name'))));
    $this->assertEquals('foo', $input->getArgument('name'), '->__construct() takes a InputDefinition as an argument');
  }

  public function testOptions()
  {
    $input = new ArrayInput(array('--name' => 'foo'), new InputDefinition(array(new InputOption('name'))));
    $this->assertEquals('foo', $input->getOption('name'), '->getOption() returns the value for the given option');

    $input->setOption('name', 'bar');
    $this->assertEquals('bar', $input->getOption('name'), '->setOption() sets the value for a given option');
    $this->assertEquals(array('name' => 'bar'), $input->getOptions(), '->getOptions() returns all option values');

    $input = new ArrayInput(array('--name' => 'foo'), new InputDefinition(array(new InputOption('name'), new InputOption('bar', '', InputOption::PARAMETER_OPTIONAL, '', 'default'))));
    $this->assertEquals('default', $input->getOption('bar'), '->getOption() returns the default value for optional options');
    $this->assertEquals(array('name' => 'foo', 'bar' => 'default'), $input->getOptions(), '->getOptions() returns all option values, even optional ones');

    try
    {
      $input->setOption('foo', 'bar');
      $this->fail('->setOption() throws a \InvalidArgumentException if the option does not exist');
    }
    catch (\InvalidArgumentException $e)
    {
    }

    try
    {
      $input->getOption('foo');
      $this->fail('->getOption() throws a \InvalidArgumentException if the option does not exist');
    }
    catch (\InvalidArgumentException $e)
    {
    }
  }

  public function testArguments()
  {
    $input = new ArrayInput(array('name' => 'foo'), new InputDefinition(array(new InputArgument('name'))));
    $this->assertEquals('foo', $input->getArgument('name'), '->getArgument() returns the value for the given argument');

    $input->setArgument('name', 'bar');
    $this->assertEquals('bar', $input->getArgument('name'), '->setArgument() sets the value for a given argument');
    $this->assertEquals(array('name' => 'bar'), $input->getArguments(), '->getArguments() returns all argument values');

    $input = new ArrayInput(array('name' => 'foo'), new InputDefinition(array(new InputArgument('name'), new InputArgument('bar', InputArgument::OPTIONAL, '', 'default'))));
    $this->assertEquals('default', $input->getArgument('bar'), '->getArgument() returns the default value for optional arguments');
    $this->assertEquals(array('name' => 'foo', 'bar' => 'default'), $input->getArguments(), '->getArguments() returns all argument values, even optional ones');

    try
    {
      $input->setArgument('foo', 'bar');
      $this->fail('->setArgument() throws a \InvalidArgumentException if the argument does not exist');
    }
    catch (\InvalidArgumentException $e)
    {
    }

    try
    {
      $input->getArgument('foo');
      $this->fail('->getArgument() throws a \InvalidArgumentException if the argument does not exist');
    }
    catch (\InvalidArgumentException $e)
    {
    }
  }

  public function testValidate()
  {
    $input = new ArrayInput(array());
    $input->bind(new InputDefinition(array(new InputArgument('name', InputArgument::REQUIRED))));

    try
    {
      $input->validate();
      $this->fail('->validate() throws a \RuntimeException if not enough arguments are given');
    }
    catch (\RuntimeException $e)
    {
    }

    $input = new ArrayInput(array('name' => 'foo'));
    $input->bind(new InputDefinition(array(new InputArgument('name', InputArgument::REQUIRED))));

    try
    {
      $input->validate();
    }
    catch (\RuntimeException $e)
    {
      $this->fail('->validate() does not throw a \RuntimeException if enough arguments are given');
    }
  }

  public function testSetFetInteractive()
  {
    $input = new ArrayInput(array());
    $this->assertTrue($input->isInteractive(), '->isInteractive() returns whether the input should be interactive or not');
    $input->setInteractive(false);
    $this->assertTrue(!$input->isInteractive(), '->setInteractive() changes the interactive flag');
  }
}
