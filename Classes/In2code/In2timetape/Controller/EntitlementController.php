<?php

namespace In2code\In2timetape\Controller;

use TYPO3\Flow\Annotations as Flow;
use DateTime;


class EntitlementController extends \TYPO3\Flow\Mvc\Controller\ActionController {

	/**
	 *
	 * @Flow\Inject
	 * @var \In2code\In2timetape\Domain\Repository\EmployeeRepository
	 */
	protected $employeeRepository;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Security\Context
	 */
	protected $securityContext;


	public function indexAction() {

		$this->view->assign('myaccount', $this->securityContext->getAccount());

		$year = date('Y');

		$employees = $this->employeeRepository->findAll();
		$entitlements = Array();

		foreach ($employees as $employee) {
			if ($employee->isActive()) {
				$holidayEntitlements = $employee->getHolidayEntitlements();
				/**
				 * @var \In2code\In2timetape\Domain\Model\HolidayEntitlement $holidayEntitlement
				 */
				foreach ($holidayEntitlements as $holidayEntitlement) {
					if ((string)$holidayEntitlement->getYear() == (string)$year) {
						$entitlements[] = $holidayEntitlement;
					}
				}
			}
		}
		$this->view->assign('year', $year);

		if ($entitlements) {
			$this->view->assign('entitlements', $entitlements);
		}
	}
}
