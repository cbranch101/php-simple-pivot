<?php
	error_reporting(E_ALL); 
	ini_set( 'display_errors','1');
	class Simple_Pivot {
		

		
		
		/**
		 * pivot function.
		 * 
		 * Given a selection of pivots and values to calculate for those pivots, pivots the data in the appropriate
		 * way.  Behaves similarly to pivot table reports.
		 * 
		 * @access public
		 * @static
		 * @param mixed $data
		 * @param mixed $details
		 * @return void
		 */
		public static function pivot($data, $details) {
			
			// get the pivots out of the details
			$pivots = isset($details['pivots']) ? $details['pivots'] : array();
			$valuesToCalculate = self::getValuesFromDetails($details);
			$data = self::setPivotFields($data, $pivots);
			$data = self::performAllPivots($data, $pivots, $valuesToCalculate);
			return $data;
			
		}
		
		/**
		 * getValuesFromDetails function.
		 * 
		 * Checks if the required values key is set in the details array
		 * throws an expection if it isn't
		 * 
		 * @access public
		 * @static
		 * @param mixed $details
		 * @return void
		 */
		public static function getValuesFromDetails($details) {
			$values = array();
			if(!isset($details['values'])) {
				$output = json_encode($details);
				throw new Exception("values missing from details : $details");
			} else {
				$values = $details['values'];
			}
			return $values;
		}
				
		
		
		/**
		 * performAllPivots function.
		 * 
		 * Progresses through all the pivots, recursively splitting them up into small and small groups and calculating 
		 * the specified values for each group
		 * 
		 * @access public
		 * @static
		 * @param mixed $data
		 * @param mixed $pivots
		 * @param mixed $valuesToCalculate
		 * @param string $name (default: 'totals')
		 * @return void
		 */
		public static function performAllPivots($data, $pivots, $valuesToCalculate, $name = 'totals') {
				
				$outputPivots = null;
				$return = array();
				
				$values = self::calculateValues($valuesToCalculate, $data);
				
				// pull out the first pivot
				$currentPivot = array_shift($pivots);
				
				if($currentPivot) {
					
					$field = $currentPivot['field'];
					$isFirst = false;
					
					$outputPivots = __::chain($data)
						->groupby($currentPivot['field'])
						->map(function($dataInPivot) use($valuesToCalculate, $pivots, $isFirst, $field) {
							$pivotName = $dataInPivot[0][$field];
							$pivot = Simple_Pivot::performAllPivots($dataInPivot, $pivots, $valuesToCalculate, $pivotName);
							return $pivot;
						})
						->value();
				}
				
				$return['values'] = $values;
				$return['name'] = $name;
				if($outputPivots) {
					$return['pivots'] = $outputPivots;
				}
				
				return $return;			
		
		}
		
		public static function setPivotFields($data, $pivots) {
			$dataWithPivotFields = __::map($data, function($row) use($pivots){
				__::each($pivots, function($pivot) use(&$row){
					if(isset($pivot['set_field'])) {
						
						$fieldValue = $pivot['set_field']($row);
						$row[$pivot['field']] = $fieldValue;
						return $row;
					}
				});
				return $row;
			});
			return $dataWithPivotFields;
		}

		
		/**
		 * performReductions function.
		 * 
		 * Performs all of the required reduction operations(summing, averaging, etc) on the current level of data
		 * 
		 * @access public
		 * @static
		 * @param array $reductions
		 * @param object $currentLevel
		 * @return array
		 */
		public static function calculateValues($valuesToCalculate, $data) {
			$reductionMap = self::getReductionMap();
			$overallCount = count($data);
			$index = 0;
			$return = __::chain($data)
				->reduce(function($prev, $next) use($valuesToCalculate, &$index, $overallCount, $reductionMap) {
					// if this is the first item being reduced
					if($prev === null) {			
						// signify that this is the first item
						$currentLocation = 'onFirst';
					} else {
						$currentLocation = 'onNext';
					}
					
					$reductionIndex = 0;

					// iterate over all of the reductions, pass in the needed variables
					$return = __::chain($valuesToCalculate)
						->map(function($valueToCalculate) use($prev, $next, $currentLocation, $index, &$reductionIndex, 
							$overallCount, $reductionMap){
							
							
							$reduction = Simple_Pivot::getReductionForValue($valueToCalculate);
							$lastFunction = isset($reduction['onLast']) ? $reduction['onLast'] : false;
							$key = $valueToCalculate['field'];
				
							$reduceFunction = $reduction[$currentLocation];
																			
							$count = isset($next[$key]) ? count($next[$key]) : 0;
							$return = $reduceFunction($prev, $next, $key, $index, $count);
							// if this is the last item
							if($index + 1 == $overallCount) {
								if($lastFunction) {
									$return = $lastFunction($return, $next, $key, $index, $count); 
								}
																									
								// get the key being set
							}
							
							return $return;
						})
					->flatten(true)
					->value();
					
					// return the most recent version of the data for the reduction
					$index++;
					return $return;
				})
				->value();
				return $return;
		}
		
		public static function getReductionMap() {
			$reductionMap = array(
				'sum' => array(
					'onFirst' => function($prev, $next, $key) {
						$current[$key] = $next[$key];
						return $current;
					},
					'onNext' => function($prev, $next, $key) {
						$current[$key] = $prev[$key] + $next[$key];
						return $current;
					},
				),
				'count' => array(
					'onFirst' => function($prev, $next, $key, $index, $count) {
						$current[$key] = $count;
						return $current;
					},
					'onNext' => function($prev, $next, $key, $index, $count) {
						$current[$key] = $count + $prev[$key];
						return $current;
					},
				),
				'average' => array(
					'onFirst' => function($prev, $next, $key, $index, $count) {
						$current['overall_sum'] = $next[$key];
						return $current;
					},
					'onNext' => function($prev, $next, $key, $index, $count) {
						$current['overall_sum'] = $prev['overall_sum'] + $next[$key];
						return $current;
					},
					'onLast' => function ($prev, $next, $key, $index, $count) {
						$totalMembers = ($index + 1);
						// calculate the average
						$current[$key] = $prev['overall_sum'] / $totalMembers;
						return $current;
					},
				),
				'min' => array(
					'onFirst' => function($prev, $next, $key, $index, $count) {
						$current[$key] = $next[$key];
						return $current;
					},
					'onNext' => function($prev, $next, $key, $index, $count) {
						$current[$key] = $next[$key] < $prev[$key] ? $next[$key] : $prev[$key];
						return $current;
					},
				),
				'max' => array(
					'onFirst' => function($prev, $next, $key, $index, $count) {
						$current[$key] = $next[$key];
						return $current;
					},
					'onNext' => function($prev, $next, $key, $index, $count) {
						$current[$key] = $next[$key] > $prev[$key] ? $next[$key] : $prev[$key];
						return $current;
					},
				),
			);
			
			return $reductionMap;
		}
		
		public static function getReductionForValue($value) {
			$reductionMap = self::getReductionMap();
			if(!isset($value['custom_calc']) && !isset($value['calc'])) {
				$output = json_encode($value);
				throw new Exception("calc function required in $output");
			} else {
				$reduction = isset($value['custom_calc']) ? $value['custom_calc'] : $reductionMap[$value['calc']];
			}
			return $reduction;
		}
		
		
	}
	
	
