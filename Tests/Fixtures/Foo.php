<?php


namespace ChameleonSystem\UpgradeHelperBundle\Tests\Fixtures;


use ChameleonSystem\CoreBundle\ServiceLocator;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerInterface;

final class Foo
{

    /**
     * @var Container
     */
    private $myContainer;
    private $variable;
    /**
     * @var ContainerInterface
     */
    private $iContainer;

    public function __construct(Container $container, ContainerInterface $containerInterface)
    {
        $this->myContainer = $container;
        $this->variable = 'no-direct-call';
        $this->iContainer = $containerInterface;
    }

    public function callingOnAttributeExplicit()
    {
        $this->myContainer->get('existing');
    }

    private function callingOnAttributeImplicit()
    {
        $this->iContainer->get($this->variable);
        $this->myContainer->get($this->variable);
    }

    private function callingOnServiceLocatorExplicit()
    {
        ServiceLocator::get('non-existing');
    }

    private function callingOnServiceLocatorImplicit()
    {
        ServiceLocator::get($this->variable);
    }


}