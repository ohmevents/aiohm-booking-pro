<?php

use PHPUnit\Framework\TestCase;
use AIOHM_Booking_PRO\Core\AIOHM_Booking_PROCoreAIOHM_BOOKING_Validation;

class ValidationTest extends TestCase
{
    public function test_sanitize_hex_color()
    {
        $this->assertEquals( '#aabbcc', \AIOHM_Booking_PRO\Core\AIOHM_Booking_PROCoreAIOHM_BOOKING_Validation::sanitize_hex_color( '#aabbcc' ) );
        $this->assertEquals( '#AABBCC', \AIOHM_Booking_PRO\Core\AIOHM_Booking_PROCoreAIOHM_BOOKING_Validation::sanitize_hex_color( '#AABBCC' ) );
        $this->assertEquals( '', \AIOHM_Booking_PRO\Core\AIOHM_Booking_PROCoreAIOHM_BOOKING_Validation::sanitize_hex_color( 'invalid' ) );
        $this->assertEquals( '#123456', \AIOHM_Booking_PRO\Core\AIOHM_Booking_PROCoreAIOHM_BOOKING_Validation::sanitize_hex_color( '#123456' ) );
    }
}
