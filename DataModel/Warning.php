<?php


namespace ChameleonSystem\UpgradeHelperBundle\DataModel;

use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Warning represents a potential illegal call to the container.
 */
final class Warning
{
    /** @var string The call is implicit using a variable */
    public const TYPE_IMPLICIT = 'implicit';
    /** @var string The call tries to fetch a non existing service */
    public const TYPE_NON_EXISTENT = 'non-existent';

    /**
     * @var CallToContainer
     */
    public $call;
    /**
     * @var string One of self::TYPE_*
     */
    public $type;

    /**
     * format can be used to ouput the warning to the console
     *
     * @param OutputInterface $output
     */
    public function format(OutputInterface $output)
    {
        $outputStyle = new OutputFormatterStyle('magenta', 'default', ['bold']);
        $output->getFormatter()->setStyle('warn', $outputStyle);
        $outputStyle = new OutputFormatterStyle('red', 'default', ['bold']);
        $output->getFormatter()->setStyle('error', $outputStyle);
        $outputStyle = new OutputFormatterStyle('cyan', 'default');
        $output->getFormatter()->setStyle('info', $outputStyle);


        switch ($this->type) {
            case (self::TYPE_IMPLICIT):
                $output->writeln('<warn>Warning: Implicit fetch from container</warn>');
                $output->writeln('<info>The actual service could not be detected by the parser because it is not explicitly stated. Make sure that all services that could be loaded here are public.</info>');

                break;
            case (self::TYPE_NON_EXISTENT):
                $output->writeln('<error>Error: Fetch of a non existing service from container</error>');
                $output->writeln('<info>This service does not exist in the current container. If the service is defined, add `public="true"` as an attribute. If it is a synthetic service you might ignore this warning.</info>');
                $output->writeln('Service: ' . $this->call->service);
                break;
        }
        $output->writeln('File: ' . $this->call->file);
        $output->writeln('Line: ' . $this->call->line);
    }

}