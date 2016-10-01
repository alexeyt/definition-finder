<?hh // strict
/*
 *  Copyright (c) 2015, Facebook, Inc.
 *  All rights reserved.
 *
 *  This source code is licensed under the BSD-style license found in the
 *  LICENSE file in the root directory of this source tree. An additional grant
 *  of patent rights can be found in the PATENTS file in the same directory.
 *
 */


namespace Facebook\DefinitionFinder;

final class NamespaceConsumer extends Consumer {
  public function getBuilder(): ScannedNamespaceBuilder {
    $parts = [];
    do {
      $this->consumeWhitespace();
      list($next, $next_type) = $this->tq->shift();
      if ($next_type === T_STRING) {
        $parts[] = $next;
        continue;
      } else if ($next_type === T_NS_SEPARATOR) {
        continue;
      } else if ($next === '{' || $next === ';') {
        break;
      }
      invariant_violation(
        'Unexpected token %s',
        var_export($next, true),
      );
    } while ($this->tq->haveTokens());

    // empty $parts is valid inside HHVM's systemlib: namespace { } is used
    // in files that also contain HH\ or __SystemLib\

    $ns = implode("\\", $parts);
    $context = $this->context;
    $context['namespace'] = $ns;

    $builder = (new ScannedNamespaceBuilder($ns))
      ->setContents(
        (new ScopeConsumer(
          $this->tq,
          $context,
          ScopeType::NAMESPACE_SCOPE,
        ))->getBuilder()
    );
    return $builder;
  }
}
