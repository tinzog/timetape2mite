<?php
namespace In2code\In2timetape\ViewHelpers;

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3\Flow\I18n;


class RequestStatusViewHelper extends AbstractViewHelper {


	/**
	 * @param int $status
	 *
	 * erlaubte Werte:
	 * 10: Urlaub beantragt
	 * 100: Urlaub freigegeben
	 * 200: Urlaub archiviert
	 * 300: Urlaub abgelehnt
	 *
	 * @return string  Status als Text
	 *
	 */
	public function render($status) {

		if ($status == 10) {
			return 'beantragt';
		} else if ($status == 100) {
			return 'freigegeben';
		} else if ($status == 200) {
			return 'archiviert';
		} else if ($status == 300) {
			return 'abgelehnt';
		} else {
			return 'n.d.';
		}

	}
}
