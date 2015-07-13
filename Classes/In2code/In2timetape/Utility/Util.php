<?php
/**
 * Created by PhpStorm.
 * User: Tine2
 * Date: 25.01.2015
 * Time: 19:45
 */

namespace In2code\In2timetape\Utility;

use DateTime;


class Util {


	/**
	 * @param string $von Anfangszeitpunkt im Format HH:mm:ss
	 * @param string $bis Endzeitpunkt im Format HH:mm:ss
	 * @throws Exception
	 * @return integer $durationInMinutes Dauer in Minuten
	 *
	 * Rückgabewert bei ungültiger Eingabe: -1
	 */
	public function calculateDurationInMinutes($von, $bis) {
		$startTime = strtotime($von);
		$endTime = strtotime($bis);
		if ($endTime < $startTime) {
			throw new Exception ('Ungültiger Zeitraum: Endzeitpunkt liegt vor Anfangszeitpunkt');
		}
		$durationInMinutes = round(($endTime - $startTime) / 60);

		return (integer)$durationInMinutes;

	}

	/**
	 * @param string $date Zu überprüfender Datumsstring
	 * @param string $format Datumsformat
	 * @return bool
	 *
	 * Prüft, ob ein Datumsstring ein gültiges Datum enthält und dem übergebenen Format entspricht
	 */
	public function validateDate($format, $date) {
		$d = DateTime::createFromFormat($format, $date);
		return $d && $d->format($format) == $date;
	}
} 
