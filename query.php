<?php

$tables_array = [
	[
		'table_name' => 'tbl_admin1',
		'primary_keys_cols' => ['id'],
		'foreign_keys_cols'=> [],
		'next_table' => [[
			'table_name' => 'tbl_admin2',
			'primary_keys_cols' => ['id'],
			'foreign_keys_cols'=> [],
			'next_table' => [[
				'table_name' => 'tbl_admin3',
				'primary_keys_cols' => ['id'],
				'foreign_keys_cols'=> [],
				'next_table' => [[
					'table_name' => 'tbl_admin4',
					'primary_keys_cols' => ['id'],
					'foreign_keys_cols'=> [],
					'next_table' => [[
						'table_name' => 'tbl_admin5',
						'primary_keys_cols' => ['id'],
						'foreign_keys_cols'=> [],
						'next_table' => [
						]
					]]
				]]
			]]
		]]
	],
	[
		'table_name' => 'db2.tbl_roles',
		'primary_keys_cols' => ['id'],
		'foreign_keys_cols'=> [],
		'next_table' => []
	],
];


$select = ['tbl_admin2'=>['a'],'tbl_admin4'=>['b']];
echo '<pre>';

list($found,$tables_array) = arrangeTable(array_keys ($select),$tables_array);
print_r($tables_array);

function arrangeTable($select_table,$tables_array) {

	$table = array();

	for( $i = 0, $i_count = count($tables_array); $i<$i_count; $i++ ) {

		if(isset($tables_array[$i]['next_table']) && count($tables_array[$i]['next_table'])>0) {

			list($found,$table[]) = arrangeTable($select_table,$tables_array[$i]['next_table']);

			if($found || in_array($tables_array[$i]['table_name'],$select_table)) {

				return [true,[
					'table_name' => $tables_array[$i]['table_name'],
					'primary_keys_cols' => $tables_array[$i]['primary_keys_cols'],
					'foreign_keys_cols'=> $tables_array[$i]['foreign_keys_cols'],
					'next_table' => $table,
					'found' => in_array($tables_array[$i]['table_name'],$select_table)?1:0
				]];
			}
			else {

				return [false,[]];	
			}
		}
		else {

			if(isset($tables_array[$i]['table_name']) && in_array($tables_array[$i]['table_name'],$select_table)) {

				return [true,[
					'table_name' => $tables_array[$i]['table_name'],
					'primary_keys_cols' => $tables_array[$i]['primary_keys_cols'],
					'foreign_keys_cols'=> $tables_array[$i]['foreign_keys_cols'],
					'next_table' => $table,
					'found' => in_array($tables_array[$i]['table_name'],$select_table)?1:0
				]];
			}
			else {

				return [false,[]];	
			}
		}
	}
}
