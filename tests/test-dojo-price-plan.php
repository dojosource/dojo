<?php
/**
 * Class Test_Dojo_Price_Plan
 *
 * @package Dojo
 */

if ( ! defined( 'ABSPATH' ) ) { die(); }


class Test_Dojo_Price_Plan extends WP_UnitTestCase {

    /**
     * Test default state on an empty plan
     */
    function test_empty_plan() {
        $plan = new Dojo_Price_Plan();

        $this->assertEquals( false, $plan->family_pricing_enabled() );
        $this->assertEquals( 0, $plan->get_price( 1 ) );
    }

    /**
     * Test default state with family pricing enabled
     */
    function test_empty_family_plan() {
        $plan = new Dojo_Price_Plan( json_encode( array(
            'family_pricing' => 1,
        ) ) );

        $this->assertEquals( true, $plan->family_pricing_enabled() );
        $this->assertEquals( 0, $plan->get_price( 1 ) );
    }

    /**
     * Test post handling and family pricing
     */
    function test_post_handling_family() {
        $_POST += array(
            'family_pricing'    => 1,
            'price_1'           => '42.55',
            'count_1'           => 2,
            'price_2'           => '11.23',
            'count_2'           => 0,
        );

        $plan = new Dojo_Price_Plan();
        $plan->handle_post();

        $this->assertTrue( $plan->family_pricing_enabled() );
        $this->assertEquals( $plan->get_price( 1 ), 42.55 );
        $this->assertEquals( $plan->get_price( 2 ), 42.55 );
        $this->assertEquals( $plan->get_price( 3 ), 11.23 );
        $this->assertEquals( $plan->get_price( 99 ), 11.23 );

        return $plan;
    }

    /**
     * Test serialize / deserialize. Uses plan object generated in test_post_handling_family
     *
     * @depends test_post_handling_family
     */
    function test_serialization( $plan ) {
        $json = (string) $plan;

        $plan2 = new Dojo_Price_Plan( $json );

        $this->assertTrue( $plan->family_pricing_enabled() );
        $this->assertEquals( $plan->get_price( 1 ), 42.55 );
        $this->assertEquals( $plan->get_price( 2 ), 42.55 );
        $this->assertEquals( $plan->get_price( 3 ), 11.23 );
        $this->assertEquals( $plan->get_price( 99 ), 11.23 );
    }
}


