<?php

if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

/**
 * @var $component SectionUfComponent
 */

if(!is_array($arResult['additionalParameters']['VALUE']))
{
	$arResult['additionalParameters']['VALUE'] = [];
}

if($arResult['userField']['SETTINGS']['LIST_HEIGHT'] < 5)
{
	$size = 5;
}
else
{
	$size = (int)$arResult['userField']['SETTINGS']['LIST_HEIGHT'];
}

$arResult['size'] = $size;
$arResult['fieldName'] = 'find_'.$arResult['fieldName'];