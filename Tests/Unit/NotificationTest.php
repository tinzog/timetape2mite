<?php
namespace In2code\In2timetape\Tests\Unit;

use In2code\In2timetape\Service\Notification as Notification;
use TYPO3\Flow\Tests\UnitTestCase;


class NotificationTest extends UnitTestCase {
	/**
	 * @test
	 */
	public function getSettingsTest() {

		$notification = new Notification();
		$notification->setSettings();
		assertArrayHasKey('in2timetape', $notification->getSettings());


	}

}
