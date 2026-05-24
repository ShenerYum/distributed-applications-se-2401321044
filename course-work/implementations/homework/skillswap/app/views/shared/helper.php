<?php

$baseUrl = 'https://skillswap';
$basepath = '/';

function norm(string $str): string
{
	return htmlspecialchars(trim($str), ENT_QUOTES, 'UTF-8');
}
