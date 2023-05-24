<?php

namespace ErickJMenezes\LaravelQueryAugment\Expressions;

use Illuminate\Database\Grammar;
use Illuminate\Database\Query\Expression;
use Illuminate\Database\Query\Grammars\MySqlGrammar;
use Illuminate\Database\Query\Grammars\SQLiteGrammar;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\BinaryOp;
use PhpParser\Node\Scalar;
use PhpParser\Node\Stmt;
use PhpParser\NodeAbstract;

/**
 * Class CaseExpression.
 *
 * @author ErickJMenezes <erickmenezes.dev@gmail.com>
 */
class CaseExpression extends Expression
{
    private Grammar $grammar;

    private const ALLOWED_NODES = [
        Stmt\If_::class,
        Stmt\ElseIf_::class,
        Stmt\Else_::class,
        Stmt\Return_::class,
        //
        Expr\PropertyFetch::class,
        Expr\Variable::class,
        //
        BinaryOp\Identical::class,
        BinaryOp\Equal::class,
        BinaryOp\NotEqual::class,
        BinaryOp\Greater::class,
        BinaryOp\GreaterOrEqual::class,
        BinaryOp\Smaller::class,
        BinaryOp\SmallerOrEqual::class,
        BinaryOp\BooleanOr::class,
        BinaryOp\BooleanAnd::class,
        //
        Scalar\LNumber::class,
        Scalar\String_::class,
    ];

    public function __construct(Stmt\If_ $cond, private readonly string $alias)
    {
        $this->validate($cond);
        parent::__construct($this->compileNode($cond));
    }

    public function getValue(Grammar $grammar)
    {
        return "{$this->value} as {$grammar->wrap($this->alias)}";
    }

    private function validate(NodeAbstract $node): void
    {
        assert(
            in_array($node::class, self::ALLOWED_NODES),
            "Invalid code found inside closure at line {$node->getLine()}:{$node->getStartTokenPos()}. Please, review the correct syntax.",
        );

        if ($node instanceof BinaryOp) {
            $this->validate($node->right);
            $this->validate($node->left);
        } elseif ($node instanceof Stmt\If_) {
            foreach ([$node->cond, ...$node->stmts, ...$node->elseifs, ...$node->else?->stmts ?? []] as $cond) {
                $this->validate($cond);
            }
            assert(
                count($node->stmts) === 1 && $node->stmts[0] instanceof Stmt\Return_,
                "Only a return statement is allowed inside the if statement at line {$node->getLine()}:{$node->getStartTokenPos()}"
            );
        } elseif ($node instanceof Stmt\ElseIf_) {
            foreach ([$node->cond, ...$node->stmts] as $cond) {
                $this->validate($cond);
            }
        } elseif ($node instanceof Stmt\Return_) {
            $this->validate($node->expr);
        } elseif ($node instanceof Expr\PropertyFetch) {
            $this->validate($node->var);
        }
    }

    private function getOperatorSigil(BinaryOp $expr): string
    {
        return match (true) {
            $expr instanceof BinaryOp\BooleanAnd => 'and',
            $expr instanceof BinaryOp\BooleanOr => 'or',
            $expr instanceof BinaryOp\Equal, $expr instanceof BinaryOp\Identical => '=',
            default => $expr->getOperatorSigil(),
        };
    }

    private function compileNode(NodeAbstract $node): string
    {
        switch ($node::class) {
            case Stmt\If_::class:
            {
                $compiled = ['(case', 'when'];

                // compile if conditions
                $compiled[] = $this->compileNode($node->cond);

                // compile if body
                $compiled[] = $this->compileNode($node->stmts[0]);

                // compile elseif statements
                foreach ($node->elseifs as $elseIf) {
                    $compiled[] = $this->compileNode($elseIf);
                }

                $compiled[] = $this->compileNode($node->else);

                $compiled[] = 'end)';
                return implode(' ', $compiled);
            }
            case Stmt\ElseIf_::class:
            {
                return "when {$this->compileNode($node->cond)} {$this->compileNode($node->stmts[0])}";
            }
            case Stmt\Else_::class:
            {
                /** @var \PhpParser\Node\Stmt\Return_ $return */
                $return = $node->stmts[0];
                return 'else ' . $this->compileNode($return->expr);
            }
            case Stmt\Return_::class:
            {
                return 'then ' . $this->compileNode($node->expr);
            }
            case Scalar\LNumber::class:
            {
                return $node->value;
            }
            case Scalar\String_::class:
            {
                return "'{$node->value}'";
            }
            case Expr\Array_::class:
            {
                return '('.implode(',', array_map(
                    fn(Expr\ArrayItem $item) => $this->compileNode($item->value),
                    $node->items,
                )).')';
            }
            case Expr\PropertyFetch::class:
            {
                return "{$node->var->name}.{$node->name->name}";
            }
            case is_a($node, BinaryOp::class):
            {
                return "({$this->compileNode($node->left)} {$this->getOperatorSigil($node)} {$this->compileNode($node->right)})";
            }
            default:
            {
                throw new \UnexpectedValueException("Unexpetec node {$node->getType()}");
            }
        }
    }
}
