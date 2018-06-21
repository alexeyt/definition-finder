<?hh // strict
/*
 *  Copyright (c) 2015-present, Facebook, Inc.
 *  All rights reserved.
 *
 *  This source code is licensed under the MIT license found in the
 *  LICENSE file in the root directory of this source tree.
 *
 */

use type Facebook\DefinitionFinder\FileParser;

abstract class AbstractPHPTest extends PHPUnit_Framework_TestCase {
  private ?FileParser $parser;

  abstract protected function getFilename(): string;
  abstract protected function getPrefix(): string;

  <<__Override>>
  protected function setUp(): void {
    $this->parser = FileParser::fromFile(
      __DIR__.'/data/'.$this->getFilename(),
    );
  }

  public function testClasses(): void {
    $this->assertEquals(
      vec[
        $this->getPrefix().'SimpleClass',
        $this->getPrefix().'SimpleAbstractClass',
        $this->getPrefix().'SimpleFinalClass',
      ],
      $this->parser?->getClassNames(),
    );
  }

  public function testInterfaces(): void {
    $this->assertEquals(
      vec[$this->getPrefix().'SimpleInterface'],
      $this->parser?->getInterfaceNames(),
    );
  }

  public function testTraits(): void {
    $this->assertEquals(
      vec[$this->getPrefix().'SimpleTrait'],
      $this->parser?->getTraitNames(),
    );
  }
}
