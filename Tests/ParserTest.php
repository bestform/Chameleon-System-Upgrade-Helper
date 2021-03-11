<?php


namespace ChameleonSystem\UpgradeHelperBundle\Tests;


use ChameleonSystem\UpgradeHelperBundle\DataModel\CallToContainer;
use ChameleonSystem\UpgradeHelperBundle\Parser\Parser;
use PHPUnit\Framework\TestCase;

final class ParserTest extends TestCase
{

    /**
     * @test
     */
    public function parserTest()
    {
        $parser = new Parser();
        $fileName = __DIR__ . '/Fixtures/Foo.php';
        $contents = file_get_contents($fileName);

        $calls = $parser->parse($contents, $fileName);

        $cases = [
            [
                'type' => CallToContainer::TYPE_EXPLICIT,
                'source' => CallToContainer::SOURCE_CONTAINER,
                'line' => 33,
            ],
            [
                'type' => CallToContainer::TYPE_VARIABLE,
                'source' => CallToContainer::SOURCE_CONTAINER,
                'line' => 38,
            ],
            [
                'type' => CallToContainer::TYPE_VARIABLE,
                'source' => CallToContainer::SOURCE_CONTAINER,
                'line' => 39,
            ],
            [
                'type' => CallToContainer::TYPE_EXPLICIT,
                'source' => CallToContainer::SOURCE_SERVICE_LOCATOR,
                'line' => 44,
            ],
            [
                'type' => CallToContainer::TYPE_VARIABLE,
                'source' => CallToContainer::SOURCE_SERVICE_LOCATOR,
                'line' => 49,
            ],
        ];

        self::assertEquals(count($cases), count($calls));

        $i = 0;
        foreach ($cases as $case) {
            self::assertEquals($case['type'], $calls[$i]->type);
            self::assertEquals($case['source'], $calls[$i]->source);
            self::assertEquals($case['line'], $calls[$i]->line);
            self::assertStringContainsString('Foo.php', $calls[$i]->file);
            $i++;
        }

    }

}