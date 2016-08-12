<?php

if ( ! defined( 'ABSPATH' ) ) { die(); }

$date         = $this->get_meta( 'schedule_date', '' );
$start_hour   = $this->get_meta( 'schedule_start_hour', '5' );
$start_minute = $this->get_meta( 'schedule_start_minute', '0' );
$start_is_pm  = $this->get_meta( 'schedule_start_is_pm', '1' );
$end_hour     = $this->get_meta( 'schedule_end_hour', '6' );
$end_minute   = $this->get_meta( 'schedule_end_minute', '0' );
$end_is_pm    = $this->get_meta( 'schedule_end_is_pm', '1' );


?>
<table class="form-table">
    <tbody>
        <tr valign="top">
            <th scope="row">Event Date</th>
            <td>
                <input type="text" id="event-date" name="schedule[date]" value="<?php echo $date ?>" placeholder="mm/dd/yyyy">
            </td>
        </tr>
        <tr valign="top">
            <th scope="row">Start Time</th>
            <td>
                <select id="event-start-hour" name="schedule[start_hour]" style="display:inline; width:auto;">
                <?php for ( $hour = 1; $hour <= 12; $hour ++ ) : ?>
                    <option value="<?php echo $hour ?>" <?php selected( $start_hour, $hour ) ?>><?php echo $hour ?></option>
                <?php endfor; ?>
                </select>
                :
                <select id="event-start-minute" name="schedule[start_minute]" style="display:inline; width:auto;">
                    <option value="0" <?php selected( $start_minute, 0 ) ?>>00</option>
                    <option value="15" <?php selected( $start_minute, 15 ) ?>>15</option>
                    <option value="30" <?php selected( $start_minute, 30 ) ?>>30</option>
                    <option value="45" <?php selected( $start_minute, 45 ) ?>>45</option>
                </select>

                <select id="event-start-is-pm" name="schedule[start_is_pm]" style="display:inline; width:auto;">
                    <option value="0" <?php selected( $start_is_pm, 0 ) ?>>AM</option>
                    <option value="1" <?php selected( $start_is_pm, 1 ) ?>>PM</option>
                </select>
            </td>
        </tr>
        <tr valign="top">
            <th scope="row">End Time</th>
            <td>
                <select id="event-end-hour" name="schedule[end_hour]" style="display:inline; width:auto;">
                <?php for ( $hour = 1; $hour <= 12; $hour ++ ) : ?>
                    <option value="<?php echo $hour ?>" <?php selected( $end_hour, $hour ) ?>><?php echo $hour ?></option>
                <?php endfor; ?>
                </select>
                :
                <select id="event-end-minute" name="schedule[end_minute]" style="display:inline; width:auto;">
                    <option value="0" <?php selected( $end_minute, 0 ) ?>>00</option>
                    <option value="15" <?php selected( $end_minute, 15 ) ?>>15</option>
                    <option value="30" <?php selected( $end_minute, 30 ) ?>>30</option>
                    <option value="45" <?php selected( $end_minute, 45 ) ?>>45</option>
                </select>

                <select id="event-end-is-pm" name="schedule[end_is_pm]" style="display:inline; width:auto;">
                    <option value="0" <?php selected( $end_is_pm, 0 ) ?>>AM</option>
                    <option value="1" <?php selected( $end_is_pm, 1 ) ?>>PM</option>
                </select>
            </td>
        </tr>
    </tbody>
</table>

