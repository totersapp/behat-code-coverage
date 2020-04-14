<?php
/**
 * Code Coverage Report Service
 *
 * @copyright 2013 Anthon Pang
 *
 * @license BSD-2-Clause
 */

namespace LeanPHP\Behat\CodeCoverage\Service;

use LeanPHP\Behat\CodeCoverage\Common\Report\Factory;
use SebastianBergmann\CodeCoverage\CodeCoverage;

/**
 * Code coverage report service
 *
 * @author Anthon Pang <apang@softwaredevelopment.ca>
 */
class ReportService
{
    /**
     * @var array
     */
    private $config;

    /**
     * @var \LeanPHP\Behat\CodeCoverage\Common\Report\Factory
     */
    private $factory;

    /**
     * Constructor
     *
     * @param array                                             $config
     * @param \LeanPHP\Behat\CodeCoverage\Common\Report\Factory $factory
     */
    public function __construct(array $config, Factory $factory)
    {
        $this->config  = $config;
        $this->factory = $factory;
    }

    /**
     * Generate report
     *
     * @param CodeCoverage $coverage
     */
    public function generateReport(CodeCoverage $coverage)
    {
        if (!$this->config['report']['final']) {
            if (!is_dir("coverage_data")) mkdir("coverage_data");
            if (!is_dir("coverage_data/data")) mkdir("coverage_data/data");
            if (!is_dir("coverage_data/test")) mkdir("coverage_data/test");
            file_put_contents("coverage_data/data/" . rand() . rand() . ".json", json_encode($coverage->getData()));
            file_put_contents("coverage_data/test/" . rand() . rand() . ".json", json_encode($coverage->getTests()));
        } else {
            $CoverageResults = [];
            $CoverageData = [];
            $CoverageTest = [];
            $EmptyLines = [];
            $num = 0;
            
            ///////////////////////////////////
            $data_dir = array_diff(scandir("coverage_data/data"), array('..', '.'));
            foreach ($data_dir as $file) {
                $result = json_decode(file_get_contents("coverage_data/data/" . $file), true);
                array_push($CoverageResults,$result);
            }
            
            if(sizeof($CoverageResults)==1)  $CoverageData = $CoverageResults[0];
            
            $FileNames = array_keys($CoverageResults[0]);
            
            foreach($FileNames as $FileName){
                foreach ($CoverageResults as $result) {
                    foreach($result[$FileName] as $LineNumber => $Features){
                        if(!isset($CoverageData[$FileName][$LineNumber])){
                            if(!isset($CoverageData[$FileName]))
                                $CoverageData[$FileName] = [];
                            $combination = [];
                            for ($i=0;$i<sizeof($CoverageResults);$i++){
                                if(array_key_exists($LineNumber,$CoverageResults[$i][$FileName]))
                                    $combination = $this->combine($combination,$CoverageResults[$i][$FileName][$LineNumber]);
                                else
                                    $combination = 3;
                            }
                            if($combination!=3)
                                $CoverageData[$FileName][$LineNumber] = $combination;
                        }
                    }
                }
            }
            
            $test_dir = array_diff(scandir("coverage_data/test"), array('..', '.'));

            foreach ($test_dir as $file) {

                $arr = json_decode(file_get_contents("coverage_data/test/" . $file), true);
                foreach ($arr as $feature => $content) {
                    $CoverageTest[$feature] = $content;
                }
            }

            $coverage->setData($CoverageData);
            $coverage->setTests($CoverageTest);
            
        }

        $format = $this->config['report']['format'];
        $options = $this->config['report']['options'];

        $report = $this->factory->create($format, $options);
        $output = $report->process($coverage);

        if ('text' === $format) {
            print_r($output);
        }
    }
    
    public function combine($content1,$content2){
        if(is_array($content1) && empty($content1)){
            return $content2;
        }
        if($content1 == 3){
            if(is_array($content2) && empty($content2))
                return 3;
            else
                return $content2;
        }
        else if(is_null($content1)){
            if((is_array($content2) && empty($content2)) || $content2 == 3)
                return $content1;
            else
                return $content2;
        }
        else{
            if(!$content2 || empty($content2) || $content2 == 3)
                return $content1;
            else
                return array_unique(array_merge($content1,$content2));
            
        }
    }
}
