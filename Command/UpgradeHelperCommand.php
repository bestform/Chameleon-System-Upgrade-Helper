<?php


namespace ChameleonSystem\UpgradeHelperBundle\Command;


use ChameleonSystem\UpgradeHelperBundle\DataModel\Warning;
use ChameleonSystem\UpgradeHelperBundle\Parser\Parser;
use ChameleonSystem\UpgradeHelperBundle\Validator\Validator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class UpgradeHelperCommand extends Command
{
    protected static $defaultName = 'chameleon_system:upgrade_helper';
    /**
     * @var Parser
     */
    private $parser;
    /**
     * @var Validator
     */
    private $validator;

    public function __construct(Parser $parser, Validator $validator)
    {
        parent::__construct(null);
        $this->parser = $parser;
        $this->validator = $validator;
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Finds all places, where a private service might have to be declared public instead')
            ->addArgument(
                'root',
                InputArgument::REQUIRED,
                "The absolute path to the root directory of the sources to parse. If you are running the command inside a container, make sure to provide the absolute path inside the container.")
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $calls = $this->parser->parseDirectory($input->getArgument('root'), $output);
        $warnings = $this->validator->validate($calls);

        $output->writeln("");

        foreach($warnings as $warning) {
            $this->writeWarning($warning, $output);
            $output->writeln("");
        }

        return 0;
    }

    /**
     * writeWarning can be used to ouput the warning to the console
     *
     * @param OutputInterface $output
     */
    private function writeWarning(Warning $warning, OutputInterface $output): void
    {
        $outputStyle = new OutputFormatterStyle('magenta', 'default', ['bold']);
        $output->getFormatter()->setStyle('warn', $outputStyle);
        $outputStyle = new OutputFormatterStyle('red', 'default', ['bold']);
        $output->getFormatter()->setStyle('error', $outputStyle);
        $outputStyle = new OutputFormatterStyle('cyan', 'default');
        $output->getFormatter()->setStyle('info', $outputStyle);

        switch ($warning->type) {
            case (Warning::TYPE_IMPLICIT):
                $output->writeln('<warn>Warning: Implicit fetch from container</warn>');
                $output->writeln('<info>The actual service could not be detected by the parser because it is not explicitly stated. Make sure that all services that could be loaded here are public.</info>');

                break;
            case (Warning::TYPE_NON_EXISTENT):
                $output->writeln('<error>Error: Fetch of a non existing service from container</error>');
                $output->writeln('<info>This service does not exist in the current container. If the service is defined, add `public="true"` as an attribute. If it is a synthetic service you might ignore this warning.</info>');
                $output->writeln('Service: ' . $warning->call->service);
                break;
        }
        $output->writeln('File: ' . $warning->call->file);
        $output->writeln('Line: ' . $warning->call->line);
    }
}