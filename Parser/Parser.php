<?php


namespace ChameleonSystem\UpgradeHelperBundle\Parser;


use ChameleonSystem\UpgradeHelperBundle\DataModel\CallToContainer;
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
     * parseDirectory will parse every .php file contained in $rootDirectory, including sub directories
     * and emit all calls to the symfony container `get` method.
     *
     * @param string $rootDirectoy
     * @param OutputInterface|null $output
     * @return array
     */
    public function parseDirectory(string $rootDirectoy, OutputInterface $output = null): array
    {
        $calls = [];
        $finder = new Finder();

        $finder
            ->in($rootDirectoy)
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

        return $calls;
    }

    /**
     * parse will parse the given $contents as php code and emit all calls to the symfony container `get` method.
     *
     * @param string $contents
     * @return CallToContainer[]
     */
    public function parse(string $contents, string $fileName): array
    {
        $phpParser = (new ParserFactory)->create(ParserFactory::PREFER_PHP7);
        $ast = $phpParser->parse($contents);

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

}