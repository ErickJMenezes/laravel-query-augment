<?php

namespace ErickJMenezes\LaravelQueryAugment\Eloquent;

use Closure;
use ErickJMenezes\LaravelQueryAugment\BuildCaseExpression;
use ErickJMenezes\LaravelQueryAugment\Expressions\CaseExpression;
use ErickJMenezes\LaravelQueryAugment\ReflectionClosure;
use Illuminate\Database\Eloquent\Builder;
use PhpParser\Node\Stmt\If_;

/**
 * Class Mixin.
 *
 * @author ErickJMenezes <erickmenezes.dev@gmail.com>
 * @mixin Builder
 */
class Mixin
{
    public function addSelectCase(): Closure
    {
        return function (Closure $conditionBuilder, string $as) {
            $reflectionClosure = new ReflectionClosure($conditionBuilder);
            $stmts = $reflectionClosure->ast()->stmts;
            if (count($stmts) === 0 || !($stmts[0] instanceof If_)) {
                throw new \InvalidArgumentException('The method Builder::addSelectCase() expects a closure with at least one if statement inside.');
            }
            $column = new CaseExpression($stmts[0], $as);
            $this->addBinding($column->bindings, 'select');
            return $this->addSelect($column);
        };
    }
}
