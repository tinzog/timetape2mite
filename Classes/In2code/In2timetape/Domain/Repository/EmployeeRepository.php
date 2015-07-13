<?php
namespace In2code\In2timetape\Domain\Repository;

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Persistence\Repository;

/**
 * @Flow\Scope("singleton")
 */
class EmployeeRepository extends Repository {

	/**
	 * @var array
	 * Mitarbeiter alphabetisch sortiert nach Nachname
	 */

	protected $defaultOrderings = array('lastName' => \TYPO3\Flow\Persistence\QueryInterface::ORDER_ASCENDING);


	/**
	 * Gibt Ordering zurück
	 * @return array DefaultOrdering
	 */
	public function getOrdering() {
		return $this->defaultOrderings;
	}

}
