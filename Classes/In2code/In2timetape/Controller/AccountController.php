<?php

namespace In2code\In2timetape\Controller;

use TYPO3\Flow\Annotations as Flow;
use \In2code\Callmelater\Domain\Model\Device;

/**
 * Account Controller for the package In2code.In2timetape
 * Adapted from the AccountController of the TYPO3.AccountManagement package
 *
 * @Flow\Scope("singleton")
 */
class AccountController extends \TYPO3\Flow\Mvc\Controller\ActionController {

	/**
	 * @Flow\Inject
	 * @var \In2code\In2timetape\Service\AccountManagementService
	 */
	protected $accountManagementService;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Security\AccountRepository
	 */
	protected $accountRepository;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Party\Domain\Repository\PartyRepository
	 */
	protected $partyRepository;


	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Security\AccountFactory
	 */
	protected $accountFactory;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Security\Cryptography\HashService
	 */
	protected $hashService;

	/**
	 * @var \TYPO3\Flow\Security\Context
	 * @Flow\Inject
	 */
	protected $securityContext;

	/**
	 * @var \TYPO3\Flow\Security\Policy\PolicyService
	 * @Flow\Inject
	 */
	protected $policyService;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Persistence\PersistenceManagerInterface
	 */
	protected $persistenceManager;

	/**
	 * @return void
	 */
	protected function initializeAction() {
		parent::initializeAction();
		if ($this->arguments->hasArgument('account')) {
			$propertyMappingConfigurationForAccount = $this->arguments->getArgument('account')->getPropertyMappingConfiguration();
			$propertyMappingConfigurationForAccountParty = $propertyMappingConfigurationForAccount->forProperty('party');
			$propertyMappingConfigurationForAccountPartyName = $propertyMappingConfigurationForAccount->forProperty('party.name');

			foreach (array($propertyMappingConfigurationForAccountParty, $propertyMappingConfigurationForAccountPartyName) as $propertyMappingConfiguration) {
				$propertyMappingConfiguration->setTypeConverterOption(
					'TYPO3\Flow\Property\TypeConverter\PersistentObjectConverter',
					\TYPO3\Flow\Property\TypeConverter\PersistentObjectConverter::CONFIGURATION_CREATION_ALLOWED,
					TRUE
				);
				$propertyMappingConfiguration->setTypeConverterOption(
					'TYPO3\Flow\Property\TypeConverter\PersistentObjectConverter',
					\TYPO3\Flow\Property\TypeConverter\PersistentObjectConverter::CONFIGURATION_MODIFICATION_ALLOWED,
					TRUE
				);
			}
		}
	}

	/**
	 * Shows a list of registers
	 *
	 * @return void
	 */
	public function indexAction() {
		$this->view->assign('accounts', $this->accountRepository->findAll());
	}

	/**
	 * Shows an account object
	 *
	 * @param \TYPO3\Flow\Security\Account $account
	 * @return void
	 */
	public function showAction($account) {
		$this->view->assign('account', $account);
	}

	/**
	 * Shows a form for creating a new account object
	 *
	 * @return void
	 */
	public function newAction() {
		$this->view->assign('roles', $this->accountManagementService->getRelevantRolesForPackage('In2code.In2timetape'));
	}

	/**
	 * Adds the given new account object to the account repository
	 *
	 * @param string $identifier
	 * @Flow\Validate(argumentName="identifier", type="NotEmpty")
	 * @Flow\Validate(argumentName="identifier", type="StringLength", options={ "minimum"=3, "maximum"=255 })
	 * @Flow\Validate(argumentName="identifier", type="In2code\In2timetape\Validation\Validator\AccountExistsValidator")
	 * @param array $password
	 * @Flow\Validate(argumentName="password", type="In2code\In2timetape\Validation\Validator\PasswordValidator")
	 * @param string $firstName
	 * @Flow\Validate(argumentName="firstName", type="NotEmpty")
	 * @Flow\Validate(argumentName="firstName", type="StringLength", options={ "minimum"=1, "maximum"=255 })
	 * @param string $lastName
	 * @Flow\Validate(argumentName="lastName", type="NotEmpty")
	 * @Flow\Validate(argumentName="lastName", type="StringLength", options={ "minimum"=1, "maximum"=255 })
	 * @param string $roles
	 * @return void
	 */
	public function createAction($identifier, array $password, $firstName, $lastName, $roles) {

		$name = new \TYPO3\Party\Domain\Model\PersonName('', $firstName, '', $lastName, '', $identifier);
		$user = new \TYPO3\Party\Domain\Model\Person();
		$user->setName($name);
		$this->partyRepository->add($user);

		//Für alle Rollen außer den Nutzern des Rest-Webservice gilt der Default-Provider (Username+Password)
		$authenticationProviderName = 'DefaultProvider';

		$account = $this->accountFactory->createAccountWithPassword($identifier, array_shift($password), array($roles), $authenticationProviderName);
		$account->setParty($user);
		$this->accountRepository->add($account);

		$this->addFlashMessage('Der neue Benutzer wurde angelegt');
		$this->redirect('index');
	}

	/**
	 * Overwrite bad Error Flash Message with new text.
	 *
	 * @return \TYPO3\Flow\Error\Message
	 */
	protected function getErrorFlashMessage() {
		switch ($this->actionMethodName) {
			case 'createAction' :
				return new \TYPO3\Flow\Error\Message('Beim Anlegen des neuen Nutzers ist ein Fehler aufgetreten.');
			default:
				return parent::getErrorFlashMessage();
		}
	}

	/**
	 * @return void
	 * @var \TYPO3\Flow\Security\Account $account
	 */
	public function resetOwnPasswordAction() {
		$tokens = $this->securityContext->getAuthenticationTokens();

		foreach ($tokens as $token) {
			if ($token->isAuthenticated()) {
				$account = $token->getAccount();
			}
		}
		if ($account instanceof \TYPO3\Flow\Security\Account) {
			$this->view->assign('account', $account);
		}
	}

	/**
	 * @return void
	 * @param \TYPO3\Flow\Security\Account $account
	 */
	public function resetPasswordAction($account) {

		if ($account instanceof \TYPO3\Flow\Security\Account) {
			$this->view->assign('account', $account);
		}
	}

	/**
	 * Updates the password for the selected user
	 *
	 * @param array $password
	 * @param \TYPO3\Flow\Security\Account $account
	 * @Flow\Validate(argumentName="password", type="\In2code\In2timetape\Validation\Validator\PasswordValidator")
	 * @return void
	 */
	public function updatePasswordAction(array $password, $account) {

		if ($account instanceof \TYPO3\Flow\Security\Account) {
			$this->accountManagementService->resetPassword($account, array_shift($password));
			$this->addFlashMessage('Das Passwort wurde geändert');
			$this->redirect('index', 'Account');
		} else {
			$this->addFlashMessage('Das Passwort konnte nicht geändert werden');
			$this->redirect('index', 'Account');
		}
	}

	/**
	 * Updates the password for the signed in user
	 *
	 * @param array $password
	 * @var \TYPO3\Flow\Security\Account $account
	 * @Flow\Validate(argumentName="password", type="\In2code\In2timetape\Validation\Validator\PasswordValidator")
	 * @return void
	 */
	public function updateOwnPasswordAction(array $password) {

		$tokens = $this->securityContext->getAuthenticationTokens();

		foreach ($tokens as $token) {
			if ($token->isAuthenticated()) {
				$account = $token->getAccount();
				$this->accountManagementService->resetPassword($account, array_shift($password));
				$this->addFlashMessage('Das Passwort wurde geändert');
				$this->redirect('index', 'Account');
			} else {
				$this->addFlashMessage('Das Passwort konnte nicht geändert werden');
				$this->redirect('index', 'Account');
			}
		}

	}


	/**
	 * @param \TYPO3\Flow\Security\Account $account
	 * @return void
	 */
	public function deleteAction(\TYPO3\Flow\Security\Account $account) {
		if ($this->securityContext->getAccount() === $account) {
			$this->addFlashMessage('Der aktuell eingeloggte Benutzer kann nicht entfernt werden');
			$this->redirect('index');
		}

		$this->accountRepository->remove($account);
		$this->persistenceManager->persistAll();
		$this->addFlashMessage('Der Benutzer wurde gelöscht.');
		$this->redirect('index');
	}

	/*
	 * Eigene Actions
	 *
	 */


	/**
	 * Action leitet auf die Show-Action für den eigenen Account um
	 * @var  \TYPO3\Flow\Security\Account $account
	 * @return void
	 */
	public function showOwnAccountAction() {

		$tokens = $this->securityContext->getAuthenticationTokens();
		$ownaccount = NULL;

		foreach ($tokens as $token) {
			if ($token->isAuthenticated()) {
				$ownaccount = $token->getAccount();
				$this->view->assign('account', $ownaccount);
			} else {
				$this->addFlashMessage('Der eigene Account kann nicht angezeigt werden');
				$this->redirect('index', 'Entitlement');
			}
		}
	}
}
