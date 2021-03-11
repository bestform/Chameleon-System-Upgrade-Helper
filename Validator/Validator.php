<?php


namespace ChameleonSystem\UpgradeHelperBundle\Validator;


use ChameleonSystem\UpgradeHelperBundle\DataModel\CallToContainer;
use ChameleonSystem\UpgradeHelperBundle\DataModel\Warning;
use Psr\Container\ContainerInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Validator can be used to validate a list of `CallToContainer` instances and produce a list of `Warning`s for
 * potential illegal calls.
 *
 * For every `CallToContainer` it checks if it is either an implicit call or if the service does not exist.
 * In both cases it produces an appropriate warning.
 *
 * The constructor takes an optional allow list to allow known synthetic services which otherwise would produce
 * false negatives.
 */
final class Validator
{

    /**
     * @var ContainerBuilder
     */
    private $container;
    /**
     * @var array
     */
    private $allowList;

    public function __construct(ContainerInterface $container, array $allowList = [])
    {
        $this->container = $container;
        $this->allowList = $allowList;
    }

    /**
     * validate
     * @param CallToContainer[] $calls
     *
     * @return Warning[]
     */
    public function validate(array $calls): array
    {
        $warnings = [];

        foreach($calls as $call) {
            switch($call->type) {
                case(CallToContainer::TYPE_VARIABLE):
                    $warning = new Warning();
                    $warning->type = Warning::TYPE_IMPLICIT;
                    $warning->call = $call;
                    $warnings[] = $warning;
                    break;
                case(CallToContainer::TYPE_EXPLICIT):
                    if (in_array($call->service, $this->allowList)) {
                        break;
                    }
                    if (!$this->container->has($call->service)) {
                        $warning = new Warning();
                        $warning->type = Warning::TYPE_NON_EXISTENT;
                        $warning->call = $call;
                        $warnings[] = $warning;
                    }
                    break;
            }
        }

        return $warnings;
    }

}