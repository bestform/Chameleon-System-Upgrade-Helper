<?php


namespace ChameleonSystem\UpgradeHelperBundle\Tests\Mock;


use Symfony\Component\DependencyInjection\ContainerInterface;

final class Container implements ContainerInterface
{

    private $services = [];

    public function set($id, $service)
    {
        $this->services[] = $id;
    }

    public function get($id, $invalidBehavior = self::EXCEPTION_ON_INVALID_REFERENCE)
    {
        // not used
    }

    public function has($id)
    {
        return in_array($id, $this->services, true);
    }

    public function initialized($id)
    {
        // not used
    }

    public function getParameter($name)
    {
        // not used
    }

    public function hasParameter($name)
    {
        // not used
    }

    public function setParameter($name, $value)
    {
        // not used
    }
}