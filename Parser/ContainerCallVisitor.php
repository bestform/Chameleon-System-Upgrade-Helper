<?php


namespace ChameleonSystem\UpgradeHelperBundle\Parser;


use ChameleonSystem\UpgradeHelperBundle\DataModel\CallToContainer;
use PhpParser\Node;
use PhpParser\Node\Name;
use PhpParser\NodeVisitorAbstract;

/**
 * ContainerCallVisitor will visit every call to a container/service locator `get` method and produce
 * a `CallToContainer` instance for every occurrence.
 */
final class ContainerCallVisitor extends NodeVisitorAbstract
{
    /**
     * @var CallToContainer[]
     */
    private $calls;
    /**
     * @var string
     */
    private $fileName;
    /**
     * @var TypeResolveVisitor
     */
    private $typeResolver;

    public function __construct(TypeResolveVisitor $typeResolver, string $fileName)
    {
        $this->calls = [];
        $this->fileName = $fileName;
        $this->typeResolver = $typeResolver;
    }


    public function leaveNode(Node $node)
    {
        if ($node instanceof Node\Expr\MethodCall) {
            if (!property_exists($node->name, 'name')) {
                return null;
            }
            if ($node->name->name !== 'get') {
                return null;
            }

            // calling on attribute of `this`
            if (!property_exists($node, 'var')) {
                return null;
            }
            if (!property_exists($node->var, 'var')) {
                return null;
            }
            if ($node->var->var->name === 'this') {
                $alias = $node->var->name->name;
                $resolvedClassName = $this->typeResolver->getAttributeType($alias);
                if ($resolvedClassName !== '\Symfony\Component\DependencyInjection\Container' && $resolvedClassName !== '\Symfony\Component\DependencyInjection\ContainerInterface') {
                    return null;
                }

                $this->calls[] = $this->createCall($node, CallToContainer::SOURCE_CONTAINER);
            }

            return null;
        }

        if ($node instanceof Node\Expr\StaticCall) {
            if (!property_exists($node->name, 'name')) {
                return null;
            }
            if ($node->name->name !== 'get') {
                return null;
            }
            if (!($node->class instanceof Name\FullyQualified)) {
                return null;
            }
            if ($node->class && $node->class->toCodeString() === '\ChameleonSystem\CoreBundle\ServiceLocator') {
                $this->calls[] = $this->createCall($node, CallToContainer::SOURCE_SERVICE_LOCATOR);
            }
        }
    }

    /**
     * createCall produces a `CallToContainer` instance with the available information from $node
     *
     * @param Node $node
     * @param string $source
     * @return CallToContainer
     */
    private function createCall(Node $node, string $source): CallToContainer
    {
        $call = new CallToContainer();
        // if the value is set explicitly we call with the name directly, otherwise we use a variable
        $argument = $node->args[0]->value->value ?? '';
        if ($argument === '') {
            $call->type = CallToContainer::TYPE_VARIABLE;
        } else {
            $call->type = CallToContainer::TYPE_EXPLICIT;
        }
        $call->source = $source;
        $call->file = $this->fileName;
        $call->service = $argument;
        $call->line = $node->getLine();

        return $call;
    }

    /**
     * @return CallToContainer[]
     */
    public function getCalls(): array
    {
        return $this->calls;
    }
}