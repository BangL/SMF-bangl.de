<?php

function template_shop_above() {
}

function template_main() {
    global $context, $user_info;

    // Logged in?
    if (!$context["user"]["is_logged"]) {
        ssi_login();
    } else {
        echo('
        <div id="admin_menu">
            <ul class="dropmenu" id="dropdown_menu_1">
                <li>
                    <a class="firstlevel'); if ($context["shop_sa"] == "index") echo(' active'); echo('" href="index.php?action=shop">
                        <span class="firstlevel">Main</span>
                    </a>
                </li>
                ');
                // If something is in the cart ...
                if ($context["shop_cart_count"] or !empty($context["shop_cart_items"])) {
                    echo('
                    <li>
                        <a class="firstlevel'); if (!empty($context["shop_cart_items"])) echo(' active'); echo('" href="index.php?action=shop&sa=cart">
                            <span class="firstlevel">View Cart</span>
                        </a>
                    </li>
                    ');
                }
                // If user is Owner or has the right to Add Perks ...
                if ($context["user"]["is_admin"]) {
                    echo('
                    <li>
                        <a class="firstlevel'); if($context["shop_sa"] == "addperk") echo(' active'); echo('" href="index.php?action=shop&sa=addperk">
                            <span class="firstlevel">Add Perk</span>
                        </a>
                    </li>
                    ');
                }
                echo('
            </ul>
        </div>
        <div id="shop">
            <div class="cat_bar">
                <h3 class="catbg">
                    <span class="ie6_header floatleft">
                        <img class="shop_icon" src="/Themes/default/images/shop/coin.png" />
                        ' . $context['shop_Head'] . '
                    </span>
                </h3>
            </div>
            <div class="windowbg">
                <span class="topslice"><span></span></span>
                <div class="shop_wrapper">
                    <div class="shop_left">
                        ');
                        // Show a little cart overview
                        if ($context["shop_cart_count"]) {
                            echo('
                            <h4><a href="index.php?action=shop&sa=cart">Perk cart</a></h4>
                            <div class="shop_cart reset smalltext">
                                ' . $context["shop_cart_count"] . ' perks in cart (' . number_format($context["shop_cart_sum"], 2) . ' EUR).
                                <form action="index.php" method="get">
                                    <input type=hidden name="action" value="shop">
                                    <input type=hidden name="sa" value="checkout">
                                    <input type=submit value="Buy!">
                                </form>
                            </div>
                            <hr />
                            ');
                        }
                        // List availible perks
                        foreach ($context["shop_prices"] as $price) {
                            echo('
                            <h4>' . number_format($price, 2) . ' EUR Perks</h4>
                            <ul class="shop_price reset smalltext">
                                ');
                                foreach ($context["shop_perks"] as $perk) {
                                    if ($perk["perk_price"] == $price) { 
                                        echo('
                                        <li>
                                            <a href="index.php?action=shop&sa=perk&perk_id=' . $perk["perk_id"] . '">
                                                ' . $perk["perk_name"]);if (!empty($perk["perk_options"])) echo(' (perk)'); echo('
                                            </a>
                                        </li>
                                        ');
                                    }
                                    foreach ($perk["perk_options"] as $option) {
                                        if ($option["option_price"] == $price) { 
                                            echo('
                                            <li>
                                                <a href="index.php?action=shop&sa=perk&perk_id=' . $perk["perk_id"] . '&len=' . $option["option_expiry_length"] . '">
                                                    ' . $perk["perk_name"] . ' (' . $option["option_expiry_length"] . ' days)
                                                </a>
                                            </li>
                                            ');
                                        }
                                    }
                                }
                                echo('
                            </ul>
                            ');
                        }
                        echo('
                    </div>
                    <div class="shop_right">
                        <div class="flow_hidden">
                            ');
                            // Show Errors
                            if (!empty($context["shop_errors"])) {
                                echo('
                                <div class="shop_errors">
                                    ');
                                    foreach($context["shop_errors"] as $myerror) {
                                        echo('
                                        <div>
                                            ' . $myerror . '
                                        </div>
                                        ');
                                    }
                                    echo('
                                </div>
                                ');
                            }
                            // Show Successes
                            if (!empty($context["shop_success"])) {
                                echo('
                                <div class="shop_success">
                                    ');
                                    foreach($context["shop_success"] as $mysuccess) {
                                        echo('
                                        <div>' . $mysuccess . '</div>
                                        ');
                                    }
                                    echo('
                                </div>
                                ');
                            }

                            // Show the shopping cart
                            if ($context["shop_sa"] == "cart"
                                    && !empty($context["shop_cart_items"])) {
                                $tablestart='
                                <h4>Perk cart</h4>
                                <div class="shop_cart_view">
                                    <div>
                                        <table class="cart">
                                            <tr>
                                                <th>Perk</th>
                                                <th>Donation</th>
                                            </tr>
                                            ';
                                            $sum=0;
                                            $tablecontent="";
                                            foreach ($context["shop_cart_items"] as $item) {
                                                $itemincart = true;
                                                $sum += $item['perk_price'];
                                                $tablecontent=$tablecontent.'
                                                <tr>
                                                    <td>
                                                        ' . $item['perk_name'];
                                                        if ($item['expiry_length'] > 0) {
                                                            $tablecontent=$tablecontent.' (<a href="index.php?action=shop&sa=perk&perk_id=' . $item['perk_id'] . '&len=' . $item['expiry_length'] . '">' . $item['expiry_length'] . ' days</a>)';
                                                        } elseif ($item['has_options']) {
                                                            $tablecontent=$tablecontent.' (<a href="index.php?action=shop&sa=perk&perk_id=' . $item['perk_id'] . '">Perk</a>)';
                                                        }
                                                        $tablecontent=$tablecontent.'
                                                        <a class="righttext" href="index.php?action=shop&sa=cartitemremove&perk_id=' . $item['perk_id'] . '">Remove</a>
                                                    </td>
                                                    <td>' . number_format($item['perk_price'], 2) . ' EUR</td>
                                                </tr>
                                                ';
                                            }
                                            $tableend='
                                            <tr>
                                                <td><span class="shop_bold">SUM</span></td>
                                                <td><span class="shop_bold">' . number_format($sum, 2) . ' EUR</span></td>
                                            </tr>
                                        </table>
                                        <hr />
                                        <form action="index.php" method="get">
                                            <input type=hidden name="action" value="shop">
                                            <input type=hidden name="sa" value="checkout">
                                            <p class="righttext">
                                                <input type=submit value="Buy!" class="button_submit">
                                            </p>
                                        </form>
                                    </div>
                                </div>
                                ';
                                if ($itemincart) {
                                    echo($tablestart.$tablecontent.$tableend);
                                }
                            }

                            // Show perk details
                            else if ($context["shop_sa"] == "perk"
                                    && isset($context["shop_perk_details"]["perk_id"])) {
                                echo('
                                <div class="shop_perk_details">
                                    <h4>' . htmlspecialchars(stripslashes(un_htmlspecialchars($context["shop_perk_details"]['perk_name']))) . '</h4>
                                    <form action="index.php" method="get">
                                        <input type=hidden name="action" value="shop">
                                        <input type=hidden name="sa" value="cartitemadd">
                                        <div>
                                            <p class="perk_details_left">
                                                <div class="post">
                                                    <span class="shop_bold">Description:</span>
                                                    <p>
                                                    ' . stripslashes(preg_replace('/\\\[rn]/', '<br />', htmlspecialchars(un_htmlspecialchars($context["shop_perk_details"]['perk_desc'])))) . '
                                                    </p>
                                                </div>
                                            </p>
                                            ');
                                            if (!empty($context["shop_perk_details"]['perk_options'])) {
                                                echo('
                                                <p class="perk_details_right lifetime">
                                                    <span class="shop_bold">Lifetime:</span>
                                                    <br />
                                                    <select name="len" id="select_expiry_length" onchange="updatePrice()" style="display: none;">
                                                        ');
                                                        foreach($context["shop_perk_details"]["perk_options"] as $perk_option) {
                                                            echo('
                                                                <option value="' . $perk_option['option_expiry_length'] . '"'); if ($perk_option['selected']) {
                                                                    echo(' selected="selected"');
                                                                    $selected = true;
                                                                    $price = $perk_option["option_price"];
                                                                } echo('>
                                                                    ' . $perk_option['option_expiry_length'] . ' days
                                                                </option>
                                                            ');
                                                        }
                                                        if ($context["shop_perk_details"]['perk_price'] > 0) {
                                                            echo('
                                                            <option value="0"'); if (!$selected) echo('selected="selected"'); echo('>Perk</option>
                                                            ');
                                                        }
                                                        echo('
                                                    </select>
                                                </p>
                                                ');
                                            }
                                            echo('
                                            <p class="perk_details_right">
                                                <span class="shop_bold">Price:</span><br />
                                                ');
                                                if (isset($selected) && $selected) {
                                                    $price_val = $price;
                                                } else {
                                                    $price_val = $context["shop_perk_details"]["perk_price"];
                                                }
                                                echo('
                                                <span class="perk_price" id="perk_price">' . number_format($price_val, 2) . '</span><span class="perk_price"> EUR</span>
                                            </p>
                                            <hr class="clearfix" />
                                            <input type=hidden name="perk_id" value="' . $context["shop_perk_details"]['perk_id'] . '" />
                                            <p class="righttext">
                                                <input type=submit value="Add to cart" class="button_submit" />
                                            </p>
                                            ');
                                            if ($context["user"]["is_admin"]) {
                                                echo('
                                                <div>
                                                    <a href="index.php?action=shop&sa=editperk&perk_id=' . $context["shop_perk_details"]['perk_id'] . '">Edit</a>
                                                </div>
                                                ');
                                            }
                                            echo('
                                        </div>
                                    </form>
                                </div>
                                ');
                            }

                            // Show Paypal waiting text
                            else if ($context["shop_sa"] == "checkout"
                                    && isset($context["shop_payment_data"])) {
                                echo('
                                <h4>Please wait...</h4>
                                <div class="shop_paypal">
                                    <div>
                                        Please wait, your order is being processed and you will be redirected to the paypal website.
                                        <form method="post" name="paypal_form" action="' . $context['paypal_url'] . '">
                                            ');
                                            foreach ($context['shop_payment_data'] as $name => $value) {
                                                echo('<input type="hidden" name="' . $name . '" value="' . $value . '"/>');
                                            }
                                            echo('
                                            If you are not automatically redirected to paypal within 5 seconds...
                                            <input type="submit" value="Click Here">
                                        </form>
                                    </div>
                                </div>
                                ');
                            }

                            // Show the Add/Edit Perk Form
                            else if ($context["shop_sa"] == "addperk"
                                    || ($context["shop_sa"] == "editperk"
                                    && isset($context["shop_perk_details"]["perk_id"]))) {
                                if ($context["user"]["is_admin"]) {
                                    echo('
                                    <div class="perk_data">
                                        <form id="packageForm">
                                            <input type="hidden" name="action" value="shop" />
                                            <input type="hidden" name="sa" value="addperksave" />
                                            <input type="hidden" name="perk_expiry_command_count" value="' . $context["shop_perk_details"]["perk_expiry_command_count"] . '" />
                                            <input type="hidden" name="perk_command_count" value="' . $context["shop_perk_details"]["perk_command_count"] . '" />
                                            <input type="hidden" name="perk_option_count" value="' . $context["shop_perk_details"]["perk_option_count"] . '" />
                                            ');
                                            if (isset($context["shop_perk_details"]["perk_id"])) {
                                                echo('<input type="hidden" name="perk_id" value="' . $context["shop_perk_details"]["perk_id"] . '" />');
                                            }
                                            echo('
                                            <h4>Edit Perk</h4>
                                            <div class="perk_name">
                                                <div><label for="perk_name">Name</label></div>
                                                <div>
                                                    <input class="text" type="text" name="perk_name" value="'); if (isset($context["shop_perk_details"]["perk_id"])) echo(htmlspecialchars(stripslashes(un_htmlspecialchars($context["shop_perk_details"]["perk_name"])))); echo('" />
                                                </div>
                                                <div><label for="perk_description">Description</label></div>
                                                <div>
                                                    <textarea name="perk_description">');
                                                    if (isset($context["shop_perk_details"]["perk_id"])) {
                                                        echo(htmlspecialchars(stripslashes(preg_replace('/\\\n/', '
', un_htmlspecialchars($context["shop_perk_details"]["perk_desc"])))));
                                                    }
                                                    echo('</textarea>
                                                </div>
                                            </div>
                                            <div class="perk_command_wrapper">
                                                <div id="perk_commands">
                                                    <div>
                                                        <label for="perk_command_1">Commands</label>
                                                        <img src="/Themes/default/images/shop/plus_button.png" id="command_add">
                                                    </div>
                                                    ');
                                                    $counter=0;
                                                    if (isset($context["shop_perk_details"]["perk_commands"])) {
                                                        foreach ($context["shop_perk_details"]["perk_commands"] as $cmd) {
                                                            $counter+=1;
                                                            echo('<div><input class="text" type="text" value="'
                                                                    . htmlspecialchars(stripslashes(un_htmlspecialchars($cmd))) .
                                                                    '" name="perk_command_'
                                                                    .  $counter .
                                                                    '"></div>');
                                                        }
                                                    }
                                                    while ($counter < $context["shop_perk_details"]["perk_command_count"]) {
                                                        $counter+=1;
                                                        echo('<div><input class="text" type="text" name="perk_command_' . $counter . '"></div>');
                                                    }
                                                    echo('
                                                </div>
                                                <div id="perk_expiry_commands">
                                                    <div>
                                                        <label for="perk_expiry_command_1">Expiry Commands</label>
                                                        <img src="/Themes/default/images/shop/plus_button.png" id="expiry_command_add">
                                                    </div>
                                                    ');
                                                    $counter=0;
                                                    if (isset($context["shop_perk_details"]["perk_expiry_commands"])) {
                                                        foreach ($context["shop_perk_details"]["perk_expiry_commands"] as $ecmd) {
                                                            $counter+=1;
                                                            echo('<div><input class="text" type="text" value="' . htmlspecialchars(stripslashes(un_htmlspecialchars($ecmd))) .'" name="perk_expiry_command_' . $counter . '"></div>');
                                                        }
                                                    }
                                                    while ($counter < $context["shop_perk_details"]["perk_expiry_command_count"]) {
                                                        $counter+=1;
                                                        echo('<div><input class="text" type="text" name="perk_expiry_command_' . $counter . '"></div>');
                                                    }
                                                    echo('
                                                </div>
                                            </div>
                                            <div class="perk_settings">
                                                <div class="perk_setting">
                                                    <div>
                                                        <label for="perk_price">Price (EUR)</label>
                                                        <img src="/Themes/default/images/shop/plus_button.png" id="perk_options_add">
                                                    </div>
                                                    <div id="perk_option_price">
                                                        ');
                                                        $counter=1;
                                                        echo('<input class="text" type="text" name="perk_price_' . $counter . '" value="'); if (isset($context["shop_perk_details"]["perk_id"])) echo(number_format($context["shop_perk_details"]["perk_price"], 2)); echo('" />');
                                                        if (isset($context["shop_perk_details"]["perk_options"])) {
                                                            foreach ($context["shop_perk_details"]["perk_options"] as $option) {
                                                                $counter+=1;
                                                                echo('<input class="text" type="text" name="perk_price_' . $counter . '" value="' . number_format($option["option_price"], 2) .'" />');
                                                            }
                                                        }
                                                        while ($counter < $context["shop_perk_details"]["perk_option_count"]) {
                                                            $counter+=1;
                                                            echo('<input class="text" type="text" name="perk_price_' . $counter . '" value="0.00" />');
                                                        }
                                                        echo('
                                                    </div>
                                                </div>
                                                <div class="perk_setting">
                                                    <div>
                                                        <label for="perk_expiry_length">Expiry Length</label>
                                                    </div>
                                                    <div id="perk_option_length">
                                                        ');
                                                        $counter=1;
                                                        echo('<input class="text" type="text" name="perk_expiry_length_' . $counter . '" value="0" />');
                                                        if (isset($context["shop_perk_details"]["perk_options"])) {
                                                            foreach ($context["shop_perk_details"]["perk_options"] as $option) {
                                                                $counter+=1;
                                                                echo('<input class="text" type="text" name="perk_expiry_length_' . $counter . '" value="' . intval($option["option_expiry_length"]) .'" />');
                                                            }
                                                        }
                                                        while ($counter < $context["shop_perk_details"]["perk_option_count"]) {
                                                            $counter+=1;
                                                            echo('<input class="text" type="text" name="perk_expiry_length_' . $counter . '" value="0" />');
                                                        }
                                                        echo('
                                                    </div>
                                                </div>
                                                <div class="perk_setting">
                                                    <div><label for="perk_require_online">Require Online</label></div>
                                                    <div>
                                                        <select name="perk_require_online">
                                                            <option value="1"'); if (isset($context["shop_perk_details"]["require_online"]) && $context["shop_perk_details"]["require_online"]) echo' selected="selected"'; echo('>Yes</option>
                                                            <option value="0"'); if (isset($context["shop_perk_details"]["require_online"]) && !$context["shop_perk_details"]["require_online"]) echo' selected="selected"'; echo('>No</option>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="perk_submit">
                                                <div>
                                                    <hr />
                                                    <p class="righttext">
                                                        ');
                                                        if ($context["shop_sa"] == "editperk") {
                                                            echo('<input type="submit" value="Save changes" class="button_submit" />');
                                                        } else {
                                                            echo('<input type="submit" value="Add perk" class="button_submit" />');
                                                        }
                                                        echo('
                                                    </p>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                    ');
                                }
                            }

                            // Show Main Site
                            else {
                                echo('
                                <h4>Mainsite</h4>
                                <div class="shop_index"><div></div></div>
                                ');
                            }

                        echo('</div>
                    </div>
                    <div class="shop_bar"><span></span></div>
                </div>
                <span class="botslice"><span></span></span>
            </div>
        </div>');
    }
}

function template_shop_below() {
}

?>
