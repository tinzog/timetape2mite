<?php
namespace In2code\In2timetape\Service;

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Security\Account;

/**
 * Account management service adapted from \TYPO3\AccountManagement\Service\AccountManagementService
 *
 * @Flow\Scope("singleton")
 */
class AccountManagementService {

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
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Persistence\PersistenceManagerInterface
	 */
	protected $persistenceManager;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Security\Policy\PolicyService
	 */
	protected $policyService;

	/**
	 * @var \TYPO3\Flow\Security\Context
	 * @Flow\Inject
	 */
	protected $securityContext;

	/**
	 * Create a new user
	 *
	 * This command creates a new user which has access to the backend user interface.
	 * It is recommended to user the email address as a username.
	 *
	 * @param string $username The username of the user to be created.
	 * @param string $password Password of the user to be created
	 * @param string $firstName First name of the user to be created
	 * @param string $middleName Middle name of the user to be created
	 * @param string $lastName Last name of the user to be created
	 * @param string $roles A comma separated list of roles to assign
	 * @param string $authenticationProvider The name of the authentication provider to use
	 * @return \TYPO3\Flow\Security\Account $account
	 */
	public function createUser($username, $password, $firstName, $middleName = '', $lastName, $roles, $authenticationProvider = 'DefaultProvider') {
		$account = $this->accountRepository->findByAccountIdentifierAndAuthenticationProviderName($username, $authenticationProvider);
		if ($account instanceof \TYPO3\Flow\Security\Account) {
			// Return exception
			return FALSE;
		}

		$user = new \TYPO3\Party\Domain\Model\Person;
		$name = new \TYPO3\Party\Domain\Model\PersonName('', $firstName, $middleName, $lastName, '', $username);
		$user->setName($name);

		$this->partyRepository->add($user);

		$account = $this->accountFactory->createAccountWithPassword($username, $password, explode(',', $roles), $authenticationProvider);
		$account->setParty($user);
		$this->accountRepository->add($account);

		return $account;
	}

	/**
	 * Removes a user
	 *
	 * This command removes a user which has access to the backend user interface.
	 *
	 * @param string $identifier The username of the user to be removed.
	 * @param string $authenticationProvider The name of the authentication provider to use
	 * @return bool
	 */
	public function removeUser($identifier, $authenticationProvider = 'DefaultProvider') {
		$account = $this->accountRepository->findByAccountIdentifierAndAuthenticationProviderName($identifier, $authenticationProvider);
		if ($account instanceof \TYPO3\Flow\Security\Account) {
			$party = $account->getParty();

			$this->partyRepository->remove($party);
			$this->accountRepository->remove($account);

			return TRUE;
		} else {
			return FALSE;
		}
	}

	/**
	 * Get Signed in account
	 *
	 * @return \TYPO3\Flow\Security\Account $account
	 */
	public function getLoggedInUser() {
		$tokens = $this->securityContext->getAuthenticationTokens();
		$account = null;

		foreach ($tokens as $token) {
			if ($token->isAuthenticated()) {
				$account = $token->getAccount();
			} else $account = null;
		}
		return $account;
	}

	/**
	 * Get Signed in account
	 *
	 * @return string $username
	 */
	public function getLoggedInUserName() {
		$tokens = $this->securityContext->getAuthenticationTokens();
		$account = null;

		foreach ($tokens as $token) {
			if ($token->isAuthenticated()) {
				$account = $token->getAccount();
			} else $account = null;
		}
		/**
		 * @var \TYPO3\Flow\Security\Account $account
		 */
		$username = $account->getAccountIdentifier();
		return $username;
	}

	/**
	 * Set a new password for the given account
	 *
	 * This allows for setting a new password for an existing user account.
	 *
	 * @param Account $account
	 * @param $password
	 * @param string $passwordHashingStrategy
	 */
	public function resetPassword(Account $account, $password, $passwordHashingStrategy = 'default') {

		$account->setCredentialsSource($this->hashService->hashPassword($password, $passwordHashingStrategy));
		$this->accountRepository->update($account);
	}

	/**
	 * Lists the Accounts of this installation
	 *
	 * The list can be filtered to match a particular pattern,
	 * and will be limited to a configurable amount of items shown.
	 *
	 * @param string $identifierFilter A filter string, matching the "LIKE" requirements for Repositories. Case-insensitive.
	 * @param integer $limit The maximum amount of accounts shown
	 * @return  \Doctrine\Common\Collections\Collection<\TYPO3\Flow\Security\Account>
	 * @see typo3.usermanagement:account:show
	 */
	public function getAccountList($identifierFilter = NULL, $limit = 100) {
		$query = $this->accountRepository->createQuery();
		if ($identifierFilter !== NULL) {
			$query->matching($query->like('accountIdentifier', $identifierFilter, FALSE))
				->setLimit($limit);
		}

		return $query->execute();
	}

	//eigene Methoden

	/**
	 * Liefert die gefundenen Rollen für das angegebene Package
	 *
	 * @param \string $packageName
	 * @return array
	 */
	public function getRelevantRolesForPackage($packageName) {
		$roles = $this->policyService->getRoles();
		$relevantRoles = array();
		foreach ($roles as $role) {
			if (strstr($role, $packageName)) {
				$relevantRoles[] = $role;
			}

		}
		return $relevantRoles;
	}

	/**
	 * Methode zur Prüfung ob ein Account die angegebene Rolle besitzt
	 *
	 * @param \TYPO3\Flow\Security\Account $account
	 * @param string $roleName
	 *
	 * @return bool
	 */
	public function accountHasRole($account, $roleName) {

		$roles = $account->getRoles();
		/**
		 * @var \TYPO3\Flow\Security\Policy\Role $role
		 */
		foreach ($roles as $role) {
			if ($role->getIdentifier() == $roleName) {
				return TRUE;
			}
		}
		return FALSE;
	}

	/**
	 * @var \TYPO3\Flow\Security\Account $account
	 * @param string $roleName
	 * @return  \Doctrine\Common\Collections\Collection<\TYPO3\Flow\Security\Account>
	 */
	public function getAccountListByRole($roleName) {
		$accounts = $this->accountRepository->findAll();
		$selectedaccounts = array();
		foreach ($accounts as $account) {
			foreach ($account->getRoles() as $role) {
				if ($role == $roleName) {
					$selectedaccounts[] = $account;
				}
			}
		}
		return $selectedaccounts;
	}

	/**
	 * Shows particular data for a given Account
	 *
	 * @param string $identifier The account identifier to show information about
	 * @param string $authenticationProvider The name of the authentication provider. Can be left out if account identifier is unambiguous
	 * @return \TYPO3\Flow\Security\Account
	 * @see typo3.usermanagement:account:list
	 */
	public function getAccount($identifier, $authenticationProvider = NULL) {
		return $this->getAccountByIdentifierOrAuthenticationProviderName($identifier, $authenticationProvider);
	}

	/**
	 * Persist Updated account
	 *
	 * @param Account $account
	 */
	public function updateAccount(Account $account) {
		$this->accountRepository->update($account);
	}

	/**
	 * Tries to find an account by its identifier only
	 * If this is ambiguous due to multiple authentication provider names, or if no Account could be found at all, the CLI execution is halted.
	 *
	 * @param string $identifier
	 * @param string $authenticationProvider
	 * @return \TYPO3\Flow\Security\Account
	 */
	public function getAccountByIdentifierOrAuthenticationProviderName($identifier, $authenticationProvider = NULL) {
		if ($authenticationProvider !== NULL) {
			$account = $this->accountRepository->findByAccountIdentifierAndAuthenticationProviderName($identifier, $authenticationProvider);
		} else {
			$accounts = $this->accountRepository->findByAccountIdentifier($identifier);
			if ($accounts->count() > 1) {
				return FALSE;
			}
			$account = $accounts->getFirst();
		}

		if ($account === NULL) {
			return FALSE;
		}

		return $account;
	}

	/**
	 * @param $role
	 * @return \TYPO3\Flow\Security\Policy\Role
	 */
	public function getRole($role) {
		return $this->policyService->getRole($role);
	}

}

?>
