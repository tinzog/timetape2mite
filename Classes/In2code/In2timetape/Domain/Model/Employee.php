<?php
namespace In2code\In2timetape\Domain\Model;

use TYPO3\Flow\Annotations as Flow;
use Doctrine\ORM\Mapping as ORM;

/**
 * @Flow\Scope("prototype")
 * @Flow\Entity
 */
class Employee {

    /**
     * @var string
     * @Flow\Validate(type="NotEmpty")
     * timetape: vorn
     */
    protected $firstName;

    /**
     * @var string
     * @Flow\Validate(type="NotEmpty")
     * timetape: nachn
     */
    protected $lastName;

    /**
     * @var string
     * @Flow\Validate(type="NotEmpty")
     * @Flow\Validate(type="EmailAddress")
     *
     * timetape: emailadresse
     */
    protected $email;

    /**
     * @var string
     * @Flow\Validate(type="NotEmpty")
     *
     * timetape: mitarbeiterid
     */
    protected $timetapeId;

    /**
     * @var string
     * @Flow\Validate(type="NotEmpty")
     *
     * timetape: abteilung
     */
    protected $department;

    /**
     * @var \Doctrine\Common\Collections\Collection<\In2code\In2timetape\Domain\Model\HolidayEntitlement>
     * @ORM\OneToMany(mappedBy="employee")
     */
    protected $holidayEntitlements;

    /**
     * @var \Doctrine\Common\Collections\Collection<\In2code\In2timetape\Domain\Model\HolidayRequest>
     * @ORM\OneToMany(mappedBy="employee")
     */
    protected $holidayRequests;

    /**
     * @var bool
     */
    protected $active;

    /**
     * @return boolean
     */
    public function isActive() {
        return $this->active;
    }

    /**
     * @param boolean $active
     */
    public function setActive($active) {
        $this->active = $active;
    }



    /**
     * Getters and Setters
     */

    /**
     * @return string
     */
    public function getTimetapeId() {
        return $this->timetapeId;
    }

    /**
     * @param string $timetapeId
     */
    public function setTimetapeId($timetapeId) {
        $this->timetapeId = $timetapeId;
    }

    /**
     * @return string
     */
    public function getDepartment() {
        return $this->department;
    }

    /**
     * @param string $department
     */
    public function setDepartment($department) {
        $this->department = $department;
    }

    /**
     * @return string
     */
    public function getEmail() {
        return $this->email;
    }

    /**
     * @param string $email
     */
    public function setEmail($email) {
        $this->email = $email;
    }

    /**
     * @return string
     */
    public function getFirstName() {
        return $this->firstName;
    }

    /**
     * @param string $firstName
     */
    public function setFirstName($firstName) {
        $this->firstName = $firstName;
    }

    /**
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getHolidayEntitlements() {
        return $this->holidayEntitlements;
    }

    /**
     * @param \Doctrine\Common\Collections\Collection $holidayEntitlements
     */
    public function setHolidayEntitlements($holidayEntitlements) {
        $this->holidayEntitlements = $holidayEntitlements;
    }

    /**
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getHolidayRequests() {
        return $this->holidayRequests;
    }

    /**
     * @param \Doctrine\Common\Collections\Collection $holidayRequests
     */
    public function setHolidayRequests($holidayRequests) {
        $this->holidayRequests = $holidayRequests;
    }

    /**
     * @return string
     */
    public function getLastName() {
        return $this->lastName;
    }

    /**
     * @param string $lastName
     */
    public function setLastName($lastName) {
        $this->lastName = $lastName;
    }



}
