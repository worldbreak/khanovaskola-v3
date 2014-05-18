<?php

namespace App\Services;

use Nette\Object;


class BlueprintPurifier extends HtmlPurifier
{

	public static function getAllowedElements()
	{
		return ['span'];
	}

	public static function getAllowedAttributes()
	{
		return ['span.class'];
	}

}