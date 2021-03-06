<?php

namespace App\Presenters;

use App\InvalidArgumentException;
use App\Models\Rme;
use App\Models\Rme\VideoView;
use App\Models\Structs\EventList;
use App\Models\Structs\VideoEvents;
use Nette\Application\BadRequestException;
use Nette\Utils\Strings;


final class Js extends Presenter
{

	/**
	 * @param int $videoId
	 * @param NULL|int $blockId
	 * @param NULL|int $schemaId
	 * @throws BadRequestException
	 */
	public function actionVideoViewBegin($videoId, $blockId = NULL, $schemaId = NULL)
	{
		if (!$video = $this->orm->contents->getById($videoId))
		{
			$this->error();
		}
		if (!$video instanceof Rme\Video)
		{
			$this->error();
		}

		$view = new VideoView();
		$view->video = $video;
		if ($blockId)
		{
			$view->block = $blockId;
		}
		if ($schemaId)
		{
			$view->schema = $schemaId;
		}
		$view->user = $this->userEntity->id;
		$this->orm->videoViews->attach($view);

		$this->orm->flush();
		$this->sendJson(['viewId' => $view->id]);
	}

	/**
	 * @param int $viewId
	 * @param float $from
	 * @param float $to
	 * @throws BadRequestException
	 */
	public function actionVideoViewSeek($viewId, $from, $to)
	{
		/** @var VideoView $view */
		if (!$view = $this->orm->videoViews->getById($viewId))
		{
			$this->error();
		}
		$view->addEvent(VideoEvents\Seek::from($from, $to));
		$this->orm->flush();
		$this->sendJson(['status' => 'ok']);
	}

	/**
	 * @param int $viewId
	 * @param float $at
	 * @param float $duration
	 * @throws BadRequestException
	 */
	public function actionVideoViewPause($viewId, $at, $duration)
	{
		/** @var VideoView $view */
		if (!$view = $this->orm->videoViews->getById($viewId))
		{
			$this->error();
		}

		$view->addEvent(VideoEvents\Pause::from($at, $duration));
		$this->orm->flush();
		$this->sendJson(['status' => 'ok']);
	}

	/**
	 * @param int $viewId
	 * @param float $at seconds
	 * @param boolean $isFullscreenNow
	 * @throws BadRequestException
	 */
	public function actionVideoViewChangeView($viewId, $at, $isFullscreenNow)
	{
		/** @var VideoView $view */
		if (!$view = $this->orm->videoViews->getById($viewId))
		{
			$this->error();
		}

		$view->addEvent(VideoEvents\ChangeView::from($at, $isFullscreenNow));
		$this->orm->flush();
		$this->sendJson(['status' => 'ok']);
	}

	/**
	 * @param int $viewId
	 * @param float $percent
	 * @param float $time total of real time watched
	 * @param float $furthest
	 * @param bool $watched
	 * @throws BadRequestException
	 */
	public function actionVideoViewTick($viewId, $percent, $time, $furthest, $watched = FALSE)
	{
		/** @var VideoView $view */
		if (!$view = $this->orm->videoViews->getById($viewId))
		{
			$this->error();
		}

		try
		{
			$view->percent = $percent;
			$view->time = $time;
			$view->furthest = $furthest;
		}
		catch (InvalidArgumentException $e)
		{
			$this->sendJson(['status' => 'error', 'watched' => FALSE]);
		}

		if (!$watched && $view->percent > 70) // real time percents
		{
			$this->trigger(EventList::VIDEO_WATCHED, [$view, $this->userEntity]);
			$this->orm->flush();
			$this->sendJson(['status' => 'ok', 'watched' => TRUE]);
		}

		$this->orm->flush();
		$this->sendJson(['status' => 'ok', 'watched' => $watched]);
	}

	public function actionGuessGender($name)
	{
		$names = Strings::split($name, '~\s+~');
		$firstName = array_shift($names);
		$lastName = array_pop($names);

		$gender = $this->orm->users->getGender($firstName, $lastName);
		$this->sendJson(['gender' => $gender]);
	}

}
