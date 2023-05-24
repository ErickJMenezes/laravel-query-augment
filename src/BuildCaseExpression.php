<?php

namespace ErickJMenezes\LaravelQueryAugment;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\BinaryOp;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Scalar\LNumber;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt;

class BuildCaseExpression
{
    public function __construct(
        private readonly string $alias,
    ) {}

    public function build(array $stmts): string
    {
        $build = ['(case'];
        foreach ($stmts as $node) {
            $isValid = $node instanceof Stmt\If_
                || $node instanceof Stmt\ElseIf_
                || $node instanceof Stmt\Else_;
            assert($isValid, "Only if, elseif, or else is allowed in the function body.");

            if ($node instanceof Stmt\If_ || $node instanceof Stmt\ElseIf_) {
                $build[] = 'when';
                $this->parseCondition($node->cond, $build);
                $build[] = 'then';
                $this->parseThen($node->stmts, $build);
            } else {
                $build[] = 'else';
            }
        }
        $build[] = 'end)';
        $build[] = 'as';
        $build[] = "'{$this->alias}'";
        return implode(" ", $build);
    }


    private function parseCondition(Expr $expr, array &$build): void
    {
        assert(
            $this->isAllowedBinaryOp($expr)
            || $expr instanceof PropertyFetch
            || $expr instanceof LNumber
            || $expr instanceof String_,
            "Operation {$expr->getType()} is not permitted.",
        );

        if ($this->isAllowedBinaryOp($expr)) {
            /** @var BinaryOp $expr */
            $operator = match (true) {
                $expr instanceof BinaryOp\BooleanAnd => 'and',
                $expr instanceof BinaryOp\BooleanOr => 'or',
                $expr instanceof BinaryOp\Equal, $expr instanceof BinaryOp\Identical => '=',
                default => $expr->getOperatorSigil(),
            };

            $sides = [];
            foreach ([$expr->left, $expr->right] as $side) {
                $sides[] = $this->getValue($side);
            }
            $build[] = $sides[0];
            $build[] = $operator;
            $build[] = $sides[1];
        } elseif ($expr instanceof PropertyFetch) {
            $build[] = implode('.', [
                $expr->var->name,
                $expr->name->name,
            ]);
        }
    }

    private function getValue(Expr $side): mixed
    {
        if ($side instanceof PropertyFetch) {
            return implode('.', [
                $side->var->name,
                $side->name->name,
            ]);
        } elseif ($side instanceof LNumber) {
            return $side->value;
        } elseif ($side instanceof String_) {
            return "'{$side->value}'";
        } elseif ($this->isAllowedBinaryOp($side)) {
            $sideBuild = [];
            $this->parseCondition($side, $sideBuild);
            return '('.implode(' ', $sideBuild).')';
        } elseif ($side instanceof Expr\Array_) {
            return '('.implode(
                ',',
                array_map(fn(Expr\ArrayItem $item) => $this->getValue($item->value), $side->items)
                ).')';
        } else {
            throw new \AssertionError("Unknown expression {$side->getType()}");
        }
    }

    private function isAllowedBinaryOp(Expr $expr): bool
    {
        return $expr instanceof BinaryOp\BooleanAnd
            || $expr instanceof BinaryOp\BooleanOr
            || $expr instanceof BinaryOp\Greater
            || $expr instanceof BinaryOp\Smaller
            || $expr instanceof BinaryOp\GreaterOrEqual
            || $expr instanceof BinaryOp\SmallerOrEqual
            || $expr instanceof BinaryOp\Equal
            || $expr instanceof BinaryOp\Identical;
    }

    /**
     * @param array<Stmt>   $stmts
     * @param array<string> $build
     *
     * @return void
     * @author ErickJMenezes <erickmenezes.dev@gmail.com>
     */
    private function parseThen(array $stmts, array &$build): void
    {
        assert(
            count($stmts) > 0 && $stmts[0] instanceof Stmt\Return_,
            "A single return statement is required inside each if/elseif/else clause"
        );
        $expr = $stmts[0]->expr;
        assert(
            $expr instanceof PropertyFetch
            || $expr instanceof LNumber
            || $expr instanceof String_,
            "Every condition must return a table column or a scalar.",
        );
        $build[] = $this->getValue($expr);
    }
}
