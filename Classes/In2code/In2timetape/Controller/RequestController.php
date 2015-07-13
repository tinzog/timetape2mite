<?php

namespace In2code\In2timetape\Controller;

use TYPO3\Flow\Annotations as Flow;
use DateTime;


class RequestController extends \TYPO3\Flow\Mvc\Controller\ActionController {

	/**
	 *
	 * @Flow\Inject
	 * @var \In2code\In2timetape\Domain\Repository\EmployeeRepository
	 */
	protected $employeeRepository;


	public function indexAction() {

		$employees = $this->employeeRepository->findAll();
		$requests = Array();

		foreach ($employees as $employee) {
			if ($employee->isActive()) {
				$holidayRequests = $employee->getHolidayRequests();
				foreach ($holidayRequests as $request) {
					$requests[] = $request;
				}
			}
		}

		if ($requests) {
			$this->view->assign('requests', $requests);
		}
	}
} 
