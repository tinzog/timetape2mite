<?php
namespace In2code\In2timetape\Service;

use TYPO3\Flow\Annotations as TFlow;
use Exception;


/**
 * @TFlow\Scope("singleton")
 * Die Annotation "Flow" musste umbenannt werden, da es sonst Namenskonflikte gab
 *
 * Class Mite adapted from https://github.com/thomasklein/mite.php
 */
class Mite {


//Unverändert übernommene Konstanten aus der Utility-Klasse "Mite.php" von Thomas Klein

############
# PROPERTIES
#######	
	private $s_header;
	private $i_port;
	private $s_miteAccountUrl;
	private $s_protocolPrefix;


############
# CONSTANTS
#######	
	const MITE_PHP_VERSION = '1.2.3';
	const MITE_DOMAIN = 'mite.yo.lk';
	const REQUEST_TIMEOUT = 5;
	const EXCEPTION_RSRC_NOT_FOUND = 100;
	const EXCEPTION_UNPARSABLE_XML = 101;
	const EXCEPTION_WRONG_REQUEST_TYPE = 102;
	const EXCEPTION_MISSING_ACCOUNT_DATA = 103;
	const EXCEPTION_NO_ACCESS = 104;
	const EXCEPTION_TIMED_OUT = 105;
	const EXCEPTION_NO_SERVER_RESPONSE = 106;
	const EXCEPTION_UNEXPECTED_RESPONSE = 107;
	const EXCEPTION_CONNECTION_REFUSED = 108;


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


############
# METHODS
###########


	/*****************************************************
	 * Inits remote with mite account data and builds general request header
	 *
	 * @param string $s_apiKey
	 * @param string $s_accountUrl
	 * @param boolean $s_userAgent
	 * @param boolean $b_useSSLSocket
	 *
	 * @throws Exception
	 */
	public function init($s_apiKey, $s_accountUrl, $s_userAgent = false, $b_useSSLSocket = true) {

		if (!$s_apiKey || !$s_accountUrl) {
			throw new Exception('Error: Api key or account URL were missing!',
				self::EXCEPTION_MISSING_ACCOUNT_DATA);
			exit;
		}

		if (!$s_userAgent) {
			$s_userAgent = "mite.php/v" . self::MITE_PHP_VERSION;
		}

		$this->i_port = 80;
		$this->s_sslPrefix = '';
		$this->s_miteAccountUrl = urlencode($s_accountUrl) . "." . self::MITE_DOMAIN;

		if ($b_useSSLSocket) {
			$this->i_port = 443;
			$this->s_protocolPrefix = "ssl://";
		}

		$this->s_header = "Host: " . $this->s_miteAccountUrl . "\r\n" .
			"X-MiteApiKey: " . $s_apiKey . "\r\n" .
			"Content-type: application/xml\r\n" .
			"User-Agent: " . $s_userAgent . "\r\n";

	}//init


	/*****************************************************
	 * Sends a request to mite and stores possible response data in $this->o_responseXml
	 *
	 * @param string $s_httpMethod 'post', 'get', 'delete', 'put'
	 * @param string $s_rsrcName ; e.g. '/time_entries.xml', '/projects.xml', '/time_entries/12345.xml'
	 * @param string $s_requestData data for POST or PUT request
	 *
	 * @throws
	 * - EXCEPTION_UNPARSABLE_XML
	 * - EXCEPTION_WRONG_REQUEST_TYPE
	 * - EXCEPTION_CONNECTION_REFUSED
	 * - EXCEPTION_RSRC_NOT_FOUND
	 * - EXCEPTION_NO_ACCESS
	 * - EXCEPTION_TIMED_OUT
	 * - EXCEPTION_NO_SERVER_RESPONSE
	 * - EXCEPTION_UNEXPECTED_RESPONSE
	 *
	 * @return object (simplexml) is empty when using method 'delete' or 'put'
	 *
	 */
	public function sendRequest($s_httpMethod, $s_rsrcName, $s_requestData = '') {

		############
		# VARS
		#######
		/*
		 * @local objects and resources
		 */
		$o_context = $o_responseXml = $r_fs = null;
		/*
		 * @local arrays
		 */
		$a_lastPhpError = $a_rawResponse = $a_response = array();
		/*
		 * @local strings
		 */
		$s_fullUrl = $s_responsePart = $s_responseBody = $s_status = $s_request = $s_protocolPrefix = '';

		############
		# ACTION
		#######
		$s_httpMethod = strtoupper($s_httpMethod);
		$s_fullUrl = $this->s_miteAccountUrl . $s_rsrcName;

		# begin to form the request
		$s_request = "$s_httpMethod $s_rsrcName HTTP/1.0\n" .
			$this->s_header;

		switch ($s_httpMethod) {

			case 'POST':
			case 'PUT':
				$s_request .= "Content-Length: " . strlen($s_requestData) . "\r\n" .
					"Connection: close\n\n" .
					$s_requestData . "\n";

				break;

			case 'GET':
			case 'DELETE':
				$s_request .= "Connection: close\n\n";
				break;

			default:
				throw new Exception('Error: Passed request type ' . $s_httpMethod .
					' not available!', self::EXCEPTION_WRONG_REQUEST_TYPE);
		}

		$r_fs = @fsockopen($this->s_protocolPrefix . $this->s_miteAccountUrl,
			$this->i_port,
			$i_errno,
			$s_errstr,
			self::REQUEST_TIMEOUT);

		# if the socket connection failed - distinguish error cases
		if (!$r_fs) {

			# get last error message
			$a_lastPhpError = error_get_last();

			# if the connection couldn't get established; e.g. port problems
			if ($i_errno == 61) {
				throw new Exception('Connection refused: ' .
					'<em>' . $a_lastPhpError['message'] . '</em>',
					self::EXCEPTION_CONNECTION_REFUSED);
			} # also note unexpected error messages
			else {
				throw new Exception(
					'There was a problem when trying to access ' . $s_fullUrl . '.<br />' .
					'<em>' . $a_lastPhpError['message'] . '</em>',
					self::EXCEPTION_NO_ACCESS);
			}
		}

		# continue here, if there were no errors when trying to establish
		# the socket connection to the remote server
		else {

			# put request into stream
			fputs($r_fs, $s_request);

			$a_requestInfo = stream_get_meta_data($r_fs);

			if ($a_requestInfo['timed_out']) {
				throw new Exception('The connection timed out when trying to reach "' . $s_fullUrl . '".',
					self::EXCEPTION_TIMED_OUT);
			}

			# get response as an array by splitting it into lines
			$a_rawResponse = explode("\n", @stream_get_contents($r_fs));

			# close connection to avoid performance issues
			@fclose($r_fs);

			# in case of a "400 Bad Request"
			if (trim($a_rawResponse[0]) == '<html>') {
				throw new Exception('Bad request (400) when trying to access "' . $s_fullUrl . '".');
			}

			$s_responsePart = 'header';

			# separate response in header and body part
			foreach ($a_rawResponse as $s_line) {

				$s_line = trim($s_line);

				# don't consider empty lines or lines with only 0 as value
				if ($s_line == '' || $s_line == '0') continue;

				# check for the first xml line which separates
				# header and body part of the response
				if (strpos($s_line, "<?xml") !== FALSE) {
					$s_responsePart = 'body';
				}
				# check for and get the http status of the response
				if (strpos($s_line, "Status: ") !== FALSE) {
					$s_status = $s_line;
				}

				$a_response[$s_responsePart][] = $s_line;
			}

			# perform actions depending on the response status
			switch (trim($s_status)) {

				case 'Status: 401 Unauthorized':
					throw new Exception('Status code 401: ' .
						'You have no access to "' . $s_fullUrl . '". Please recheck the provided ' .
						'mite account data in your preferences. Maybe somehting has changed ' .
						'since your last visit?',
						self::EXCEPTION_NO_ACCESS);
					break;

				case 'Status: 404 Not Found':
					throw new Exception('Status code 404: ' .
						'Resource "' . $s_fullUrl . '" does not exist.',
						self::EXCEPTION_RSRC_NOT_FOUND);
					break;

				case 'Status: 302 Found':
					throw new Exception('Status code 302: ' .
						'Resource "' . $s_fullUrl . '" does not exist or was moved to another uri.',
						self::EXCEPTION_RSRC_NOT_FOUND);
					break;

				case 'Status: 500 Internal Server Error':
					throw new Exception('Status code 500: ' .
						'The server encountered an unexpected condition ' .
						'when trying to handle the request to "' . $s_fullUrl . '"');
					break;

				# Created - new resource created; returns the new resource as response
				case 'Status: 201 Created':
					# OK - if the resource was deleted returns nothing
					#	 - if a resource was requested returns the resource(-s) as response
				case 'Status: 200 OK':

					# nothing more to expect if a resource was deleted or updated
					if (($s_httpMethod == "DELETE") || ($s_httpMethod == "PUT"))
						break;

					# form response body
					$s_responseBody = trim(implode('', $a_response['body']));

					if (trim($s_responseBody) == '') {
						throw new Exception('Empty server response document for ' . $s_fullUrl,
							self::EXCEPTION_NO_SERVER_RESPONSE);
					}

					$o_responseXml = @simplexml_load_string($s_responseBody);

					if (!$o_responseXml) {

						$a_lastPhpError = error_get_last();

						throw new Exception('Could not parse resource "' . $s_fullUrl . '"<br />' .
							'<em>' . $a_lastPhpError['message'] . '</em>',
							self::EXCEPTION_UNPARSABLE_XML);
					}
					break;
				# error: an unexpected http status code was returned
				default:
					throw new Exception('The response for handling resource "' . $s_fullUrl . '" ' .
						'with method "' . $s_httpMethod . '" was not expected: ' .
						'<em>' . $s_status . '</em>.',
						self::EXCEPTION_UNEXPECTED_RESPONSE);
			}
		}

		return $o_responseXml;
	}//sendRequest


##############
# FUNCTIONS  #
##############
	/**
	 * @param string $entryID
	 * @return object
	 * @throws Exception
	 */
	public function getTimeEntry($entryID) {
		$result = $this->sendRequest('GET', '/time_entries/' . $entryID . '.xml');
		return $result;
	}

	/**
	 * @return object
	 * @throws Exception
	 */
	public function getTimeEntries() {
		$result = $this->sendRequest('GET', '/time_entries.xml');
		return $result;
	}

	public function getUsers() {
		$result = $this->sendRequest('GET', '/users.xml');
		return $result;
	}

	/**
	 * @param string $projectID
	 * @return object
	 * @throws Exception
	 */
	public function getTimeEntriesForProject($projectID) {
		$result = $this->sendRequest('GET', '/time_entries.xml?project_id=' . $projectID);
		return $result;
	}

	/**
	 * @param string $userID
	 * @return object
	 * @throws Exception
	 */
	public function getTimeEntriesForUser($userID) {
		$result = $this->sendRequest('GET', '/time_entries.xml?user_id=' . $userID);
		return $result;
	}

	/**
	 * @param string $userID
	 * @param string $day
	 * @throws Exception
	 * @return object
	 */
	public function getTodaysTimeEntriesForUser($userID) {
		$result = $this->sendRequest('GET', '/daily?user_id=' . $userID);
		return $result;
	}

	/**
	 * @param string $userID
	 * @param string $day
	 * @throws Exception
	 * @return object
	 */
	public function buildDeleteEntriesRequest($userID, $day) {
		$result = 'https://' . $this->settings['mite']['url'] . '.' . self::MITE_DOMAIN . '/time_entries.xml?api_key=' . $this->settings['mite']['apiKey'] . '&date-at=' . $day . '&user_id=' . $userID;
		return $result;
	}

	/**
	 * @param $date
	 * @param $minutes
	 * @param $projectId
	 * @param $service_id
	 * @param $user_id
	 * @param $note
	 * @return object
	 * @throws Exception
	 */

	public function createHolidayTimeEntry($date, $minutes, $projectId, $service_id, $user_id, $note) {
		if ($this->settings['mite']['lockTimeEntries']) {
			$lock = 'true';
		} else $lock = 'false';
		$requestBody = "<time-entry><date-at>$date</date-at><minutes>$minutes</minutes><project-id>$projectId</project-id><service-id>$service_id</service-id><user-id>$user_id</user-id><note>$note</note><locked>$lock</locked></time-entry>";
		$result = $this->sendRequest('POST', '/time_entries.xml', $requestBody);
		return $result;
	}

	/**
	 * @param string $entryID
	 * @return object
	 * @throws Exception
	 */
	public function forceDeleteEntry($entryID) {
		//Da Einträge als "locked" markiert sind, muss apiKey noch einmal im Request mitgesendet werden
		$result = $this->sendRequest('DELETE', '/time_entries/' . $entryID . '.xml?time_entry[force]=true&api_key=' . $this->settings['mite']['apiKey'] . '&force=true');
		return $result;
	}

}
