<?php

$baseUrl = 'https://skillswap';
$basepath = '/';

if (!function_exists('norm')) {
	function norm(string $str): string
	{
		return htmlspecialchars(trim($str), ENT_QUOTES, 'UTF-8');
	}
}
