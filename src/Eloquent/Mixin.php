<?php

namespace ErickJMenezes\LaravelQueryAugment\Eloquent;

use Closure;
use ErickJMenezes\LaravelQueryAugment\BuildCaseExpression;
use ErickJMenezes\LaravelQueryAugment\ReflectionClosure;
use Illuminate\Database\Eloquent\Builder;

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
            $expressionBuilder = new BuildCaseExpression($as);
            return $this->selectRaw($expressionBuilder->build($reflectionClosure->ast()->stmts));
        };
    }
}
