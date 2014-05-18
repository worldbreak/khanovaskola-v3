<?php

namespace App\Rme;

use App;
use App\MustNeverHappenException;
use App\Orm\Repository;
use Nette\Neon\Neon;


/**
 * @method App\Rme\Badge getByKey($key)
 */
class BadgesRepository extends Repository
{

	static private $types;

	public function getEntityClassName(array $data = NULL)
	{
		if ($data === NULL)
		{
			return self::getTypes();
		}

		if (isset($data['key']))
		{
			return "App\\Rme\\Badges\\$data[key]";
		}

		throw new MustNeverHappenException;
	}

	public static function getTypes()
	{
		if (!self::$types)
		{
			self::$types = [];
			$raw = file_get_contents(__DIR__ . '/../../../config/badges.neon');
			$config = Neon::decode($raw);
			foreach ($config['events']['subscribers'] as $class)
			{
				self::$types[] = $class;
			}
		}

		return self::$types;
	}

}
