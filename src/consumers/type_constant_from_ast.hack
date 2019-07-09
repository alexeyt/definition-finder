/*
 *  Copyright (c) 2015-present, Facebook, Inc.
 *  All rights reserved.
 *
 *  This source code is licensed under the MIT license found in the
 *  LICENSE file in the root directory of this source tree.
 *
 */

namespace Facebook\DefinitionFinder;

use namespace Facebook\HHAST;
use namespace HH\Lib\C;

function type_constant_from_ast(
  ConsumerContext $context,
  HHAST\TypeConstDeclaration $node,
): ScannedTypeConstant {
  $is_abstract = C\any(
    $node->getModifiers()?->getChildren() ?? vec[],
    $t ==> $t instanceof HHAST\AbstractToken,
  );
  $name = $node->getName();
  return (
    new ScannedTypeConstant(
      $node,
      name_from_ast($node->getName() ?? HHAST\Missing()),
      context_with_node_position($context, $node)['definitionContext'],
      /* docblock = */ null,
      typehint_from_ast(
        $context,
        $is_abstract
          ? $node->getTypeConstraint()?->getType()
          : $node->getTypeSpecifier(),
      ),
      $is_abstract
        ? AbstractnessToken::IS_ABSTRACT
        : AbstractnessToken::NOT_ABSTRACT,
    )
  );
}
