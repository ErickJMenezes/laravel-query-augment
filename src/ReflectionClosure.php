<?php

namespace ErickJMenezes\LaravelQueryAugment;

use Illuminate\Support\Collection;
use Laravel\SerializableClosure\Support\ReflectionClosure as BaseReflectionClosure;
use PhpParser\Node\Expr\Closure;
use PhpParser\ParserFactory;

class ReflectionClosure extends BaseReflectionClosure
{
    /**
     * Extract the AST from the closure code.
     *
     * @return \PhpParser\Node\Expr\Closure
     * @author ErickJMenezes <erickmenezes.dev@gmail.com>
     */
    public function ast(): Closure
    {
        return (new ParserFactory())
            ->create(ParserFactory::PREFER_PHP7)
            ->parse('<?php ' . $this->getCode() . ';')[0]
            ->expr;
    }
}
