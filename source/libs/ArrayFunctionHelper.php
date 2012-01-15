<?php

/**
 * ArrayFunctionHelper
 *
 * @author Matej Baćo <matejbaco@gmail.com>
 * @license http://www.gnu.org/licenses/lgpl-3.0.txt GNU LESSER GENERAL PUBLIC LICENSE
*/

class ArrayFunctionHelper
{
	/**
	 * arrayMerge
	 *
	 * Merges named array's.
	 *
	 * Example:
	 * $array_A = array('key_1' => 'value_A_1', 'some_key' => 'some_value');
	 * $array_B = array('key_1' => 'value_B_1', 'key_2' => 'value_B_2');
	 * $result = ArrayFunctionHelper::arrayMerge($array_A, $array_B);
	 *
	 * $result will be:
	 * array(
	 * 	'key_1' => 'value_B_1',
	 * 	'some_key' => 'some_value',
	 * 	'key_2' => 'value_B_2'
	 * )
	*/
	public static function arrayMerge()
	{
		$arg_list = func_get_args();

		$final_array = array();

		foreach ($arg_list as $single_array)
		{
			if (!is_array($single_array))
			{
				throw new Exception('Wrong use, method accepts only arrays.');
			}

			foreach ($single_array as $key => $val)
			{
				$final_array[$key] = $val;
			}
		}

		return $final_array;
	}
}