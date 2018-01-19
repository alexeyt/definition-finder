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

namespace Facebook\DefinitionFinder;

class ScannedScope extends ScannedBase {

  public function __construct(
    self::TContext $context,
    private vec<ScannedBasicClass> $classes,
    private vec<ScannedInterface> $interfaces,
    private vec<ScannedTrait> $traits,
    private vec<ScannedFunction> $functions,
    private vec<ScannedMethod> $methods,
    private vec<ScannedTypehint> $usedTraits,
    private vec<ScannedProperty> $properties,
    private vec<ScannedConstant> $constants,
    private vec<ScannedTypeConstant> $typeConstants,
    private vec<ScannedEnum> $enums,
    private vec<ScannedType> $types,
    private vec<ScannedNewtype> $newtypes,
  ) {
    parent::__construct(
      '__SCOPE__',
      $context,
      /* attributes = */ dict[],
      /* docblock = */ null,
    );
  }

  public static function getType(): ?DefinitionType {
    return null;
  }

  public function getClasses(): vec<ScannedBasicClass> {
    return $this->classes;
  }

  public function getInterfaces(): vec<ScannedInterface> {
    return $this->interfaces;
  }

  public function getTraits(): vec<ScannedTrait> {
    return $this->traits;
  }

  public function getUsedTraits(): vec<ScannedTypehint> {
    return $this->usedTraits;
  }

  public function getFunctions(): vec<ScannedFunction> {
    return $this->functions;
  }

  public function getMethods(): vec<ScannedMethod> {
    return $this->methods;
  }

  public function getProperties(): vec<ScannedProperty> {
    return $this->properties;
  }

  public function getConstants(): vec<ScannedConstant> {
    return $this->constants;
  }

  public function getTypeConstants(): vec<ScannedTypeConstant> {
    return $this->typeConstants;
  }

  public function getEnums(): vec<ScannedEnum> {
    return $this->enums;
  }

  public function getTypes(): vec<ScannedType> {
    return $this->types;
  }

  public function getNewtypes(): vec<ScannedNewtype> {
    return $this->newtypes;
  }
}
