<?hh // strict
/*
 *  Copyright (c) 2015-present, Facebook, Inc.
 *  All rights reserved.
 *
 *  This source code is licensed under the BSD-style license found in the
 *  LICENSE file in the root directory of this source tree. An additional grant
 *  of patent rights can be found in the PATENTS file in the same directory.
 *
 */

namespace Facebook\DefinitionFinder\Test;

use Facebook\DefinitionFinder\FileParser;

class NamingTest extends \PHPUnit_Framework_TestCase {
  public function testFunctionCalledSelect(): void {
    // 'select' is a T_SELECT, not a T_STRING
    $data = '<?hh function select() {}';

    // Check that it parses
    $parser = FileParser::FromData($data);
    $this->assertNotNull($parser->getFunction('select'));
  }

  /** Things that are valid names, but have a weird token type */
  public function specialNameProvider(): array<array<string>> {
    return [
      ['dict'], // HHVM >= 3.13
      ['vec'], // HHVM >= 3.14
      ['keyset'], // HHVM >= 3.15
      ['Category'],
      ['Super'],
      ['Attribute'],
      ['varray'], // HHVM >= 3.19
      ['darray'], // HHVM >= 3.19
    ];
  }

  /** @dataProvider specialNameProvider */
  public function testSpecialReturnType(string $type): void {
    $data = '<?hh function foo(): '.$type.' {}';
    $parser = FileParser::FromData($data);
    $func = $parser->getFunction('foo');
    $this->assertSame($type, $func->getReturnType()?->getTypeName());
  }

  /** @dataProvider specialNameProvider */
  public function testSpecialNameAsFuncName(string $type): void {
    $data = '<?hh function '.$type.'(): void {}';
    $parser = FileParser::FromData($data);
    $func = $parser->getFunction($type);
    $this->assertSame('void', $func->getReturnType()?->getTypeName());
    $this->assertSame($type, $func->getName());
  }

  /** @dataProvider specialNameProvider */
  public function testSpecialNameAsClassName(string $type): void {
    $data = '<?hh class '.$type.' { }';
    $parser = FileParser::FromData($data);
    $class = $parser->getClass($type);
    $this->assertNotNull($class);
  }

  /** @dataProvider specialNameProvider */
  public function testSpecialNameAsNamespaceName(string $type): void {
    $data = '<?hh namespace '.$type.' { class Foo {} }';
    $parser = FileParser::FromData($data);
    $class = $parser->getClass($type."\\Foo");
    $this->assertNotNull($class);
  }

  /** @dataProvider specialNameProvider */
  public function testSpecialNameAsUsedName(string $type): void {
    $data = '<?hh use Foo\\'.$type.'; class Herp extends '.$type.' { }';
    $parser = FileParser::FromData($data);
    $class = $parser->getClass('Herp');
    $this->assertNotNull($class);
  }

  /** @dataProvider specialNameProvider */
  public function testSpecialNameAsUsedAsName(string $type): void {
    $data = '<?hh use Foo\\Bar as '.$type.'; class Herp extends '.$type.' { }';
    $parser = FileParser::FromData($data);
    $class = $parser->getClass('Herp');
    $this->assertNotNull($class);
  }

  public function testConstantCalledOn(): void {
    $data = '<?hh class Foo { const ON = 0; }';

    $this->assertEquals(
      vec['ON'],
      FileParser::FromData($data)
        ->getClass('Foo')
        ->getConstants()
        ->map($x ==> $x->getName()),
    );
  }

  public function testClassMagicConstant(): void {
    $data = "<?hh Foo::class;\nclass Foo{}";

    // This could throw because the ; comes after the keyword class
    $this->assertEquals(
      'Foo',
      FileParser::FromData($data)->getClass('Foo')->getName(),
    );
  }

  public function testClassConstant(): void {
    $data = "<?hh Herp::DERP;\nclass Foo{}";

    $this->assertEquals(
      'Foo',
      FileParser::FromData($data)->getClass('Foo')->getName(),
    );
  }

  /** The noramlization blacklist shouldn't apply to things we define */
  public function testNamespacedClassCalledCollection(): void {
    $data = '<?php namespace Foo\Bar; class Collection {}';

    $this->assertEquals(
      vec['Foo\Bar\Collection'],
      FileParser::FromData($data)->getClassNames(),
    );
  }

  public function testNamespaceResolutionDependingOnSourceType(): void {
    $php = "<?php namespace Foo; class MyClass extends Collection {}";
    $hack = "<?hh namespace Foo; class MyClass extends Collection {}";

    $php_class = FileParser::FromData($php)->getClass("Foo\\MyClass");
    $hack_class = FileParser::FromData($hack)->getClass("Foo\\MyClass");

    $this->assertSame("Foo\\Collection", $php_class->getParentClassName());
    $this->assertSame('Collection', $hack_class->getParentClassName());
  }

  public function testScalarParameterInNamespace(): void {
    // This is correct for PHP7, not for PHP5 though. If you're using Hack,
    // you're more likely to be using scalar typehints than not.
    $php = '<?php namespace Foo; function myfunc(): string {}';
    $hack = '<?hh namespace Foo; function myfunc(): string {}';

    $php_func = FileParser::FromData($php)->getFunction("Foo\\myfunc");
    $hack_func = FileParser::FromData($hack)->getFunction("Foo\\myfunc");

    $this->assertEquals('string', $php_func->getReturnType()?->getTypeName());
    $this->assertEquals('string', $hack_func->getReturnType()?->getTypeName());
  }

  public function testReturnsThisInNamespace(): void {
    $code = "<?hh\n".
      "namespace Foo;\n".
      "class MyClass {\n".
      "  function foo(): this { }\n".
      "}";
    $parser = FileParser::FromData($code);
    $class = $parser->getClass("Foo\\MyClass");
    $method = $class->getMethods()->at(0);
    $this->assertSame('this', $method->getReturnType()?->getTypeName());
  }

  public function testReturnsClassGenericInNamespace(): void {
    $code = "<?hh\n".
      "namespace Foo;\n".
      "class MyClass<T> {\n".
      "  function foo(): T { }\n".
      "}";
    $parser = FileParser::FromData($code);
    $class = $parser->getClass("Foo\\MyClass");
    $method = $class->getMethods()->at(0);
    $this->assertSame('T', $method->getReturnType()?->getTypeName());
  }

  public function testReturnsNullableClassGenericInNamespace(): void {
    $code = "<?hh\n".
      "namespace Foo;\n".
      "class MyClass<T> {\n".
      "  function foo(): ?T { }\n".
      "}";
    $parser = FileParser::FromData($code);
    $class = $parser->getClass("Foo\\MyClass");
    $method = $class->getMethods()->at(0);
    $this->assertSame('T', $method->getReturnType()?->getTypeName());
    $this->assertTrue($method->getReturnType()?->isNullable());
  }

  public function testReturnsMethodGenericInNamespace(): void {
    $code = "<?hh\n".
      "namespace Foo;\n".
      "class MyClass {\n".
      "  function foo<T>(): T { }\n".
      "}";
    $parser = FileParser::FromData($code);
    $class = $parser->getClass("Foo\\MyClass");
    $method = $class->getMethods()->at(0);
    $this->assertSame('T', $method->getReturnType()?->getTypeName());
  }

  /**
   * Make sure that method generics are added to class generics, instead of
   * replacing them.
   */
  public function testClassGenericsInMethodWithGenerics(): void {
    $code = "<?hh\n".
      "namespace Foo;\n".
      "class MyClass<TClassGeneric> {\n".
      "  function foo<TFunctionGeneric>(\n".
      "    TFunctionGeneric \$p,\n".
      "  ): TClassGeneric {}";
    $parser = FileParser::FromData($code);
    $class = $parser->getClass("Foo\\MyClass");
    $method = $class->getMethods()->at(0);
    $this->assertSame(
      'TClassGeneric',
      $method->getReturnType()?->getTypeName(),
    );
    $this->assertSame(
      'TFunctionGeneric',
      $method->getParameters()->at(0)->getTypehint()?->getTypeName(),
    );
  }

  public function testTakesMethodGenericInNamespace(): void {
    $code = "<?hh\n".
      "namespace Foo;\n".
      "class MyClass {\n".
      "  function foo<T>(T \$bar): void { }\n".
      "}";
    $parser = FileParser::FromData($code);
    $class = $parser->getClass("Foo\\MyClass");
    $method = $class->getMethods()->at(0);
    $this->assertSame(
      'T',
      $method->getParameters()->at(0)->getTypehint()?->getTypeName(),
    );
  }
}
