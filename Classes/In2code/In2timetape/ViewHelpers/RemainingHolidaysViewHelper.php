<?php
namespace In2code\In2timetape\ViewHelpers;

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3\Flow\I18n;


class RemainingHolidaysViewHelper extends AbstractViewHelper {


	/**
	 * @param \In2code\In2timetape\Domain\Model\HolidayEntitlement $holidayEntitlement
	 * @return float
	 */
	public function render($holidayEntitlement) {

		$remainingDays = $holidayEntitlement->getDaysEntitledThisYear() + $holidayEntitlement->getRemainingDaysFromLastYear() - $holidayEntitlement->getDaysTakenThisYear() - $holidayEntitlement->getDaysExpiredFromLastYear();

		return number_format($remainingDays, 1, ',', '.');
	}
}
