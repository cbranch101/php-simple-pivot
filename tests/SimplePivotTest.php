<?php

	error_reporting(E_ALL); 
	ini_set( 'display_errors','1');
	require_once('/Users/cbranch101/Sites/clay/movement_strategy/php-underscore/underscore.php');
	require_once('/Users/cbranch101/Sites/clay/movement_strategy/php_simple_pivot/simple_pivot.php');
	require_once('/Users/cbranch101/Sites/clay/movement_strategy/functional_test_builder/functional_test_builder.php');

	class SimplePivotTest extends PHPUnit_Framework_TestCase {
		
		static $functionalBuilderConfig;
				
		static $verifyExpectedActual = true;
		
		function __construct() {
			self::$functionalBuilderConfig = self::getFunctionalBuilderConfig();
			
		}
		
		public function getFunctionalBuilderConfig() {
			return array(
				'configuration_map' => self::getConfigurationMap(),
				'entry_point_map' => self::getEntryPointMap(),
			);
		}
		
		public function getExpectedActualFunction() {
			$expAct = function($expectedActual) {
				return SimplePivotTest::buildExpectedActualArgs($expectedActual['expected'], $expectedActual['actual']);
			};
			
			return $expAct;
		}
		
		public function buildExpectedActualArgs($expected, $actual) {
			if($expected != $actual && self::$verifyExpectedActual) {
				$output = Test_Builder::confirmExpected($expected, $actual);
				print_r($output);
			}
			return array(
				 'expected' => $expected,
				 'actual' => $actual,
			);
		}
		
		public function buildTest($test) {
			Test_Builder::buildTest($test, self::$functionalBuilderConfig);
		}
		
		public function getEntryPointMap() {
			
			return array(
				'all' => self::getAllEntryPoint(),
				'calculate_values' => self::getCalculateValuesEntryPoint(),
				'pivot' => self::getPivotEntryPoint(),
			);
			
		}
		
		public function getAllEntryPoint() {
			$expAct = self::getExpectedActualFunction();
			
			return array(
				'test' => $this,
				'build_input' => function($input) {
					return $input;
				},
				'get_assert_args' => function($output, $assertInput) use($expAct){
					return $expAct(
						array(
							'expected' => $assertInput['expected'],					
							'actual' => $output,
						)
					);

				},
				'input' => array(),
				'extra_params' => array(),
				'assert_input' => array(),
				'asserts' => array (
					'assertEquals' => array(
						'expected', 
						'actual',
					),
				),
			);
		}
		
		public function getCalculateValuesEntryPoint() {
			
			return array(
				'input' => array(),
				'get_output' => function($input, $extraParams) {
					$valuesToCalculate = $input['values_to_calculate'];
					$data = $input['data'];
					return Simple_Pivot::calculateValues($valuesToCalculate, $data);
				},
			);
			
		}
		
		public function getPivotEntryPoint() {
			
			return array(
				'input' => array(),
				'get_output' => function($input, $extraParams) {
					$details = $input['details'];
					$data = $input['data'];
					return Simple_Pivot::pivot($data, $details);
				},
			);
			
		}
		
		public function getConfigurationMap() {
			return array(
				'single_value_sum' => self::getSingleValueSumConfiguration(),
				'single_pivot_single_value' => self::getMultiplePivotMultipleValueConfiguration(),
			);
		}
		
		public function getMultiplePivotMultipleValueConfiguration() {
			return array(
				'input' => array(
					'data' => array(
						array(
							'campaign_name' => 'PRJT1/CMP1',
							'impressions' => 2,
							'clicks' => 1,
						),
						array(
							'campaign_name' => 'PRJT1/CMP1',
							'impressions' => 2,
							'clicks' => 1,
						),
						array(
							'campaign_name' => 'PRJT2/CMP2',
							'impressions' => 2,
							'clicks' => 1,
						),
						array(
							'campaign_name' => 'PRJT2/CMP1',
							'impressions' => 2,
							'clicks' => 1,
						),
					),
					'details' => array(
						'pivots' => array(
							array(
								'field' => 'project',
								'set_field' => function($row) {
									$campaign = $row['campaign_name'];
									$campaignPieces = explode("/", $campaign);
									return $campaignPieces[0];
								},
							),
							array(
								'field' => 'campaign',
								'set_field' => function($row) {
									$campaign = $row['campaign_name'];
									$campaignPieces = explode("/", $campaign);
									return $campaignPieces[1];
								},
							),
						),
						'values' => array(
							array(
								'field' => 'clicks',
								'calc' => 'sum',
							),
							array(
								'field' => 'impressions',
								'calc' => 'sum',
							),
						),
					),
				),
				'assert_input' => array(
					'expected' => array(
						'values' => array(
							'clicks' => 4,
							'impressions' => 8,
						),
						'name' => 'totals',
						'pivots' => array(
							'PRJT1' => array(
								'values' => array(
									'clicks' => 2,
									'impressions' => 4,
								),
								'name' => 'PRJT1',
								'pivots' => array(
									'CMP1' => array(
										'values' => array(
											'clicks' => 2,
											'impressions' => 4,
										),
										'name' => 'CMP1',
									),
								),
							),
							'PRJT2' => array(
								'values' => array(
									'clicks'=> 2,
									'impressions' => 4,
								),
								'name' => 'PRJT2',
								'pivots' => array(
									'CMP2' => array(
										'values' => array(
											'clicks' => 1,
											'impressions' => 2,
										),
										'name' => 'CMP2',
									),
									'CMP1' => array(
										'values' => array(
											'clicks' => 1,
											'impressions' => 2,
										),
										'name' => 'CMP1',
									),
								),
							),
						),
					),
				),
				
			);
		}
		
		
		public function getSingleValueSumConfiguration() {
			return array(
				'input' => array(
					'data' => array(
						array(
							'impressions' => 100,
							'clicks' => 200,
							'fans' => 50,
						),
						array(
							'impressions' => 100,
							'clicks' => 100,
							'fans' => 50,
						),
						array(
							'impressions' => 400,
							'clicks' => 100,
							'fans' => 50,
						),
					),
					'values_to_calculate' => array(
						array(
							'field' => 'clicks',
							'calc' => 'sum',
						),
					),
				),
				'assert_input' => array(
					'expected' => array(
						'clicks' => 400,
					),
				),
				
			);
		}
		
		public function testSingleValueSum() {
			
			$test = array(
				'entry_point' => 'calculate_values',
				'configuration' => 'single_value_sum',
			);
			self::buildTest($test);
			
		}
				
		public function testMultiValueSum() {
			
			$test = array(
				'entry_point' => 'calculate_values',
				'configuration' => 'single_value_sum',
				'alterations' => array(
					'input' => function($input) {
						$input['values_to_calculate'][1] = array(
							'field' => 'impressions',
							'calc' => 'sum',
						);
						return $input;
					},
					'assert_input' => function($assertInput) {
						$assertInput['expected']['impressions'] = 600;
						return $assertInput;
					}
				),
			);
			self::buildTest($test);
			
		}
		
		public function testMax() {
			
			$test = array(
				'entry_point' => 'calculate_values',
				'configuration' => 'single_value_sum',
				'alterations' => array(
					'input' => function($input) {
						$input['values_to_calculate'][1] = array(
							'field' => 'impressions',
							'calc' => 'max',
						);
						return $input;
					},
					'assert_input' => function($assertInput) {
						$assertInput['expected']['impressions'] = 400;
						return $assertInput;
					}
				),
			);
			self::buildTest($test);
			
		}
		
		public function testMin() {
			
			$test = array(
				'entry_point' => 'calculate_values',
				'configuration' => 'single_value_sum',
				'alterations' => array(
					'input' => function($input) {
						$input['values_to_calculate'][1] = array(
							'field' => 'impressions',
							'calc' => 'min',
						);
						return $input;
					},
					'assert_input' => function($assertInput) {
						$assertInput['expected']['impressions'] = 100;
						return $assertInput;
					}
				),
			);
			self::buildTest($test);
			
		}
		
		public function testCustomCalc() {
			
			$test = array(
				'entry_point' => 'calculate_values',
				'configuration' => 'single_value_sum',
				'alterations' => array(
					'input' => function($input) {
						$input['values_to_calculate'][1] = array(
							'field' => 'impressions',
							'custom_calc' => array(
								'onFirst' => function($prev, $next, $key, $index, $count) {
									$current['clicks'] = $next['clicks'];
									$current['impressions'] = $next['impressions'];
									return $current;
								},
								'onNext' => function($prev, $next, $key, $index, $count) {
									$current['clicks'] = $prev['clicks'] += $next['clicks'];
									$current['impressions'] = $prev['impressions'] += $next['impressions'];
									return $current;
								},
								'onLast' => function ($prev, $next, $key, $index, $count) {
									if($prev['clicks'] > 0) {
										$current['ctr'] = $prev['impressions'] / $prev['clicks'];
									} else {
										$current['ctr'] = 0;
									};
									return $current;
								},
							),
						);
						return $input;
					},
					'assert_input' => function($assertInput) {
						$assertInput['expected']['ctr'] = 1.5;
						return $assertInput;
					},
				),
			);
			self::buildTest($test);
			
		}
		
	    /**
	     * @expectedException Exception
	     * @expectedExceptionMessage calc function required in {"field":"impressions"}
	     */		
		public function testCalcError() {
			$test = array(
				'entry_point' => 'calculate_values',
				'configuration' => 'single_value_sum',
				'alterations' => array(
					'input' => function($input) {
						$input['values_to_calculate'][1] = array(
							'field' => 'impressions',
						);
						return $input;
					},
					'assert_input' => function($assertInput) {
						$assertInput['expected']['impressions'] = 3;
						return $assertInput;
					},
				),
			);
			self::buildTest($test);
		}
		
		public function testCount() {
			
			$test = array(
				'entry_point' => 'calculate_values',
				'configuration' => 'single_value_sum',
				'alterations' => array(
					'input' => function($input) {
						$input['values_to_calculate'][1] = array(
							'field' => 'impressions',
							'calc' => 'count',
						);
						return $input;
					},
					'assert_input' => function($assertInput) {
						$assertInput['expected']['impressions'] = 3;
						return $assertInput;
					},
				),
			);
			self::buildTest($test);
			
		}
		
		public function testMultipleValueDifferenCalcs() {
			
			$test = array(
				'entry_point' => 'calculate_values',
				'configuration' => 'single_value_sum',
				'alterations' => array(
					'input' => function($input) {
						$input['values_to_calculate'][1] = array(
							'field' => 'impressions',
							'calc' => 'average',
						);
						return $input;
					},
					'assert_input' => function($assertInput) {
						$assertInput['expected']['impressions'] = 200;
						return $assertInput;
					}
				),
			);
			self::buildTest($test);
			
		}
		
		public function testSinglePivotSingleValue() {
			$test = array(
				'configuration' => 'single_pivot_single_value',
				'entry_point' => 'pivot',
			);
			$test = self::buildTest($test);
		}
	   
	    /**
	     * @expectedException Exception
	     * @expectedExceptionMessage values missing from details : Array
	     */		
		public function testNoValuesError() {
			$test = array(
				'configuration' => 'single_pivot_single_value',
				'entry_point' => 'pivot',
				'alterations' => array(
					'input' => function($input) {
						unset($input['details']['values']);
						return $input;
					},
				),
			);
			$test = self::buildTest($test);
		}
		
	}
