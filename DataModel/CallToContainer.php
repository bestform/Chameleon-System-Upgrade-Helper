<?php


namespace ChameleonSystem\UpgradeHelperBundle\DataModel;

/**
 * CallToContainer represents a call to the `get` method on the symfony container
 */
final class CallToContainer
{
    /** @var string Explicit call using a string as argument. eg. $container->get('foo') */
    public const TYPE_EXPLICIT = 'explicit';
    /** @var string Call using a variable. eg. $container->get($foo) */
    public const TYPE_VARIABLE = 'variable';

    /** @var string The callee is the symfony container */
    public const SOURCE_CONTAINER = 'container';
    /** @var string The callee is the service locator */
    public const SOURCE_SERVICE_LOCATOR = 'service locator';

    /** @var string One of self::TYPE_* */
    public $type;
    /** @var string One of self::SOURCE_* */
    public $source;
    /** @var string The filename in which the call happens */
    public $file;
    /** @var string The service name. If it is TYPE_VARIABLE this field is empty */
    public $service;
    /** @var int The line of code */
    public $line;
}