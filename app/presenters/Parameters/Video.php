<?php

namespace App\Presenters\Parameters;

use App\Models\Rme;
use App\Presenters\Presenter;


trait Video
{

	/**
	 * @var int
	 * @persistent
	 */
	public $videoId;

	/**
	 * @var Rme\Video
	 */
	protected $video;

	protected function loadVideo()
	{
		/** @var Presenter $this */
		$this->video = $this->orm->contents->getById($this->videoId);
		if (!$this->video || ! $this->video instanceof Rme\Video)
		{
			$this->error();
		}
	}

}
