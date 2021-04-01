<?php


namespace ChameleonSystem\UpgradeHelperBundle\Parser;


use ChameleonSystem\UpgradeHelperBundle\DataModel\CallToContainer;
use PhpParser\Error;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\ParserFactory;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;

/**
 * Parser will parse the given source code and emit lists of `CallToContainer` for further inspection.
 * See `Validator`
 */
final class Parser
{

    /**
     * @var \PhpParser\Parser
     */
    private $parser;

    /**
     * @var string[]
     */
    private $errors;

    public function __construct()
    {
        // see https://github.com/nikic/PHP-Parser/blob/master/doc/2_Usage_of_basic_components.markdown
        ini_set('xdebug.max_nesting_level', '3000');
        $this->parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP7);
        $this->errors = [];
    }

    /**
     * parseDirectory will parse every .php file contained in $rootDirectory, including sub directories
     * and emit all calls to the symfony container `get` method.
     *
     * @param string $rootDirectoy
     * @param OutputInterface|null $output
     * @param string[] $filterDirectories
     * @return CallToContainer[]
     */
    public function parseDirectory(string $rootDirectoy, OutputInterface $output = null, $filterDirectories = ['chameleon-system/upgrade-helper']): array
    {
        $calls = [];
        $finder = new Finder();

        $finder
            ->in($rootDirectoy)
            ->filter(static function(\SplFileInfo $fileInfo) use ($filterDirectories) {
                foreach ($filterDirectories as $dir) {
                    if (str_contains($fileInfo->getPathname(), $dir)) {
                        return false;
                    }
                }
                return true;
            })
            ->files()
            ->name('*.php');

        $progressBar = null;
        if ($output !== null) {
            $progressBar = new ProgressBar($output, $finder->count());
        }

        foreach ($finder as $file) {
            $parsedCalls = $this->parse($file->getContents(), $file->getPathname());
            $newCalls = array_merge($calls, $parsedCalls);
            $calls = $newCalls;
            if ($progressBar !== null) {
                $progressBar->advance(1);
            }
        }

        if ($progressBar !== null) {
            $progressBar->finish();
        }

        $this->logErrors($output);

        return $calls;
    }

    /**
     * parse will parse the given $contents as php code and emit all calls to the symfony container `get` method.
     *
     * @param string $contents
     * @param string $fileName
     * @return CallToContainer[]
     */
    public function parse(string $contents, string $fileName): array
    {
        try {
            $ast = $this->parser->parse($contents);
        } catch (Error $e) {
            $this->errors[] = sprintf('Could not parse file %s: %s', $fileName, $e->getMessage());
            return [];
        }

        $traverser = new NodeTraverser();
        $nameResolver = new NameResolver();
        $typeResolver = new TypeResolveVisitor();

        $traverser->addVisitor($nameResolver);
        $traverser->addVisitor($typeResolver);
        $containerCallVisitor = new ContainerCallVisitor($typeResolver, $fileName);
        $traverser->addVisitor($containerCallVisitor);

        $traverser->traverse($ast);

        return $containerCallVisitor->getCalls();
    }

    /**
     * logErrors
     *
     * @param OutputInterface|null $output
     */
    private function logErrors(OutputInterface $output = null)
    {
        if ($output === null) {
            return;
        }

        $output->writeln("");
        foreach ($this->errors as $error) {
            $output->writeln(sprintf('<warn>%s</warn>', $error));
        }
    }
}