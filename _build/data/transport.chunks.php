<?php

$chunks = [];

$tmp = [
	'msMCDCountTpl' => [
		'file' => 'msmcdcount',
		'description' => '',
	],
	'msMCDMiniCartOuterTpl' => [
		'file' => 'msmcdminicartouter',
		'description' => '',
	],
	'msMCDMiniCartRowTpl' => [
		'file' => 'msmcdminicartrow',
		'description' => '',
	],
	'msMCDProductsRowTpl' => [
		'file' => 'msmcdproductsrow',
		'description' => '',
	],
	'msMCDProductsRowInputTpl' => [
		'file' => 'msmcdproductsrowinput',
		'description' => '',
	],
];

// Save chunks for setup options
$BUILD_CHUNKS = array();

foreach ($tmp as $k => $v) {
	/* @avr modChunk $chunk */
	$chunk = $modx->newObject('modChunk');
	$chunk->fromArray(array(
		'id' => 0,
		'name' => $k,
		'description' => @$v['description'],
		'snippet' => file_get_contents($sources['source_core'] . '/elements/chunks/chunk.' . $v['file'] . '.tpl'),
		'static' => BUILD_CHUNK_STATIC,
		'source' => 1,
		'static_file' => 'core/components/' . PKG_NAME_LOWER . '/elements/chunks/chunk.' . $v['file'] . '.tpl',
	), '', true, true);

	$chunks[] = $chunk;

	$BUILD_CHUNKS[$k] = file_get_contents($sources['source_core'] . '/elements/chunks/chunk.' . $v['file'] . '.tpl');
}

unset($tmp);
return $chunks;
