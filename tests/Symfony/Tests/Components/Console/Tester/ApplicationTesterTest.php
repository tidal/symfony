<?php

/*
 * This file is part of the symfony package.
 * (c) Fabien Potencier <fabien.potencier@symfony-project.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Tests\Components\Console\Tester;

use Symfony\Components\Console\Application;
use Symfony\Components\Console\Output\Output;
use Symfony\Components\Console\Tester\ApplicationTester;

class ApplicationTesterTest extends \PHPUnit_Framework_TestCase
{
  protected $application;
  protected $tester;

  public function setUp()
  {
    $this->application = new Application();
    $this->application->setAutoExit(false);
    $this->application->register('foo')
      ->addArgument('command')
      ->addArgument('foo')
      ->setCode(function ($input, $output) { $output->writeln('foo'); })
    ;

    $this->tester = new ApplicationTester($this->application);
    $this->tester->run(array('command' => 'foo', 'foo' => 'bar'), array('interactive' => false, 'decorated' => false, 'verbosity' => Output::VERBOSITY_VERBOSE));
  }

  public function testRun()
  {
    $this->assertEquals(false, $this->tester->getInput()->isInteractive(), '->execute() takes an interactive option');
    $this->assertEquals(false, $this->tester->getOutput()->isDecorated(), '->execute() takes a decorated option');
    $this->assertEquals(Output::VERBOSITY_VERBOSE, $this->tester->getOutput()->getVerbosity(), '->execute() takes a verbosity option');
  }

  public function testGetInput()
  {
    $this->assertEquals('bar', $this->tester->getInput()->getArgument('foo'), '->getInput() returns the current input instance');
  }

  public function testGetOutput()
  {
    rewind($this->tester->getOutput()->getStream());
    $this->assertEquals("foo\n", stream_get_contents($this->tester->getOutput()->getStream()), '->getOutput() returns the current output instance');
  }

  public function testGetDisplay()
  {
    $this->assertEquals("foo\n", $this->tester->getDisplay(), '->getDisplay() returns the display of the last execution');
  }
}
