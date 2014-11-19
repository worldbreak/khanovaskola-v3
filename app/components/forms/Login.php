<?php

namespace App\Components\Forms;

use App\Models\Orm\RepositoryContainer;
use App\Models\Services\UserMerger;
use App\Presenters\Presenter;
use Nette\Security\AuthenticationException;


class Login extends Form
{

	/**
	 * @var RepositoryContainer
	 * @inject
	 */
	public $orm;

	/**
	 * @var UserMerger
	 * @inject
	 */
	public $userMerger;

	public function setup()
	{
		$this->addText('email')
			->addRule($this::FILLED, 'email.missing')
			->addRule($this::EMAIL, 'email.wrong');

		$this->addPassword('password')
			->addRule($this::FILLED, 'password.missing');

		$this->addSubmit();
	}

	public function onSuccess()
	{
		$v = $this->getValues();

		/** @var Presenter $presenter */
		$presenter = $this->presenter;
		try
		{
			$userEntity = $this->orm->users->getByEmail($v->email);
			if ($userEntity && !$userEntity->registered)
			{
				$presenter->redirect('Auth:registration', ['email' => $v->email]);
			}

			$guest = NULL;
			if ($presenter->user->isPersistedGuest())
			{
				// If guest user is persisted, link to it
				// locally so we dont override it with login.
				$guest = $presenter->getUserEntity();
			}

			$presenter->user->login($v->email, $v->password);

			if ($guest)
			{
				$this->userMerger->mergeUserInto($guest, $presenter->getUserEntity());
				$this->orm->flush();
			}

			$presenter->onLogin($userEntity, NULL, 'password');
		}
		catch (AuthenticationException $e)
		{
			$presenter->flashError($e->getMessage());
		}
	}
}
