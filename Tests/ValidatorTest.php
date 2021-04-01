<?php


namespace ChameleonSystem\UpgradeHelperBundle\Tests;


use ChameleonSystem\UpgradeHelperBundle\DataModel\CallToContainer;
use ChameleonSystem\UpgradeHelperBundle\DataModel\Warning;
use ChameleonSystem\UpgradeHelperBundle\Parser\Parser;
use ChameleonSystem\UpgradeHelperBundle\Tests\Mock\Container;
use ChameleonSystem\UpgradeHelperBundle\Validator\Validator;
use PHPUnit\Framework\TestCase;

final class ValidatorTest extends TestCase
{

    /**
     * @test
     */
    public function validatorTest()
    {
        $container = new Container();
        $container->set('existing', null);

        $parser = new Parser();
        $calls = $parser->parseDirectory(__DIR__ . '/Fixtures');

        $validator = new Validator($container);

        $warnings = $validator->validate($calls);

        $cases = [
            [
                'line' => 38,
                'type' => Warning::TYPE_IMPLICIT,
                'source' => CallToContainer::SOURCE_CONTAINER,
                'service' => '',
            ],
            [
                'line' => 39,
                'type' => Warning::TYPE_IMPLICIT,
                'source' => CallToContainer::SOURCE_CONTAINER,
                'service' => '',
            ],
            [
                'line' => 44,
                'type' => Warning::TYPE_NON_EXISTENT,
                'source' => CallToContainer::SOURCE_SERVICE_LOCATOR,
                'service' => 'non-existing',
            ],
            [
                'line' => 49,
                'type' => Warning::TYPE_IMPLICIT,
                'source' => CallToContainer::SOURCE_SERVICE_LOCATOR,
                'service' => '',
            ],
        ];

        self::assertEquals(count($cases), count($warnings));

        $i = 0;
        foreach ($cases as $case) {
            self::assertEquals($case['line'], $warnings[$i]->call->line);
            self::assertEquals($case['type'], $warnings[$i]->type);
            self::assertEquals($case['source'], $warnings[$i]->call->source);
            self::assertEquals($case['service'], $warnings[$i]->call->service);
            $i++;
        }
    }

    /**
     * @test
     */
    public function validatorAllowlistTest()
    {
        $container = new Container();
        $container->set('existing', null);

        $parser = new Parser();
        $calls = $parser->parseDirectory(__DIR__ . '/Fixtures');

        $validator = new Validator($container, ['non-existing']);

        $warnings = $validator->validate($calls);

        $cases = [
            [
                'line' => 38,
                'type' => Warning::TYPE_IMPLICIT,
                'source' => CallToContainer::SOURCE_CONTAINER,
                'service' => '',
            ],
            [
                'line' => 39,
                'type' => Warning::TYPE_IMPLICIT,
                'source' => CallToContainer::SOURCE_CONTAINER,
                'service' => '',
            ],
            [
                'line' => 49,
                'type' => Warning::TYPE_IMPLICIT,
                'source' => CallToContainer::SOURCE_SERVICE_LOCATOR,
                'service' => '',
            ],
        ];

        self::assertEquals(count($cases), count($warnings));

        $i = 0;
        foreach ($cases as $case) {
            self::assertEquals($case['line'], $warnings[$i]->call->line);
            self::assertEquals($case['type'], $warnings[$i]->type);
            self::assertEquals($case['source'], $warnings[$i]->call->source);
            self::assertEquals($case['service'], $warnings[$i]->call->service);
            $i++;
        }
    }

}