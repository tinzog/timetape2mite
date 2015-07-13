<?php
namespace In2code\In2timetape\Controller;

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Mvc\Controller\ActionController;

/**
 * LoginController
 *
 */
class LoginController extends ActionController {

	/**
	 * @var \TYPO3\Flow\Security\Authentication\AuthenticationManagerInterface
	 * @Flow\Inject
	 */
	protected $authenticationManager;

	/**
	 * @var \TYPO3\Flow\Security\AccountRepository
	 * @Flow\Inject
	 */
	protected $accountRepository;

	/**
	 * @var \TYPO3\Flow\Security\AccountFactory
	 * @Flow\Inject
	 */
	protected $accountFactory;

	/**
	 * index action, does only display the form
	 */
	public function indexAction() {
	}

	/**
	 * @throws \TYPO3\Flow\Security\Exception\AuthenticationRequiredException
	 * @return void
	 */
	public function authenticateAction() {
		try {
			$this->authenticationManager->authenticate();
			$this->addFlashMessage('Login erfolgreich');
			$this->redirect('index', 'Entitlement');
		} catch (\TYPO3\Flow\Security\Exception\AuthenticationRequiredException $exception) {
			$this->addFlashMessage('Falscher Benutzername oder Passwort.');
			throw $exception;
		}
	}

	public function logoutAction() {
		$this->authenticationManager->logout();
		$this->addFlashMessage('Sie wurden ausgeloggt');
		$this->redirect('index', 'Login');
	}

	/**
	 * @return void
	 */
	public function registerAction() {
		$this->redirect('new', 'Account');
	}
}
