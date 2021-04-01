<?php


namespace ChameleonSystem\UpgradeHelperBundle\Parser;


use ChameleonSystem\UpgradeHelperBundle\DataModel\CallToContainer;
use PhpParser\Node;
use PhpParser\Node\Name;
use PhpParser\NodeVisitorAbstract;
use Psr\Container\ContainerInterface;

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
            $this->handleMethodCall($node);
        }

        if ($node instanceof Node\Expr\StaticCall) {
            $this->handleStaticCall($node);
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

    /**
     * handleMethodCall
     * @param Node\Expr\MethodCall $node
     */
    private function handleMethodCall(Node\Expr\MethodCall $node): void
    {
        if (!property_exists($node->name, 'name')) {
            return;
        }
        if ($node->name->name !== 'get') {
            return;
        }

        // calling on attribute of `this`
        if (!property_exists($node, 'var')) {
            return;
        }
        if (!property_exists($node->var, 'var')) {
            return;
        }
        if ($node->var->var->name === 'this') {
            $alias = $node->var->name->name;
            $resolvedClassName = $this->typeResolver->getAttributeType($alias);
            if ($resolvedClassName === null) {
                return;
            }
            $resolvedClassName = ltrim($resolvedClassName, '\\');
            if(!$this->nameIsResolvable($resolvedClassName)) {
                return;
            }
            if (!in_array(ContainerInterface::class, class_implements($resolvedClassName), true)) {
                return;
            }

            $this->calls[] = $this->createCall($node, CallToContainer::SOURCE_CONTAINER);
        }
    }

    /**
     * handleStaticCall
     * @param Node\Expr\StaticCall $node
     */
    private function handleStaticCall(Node\Expr\StaticCall $node): void
    {
        if (!property_exists($node->name, 'name')) {
            return;
        }
        if ($node->name->name !== 'get') {
            return;
        }
        if (!($node->class instanceof Name\FullyQualified)) {
            return;
        }
        if ($node->class && $node->class->toCodeString() === '\ChameleonSystem\CoreBundle\ServiceLocator') {
            $this->calls[] = $this->createCall($node, CallToContainer::SOURCE_SERVICE_LOCATOR);
        }
    }

    /**
     * nameIsResolvable
     * @param string $resolvedClassName
     * @return bool
     */
    private function nameIsResolvable(string $resolvedClassName): bool
    {
        return interface_exists($resolvedClassName) || class_exists($resolvedClassName) || trait_exists($resolvedClassName);
    }
}