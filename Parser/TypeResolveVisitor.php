<?php


namespace ChameleonSystem\UpgradeHelperBundle\Parser;


use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

/**
 * TypeResolveVisitor keeps track of all parameter types and corresponding assignments to class attributes.
 */
final class TypeResolveVisitor extends NodeVisitorAbstract
{
    private $typeMap = [];
    private $attributesMap = [];

    public function enterNode(Node $node)
    {
        if ($node instanceof Node\Param && property_exists($node, 'type') && $node->type !== null) {
            if ($node->type instanceof Node\Name\FullyQualified) {
                $this->typeMap[$node->var->name] = $node->type->toCodeString();
            }
            if ($node->type instanceof Node\Identifier) {
                $this->typeMap[$node->var->name] = $node->type->name;
            }

        }

        if ($node instanceof Node\Expr\Assign) {
            if ($this->nodeAssignsValueToClassAttribute($node)) {
                if ($this->nodeAssignsToKnownTypedAttribute($node)) {
                    $this->attributesMap[$node->var->name->name] = $this->typeMap[$node->expr->name];
                }
            }
        }
    }

    public function getAttributeType(string $name): ?string
    {
        return $this->attributesMap[$name] ?? null;
    }

    /**
     * nodeAssignsValueToClassAttribute
     *
     * @param Node\Expr\Assign $node
     * @return bool
     */
    private function nodeAssignsValueToClassAttribute(Node\Expr\Assign $node): bool
    {
        return
            property_exists($node, 'var') &&
            property_exists($node->var, 'var') &&
            property_exists($node->var->var, 'name') &&
            $node->var->var->name === 'this';
    }

    /**
     * nodeAssignsToKnownTypedAttribute
     *
     * @param Node\Expr\Assign $node
     * @return bool
     */
    private function nodeAssignsToKnownTypedAttribute(Node\Expr\Assign $node): bool
    {
        return
            property_exists($node->expr, 'name') &&
            is_string($node->expr->name) &&
            array_key_exists($node->expr->name, $this->typeMap);
    }
}