<?php


namespace ChameleonSystem\UpgradeHelperBundle\DataModel;

use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Warning represents a potential illegal call to the container.
 */
final class Warning
{
    /**
     * @var string The call is implicit using a variable
     */
    public const TYPE_IMPLICIT = 'implicit';
    /**
     * @var string The call tries to fetch a non existing service
     */
    public const TYPE_NON_EXISTENT = 'non-existent';

    /**
     * @var CallToContainer
     */
    public $call;
    /**
     * @var string One of self::TYPE_*
     */
    public $type;

}