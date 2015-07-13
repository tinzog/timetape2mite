<?php

namespace In2code\In2timetape\Service;


use TYPO3\Flow\Annotations as Flow;
use Exception;

class Notification {


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
	 * @param string $subject
	 * @param string $body
	 * @throws Exception
	 */
	public function sendNotificationMessage($subject, $body) {
		try {
			$mail = new \TYPO3\SwiftMailer\Message();
			$senderMail = $this->settings['mail']['senderMail'];
			$senderName = $this->settings['mail']['senderName'];
			$recipientMail = $this->settings['mail']['recipientMail'];
			$recipientName = $this->settings['mail']['recipientName'];
			$ccRecipientMail = $this->settings['mail']['ccRecipientMail'];
			$ccRecipientName = $this->settings['mail']['ccRecipientName'];
			$mail->setFrom(array($senderMail => $senderName))
				->setTo(array($recipientMail => $recipientName))
				->setCc(array($ccRecipientMail => $ccRecipientName))
				->setSubject($subject)
				->setBody($body, 'text/html')
				->send();
		} catch (Exception $ex) {
			throw new Exception ('Error: Notification could not be sent. ' . $ex->getMessage());
		}
	}

	/**
	 * @param string $subject
	 * @param string $body
	 * @throws Exception
	 */
	public function sendNotificationToAccountant($subject, $body) {
		try {
			$mail = new \TYPO3\SwiftMailer\Message();
			$senderMail = $this->settings['mail']['senderMail'];
			$senderName = $this->settings['mail']['senderName'];
			$accountantMail = $this->settings['mail']['accountantMail'];
			$accountantName = $this->settings['mail']['accountantName'];
			$recipientMail = $this->settings['mail']['recipientMail'];
			$recipientName = $this->settings['mail']['recipientName'];
			$ccRecipientMail = $this->settings['mail']['ccRecipientMail'];
			$ccRecipientName = $this->settings['mail']['ccRecipientName'];


			$mail->setFrom(array($senderMail => $senderName))
				->setTo(array($accountantMail => $accountantName))
				->setCc(array($ccRecipientMail => $ccRecipientName, $recipientMail => $recipientName))
				->setSubject($subject)
				->setBody($body, 'text/html')
				->send();
		} catch (Exception $ex) {
			throw new Exception ('Error: Notification could not be sent. ' . $ex->getMessage());
		}
	}

}
