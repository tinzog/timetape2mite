<?php
namespace In2code\In2timetape\Domain\Model;

use TYPO3\Flow\Annotations as Flow;
use Doctrine\ORM\Mapping as ORM;

/**
 * @Flow\Scope("prototype")
 * @Flow\Entity
 */
class HolidayEntitlement {

	/**
	 * @var \In2code\In2timetape\Domain\Model\Employee
	 * @Flow\Validate(type="NotEmpty")
	 * @ORM\ManyToOne(inversedBy="holidayEntitlements")
	 */
	protected $employee;

	/**
	 * @var integer
	 * @Flow\Validate(type="NotEmpty")
	 * @Flow\Validate(type="Integer")
	 * @Flow\Validate(type="NumberRange", options={ "minimum"=2013, "maximum"=2050 })
	 * timetape: jahr
	 */
	protected $year;

	/**
	 * @var float
	 * @Flow\Validate(type="NotEmpty")
	 * @Flow\Validate(type="Float")
	 * timetape: urlaubsanspruchtage
	 */
	protected $daysEntitledThisYear;

	/**
	 * @var float
	 * @Flow\Validate(type="NotEmpty")
	 * @Flow\Validate(type="Float")
	 * timetape: restvorjahrtage
	 */
	protected $remainingDaysFromLastYear;

	/**
	 * @var float
	 * @Flow\Validate(type="NotEmpty")
	 * @Flow\Validate(type="Float")
	 * timetape: restvorjahrtageverfallen
	 */
	protected $daysExpiredFromLastYear;

	/**
	 * @var float
	 * @Flow\Validate(type="NotEmpty")
	 * @Flow\Validate(type="Float")
	 * timetape: urlaubgenommentage
	 */
	protected $daysTakenThisYear;

	/**
	 * Getters and Setters
	 */

	/**
	 * @return float
	 */
	public function getDaysEntitledThisYear() {
		return $this->daysEntitledThisYear;
	}

	/**
	 * @param float $daysEntitledThisYear
	 */
	public function setDaysEntitledThisYear($daysEntitledThisYear) {
		$this->daysEntitledThisYear = $daysEntitledThisYear;
	}

	/**
	 * @return float
	 */
	public function getDaysExpiredFromLastYear() {
		return $this->daysExpiredFromLastYear;
	}

	/**
	 * @param float $daysExpiredFromLastYear
	 */
	public function setDaysExpiredFromLastYear($daysExpiredFromLastYear) {
		$this->daysExpiredFromLastYear = $daysExpiredFromLastYear;
	}

	/**
	 * @return float
	 */
	public function getDaysTakenThisYear() {
		return $this->daysTakenThisYear;
	}

	/**
	 * @param float $daysTakenThisYear
	 */
	public function setDaysTakenThisYear($daysTakenThisYear) {
		$this->daysTakenThisYear = $daysTakenThisYear;
	}

	/**
	 * @return Employee
	 */
	public function getEmployee() {
		return $this->employee;
	}

	/**
	 * @param Employee $employee
	 */
	public function setEmployee($employee) {
		$this->employee = $employee;
	}

	/**
	 * @return float
	 */
	public function getRemainingDaysFromLastYear() {
		return $this->remainingDaysFromLastYear;
	}

	/**
	 * @param float $remainingDaysFromLastYear
	 */
	public function setRemainingDaysFromLastYear($remainingDaysFromLastYear) {
		$this->remainingDaysFromLastYear = $remainingDaysFromLastYear;
	}

	/**
	 * @return int
	 */
	public function getYear() {
		return $this->year;
	}

	/**
	 * @param int $year
	 */
	public function setYear($year) {
		$this->year = $year;
	}


}
