<?php

namespace App\Presenters;

use App\Components\ColumnChart;
use App\Components\FlashTrait;
use App\Components\GistRenderer;
use App\Components\Search;
use App\DeprecatedException;
use App\Model\EventList;
use App\Orm\ContentEntity;
use App\Orm\Highlight;
use App\Rme\BadgeUserBridge;
use App\Rme\RepositoryContainer;
use App\Rme\User;
use App\Services\Translator;
use Kdyby\Events\EventArgsList;
use Kdyby\Events\EventManager;
use Kdyby\Events\Subscriber;
use Monolog\Logger;
use Nette;
use Nette\Http\Session;


/**
 * @property-read Nette\Templating\FileTemplate $template
 * @property-read User $userEntity
 */
abstract class BasePresenter extends Nette\Application\UI\Presenter implements Subscriber
{

	use FlashTrait;

	/** @var RepositoryContainer @inject */
	public $orm;

	/** @var Translator @inject */
	public $translator;

	/** @var EventManager @inject */
	public $eventManager;

	/** @var Logger @inject */
	public $log;

	/** @var Session @inject */
	public $session;

	/** @deprecated */
	public $context;

	public function startup()
	{
		parent::startup();
		$this->translator->setLanguage('cs');
		$this->eventManager->addEventSubscriber($this);
	}

	public function getSubscribedEvents()
	{
		return [EventList::BADGE_AWARDED];
	}

	public function onBadgeAwarded(BadgeUserBridge $bridge)
	{
		$this->flashSuccess('badges.awarded', [
			'badge' => $bridge->title
		]);
	}

	/**
	 * @deprecated
	 * @throws \App\DeprecatedException
	 */
	public function getContext()
	{
		throw new DeprecatedException('Use /** @var ClassName @inject */ on public property instead');
	}

	/**
	 * Does not flush automatically!
	 * @param $event
	 * @param array $args
	 */
	protected function trigger($event, array $args = [])
	{
		$this->eventManager->dispatchEvent($event, new EventArgsList($args));
	}

	/**
	 * @return User
	 */
	public function getUserEntity()
	{
		$userEntity = $this->orm->users->getById($this->user->id);
		if ($this->user->loggedIn && !$userEntity)
		{
			$this->user->logout(TRUE);
		}

		return $userEntity;
	}

	public function link($destination, $args = [])
	{
		if ($destination instanceof Highlight)
		{
			$destination = $destination->getEntity();
		}
		if ($destination instanceof ContentEntity)
		{
			$type = $destination->getShortEntityName();
			$param = lcFirst($type) . 'Id';
			return parent::link("$type:", [$param => $destination->id]);
		}

		return parent::link($destination, $args);
	}

	public function beforeRender()
	{
		parent::beforeRender();
		$this->template->setTranslator($this->translator);
		$this->template->userEntity = $this->userEntity;
	}

	public function createComponentGist()
	{
		return new GistRenderer($this->translator, $this->getTemplateFactory());
	}

	public function createComponentSearch()
	{
		return new Search($this->translator, $this->getTemplateFactory());
	}

	public function createComponentColumnChart()
	{
		return new ColumnChart($this->translator, $this->getTemplateFactory());
	}

	public function redirectToAuth()
	{
		$this->session->getSection('auth')->loginBacklink = $this->storeRequest();

		if ($this->user->getLogoutReason() === Nette\Security\IUserStorage::INACTIVITY)
		{
			$this->flashInfo('auth.reason.inactivity');
		}
		else
		{
			$this->flashInfo('auth.reason.generic');
		}
		$this->redirect('Auth:in');
	}

}
