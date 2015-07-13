<?php
namespace In2code\In2timetape\Domain\Model;

use TYPO3\Flow\Annotations as Flow;
use Doctrine\ORM\Mapping as ORM;

/**
 * @Flow\Scope("prototype")
 * @Flow\Entity
 */
class HolidayRequest {

	/**
	 * @var string
	 * @Flow\Validate(type="NotEmpty")
	 *
	 * timetape: urlaubsantragid
	 */
	protected $requestId;

	/**
	 * @var \In2code\In2timetape\Domain\Model\Employee
	 * @Flow\Validate(type="NotEmpty")
	 * @ORM\ManyToOne(inversedBy="holidayRequests")
	 *
	 * timetape: mitarbeiterid
	 */
	protected $employee;

	/**
	 * @var string
	 *
	 * Name des Vertreters
	 *
	 * timetape: vertretungmitarbeiterid
	 */
	protected $replacement;

	/**
	 * @var \DateTime
	 * @Flow\Validate(type="NotEmpty")
	 *
	 * timetape: create
	 */
	protected $dateCreated;

	/**
	 * @var \DateTime
	 * @Flow\Validate(type="NotEmpty")
	 *
	 * timetape: update
	 */
	protected $dateUpdated;

	/**
	 * @var \DateTime
	 * @Flow\Validate(type="NotEmpty")
	 *
	 * timetape: urlaubvon
	 */

	protected $startDate;

	/**
	 * @var \DateTime
	 * @Flow\Validate(type="NotEmpty")
	 *
	 * timetape: urlaubbis
	 */
	protected $endDate;

	/**
	 * @var integer
	 * @Flow\Validate(type="Integer")
	 * @Flow\Validate(type="NumberRange", options={ "minimum"=10, "maximum"=300 })
	 *
	 * erlaubte Werte:
	 * 10: Urlaub beantragt
	 * 100: Urlaub freigegeben
	 * 200: Urlaub archiviert
	 * 300: Urlaub abgelehnt
	 *
	 * DE: Status des Urlaubsantrags
	 *
	 * timetape: urlaubstatus
	 */
	protected $status;

	/**
	 * @var float
	 * @Flow\Validate(type="NotEmpty")
	 * @Flow\Validate(type="Float")
	 * @Flow\Validate(type="NumberRange", options={ "minimum"=0, "maximum"=60 })
	 *
	 * timetape: urlaubstage
	 */
	protected $numberOfDays;


	/**
	 * Getters and Setters
	 */

	/**
	 * @return \DateTime
	 */
	public function getDateUpdated() {
		return $this->dateUpdated;
	}

	/**
	 * @param \DateTime $dateUpdated
	 */
	public function setDateUpdated($dateUpdated) {
		$this->dateUpdated = $dateUpdated;
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
	 * @return int
	 */
	public function getStatus() {
		return $this->status;
	}

	/**
	 * @param int $status
	 */
	public function setStatus($status) {
		$this->status = $status;
	}

	/**
	 * @return \DateTime
	 */
	public function getEndDate() {
		return $this->endDate;
	}

	/**
	 * @param \DateTime $endDate
	 */
	public function setEndDate($endDate) {
		$this->endDate = $endDate;
	}

	/**
	 * @return float
	 */
	public function getNumberOfDays() {
		return $this->numberOfDays;
	}

	/**
	 * @param float $numberOfDays
	 */
	public function setNumberOfDays($numberOfDays) {
		$this->numberOfDays = $numberOfDays;
	}

	/**
	 * @return string
	 */
	public function getReplacement() {
		return $this->replacement;
	}

	/**
	 * @param string $replacement
	 */
	public function setReplacement($replacement) {
		$this->replacement = $replacement;
	}

	/**
	 * @return string
	 */
	public function getRequestId() {
		return $this->requestId;
	}

	/**
	 * @param string $requestId
	 */
	public function setRequestId($requestId) {
		$this->requestId = $requestId;
	}

	/**
	 * @return \DateTime
	 */
	public function getStartDate() {
		return $this->startDate;
	}


	/**
	 * @param \DateTime $startDate
	 */
	public function setStartDate($startDate) {
		$this->startDate = $startDate;
	}

	/**
	 * @return \DateTime
	 */
	public function getDateCreated() {
		return $this->dateCreated;
	}

	/**
	 * @param \DateTime $dateCreated
	 */
	public function setDateCreated($dateCreated) {
		$this->dateCreated = $dateCreated;
	}


}
