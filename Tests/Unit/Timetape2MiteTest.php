<?php
namespace In2code\In2timetape\Tests\Unit;

use In2code\In2timetape\Command\Timetape2MiteCommandController as Timetape2MiteCommandController;
use TYPO3\Flow\Tests\UnitTestCase as UnitTestCase;


class Timetape2MiteTest extends UnitTestCase {
	/**
	 * @test
	 */
	public function calculateDurationInMinutesTest() {

		$in2Mite = new Timetape2MiteCommandController();

		$time1 = '00:00:00';
		$time2 = '23:59:59';
		$time3 = '00:17:17';
		$time4 = '00:01:00';

		$actual = $in2Mite->calculateDurationInMinutes($time1, $time4);
		$expected = 1;

		$this->assertSame($expected, $actual);

		$actual = $in2Mite->calculateDurationInMinutes($time1, $time2);
		$expected = 1440;

		$this->assertSame($expected, $actual);

		$actual = $in2Mite->calculateDurationInMinutes($time4, $time3);
		$expected = 16;

		$this->assertSame($expected, $actual);

	}
}
