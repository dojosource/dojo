<?php
/**
 * Utility class for handling price plan definitions
 */

class Dojo_Price_Plan extends Dojo_WP_Base {
    private $state;

    public function __construct( $plan_json = null ) {
        if ( empty( $plan_json ) ) {
            $plan_json = '{}';
        }
        try {
            $this->state = json_decode( $plan_json );
        } catch (Exception $ex) {
            $this->debug( 'Error decoding price plan ' . $ex->getMessage() );
            $this->state = new StdClass();
        }

        if ( ! isset( $this->state->rule_count ) ) {
            $this->state->rule_count = 0;
        }
    }

    /**
     * @return bool
     */
    public function family_pricing_enabled() {
        return isset( $this->state->family_pricing ) && 1 == $this->state->family_pricing;
    }

    /**
     * Gets the monthly price for the Nth person in the household
     *
     * @param int $person Starting at 1
     *
     * @return float Dollars
     */
    public function get_price( $person ) {
        $family_pricing_enabled = isset( $this->state->family_pricing ) && 1 == $this->state->family_pricing;
        $simple_price = isset( $this->state->simple_price ) ? $this->state->simple_price : '';
        $rule_count = isset( $this->state->rule_count ) ? $this->state->rule_count : 1;

        if ( ! $family_pricing_enabled ) {
            return (float) $simple_price;
        }

        $person_count = 0;
        $price = 0;
        for ( $rule = 1; $rule <= $rule_count; $rule ++ ) {
            $price_x = 'price_' . $rule;
            $count_x = 'count_' . $rule;
            $price = 0;
            $count = 1;
            if ( isset( $this->state->$price_x ) ) {
                $price = $this->state->$price_x;
            }
            if ( isset( $this->state->$count_x ) ) {
                $count = $this->state->$count_x;
            }

            $person_count += $count;
            if ( $person_count >= $person ) {
                break;
            }
        }

        return (float) $price;
    }

    /**
     * Generate human readable description of price plan
     * @param string $singular Singular term for what the price plan is describing
     * @param string $recurring Text describing a recurrence like, "per month".
     *
     * @return string
     */
    public function describe( $singular, $recurring = null ) {
        $family_pricing_enabled = isset( $this->state->family_pricing ) && 1 == $this->state->family_pricing;
        $simple_price = isset( $this->state->simple_price ) ? $this->state->simple_price : '';
        $rule_count = isset( $this->state->rule_count ) ? $this->state->rule_count : 1;

        $price = array();
        $price_count = array();
        for ( $rule = 1; $rule <= $rule_count; $rule ++ ) {
            $price_x = 'price_' . $rule;
            $count_x = 'count_' . $rule;
            $price[ $rule ] = $this->state->$price_x;
            $price_count[ $rule ] = $this->state->$count_x;
        }

        // add leading space to recurring text
        if ( $recurring ) {
            $recurring = ' ' . $recurring;
        } else {
            $recurring = '';
        }

        if ( ! $family_pricing_enabled ) {
            return 'This ' . $singular . ' is $' . $simple_price . ' per person' . $recurring . '.';
        }

        if ( 0 == $rule_count) {
            return 'There is no cost for this ' . $singular . '.';
        } elseif ( 1 == $rule_count ) {
            return 'This ' . $singular . ' is $' . $price[1] . ' per person' . $recurring . '.';
        } else {
            $text = 'This ' . $singular . ' is $';
            for ( $rule = 1; $rule <= 5 ; $rule ++ ) {
                if ( $rule > 1 ) {
                    if ( 0 == $price[ $rule ] ) {
                        $text .= ', then no cost for ';
                    } else {
                        $text .= ', then $';
                    }
                }

                if ( 0 != $price[ $rule ] ) {
                    $text .= $price[ $rule ] . $recurring . ' for ';
                }

                if ( $price_count[ $rule ] > 1 ) {
                    $text .= 'the ' . ( 1 == $rule ? 'first ' : 'next ' ) . $price_count[ $rule ] . ' family members';
                } elseif ( 1 == $price_count[ $rule ] ) {
                    $text .= 'the ' . ( 1 == $rule ? 'first ' : 'next ' ) . 'family member';
                } else {
                    $text .= 'each additional family member.';
                    break;
                }
            }
            return $text;
        }
    }

    /**
     * Outputs the edit block to include in a settings form. Should be included in the same
     * place you would put a normal form input
     */
    public function render_edit() {
        $family_pricing_enabled = isset( $this->state->family_pricing ) && 1 == $this->state->family_pricing;
        $simple_price = isset( $this->state->simple_price ) ? $this->state->simple_price : '';
        $rule_count = isset( $this->state->rule_count ) ? $this->state->rule_count : 1;

        // make sure we have at least one empty rule
        if ( 0 == $rule_count ) {
            $rule_count = 1;
        }
        ?>
        <div class="dojo-block">
            <label for="family_pricing">
                <input type="checkbox" id="family_pricing" name="family_pricing" value="1" <?php checked( $family_pricing_enabled, '1' ) ?>>
                Enable family pricing
            </label>

            <div class="simple-pricing"<?php echo $family_pricing_enabled ? ' style="display:none;"' : '' ?>>
                <table class="form-table">
                    <tbody>
                        <tr valign="top">
                            <th scope="row">Cost per person</th>
                            <td>
                                <span>$<span><input type="text" id="simple_price" name="simple_price" size="8" style="display:inline;width:auto;" value="<?php echo esc_attr( $simple_price ) ?>">
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="family-pricing"<?php echo $family_pricing_enabled ? '' : ' style="display:none;"' ?>>
            <?php for ( $rule = 1; $rule <= $rule_count; $rule ++ ) : ?>
                <?php
                $price_x = 'price_' . $rule;
                $count_x = 'count_' . $rule;
                $price = $count = '';
                if ( isset( $this->state->$price_x ) ) {
                    $price = $this->state->$price_x;
                }
                if ( isset( $this->state->$count_x ) ) {
                    $count = $this->state->$count_x;
                }
                ?>
                <table class="form-table" data-rule="<?php echo $rule ?>">
                    <tbody>
                        <tr valign="top">
                            <th scope="row" class="dojo-plan-cost"><?php echo 1 == $rule ? 'Cost' : 'then cost' ?> per person</th>
                            <td>
                                <span>$<span><input type="text" name="<?php echo $price_x ?>" size="8" style="display:inline;width:auto;" value="<?php echo esc_attr( $price ) ?>">
                            </td>
                        </tr>
                        <tr valign="top">
                            <th scope="row" class="dojo-plan-for">for <?php echo 1 == $rule ? 'first' : 'next' ?></th>
                            <td>
                                <select class="price-count" name="<?php echo $count_x ?>" style="display:inline; width:auto;">
                                <?php for ( $c = 0; $c <= 5; $c++ ) : ?>
                                    <option value="<?php echo $c ?>" <?php selected( $count, $c ) ?>><?php echo 0 == $c ? 'Unlimited' : $c ?></option>
                                <?php endfor; ?>
                                </select>
                                family members
                            </td>
                    </tbody>
                </table>
                <?php if ( 0 == $count ) { break; } ?>
            <?php endfor; ?>
            </div>
        </div>

        <script>
        jQuery(function($) {
          function onCountChange(select) {
            var ruleBlock = select.closest('.form-table');
            var rule = parseInt(ruleBlock.attr('data-rule'));
            if (select.val() != 0) {
              rule++;
              var next = $('table[data-rule="' + rule + '"]');
              if (0 == next.length) {
                var newBlock = ruleBlock.clone();
                ruleBlock.after(newBlock);
                newBlock.find('input').val('');
                newBlock.find('input').attr('name', 'price_' + rule);
                newBlock.find('.price-count').val('0');
                newBlock.find('.price-count').attr('name', 'count_' + rule);
                newBlock.find('.dojo-plan-cost').text('then cost');
                newBlock.find('.dojo-plan-for').text('for next');
                newBlock.attr('data-rule', rule);
                newBlock.find('.price-count').change(function() {
                  onCountChange($(this));
                });
              }
              else {
                next.show();
                onCountChange(next.find('select'));
              }
            }
            else {
              ruleBlock.nextAll().hide();
            }
          }

          $('.price-count').change(function() {
            onCountChange($(this));
          });

          $('input[name=family_pricing]').change(function() {
            if ($(this).is(':checked')) {
                $('.simple-pricing').hide();
                $('.family-pricing').show();
            }
            else {
                $('.family-pricing').hide();
                $('.simple-pricing').show();
            }
          });
        });
        </script>
        <?php
    }

    /**
     * Call to extract post parameters from settings form. To save result to settings
     * cast this object to a string.
     */
    public function handle_post() {
        if ( isset( $_POST['family_pricing'] ) && '1' == $_POST['family_pricing'] ) {
            $this->state->family_pricing = 1;
        }
        else {
            $this->state->family_pricing = 0;
        }

        if ( isset( $_POST['simple_price'] ) ) {
            $this->state->simple_price = $_POST['simple_price'];
        }
        else {
            $this->state->simple_price = '';
        }

        for ( $rule = 1; true; $rule ++ ) {
            $price_x = 'price_' . $rule;
            $count_x = 'count_' . $rule;
            if ( isset ( $_POST[ $price_x ] ) && isset ( $_POST[ $count_x ] ) ) {
                $this->state->$price_x = $_POST[ $price_x ];
                $this->state->$count_x = $_POST[ $count_x ];
            }
            else {
                $this->state->rule_count = $rule - 1;
                break;
            }
        }
    }

    public function __toString() {
        return json_encode( $this->state );
    }
}

