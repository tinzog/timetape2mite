<?php
namespace In2code\In2timetape\Command;

use DateTime;
use Exception;
use In2code\In2timetape\Domain\Model\Employee as Employee;
use In2code\In2timetape\Domain\Model\HolidayEntitlement;
use In2code\In2timetape\Domain\Model\HolidayRequest as HolidayRequest;
use TYPO3\Flow\Annotations as Flow;
use SimpleXMLElement;
use In2code\In2timetape\Service\Notification as Notification;

/**
 * @Flow\Scope("singleton")
 */
class TimetapeCommandController extends \TYPO3\Flow\Cli\CommandController {

    /**
     * @Flow\Inject
     * @var \In2code\In2timetape\Service\Mite
     *
     * Service-Klasse für die Mite-Aufrufe
     */
    protected $mite;

    /**
     * @Flow\Inject
     * @var \In2code\In2timetape\Utility\Util
     *
     * Utility-Klasse für Berechnungen etc.
     */
    protected $util;

    /**
     *
     * @Flow\Inject
     * @var Notification
     *
     * Service-Klasse für den Email-Versand
     */
    protected $notificationService;

    /**
     * Standalone Template für den Mailversand
     *
     * @Flow\Inject
     * @var \TYPO3\Fluid\View\StandaloneView
     */
    protected $standaloneView;

    /**
     *
     * @Flow\Inject
     * @var \In2code\In2timetape\Domain\Repository\EmployeeRepository
     */
    protected $employeeRepository;

    /**
     *
     * @Flow\Inject
     * @var \TYPO3\Flow\Persistence\PersistenceManagerInterface
     */
    protected $persistenceManager;

    /**
     * @var array
     */
    protected $settings;

    /**
     * Inject the settings
     *
     * @param array $settings
     * @return void
     */
    public function injectSettings(array $settings) {
        $this->settings = $settings;
    }

    /**
     * Get settings
     *
     * @return array
     */
    public function getSettings() {
        return $this->settings;
    }

    /**
     * @return void
     * @param string $date
     *
     * Dieser Command schreibt die Zeiten aus Timetape für den angegebenen Tag aus Timetape nach Mite,
     * sofern ein Urlaubstag, Feiertag oder Freier Tag vorliegen
     * Bei Aufruf mit Datumsparameter wird der angegebene Tag verwendet, ansonsten der aktuellle (heutige) Tag
     *
     * Aufruf mit Parameter:
     * ./flow timetape:writetomite --date 2014-12-12
     * Aufruf ohne Parameter:
     * ./flow timetape:writetomite
     */
    public function writeToMiteCommand($date = null) {

        try {
            $this->initMite();
        } catch (Exception $ex) {
            $this->giveErrorMessage($ex);
            return;
        }

        if ($date) {
            $dateFormat = 'Y-m-d';
            if ($this->util->validateDate($dateFormat, $date)) {
                $datestring = $date;
            } else {
                $this->outputLine('Please enter a valid date (format: Y-m-d)!');
                return;
            }
        } else {
            //heutiges Datum;
            $datestring = date('Y-m-d');
        }

        try {
            $xml = $this->parseTimetapeData($datestring, $datestring);
        } catch (Exception $ex) {
            $this->giveErrorMessage($ex);
            return;
        }

        $responses = Array();
        $mitarbeiterHolidays = Array();

        /**
         * @var int $counter   Zähler für die gelöschten Einträge
         */
        $counter = 0;

        foreach ($xml->mitarbeiter as $mitarbeiter) {
            //Initialisieren und Zurücksetzen der Benutzerdaten
            $email = null;
            $user_Id = null;


            $email = (string)$mitarbeiter->emailadresse;
            //Überprüfen, ob der Timetape-Nutzer einen Mite-Account mit derselben email-Adresse hat
            try {
                $user_Id = $this->findMiteUserByMail($email);
            } catch (Exception $ex) {
                $this->giveErrorMessage($ex);
                return;
            }
            //Wenn der Timetape-User keinen zugehörigen Mite-Account hat, wird dieser übersprungen
            if ($user_Id) {
            //Entfernen der für diesen Tag bereits von Timetape gesetzten Einträge

                foreach ($mitarbeiter->tagelist->tag as $tag) {

                    //es werden nur die Zeiteinträge für den angegebenen Tag untersucht

                    if ((string)$tag->datum == $datestring) {
                        foreach ($tag->zeitenlist->zeit as $zeit) {
                            try {
                                $minutes = $this->util->calculateDurationInMinutes($zeit->zeitvon, $zeit->zeitbis);
                            } catch (Exception $ex) {
                                $this->giveErrorMessage($ex);
                                break;
                            }

                            switch ((string)$zeit->typ) {
                                case 'URLAUB':
                                case 'URLAUB_HALB_1':
                                case 'URLAUB_HALB_2':
                                    if ($this->settings['mite']['writeUrlaubEntries']) {
                                        $note = $this->settings['mite']['noteUrlaub'];
                                        try {
                                            $responses[] = $this->postHolidayToMite($datestring, $user_Id, $minutes, $note);
                                            $mitarbeiterHolidays[] = $note . ": " . $email;
                                            $this->outputLine($datestring . ': ' . $note . ', Time entry for ' . $email . ', minutes: ' . $minutes);
                                        } catch (Exception $ex) {
                                            $message = $ex->getMessage();
                                            $this->outputLine('Exception: ' . $message);
                                            $this->notificationService->sendNotificationMessage('Error Timetape2Mite', $message);
                                            return;
                                        }
                                    }
                                    break;
                                case 'FEIERTAG':
                                case 'FEIERTAG_HALB_1':
                                case 'FEIERTAG_HALB_2':
                                    if ($this->settings['mite']['writeFeiertagEntries']) {
                                        $note = $this->settings['mite']['noteFeiertag'];
                                        try {
                                            $responses[] = $this->postBankHolidayToMite($datestring, $user_Id, $minutes, $note);
                                            $mitarbeiterHolidays[] = $note . ": " . $email;
                                            $this->outputLine($datestring . ': ' . $note . ', Time entry for ' . $email . ', minutes: ' . $minutes);
                                        } catch (Exception $ex) {
                                            $this->giveErrorMessage($ex);
                                            return;
                                        }
                                    }
                                    break;
                                case 'FREIERTAG':
                                case 'FREIERTAG_HALB_1':
                                case 'FREIERTAG_HALB_2':
                                    if ($this->settings['mite']['writeFreiertagEntries']) {
                                        $note = $this->settings['mite']['noteFreiertag'];
                                        try {
                                            $responses[] = $this->postBankHolidayToMite($datestring, $user_Id, $minutes, $note);
                                            $mitarbeiterHolidays[] = $note . ": " . $email;
                                            $this->outputLine($datestring . ': ' . $note . ', Time entry for ' . $email . ', minutes: ' . $minutes);
                                        } catch (Exception $ex) {
                                            $this->giveErrorMessage($ex);
                                            return;
                                        }
                                    }
                                    break;
                                default:
                                    break;
                            }
                        }
                    }
                }
            }
        }
        $today = date('Y-m-d H:i:s');
        if ($mitarbeiterHolidays) {
            $message = implode(", ", $mitarbeiterHolidays);
            if ($this->settings['mail']['sendNewEntryMail']) {
                $this->outputLine('Number of deleted previous entries: '.$counter);
                $this->notificationService->sendNotificationMessage($this->settings['mail']['newEntriesSubject'], $today . ': ' . $message);
            }
        } else {
            $this->outputLine('No Timetape entry to be written to Mite');
            if ($this->settings['mail']['sendNoEntryMail']) {
                $this->notificationService->sendNotificationMessage($this->settings['mail']['noEntriesSubject'], $today . ': ' . $this->settings['mail']['noEntriesMessage']);
            }
        }
        return;
    }

    /**
     * @return void
     * @return string date
     *
     * Dieser Command schreibt Daten für Urlaubsanträge und Urlaubsansprüche aus Timetape in die Datenbank
     * Es werden über die Timetape-Api alle bisher gespeicherten Urlaubsanträge /- ansprüche ausgegeben
     * Ein Setzen des Datums hat keinen Einfluss auf die Ausgabe, deshalb gibt es hier keinen Datumsparameter
     *
     * Aufruf ohne Parameter
     * ./flow timetape:savetimetapedata
     */
    public function saveTimetapeDataCommand() {
        //Einlesen der Timetape-Daten  ohne Datumsparameter
        try {
            $xml = $this->parseTimetapeData();
        } catch (Exception $ex) {
            $this->giveErrorMessage($ex);
            return;
        }

        foreach ($xml->mitarbeiter as $mitarbeiter) {
            try {
                $this->saveEmployeeIfNotExists($mitarbeiter);
            } catch (Exception $ex) {
                $this->giveErrorMessage($ex);
                return;
            }
            /**
             * @var SimpleXMLElement $urlaubsantrag
             * @var SimpleXMLElement $mitarbeiter
             */
            foreach ($mitarbeiter->urlaubsantraegelist->urlaubsantrag as $urlaubsantrag) {

                try {
                    $this->saveHolidayRequest($mitarbeiter, $urlaubsantrag);
                } catch (Exception $ex) {
                    $this->giveErrorMessage($ex);
                    return;
                }
            }
            /**
             * @var SimpleXMLElement $urlaubsanspruch
             * @var SimpleXMLElement $mitarbeiter
             */
            foreach ($mitarbeiter->urlaubsanspruchlist->urlaubsanspruch as $urlaubsanspruch) {

                try {
                    $this->saveHolidayEntitlement($mitarbeiter, $urlaubsanspruch);
                } catch (Exception $ex) {
                    $this->giveErrorMessage($ex);
                    return;
                }
            }
        }
        $datestring = date('Y-m-d H:i:s');
        $this->outputLine($datestring . ': Data saved successfully');
        if ($this->settings['mail']['sendSavedMail']) {
            $this->notificationService->sendNotificationMessage($this->settings['mail']['savedDataSubject'], $datestring . ': ' . $this->settings['mail']['savedDataMessage']);
        }
    }

    public function sendAccountantMailCommand() {

        $year = date('Y');
        $datetime = date('d.m.Y H:i:s');

        $templatepath = $this->settings['mail']['emailTemplate'];
        $this->standaloneView->setFormat('html');
        $this->standaloneView->setTemplatePathAndFilename($templatepath);

        $employees = $this->employeeRepository->findAll();

        $entitlements = Array();

        foreach ($employees as $employee) {
            //Nur aktive Mitarbeiter werden berücksichtigt
            //aktiv bedeutet derzeit, dass kein Austrittsdatum eingetragen ist,
            //weil in der Timetape-Api das Feld "active" nicht verfügbar ist
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
        $this->standaloneView->assign('year', $year);
        $this->standaloneView->assign('date', $datetime);
        $this->standaloneView->assign('entitlements', $entitlements);
        $body = $this->standaloneView->render();

        $this->outputLine('Mail sent to accountant');

        $this->notificationService->sendNotificationToAccountant($this->settings['mail']['accountantMailSubject'], $body);
    }


###########################
#     Hilfsfunktionen
###########################


    /**
     * @param $von string Anfangsdatum für die Zeitenträge im Format 'Y-m-d'
     * @param $bis string Enddatum für die Zeitenträge im Format 'Y-m-d'
     * @throws Exception
     * @return \SimpleXMLElement $xml Die Daten der Timetape-Api im gewählten Zeitraum
     *
     */
    public function parseTimetapeData($von = null, $bis = null) {
        //erster Schritt: Verbindung mit der Timetape-Api und Einlesen des XML
        $requestParameter = '';
        if ($von && $bis) {
            $requestParameter = '&von=' . $von . '&bis=' . $bis;
        }

        // Für das Laden des XML wird über den Stream Context ein timeout von 5 Sekunden gesetzt
        // Falls die Daten nicht innterhalb dieses Zeitraums geholt werden können, wird eine Exception geworfen
        try {
            $context = stream_context_create(array('https' => array('timeout' => 5)));
            $timetapeApi = $this->settings['timetape']['api'];

            $file = file_get_contents($timetapeApi . $requestParameter, false, $context);

            //XML im SimpleXML-Format für den Rückgabewert
            $xml = simplexml_load_string((string)($file));

            if (!($xml)) {
                throw new Exception('Error: Timetape-XML could not be loaded');
            }
        } catch (Exception $ex) {
            $xml = null;
            throw new Exception('Error: Parsing of Timetape-XML failed.'. $ex->getMessage());
        }
        //zweiter Schritt: Validierung des geladenen XML gegen das XSD
        $xsdScheme = $this->settings['timetape']['pathToTimetapeXsd'];
        $dom = new \DOMDocument();
        if (!$dom->loadXML($file)) {
            throw new Exception ('Error: Timetape-XML could not be loaded');
        };
        //Validierung gegen das gegebene Schema
        if (!$dom->schemaValidate($xsdScheme)) {
            throw new Exception ('Error: Timetape-XML is not valid');
        }
        return $xml;
    }


    /**
     * @param $ex Exception
     * @return void
     * @throws Exception
     */
    public function giveErrorMessage($ex) {
        $message = $ex->getMessage();
        $this->outputLine('Exception: ' . $message);
        if ($this->settings['mail']['sendErrorMails']) {
            $this->notificationService->sendNotificationMessage($this->settings['mail']['errorsSubject'], $message);
        }
    }

###########################
#     Funktionen für Mite
###########################

    /**
     * @throws Exception
     * @return void
	 *
     * Initialisierung des Mite-Services*
     */
    public function initMite() {
        try {
            $apiKey = $this->settings['mite']['apiKey'];
            $url = $this->settings['mite']['url'];
            $userAgent = $this->settings['mite']['userAgent'];
            $this->mite->init($apiKey, $url, $userAgent, true);
        } catch (Exception $ex) {
            throw new Exception('Error: Initialization of Mite failed: ' . $ex->getMessage());
        }
    }

    /**
     * @param string $userEmail
     * @throws Exception
     * @return integer $userId
     *
     * Für eine übergebene Emailadresse wird die entsprechende Mite-ID zurück gegeben, sofern vorhanden
     */
    public function findMiteUserByMail($userEmail) {

        try {
            $users = $this->mite->getUsers();
            if (!$users) {
                throw new Exception('Error: Mite users could not be found');
            } else {
                foreach ($users->user as $user) {
                    if ($user->email == $userEmail) {
                        return $user->id;
                    }
                }
                return null;
            }
        } catch (Exception $ex) {
            throw new Exception ($ex->getMessage());
        }
    }

    /**
     * @param string $date Datum im Format 'YYYY-mm-dd'
     * @param integer $minutes Dauer des Urlaubs in Minuten
     * @param string $user_id ID des Timetape-Nutzers
     * @param string $note Bemerkungsfeld
     * @return SimpleXMLElement $response
     * @throws Exception
     */
    public function postHolidayToMite($date, $user_id, $minutes, $note) {
        $response = null;
        try {
            $urlaubProjectId = $this->settings['mite']['urlaubProjectId'];
            $abwesenheitServiceId = $this->settings['mite']['abwesenheitServiceId'];
            $response = $this->mite->createHolidayTimeEntry($date, $minutes, $urlaubProjectId, $abwesenheitServiceId, $user_id, $note);
        } catch (Exception $ex) {
            throw new Exception ('Error: holiday time entry failed for user:  ' . $user_id . ':' . $ex->getMessage());
        }
        return $response;
    }

    /**
     * @param string $date Datum im Format 'YYYY-mm-dd'
     * @param integer $minutes Dauer des Urlaubs in Minuten
     * @param string $user_id ID des Timetape-Nutzers
     * @param string $note Bemerkungsfeld
     * @return SimpleXMLElement $response
     * @throws Exception
     *
     * Für Feiertage und Freie Tage werden in Mite dieselben Projekt/Service-Ids verwendent,
     * deshalb wird beim Eintragen in Mite nicht zwischen Feiertagen und Freien Tagen unterschieden
     */
    public function postBankHolidayToMite($date, $user_id, $minutes, $note) {
        $response = null;
        try {
            $feiertagProjectId = $this->settings['mite']['feiertagProjectId'];
            $abwesenheitServiceId = $this->settings['mite']['abwesenheitServiceId'];
            $response = $this->mite->createHolidayTimeEntry($date, $minutes, $feiertagProjectId, $abwesenheitServiceId, $user_id, $note);
        } catch (Exception $ex) {
            throw new Exception ('Error: bank holiday time entry failed for user:   ' . $user_id . ':' . $ex->getMessage());
        }
        return $response;
    }


	/**
	 * @param string $user_Id
	 * @param string $date
	 * @return int $counter
	 * @throws Exception
	 */
    public function deleteOldTimetapeEntries($user_Id, $date){

        $this->initMite();

        // Für das Laden des XML wird über den Stream Context ein timeout von 5 Sekunden gesetzt
        // Falls die Daten nicht innterhalb dieses Zeitraums geholt werden können, wird eine Exception geworfen
        try {
            $context = stream_context_create(array('https' => array('timeout' => 5)));
            $requestURL = $this->mite->buildDeleteEntriesRequest($user_Id, $date);
            //Zeiteinträge in Mite für den angegebenen Nutzer und Tag
            $file = file_get_contents($requestURL, false, $context);

            /**
             * @var SimpleXMLElement $timeEntries
             */
            $timeEntries = simplexml_load_string((string)($file));

            if (!($timeEntries)) {
                throw new Exception('Error: Time entries could not be loaded');
            }
        } catch (Exception $ex) {
            $timeEntries = null;
            throw new Exception('Error: Parsing of Timetape-XML failed.'. $ex->getMessage());
        }

        //Alte Einträge aus Timetape löschen
        $counter = 0;
        foreach ($timeEntries as $timeEntry){
            if (((string)$timeEntry->note == $this->settings['mite']['noteUrlaub']) ||
                ((string)$timeEntry->note == $this->settings['mite']['noteFeiertag']) ||
                ((string)$timeEntry->note == $this->settings['mite']['noteFreiertag']))
            {
                $id = $timeEntry->id;
                $this->mite->forceDeleteEntry($id);
                /** @var int $counter */
                $counter++;
            }
        }
        return $counter;
    }

###########################
#     Funktionen für Datensicherung
###########################
    /**
     *
     * @param string $emloyeeId
     * @param SimpleXMLElement $mitarbeiter
     * @return void
     * @throws Exception
     *
     * Aktualisiert bestehende Mitarbeiter und legt neue Mitarbeiter an, falls sie noch nicht existieren
     *
     */

    public function saveEmployeeIfNotExists($mitarbeiter) {

        $employee = $this->employeeRepository->findOneByTimetapeId((string)$mitarbeiter->mitarbeiterid);

        if (!$employee) {
            $employee = new Employee();
            $employee->setTimetapeId((string)$mitarbeiter->mitarbeiterid);
            /**
             * @var \In2code\In2timetape\Domain\Model\Employee $newEmployee
             */
            $employee->setFirstName((string)$mitarbeiter->vorn);
            $employee->setLastName((string)$mitarbeiter->nachn);
            $employee->setEmail((string)$mitarbeiter->emailadresse);
            $employee->setDepartment((string)$mitarbeiter->abteilung);
            if ((string)$mitarbeiter->austritt == '0000-00-00') {
                $employee->setActive(true);
            } else {
                $employee->setActive(false);
            }
            try {
                //Mitarbeiter neu anlegen: add
                $this->persistenceManager->add($employee);
                $this->persistenceManager->persistAll();
            } catch (Exception $ex) {
                throw new Exception ('Error: employee ' . $mitarbeiter->emailadresse . ' could not be saved. ' . $ex->getMessage());
            }
        }
        else {
            /**
             * @var \In2code\In2timetape\Domain\Model\Employee $newEmployee
             */
            $employee->setFirstName((string)$mitarbeiter->vorn);
            $employee->setLastName((string)$mitarbeiter->nachn);
            $employee->setEmail((string)$mitarbeiter->emailadresse);
            $employee->setDepartment((string)$mitarbeiter->abteilung);
            if ((string)$mitarbeiter->austritt == '0000-00-00') {
                $employee->setActive(true);
            } else {
                $employee->setActive(false);
            }
            try {
                //Mitarbeiter aktualisieren: update
                $this->persistenceManager->update($employee);
                $this->persistenceManager->persistAll();
            } catch (Exception $ex) {
                throw new Exception ('Error: employee ' . $mitarbeiter->emailadresse . ' could not be saved. ' . $ex->getMessage());
            }
        }
    }


    /**
     *
     * @param SimpleXMLElement $urlaubsantrag
     * @param SimpleXMLElement $mitarbeiter
     * @return void
     * @throws Exception
     *
     */
    public function saveHolidayRequest($mitarbeiter, $urlaubsantrag) {

        /**
         * @var \In2code\In2timetape\Domain\Model\Employee $employee
         */
        $employee = $this->employeeRepository->findOneByTimetapeId((string)$mitarbeiter->mitarbeiterid);
        /**
         * @var \Doctrine\Common\Collections\Collection<\In2code\In2timetape\Domain\Model\HolidayRequest> $holidayRequests
         */
        $holidayRequests = $employee->getHolidayRequests();

        if ($holidayRequests) {
            foreach ($holidayRequests as $holidayRequest) {

                /**
                 * @var \In2code\In2timetape\Domain\Model\HolidayRequest $holidayRequest
                 */
                if ((string)$holidayRequest->getRequestId() == (string)$urlaubsantrag->urlaubsantragid) {
                    //Falls Antrag bereits vorhanden, wird er gelöscht und neu geschrieben
                    $employee->getHolidayRequests()->clear($holidayRequest);
                }
            }
        }

        $dateFormat = 'Y-m-d H:i:s';

        $holidayRequest = new HolidayRequest();
        if (!($this->util->validateDate($dateFormat, $urlaubsantrag->create)) &&
            ($this->util->validateDate($dateFormat, $urlaubsantrag->update)) &&
            ($this->util->validateDate($dateFormat, $urlaubsantrag->urlaubvon)) &&
            ($this->util->validateDate($dateFormat, $urlaubsantrag->urlaubbis)))
        {

            throw new Exception ('Error: Invalid date format!');
        }

        $holidayRequest->setDateCreated(DateTime::createFromFormat($dateFormat, (string)$urlaubsantrag->create));
        $holidayRequest->setDateUpdated(DateTime::createFromFormat($dateFormat, (string)$urlaubsantrag->update));
        $holidayRequest->setStartDate(DateTime::createFromFormat($dateFormat, (string)$urlaubsantrag->urlaubvon));
        $holidayRequest->setEndDate(DateTime::createFromFormat($dateFormat, (string)$urlaubsantrag->urlaubbis));
        $holidayRequest->setEmployee($employee);
        /**
         * @var \In2code\In2timetape\Domain\Model\Employee $replacement
         */
        $replacement = $this->employeeRepository->findOneByTimetapeId((string)$urlaubsantrag->vertretungmitarbeiterid);


        if ($replacement) {
            $name = ''. $replacement->getFirstName() .' '. $replacement->getLastName();
            $holidayRequest->setReplacement($name);
        } else {
            $holidayRequest->setReplacement('');
        }
        $holidayRequest->setNumberOfDays((float)$urlaubsantrag->urlaubstage);
        $holidayRequest->setRequestId((string)$urlaubsantrag->urlaubsantragid);
        $holidayRequest->setStatus((string)$urlaubsantrag->urlaubstatus);
        try {
            $this->persistenceManager->add($holidayRequest);
            $this->persistenceManager->persistAll();
        } catch (Exception $ex) {
            throw new Exception ('Error: Holiday request could not be saved. ' . $ex->getMessage());
        }
    }

    /**
     *
     * @param SimpleXMLElement $urlaubsanspruch
     * @param SimpleXMLElement $mitarbeiter
     * @return void
     * @throws Exception
     *
     */
    public function saveHolidayEntitlement($mitarbeiter, $urlaubsanspruch) {

        /**
         * @var \In2code\In2timetape\Domain\Model\Employee $employee
         */
        $employee = $this->employeeRepository->findOneByTimetapeId((string)$mitarbeiter->mitarbeiterid);
        /**
         * @var \Doctrine\Common\Collections\Collection<\In2code\In2timetape\Domain\Model\HolidayRequest> $holidayRequests
         */
        $holidayEntitlements = $employee->getHolidayEntitlements();

        if ($holidayEntitlements) {
            foreach ($holidayEntitlements as $holidayEntitlement) {
                /**
                 * @var \In2code\In2timetape\Domain\Model\HolidayEntitlement $holidayEntitlement
                 */
                if ((string)$holidayEntitlement->getYear() == (string)$urlaubsanspruch->jahr) {
                    //Falls Antrag bereits vorhanden, wird er gelöscht und neu geschrieben
                    $employee->getHolidayEntitlements()->clear($holidayEntitlement);
                }
            }
        }

        $holidayEntitlement = new HolidayEntitlement();

        $holidayEntitlement->setEmployee($employee);
        $holidayEntitlement->setDaysEntitledThisYear((float)$urlaubsanspruch->urlaubsanspruchtage);
        $holidayEntitlement->setRemainingDaysFromLastYear((float)$urlaubsanspruch->restvorjahrtage);
        $holidayEntitlement->setDaysExpiredFromLastYear((float)$urlaubsanspruch->restvorjahrtageverfallen);
        $holidayEntitlement->setDaysTakenThisYear((float)$urlaubsanspruch->urlaubgenommentage);
        $holidayEntitlement->setYear((int)$urlaubsanspruch->jahr);
        try {
            $this->persistenceManager->add($holidayEntitlement);
            $this->persistenceManager->persistAll();
        } catch (Exception $ex) {
            throw new Exception ('Error: Holiday request could not be saved. ' . $ex->getMessage());
        }
    }
}
