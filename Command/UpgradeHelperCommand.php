<?php


namespace ChameleonSystem\UpgradeHelperBundle\Command;


use ChameleonSystem\UpgradeHelperBundle\Parser\Parser;
use ChameleonSystem\UpgradeHelperBundle\Validator\Validator;
use Symfony\Component\Console\Command\Command;
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

    protected function configure()
    {
        $this
            ->setDescription('Finds all places, where a private service might have to be declared public instead')
            ->addArgument(
                'root',
                InputArgument::REQUIRED,
                "The absolute path to the root directory of the sources to parse. If you are running the command inside a container, make sure to provide the absolute path inside the container.")
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $calls = $this->parser->parseDirectory($input->getArgument('root'), $output);
        $warnings = $this->validator->validate($calls);

        $output->writeln("");

        foreach($warnings as $warning) {
            $warning->format($output);
            $output->writeln("");
        }

        return 0;
    }
}