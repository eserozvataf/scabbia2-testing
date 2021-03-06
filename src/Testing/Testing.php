<?php
/**
 * Scabbia2 Testing Component
 * https://github.com/eserozvataf/scabbia2
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @link        https://github.com/eserozvataf/scabbia2-testing for the canonical source repository
 * @copyright   2010-2016 Eser Ozvataf. (http://eser.ozvataf.com/)
 * @license     http://www.apache.org/licenses/LICENSE-2.0 - Apache License, Version 2.0
 */

namespace Scabbia\Testing;

use Scabbia\Helpers\FileSystem;
use Scabbia\Formatters\FormatterInterface;
use Scabbia\Formatters\Formatters;

/**
 * A small test implementation which helps us during the development of
 * Scabbia2 PHP Framework's itself and related production code
 *
 * @package     Scabbia\Testing
 * @author      Eser Ozvataf <eser@ozvataf.com>
 * @since       2.0.0
 */
class Testing
{
    /**
     * Runs given unit tests
     *
     * @param array              $uTestClasses set of unit test classes
     * @param FormatterInterface $uFormatter   formatter class
     *
     * @return int exit code
     */
    public static function runUnitTests(array $uTestClasses, $uFormatter = null)
    {
        if ($uFormatter === null) {
            $uFormatter = Formatters::getCurrent();
        }

        $tIsEverFailed = false;

        $uFormatter->writeHeader(1, "Unit Tests");

        /** @type string $tTestClass */
        foreach ($uTestClasses as $tTestClass) {
            $uFormatter->writeHeader(2, $tTestClass);

            $tInstance = new $tTestClass ();
            $tInstance->test();

            if ($tInstance->isFailed) {
                $tIsEverFailed = true;
            }

            foreach ($tInstance->testReport as $tTestName => $tTest) {
                $tFails = [];
                foreach ($tTest as $tTestCase) {
                    if ($tTestCase["failed"]) {
                        $tFails[] = [
                            "operation" => $tTestCase["operation"],
                            "message" => $tTestCase["message"]
                        ];
                    }
                }

                if (count($tFails) === 0) {
                    $uFormatter->write(sprintf("[OK] %s", $tTestName));
                } else {
                    $uFormatter->writeColor("red", sprintf("[FAIL] %s", $tTestName));
                    $uFormatter->writeArray($tFails);
                }
            }
        }

        if ($tIsEverFailed) {
            return 1;
        }

        return 0;
    }

    /**
     * Starts the code coverage
     *
     * @return void
     */
    public static function coverageStart()
    {
        if (!extension_loaded("xdebug")) {
            return;
        }

        xdebug_start_code_coverage(XDEBUG_CC_UNUSED | XDEBUG_CC_DEAD_CODE);
    }

    /**
     * Stops the code coverage
     *
     * @return array results
     */
    public static function coverageStop()
    {
        if (!extension_loaded("xdebug")) {
            return null;
        }

        $tCoverageData = xdebug_get_code_coverage();
        xdebug_stop_code_coverage();

        $tFinal = [
            "files" => [],
            "total" => [ "coveredLines" => 0, "totalLines" => 0 ]
        ];

        foreach ($tCoverageData as $tPath => $tLines) {
            $tFileCoverage = [
                "path"         => $tPath,
                "coveredLines" => array_keys($tLines),
                "totalLines"   => FileSystem::getFileLineCount($tPath)
            ];

            $tFinal["files"][] = $tFileCoverage;
            $tFinal["total"]["coveredLines"] += count($tFileCoverage["coveredLines"]);
            $tFinal["total"]["totalLines"] += $tFileCoverage["totalLines"];
        }

        $tFinal["total"]["percentage"] = ($tFinal["total"]["coveredLines"] * 100) / $tFinal["total"]["totalLines"];

        return $tFinal;
    }
}
