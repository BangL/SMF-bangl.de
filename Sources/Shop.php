<?php
/**********************************************************************************
* Shop.php                                                                        *
***********************************************************************************
* Shop: A perk sop system for Simple Machines Forum                               *
* =============================================================================== *
* Software Version:           Shop 1.0                                          *
* Original Mod by:            BangL                                               *
* Copyright 2012 by:          BangL                                               *
***********************************************************************************
***********************************************************************************/
//##################################################################
ini_set("display_errors",1);
if (!defined('SMF'))
    die('Hacking attempt...');

require_once('paypal.class.php');  // include the paypal class file

function Shop() {
    global $p, $context, $scripturl, $smcFunc, $settings, $txt;

    //$context['paypal_url'] = 'https://www.sandbox.paypal.com/cgi-bin/webscr';   // testing paypal url
    //$context['paypal_email'] = 'usa_1349142597_biz@googlemail.com';
    $context['paypal_url'] = 'https://www.paypal.com/cgi-bin/webscr';     // paypal url
    $context['paypal_email'] = 'henno.rickowski@googlemail.com';

    $p = new paypal_class;             // initiate an instance of the paypal class
    $p->paypal_url = $context['paypal_url'];

    // Load our template.
    loadTemplate('Shop');

    // A default page title.
    $context['page_title'] = $context['forum_name'] . ' ' . $txt['shop'];
    $context['shop_Head'] = $txt['shop'];
    $context['page_title_html_safe'] = $smcFunc['htmlspecialchars'](stripslashes(un_htmlspecialchars($context['page_title'])));

    // define the navigational link tree to be shown at the top of the page.
    $context['linktree'][] = array(
        'url' => $scripturl. '?action=shop',
        'name' => $txt['shop'],
    );

    // Use the shop layer
    $context['template_layers'][] = 'shop';
    // Add our stylesheet.
    $context['html_headers'] .= '
    <link rel="stylesheet" type="text/css" href="' . $settings['default_theme_url'] . '/shop.css" />';

    // Some actions we can do.
    $actions = array(
        'index' => 'ShopIndex',
        'perk' => 'ShopPerk',
        'cart' => 'ShopCart',
        'cartitemadd' => 'ShopCartitemAdd',
        'cartitemremove' => 'ShopCartitemRemove',
        'checkout' => 'ShopCheckout',
        'success' => 'ShopSuccess',
        'cancel' => 'ShopCancel',
        'ipn' => 'ShopIPN',
        'addperk' => 'ShopPerkForm',
        'editperk' => 'ShopPerkForm',
        'addperksave' => 'ShopPerkSave',
        'editperksave' => 'ShopPerkSave',
    );

    //Get current time
    $context["shop_thisdate"] = time();

    //Create arrays to collect all error/success messages
    $context["shop_errors"] = array();
    $context["shop_success"] = array();

    // Get the current action.
    $action = (isset($_GET['sa'])) ? $_GET['sa'] : 'index';

    // Check if the action exist, otherwise go to the index.
    if (isset($actions[$action])) {
        $context["shop_sa"] = $action;
        $actions[$action]();
    } else{
        $context["shop_sa"] = $action;
        ShopIndex();
    }

    // Transactions?
    if (isset($context["shop_transaction_started"]) && !$context["shop_transaction_done"]) {
        $smcFunc['db_transaction']('rollback');
    }
}

//##################################################################
// Views

function ShopIndex() {
    global $context, $smcFunc;
    if (!$context["user"]["is_logged"]) return;

    // ....

    PerkMenu();
    CartOverview();
    if ($context["shop_sa"] != "checkout")
            $context["shop_sa"] = "index";
}

function ShopPerk() {
    global $context, $smcFunc;
    if (!$context["user"]["is_logged"]) return;

    if (!isset($_GET['perk_id'])) {
        array_push($context["shop_errors"], "Invalid perk id");
        ShopIndex();
        return;
    }

    // Get Perk data
    if (isset($_GET['len'])) {
        $context["shop_perk_details"] = db_get_perk($_GET['perk_id'], $_GET['len']);
    } else {
        $context["shop_perk_details"] = db_get_perk($_GET['perk_id'], 0);
    }

    if (empty($context["shop_perk_details"]['perk_id'])) {
        array_push($context["shop_errors"], "Invalid perk id");
        ShopIndex();
        return;
    }

    // Has this perk options?
    if (!empty($context["shop_perk_details"]["perk_options"])) {
        // Add js for option selection/price change.
        $context["html_headers"] .= '
        <script type="text/javascript" src="/js/jquery-1.8.2.min.js"></script>
        <script type="text/javascript"><!-- // --><![CDATA[
            window.onload = function () {

                var prices = new Array()
                ';
                foreach ($context["shop_perk_details"]["perk_options"] as $option) {
                    $context["html_headers"] .= '
                    prices[' . $option['option_expiry_length'] . ']="' . $option["option_price"] . '";
                    ';
                }
                $context["html_headers"] .= '
                prices[0]="' . $context["shop_perk_details"]["perk_price"] . '"

                $("#perk_price").show();
                $("#select_expiry_length").show();
                $("#select_expiry_length").change(function() {
                    $("#perk_price").html(parseFloat(prices[$("#select_expiry_length").val()]).toFixed(2));
                    $("#perk_price").blur();
                });

            }
        // ]]></script>';
    }

    PerkMenu();
    CartOverview();
}

function ShopCart() {
    global $context, $smcFunc;
    if (!$context["user"]["is_logged"]) return;

    $context["shop_cart_items"] = db_get_cart($context['user']['id']);

    if (empty($context["shop_cart_items"])) {
        ShopIndex();
        return;
    }

    PerkMenu();
}

function ShopPerkForm() {
    global $context, $smcFunc;
    if (!$context["user"]["is_logged"]) return;
    if (!$context["user"]["is_admin"]) return;

    // Set defaults
    $minfields = 4;
    $context["shop_perk_details"]["perk_expiry_command_count"] = $minfields;
    $context["shop_perk_details"]["perk_command_count"] = $minfields;
    $context["shop_perk_details"]["perk_option_count"] = $minfields;

    // Load left side
    PerkMenu();
    CartOverview();

    // Edit action ?
    if ($context["shop_sa"] == "editperk" && isset($_GET['perk_id'])) {

        // Load perk data
        $context["shop_perk_details"] = db_get_perk($_GET['perk_id'], 0);
        if (empty($context["shop_perk_details"]['perk_id'])) {
            // Perk id is invalid
            array_push($context["shop_errors"], "Invalid perk id");
            return;
        }

        // Get option count
        if (!isset($context["shop_perk_details"]["perk_options"])
                || count($context["shop_perk_details"]["perk_options"]) < $minfields) {
            $context["shop_perk_details"]["perk_option_count"] = $minfields;
        } else {
            $context["shop_perk_details"]["perk_option_count"] = count($context["shop_perk_details"]["perk_options"]);
        }

        // Get the commands for this perk
        if (!isset($context["shop_perk_details"]["perk_commands"])
                || count($context["shop_perk_details"]["perk_commands"]) < $minfields) {
            $context["shop_perk_details"]["perk_command_count"] = $minfields;
        } else {
            $context["shop_perk_details"]["perk_command_count"] = count($context["shop_perk_details"]["perk_commands"]);
        }

        // Get the expiry commands for this perk
        if (!isset($context["shop_perk_details"]["perk_expiry_commands"])
                || count($context["shop_perk_details"]["perk_expiry_commands"]) < $minfields) {
            $context["shop_perk_details"]["perk_expiry_command_count"] = $minfields;
        } else {
            $context["shop_perk_details"]["perk_expiry_command_count"] = count($context["shop_perk_details"]["perk_expiry_commands"]);
        }
    }

    // Add js for command/option increasing
    $context["html_headers"] .= '
    <script type="text/javascript" src="/js/jquery-1.8.2.min.js"></script>
    <script type="text/javascript"><!-- // --><![CDATA[
        window.onload = function () {

            var currentCommandCount = ' . $context["shop_perk_details"]["perk_command_count"] . ';
            $("#command_add").show();
            $("#command_add").click(function()
            {
                currentCommandCount++;
                $("#perk_commands").append(\'<div><input class="text" type="text" name="perk_command_\' + currentCommandCount + \'"></div>\');
                $("#packageForm").find("input[name=\'perk_command_count\']").attr("value", currentCommandCount);
                return false;
            });

            var currentExpiryCommandCount = ' . $context["shop_perk_details"]["perk_expiry_command_count"] . ';
            $("#expiry_command_add").show();
            $("#expiry_command_add").click(function()
            {
                currentExpiryCommandCount++;
                $("#perk_expiry_commands").append(\'<div><input class="text" type="text" name="perk_expiry_command_\' + currentExpiryCommandCount + \'"></div>\');
                $("#packageForm").find("input[name=\'perk_expiry_command_count\']").attr("value", currentExpiryCommandCount);
                return false;
            });

            var currentPerkOptionCount = ' . $context["shop_perk_details"]["perk_option_count"] . ';
            $("#perk_options_add").show();
            $("#perk_options_add").click(function()
            {
                currentPerkOptionCount++;
                $("#perk_option_price").append(\'<input class="text" type="text" name="perk_price_\' + currentPerkOptionCount + \'" value="0.00" />\');
                $("#perk_option_length").append(\'<input class="text" type="text" name="perk_expiry_length_\' + currentPerkOptionCount + \'" value="0" />\');
                $("#packageForm").find("input[name=\'perk_option_count\']").attr("value", currentPerkOptionCount);
                return false;
            });

        }
    // ]]></script>';

}

//##################################################################
// Templates

function PerkMenu() {
    global $context, $smcFunc;
    if (!$context["user"]["is_logged"]) return;

    // Get all perks
    $context["shop_perks"] = array();
    $context["shop_perks"] = db_get_perks();

    // Get all prices
    $context["shop_prices"] = array();
    $context["shop_prices"] = db_get_prices();
    sort($context["shop_prices"]);

    // Perks There?
    if (count($context["shop_perks"]) == 0) {
        array_push($context["shop_errors"], 'No perks availible.');
    }
}

function CartOverview() {
    global $context, $smcFunc;
    if (!$context["user"]["is_logged"]) return;

    $context["shop_cart_count"] = 0;
    $context["shop_cart_sum"] = 0;
    // Get cart content overview of current user
    $cart_overview = db_get_cart_overview($context['user']['id']);
    $context["shop_cart_count"] += $cart_overview['count'];
    $context["shop_cart_sum"] += $cart_overview['sum'];
}

//##################################################################
// Posts

function ShopCartitemAdd() {
    global $context, $smcFunc;
    if (!$context["user"]["is_logged"]) return;

    if (!isset($_GET['perk_id'])) {
        array_push($context["shop_errors"], "Invalid perk id");
        ShopCart();
        return;
    }

    // Get Perk data
    $validlen = false;
    if (isset($_GET['len'])) {
        $context["shop_perk_details"] = db_get_perk($_GET['perk_id'], $_GET['len']);
        foreach ($context["shop_perk_details"]["perk_options"] as $option) {
            if ($option["option_expiry_length"] == $_GET['len'] && $option["option_price"] > 0) {
                $validlen = true;
                $len = $_GET['len'];
            }
        }
    }
    if (!$validlen) {
        $len = 0;
        $context["shop_perk_details"] = db_get_perk($_GET['perk_id'], $len);
    }
    if (!isset($context["shop_perk_details"]['perk_id'])) {
        array_push($context["shop_errors"], "Invalid perk id");
        ShopIndex();
        return;
    }

    // Add perk to cart
    db_add_cartitem($context['user']['id'], $context["shop_perk_details"]['perk_id'], $len);

    // Success!
    array_push($context["shop_success"], 'Perk added to cart.');
    ShopCart();
}

function ShopCartitemRemove() {
    global $context, $smcFunc;
    if (!$context["user"]["is_logged"]) return;

    if (!isset($_GET['perk_id'])) {
        array_push($context["shop_errors"], 'Invalid perk id.');
    }

    db_remove_cartitem($context["user"]["id"], $_GET["perk_id"]);

    array_push($context["shop_success"], 'Perk removed from cart.');
    ShopCart();
}

function ShopPerkSave() {
    global $context, $smcFunc;
    if (!$context["user"]["is_logged"]) return;
    if (!$context['user']['is_admin']) return;

    $perk = array();
    if (isset($_GET['perk_id'])) {
        $perk["perk_id"] = $_GET['perk_id'];
    }
    $perk["perk_name"] = $_GET['perk_name'];
    $perk["perk_desc"] = $_GET['perk_description'];
    $perk["require_online"] = $_GET['perk_require_online'];
    $perk["perk_commands"] = array();
    $perk["perk_expiry_commands"] = array();
    $perk["perk_options"] = array();

    // TODO: Check Data

    $perk["perk_price"] = 0;
    for ($i = 1; $i <= $_GET['perk_option_count']; $i++) {
        if (isset($_GET["perk_price_" . $i])  && isset($_GET["perk_expiry_length_" . $i])) {
            if ($_GET["perk_price_" . $i] > 0  && $_GET["perk_expiry_length_" . $i] > 0) {
                array_push($perk["perk_options"], array(
                    "option_price" => $_GET["perk_price_" . $i],
                    "option_expiry_length" => $_GET["perk_expiry_length_" . $i],
                ));
            } elseif ($_GET["perk_price_" . $i] > 0) {
                $perk["perk_price"] = $_GET["perk_price_" . $i];
            }
        }
    }

    // Add the commands
    for ($i = 1; $i <= $_GET['perk_command_count']; $i++) {
        if (!empty($_GET["perk_command_" . $i])) {
            array_push($perk["perk_commands"], $_GET["perk_command_" . $i]);
        }
    }

    // Add the expiry commands
    for ($i = 1; $i <= $_GET['perk_expiry_command_count']; $i++) {
        if (!empty($_GET["perk_expiry_command_" . $i])) {
            array_push($perk["perk_expiry_commands"], $_GET["perk_expiry_command_" . $i]);
        }
    }

    db_add_perk($perk);

    if ($_GET['sa'] == "editperksave") {
        array_push($context["shop_success"], "Perk edited.");
    } elseif ($_GET['sa'] == "addperksave") {
        array_push($context["shop_success"], "Perk added.");
    }
    ShopIndex();
}

function ShopCheckout() {
    global $p, $context, $smcFunc;

    if ($context["user"]["is_logged"]) {

        // Begin transaction
        $smcFunc['db_transaction']('begin');

        // Mark that this transaction is not finished yet
        $context["shop_transaction_started"] = true;
        $context["shop_transaction_done"] = false;

        // Get all selected perks WITHOUT options
        $result = $smcFunc['db_query']('', '
            SELECT ci.perk_id, p.perk_price, p.perk_name, p.perk_desc, p.require_online, (o.perk_id >= 0) AS has_options
            FROM pp_perks AS p
            left JOIN pp_perk_options AS o ON o.perk_id = p.perk_id
            left JOIN pp_cartitems AS ci ON ci.perk_id = p.perk_id AND ci.expiry_length = 0
            WHERE member_id = {int:member_id} GROUP BY (ci.perk_id)
        ',array(
            "member_id" => $context['user']['id'],
        ));
        if (!$result) {
            array_push($context["shop_errors"], $smcFunc['db_error']());
            ShopIndex();
            return;
        }
        $items = array();
        while ($row = $smcFunc['db_fetch_assoc']($result)) {
            array_push($items, array(
                "perk_id" => $row['perk_id'],
                "perk_name" => $row['perk_name'],
                "perk_desc" => $row['perk_desc'],
                "perk_price" => $row['perk_price'],
                "require_online" => $row["require_online"],
                "expiry_length" => 0,
                "has_options" => $row['has_options'],
            ));
        }
        $smcFunc['db_free_result']($result);

        // Get all selected perks WITH options
        $result = $smcFunc['db_query']('', '
            SELECT ci.perk_id, o.option_price, p.perk_name, p.perk_desc, p.require_online, ci.expiry_length
            FROM pp_cartitems AS ci
            JOIN pp_perk_options AS o ON ci.perk_id = o.perk_id AND ci.expiry_length > 0 AND ci.expiry_length = o.option_expiry_length
            JOIN pp_perks AS p ON p.perk_id = ci.perk_id
            WHERE member_id = {int:member_id}
        ',array(
            "member_id" => $context['user']['id'],
        ));
        if (!$result) {
            array_push($context["shop_errors"], $smcFunc['db_error']());
            ShopIndex();
            return;
        }
        while ($row = $smcFunc['db_fetch_assoc']($result)) {
            array_push($items, array(
                "perk_id" => $row['perk_id'],
                "perk_name" => $row['perk_name'],
                "perk_desc" => $row['perk_desc'],
                "perk_price" => $row['option_price'],
                "require_online" => $row["require_online"],
                "expiry_length" => $row['expiry_length'],
                "has_options" => 1,
            ));
        }
        $smcFunc['db_free_result']($result);
        if (count($items) == 0) {
            array_push($context["shop_errors"], "No items in cart.");
            ShopIndex();
            return;
        }

        // Prepare order, to get the id for following foreign-keys
        $smcFunc['db_insert'](
            'replace',
            'pp_orders',
            array(
                'member_id' => 'int',
                'purchased' => 'string',
            ),
            array(
                $context["user"]["id"],
                date("Y-m-d H:i:s", $context["shop_thisdate"]),
            )
        ,array('order_id'));
        // Get back the created order id
        $orderid = $smcFunc['db_insert_id']('pp_orders', 'order_id');
        if (!isset($orderid)) {
            array_push($context["shop_errors"], 'Error creating order.');
            ShopIndex();
            return;
        }

        // This will be our result, we send to paypal as donation value
        $ordersum=0;

        foreach ($items as $item) {

            // First we insert this item into the activation list
            $smcFunc['db_insert'](
                'replace',
                'pp_orders_perks',
                array(
                    'perk_id' => 'int',
                    'order_id' => 'int',
                    'perk_name' => 'string',
                    'perk_desc' => 'text',
                    'perk_price' => 'float',
                    'perk_require_online' => 'int',
                    'perk_expiry_length' => 'int',
                    'has_options' => 'int',
                ),
                array(
                    $item['perk_id'],
                    $orderid,
                    $item['perk_name'],
                    $item['perk_desc'],
                    $item['perk_price'],
                    $item['require_online'],
                    $item['expiry_length'],
                    intval($item['has_options']),
                )
            ,array('order_perk_id'));
            $orderperkid = $smcFunc['db_insert_id']('pp_orders_perks', 'order_perk_id');

            // Get the commands for this perk
            $result = $smcFunc['db_query']('', '
                SELECT *
                FROM pp_perk_commands
                WHERE perk_id = {int:perk_id}
            ',array(
                "perk_id" => $item['perk_id'],
            ));
            if (!$result) {
                array_push($context["shop_errors"], $smcFunc['db_error']());
                ShopIndex();
                return;
            }
            // The list of all commands
            $cmds = array();
            while ($row = $smcFunc['db_fetch_assoc']($result)) {
                array_push($cmds, $row['command']);
            }
            $smcFunc['db_free_result']($result);

            // Put these commands in our orders tables
            foreach ($cmds as $cmd) {
                $smcFunc['db_insert'](
                    'replace',
                    'pp_orders_perks_commands',
                    array(
                        'orders_perks_id' => 'int',
                        'command' => 'string',
                    ),
                    array(
                        $orderperkid,
                        $cmd,
                    )
                ,array('orders_perks_commands_id'));
            }

            // Get the expiry commands for this perk
            $result = $smcFunc['db_query']('', '
                SELECT *
                FROM pp_perk_expiry_commands
                WHERE perk_id = {int:perkid}
            ',array(
                "perkid" => $item['perk_id'],
            ));
            if (!$result) {
                array_push($context["shop_errors"], $smcFunc['db_error']());
                ShopIndex();
                return;
            }
            // The list of all expiry commands
            $ecmds = array();
            while ($row = $smcFunc['db_fetch_assoc']($result)) {
                array_push($ecmds, $row['expiry_command']);
            }
            $smcFunc['db_free_result']($result);

            // Put these expiry commands in our orders tables
            foreach ($ecmds as $ecmd) {
                $smcFunc['db_insert'](
                    'replace',
                    'pp_orders_perks_expiry_commands',
                    array(
                        'orders_perks_id' => 'int',
                        'expiry_command' => 'string',
                    ),
                    array(
                        $orderperkid,
                        $ecmd,
                    )
                ,array('orders_perks_expiry_commands_id'));
            }

            // finally add the price to the paypal sum
            $ordersum+=$item['perk_price'];
        }

        // Commit the transaction
        $smcFunc['db_transaction']('commit');
        // Mark this transaction as done now.
        $context["shop_transaction_done"] = true;

        $context['shop_payment_data'] = array(
            'business' => $context['paypal_email'],
            'return' => 'http://bangl.de/index.php?action=shop&sa=success',
            'cancel_return' => 'http://bangl.de/index.php?action=shop&sa=cancel',
            'notify_url' => 'http://bangl.de/index.php?action=shop&sa=ipn',
            'item_name' => "Donation",
            'amount' => $ordersum,
            'key' => md5(date("Y-m-d:").rand()),
            'item_number' => $orderid,
            'rm' => '2',
            'cmd' => '_xclick',
            'currency_code' => 'EUR',
        );
        ShopIndex();
    }
}

function ShopSuccess() {
    global $context;
    if ($context["user"]["is_logged"]) {
        array_push($context["shop_success"], "Thank you for your Donation.");

        // DEBUG
        foreach ($_POST as $key => $value) {
            array_push($context["shop_success"], "$key: $value");
        }
        // DEBUG END

        // TODO: Delete cart here

        ShopIndex();
    }
}

function ShopCancel() {
    global $context;
    if ($context["user"]["is_logged"]) {
        array_push($context["shop_errors"], "The order was canceled!");
        ShopIndex();
    }
}

function ShopIPN() {
    global $p, $context, $smcFunc;

    if ($p->validate_ipn()) { 

        $order_email = $p->ipn_data['payer_email'];
        $order_id = $p->ipn_data['item_number'];
        $order_status = $p->ipn_data['payment_status'];
        foreach ($p->ipn_data as $key => $value) {
            $order_debugdata .= "\n$key: $value";
        }
        if($order_status == 'Completed' or $order_status == 'Pending'){
            $result = $smcFunc['db_query']('', '
                UPDATE pp_orders
                SET paid = {date:paid}
                WHERE order_id = {int:order_id}
            ',array(
                "paid" => $context["shop_thisdate"],
                "order_id" => $order_id,
            ));
            if (!$result) {
                return;
            }
            $smcFunc['db_free_result']($result);
        }
    }
}

//##################################################################
// Database calls

// Get all "perk_options"
// by perk_id
//
// returns 2dim assoc array
function db_get_perk_options($perk_id) {
    global $context, $smcFunc;
    if (!$context["user"]["is_logged"]) return;

    $result = array();

    $dbresult = $smcFunc['db_query']('', '
        SELECT option_expiry_length, option_price
        FROM pp_perk_options
        WHERE perk_id = {int:perk_id}
    ',array(
        "perk_id" => $perk_id,
    ));
    if (!$dbresult) {
        array_push($context["shop_errors"], $smcFunc['db_error']());
        return;
    }
    while ($row = $smcFunc['db_fetch_assoc']($dbresult)) {
        array_push(
            $result,
            array(
                "option_expiry_length" => $row["option_expiry_length"],
                "option_price" => $row["option_price"],
            )
        );
    }
    $smcFunc['db_free_result']($dbresult);

    return $result;
}

// Get all "perk_commands"
// by perk_id
//
// returns 2dim assoc array
function db_get_perk_commands($perk_id) {
    global $context, $smcFunc;
    if (!$context["user"]["is_logged"]) return;

    $result = array();

    $dbresult = $smcFunc['db_query']('', '
        SELECT *
        FROM pp_perk_commands
        WHERE perk_id = {int:perk_id}
    ',array(
        "perk_id" => $perk_id,
    ));
    if (!$dbresult) {
        array_push($context["shop_errors"], $smcFunc['db_error']());
        ShopIndex();
        return;
    }
    // The list of all commands
    while ($row = $smcFunc['db_fetch_assoc']($dbresult)) {
        array_push($result, $row['command']);
    }
    $smcFunc['db_free_result']($dbresult);

    return $result;
}

// Get all "perk_expiry_commands"
// by perk_id
//
// returns 2dim assoc array
function db_get_perk_expiry_commands($perk_id) {
    global $context, $smcFunc;
    if (!$context["user"]["is_logged"]) return;

    $result = array();

    $dbresult = $smcFunc['db_query']('', '
        SELECT *
        FROM pp_perk_expiry_commands
        WHERE perk_id = {int:perk_id}
    ',array(
        "perk_id" => $perk_id,
    ));
    if (!$dbresult) {
        array_push($context["shop_errors"], $smcFunc['db_error']());
        ShopIndex();
        return;
    }
    // The list of all expiry commands
    while ($row = $smcFunc['db_fetch_assoc']($dbresult)) {
        array_push($result, $row['expiry_command']);
    }
    $smcFunc['db_free_result']($dbresult);

    return $result;
}

// Get all "perks"
//
// returns 2dim assoc array
function db_get_perks() {
    global $context, $smcFunc;
    if (!$context["user"]["is_logged"]) return;

    $tmp = array();
    $dbresult = $smcFunc['db_query']('', '
        SELECT perk_id, perk_name, perk_desc, require_online, perk_price
        FROM pp_perks
    ',array());
    if (!$dbresult) {
        array_push($context["shop_errors"], $smcFunc['db_error']());
        return;
    }
    while ($row = $smcFunc['db_fetch_assoc']($dbresult)) {
        array_push(
            $tmp,
            array(
                "perk_id" => $row["perk_id"],
                "perk_name" => $row["perk_name"],
                "perk_desc" => $row["perk_desc"],
                "require_online" => $row["require_online"],
                "perk_price" => $row["perk_price"],
            )
        );
    }
    $smcFunc['db_free_result']($dbresult);

    // Push the options into the array
    $result = array();
    foreach ($tmp as $perk) {
        array_push(
            $result,
            array(
                "perk_id" => $perk["perk_id"],
                "perk_name" => $perk["perk_name"],
                "perk_desc" => $perk["perk_desc"],
                "require_online" => $perk["require_online"],
                "perk_price" => $perk["perk_price"],
                "perk_options" => db_get_perk_options($perk["perk_id"]),
                "perk_commands" => db_get_perk_commands($perk["perk_id"]),
                "perk_expiry_commands" => db_get_perk_expiry_commands($perk["perk_id"]),
            )
        );
    }
    return $result;
}

// Get "perk"
// by perk_id, (expiry_length)
//
// returns assoc array
function db_get_perk($perk_id, $expiry_length) {
    global $context, $smcFunc;
    if (!$context["user"]["is_logged"]) return;

    $dbresult = $smcFunc['db_query']('', '
        SELECT p.perk_id, perk_name, perk_desc, require_online, perk_price, option_price, option_expiry_length
        FROM pp_perk_options AS o
        RIGHT JOIN pp_perks AS p ON o.perk_id = p.perk_id
        WHERE p.perk_id = {int:perk_id}
    ',array(
        "perk_id" => $perk_id,
    ));
    if (!$dbresult) {
        array_push($context["shop_errors"], $smcFunc['db_error']());
        return;
    }
    while ($row = $smcFunc['db_fetch_assoc']($dbresult)) {
        $result["perk_id"] = $row['perk_id'];
        $result["perk_name"] = $row['perk_name'];
        $result["perk_desc"] = $row['perk_desc'];
        $result["require_online"] = $row['require_online'];
        $result["perk_price"] = $row['perk_price'];

        // Has this perk options?
        if ($row['option_expiry_length'] > 0) {

            // Is this option the even selected one?
            $selected = false;
            if ($expiry_length == $row['option_expiry_length'])
                $selected = true;

            if (empty($result["perk_options"]))
                $result["perk_options"] = array();
            array_push($result["perk_options"], array(
                "option_price" => $row['option_price'],
                "option_expiry_length" => $row['option_expiry_length'],
                "selected" => $selected,
            ));
        }

        $result["perk_commands"] = db_get_perk_commands($perk_id);
        $result["perk_expiry_commands"] = db_get_perk_expiry_commands($perk_id);
    }
    $smcFunc['db_free_result']($dbresult);

    return $result;
}

// Get all "perk_prices" and "option_prices" merged and grouped.
//
// returns float array
function db_get_prices() {
    global $context, $smcFunc;
    if (!$context["user"]["is_logged"]) return;

    $result = array();

    $dbresult = $smcFunc['db_query']('', '
        SELECT p.perk_price, o.option_price
        FROM pp_perk_options AS o
        RIGHT JOIN pp_perks AS p ON o.perk_id = p.perk_id
        GROUP BY p.perk_price, o.option_price
    ',array());
    if (!$dbresult) {
        array_push($context["shop_errors"], $smcFunc['db_error']());
        return;
    }
    while ($row = $smcFunc['db_fetch_assoc']($dbresult)) {
        if (!in_array($row["perk_price"], $result) && $row["perk_price"] != 0) {
            array_push($result, $row["perk_price"]);
        }
        if (!in_array($row["option_price"], $result) && $row["option_price"] != 0) {
            array_push($result, $row["option_price"]);
        }
    }
    $smcFunc['db_free_result']($dbresult);
    
    return $result;
}

// Get all cartitems
// by member_id
//
// returns 2dim assoc array
function db_get_cart($member_id) {
    global $context, $smcFunc;
    if (!$context["user"]["is_logged"]) return;

    $result = array();

    // Get all selected perks WITHOUT options
    $dbresult = $smcFunc['db_query']('', '
        SELECT ci.perk_id, p.perk_price, p.perk_name, (o.perk_id >= 0) AS has_options
        FROM pp_perks AS p
        left JOIN pp_perk_options AS o ON o.perk_id = p.perk_id
        left JOIN pp_cartitems AS ci ON ci.perk_id = p.perk_id AND ci.expiry_length = 0
        WHERE member_id = {int:member_id} GROUP BY (ci.perk_id)
    ',array(
        "member_id" => $member_id,
    ));
    if (!$dbresult) {
        array_push($context["shop_errors"], $smcFunc['db_error']());
        return;
    }
    while ($row = $smcFunc['db_fetch_assoc']($dbresult)) {
        array_push($result, array(
            "perk_id" => $row['perk_id'],
            "perk_price" => $row['perk_price'],
            "perk_name" => $row['perk_name'],
            "expiry_length" => 0,
            "has_options" => $row['has_options'],
        ));
    }
    $smcFunc['db_free_result']($dbresult);

    // Get all selected perks WITH options
    $dbresult = $smcFunc['db_query']('', '
        SELECT ci.perk_id, o.option_price, p.perk_name, ci.expiry_length
        FROM pp_cartitems AS ci
        JOIN pp_perk_options AS o ON ci.perk_id = o.perk_id AND ci.expiry_length > 0 AND ci.expiry_length = o.option_expiry_length
        JOIN pp_perks AS p ON p.perk_id = ci.perk_id
        WHERE member_id = {int:member_id}
    ',array(
        "member_id" => $member_id,
    ));
    if (!$dbresult) {
        array_push($context["shop_errors"], $smcFunc['db_error']());
        return;
    }
    while ($row = $smcFunc['db_fetch_assoc']($dbresult)) {
        array_push($result, array(
            "perk_id" => $row['perk_id'],
            "perk_price" => $row['option_price'],
            "perk_name" => $row['perk_name'],
            "expiry_length" => $row['expiry_length'],
            "has_options" => 1,
        ));
    }
    $smcFunc['db_free_result']($dbresult);

    sort($result);
    return $result;
}

// Get a small overview about a cart
// by member_id
//
// returns assoc array
function db_get_cart_overview($member_id) {
    global $context, $smcFunc;
    if (!$context["user"]["is_logged"]) return;

    $result = array();
    $result["count"] = 0;
    $result["sum"] = 0;

    // Get prices of all cartitems WITHOUT options
    $dbresult = $smcFunc['db_query']('', '
        SELECT SUM(p.perk_price) AS sum, COUNT(*) AS count
        FROM pp_cartitems AS ci
        JOIN pp_perks AS p ON ci.perk_id = p.perk_id AND ci.expiry_length = 0
        WHERE member_id = {int:member_id}
    ',array(
        "member_id" => $member_id,
    ));
    if (!$dbresult) {
        array_push($context["shop_errors"], $smcFunc['db_error']());
        return;
    }
    while ($row = $smcFunc['db_fetch_assoc']($dbresult)) {
        $result["count"] += $row['count'];
        $result["sum"] += $row['sum'];
    }
    $smcFunc['db_free_result']($dbresult);

    // Get prices of all cartitems WITH options, and add
    $dbresult = $smcFunc['db_query']('', '
        SELECT SUM(p.option_price) AS sum, COUNT(*) AS count
        FROM pp_cartitems AS ci
        JOIN pp_perk_options AS p ON ci.perk_id = p.perk_id AND ci.expiry_length > 0 AND ci.expiry_length = p.option_expiry_length
        WHERE member_id = {int:member_id}
    ',array(
        "member_id" => $member_id,
    ));
    if (!$dbresult) {
        array_push($context["shop_errors"], $smcFunc['db_error']());
        return;
    }
    while ($row = $smcFunc['db_fetch_assoc']($dbresult)) {
        $result["count"] += $row['count'];
        $result["sum"] += $row['sum'];
    }
    $smcFunc['db_free_result']($dbresult);
    
    return $result;
}

function db_add_cartitem($member_id, $perk_id, $expiry_length) {
    global $context, $smcFunc;
    if (!$context["user"]["is_logged"]) return;

    // Add perk to cart
    $smcFunc['db_insert'](
        'insert',
        'pp_cartitems',
        array(
            'member_id' => 'int',
            'perk_id' => 'int',
            'expiry_length' => 'int',
        ),
        array(
            $member_id,
            $perk_id,
            $expiry_length,
        )
    ,array('cartitem_id'));
}

function db_remove_cartitem($member_id, $perk_id) {
    global $context, $smcFunc;
    if (!$context["user"]["is_logged"]) return;

    // Remove perk from cart
    $result = $smcFunc['db_query']('', '
        DELETE FROM pp_cartitems
        WHERE member_id = {int:member_id}
        AND perk_id = {int:perk_id}
    ',array(
        "member_id" => $member_id,
        "perk_id" => $perk_id,
    ));
    if (!$result) {
        array_push($context["shop_errors"], $smcFunc['db_error']());
        return;
    }
    $smcFunc['db_free_result']($result);

}

function db_add_perk($perk_data) {
    global $context, $smcFunc;
    if (!$context["user"]["is_logged"]) return;
    if (!$context["user"]["is_admin"]) return;

    $smcFunc['db_transaction']('begin');
    // Mark that this transaction is not finished yet
    $context["shop_transaction_started"] = true;
    $context["shop_transaction_done"] = false;

    if (isset($perk_data['perk_id'])) {
        $perk_id = $perk_data['perk_id'];

        // Update the perk main data
        $result = $smcFunc['db_query']('', '
            UPDATE pp_perks
            SET perk_name = {string:perk_name}
            , perk_desc = {text:perk_desc}
            , perk_price = {float:perk_price}
            , require_online = {int:require_online}
            WHERE perk_id = {int:perk_id}
        ', array(
            "perk_name" => mysql_real_escape_string(stripslashes(un_htmlspecialchars($perk_data["perk_name"]))),
            "perk_desc" => preg_replace('/\\\r/', '', mysql_real_escape_string(stripslashes(un_htmlspecialchars($perk_data["perk_desc"])))),
            "perk_price" => $perk_data["perk_price"],
            "require_online" => $perk_data["require_online"],
            "perk_id" => $perk_id,
        ));
        if (!$result) {
            array_push($context["shop_errors"], $smcFunc['db_error']());
            return;
        }

        // Clean up the commands
        $result = $smcFunc['db_query']('', '
            DELETE FROM pp_perk_commands
            WHERE perk_id = {int:perk_id}
        ', array(
            "perk_id" => $perk_id,
        ));
        if (!$result) {
            array_push($context["shop_errors"], $smcFunc['db_error']());
            return;
        }

        // Clean up the expiry commands
        $result = $smcFunc['db_query']('', '
            DELETE FROM pp_perk_expiry_commands
            WHERE perk_id = {int:perk_id}
        ', array(
            "perk_id" => $perk_id,
        ));
        if (!$result) {
            array_push($context["shop_errors"], $smcFunc['db_error']());
            return;
        }

        // Clean up the options
        $result = $smcFunc['db_query']('', '
            DELETE FROM pp_perk_options
            WHERE perk_id = {int:perk_id}
        ', array(
            "perk_id" => $perk_id,
        ));
        if (!$result) {
            array_push($context["shop_errors"], $smcFunc['db_error']());
            return;
        }
    } else {
        // ADD PERK ACTION ONLY

        // Create a new perk
        $smcFunc['db_insert'](
            'insert',
            'pp_perks',
            array(
                'perk_name' => 'string',
                'perk_desc' => 'text',
                'perk_price' => 'float',
                'require_online' => 'int',
            ),
            array(
                mysql_real_escape_string(stripslashes(un_htmlspecialchars($perk_data["perk_name"]))),
                preg_replace('/\\\r/', '', mysql_real_escape_string(stripslashes(un_htmlspecialchars($perk_data["perk_desc"])))),
                $perk_data["perk_price"],
                $perk_data["require_online"],
            )
        , array('perk_id'));
        $perk_id = $smcFunc['db_insert_id']('pp_perks', 'perk_id');
    }

    // Add the commands
    foreach ($perk_data['perk_commands'] as $cmd) {
        if ($cmd != "") {
            $smcFunc['db_insert'](
                'insert',
                'pp_perk_commands',
                array(
                    'perk_id' => 'int',
                    'command' => 'string',
                ),
                array(
                    $perk_id,
                    mysql_real_escape_string(stripslashes(un_htmlspecialchars($cmd))),
                )
            , array('command_id'));
        }
    }

    // Add the expiry commands
    foreach ($perk_data['perk_expiry_commands'] as $ecmd) {
        if ($ecmd != "") {
            $smcFunc['db_insert'](
                'insert',
                'pp_perk_expiry_commands',
                array(
                    'perk_id' => 'int',
                    'expiry_command' => 'string',
                ),
                array(
                    $perk_id,
                    mysql_real_escape_string(stripslashes(un_htmlspecialchars($ecmd))),
                )
            , array('expiry_command_id'));
        }
    }

    // Add the options
    foreach ($perk_data['perk_options'] as $option) {
        if ($option["option_expiry_length"] > 0 && $option["option_price"] > 0) {
            $smcFunc['db_insert'](
                'insert',
                'pp_perk_options',
                array(
                    'perk_id' => 'int',
                    'option_expiry_length' => 'int',
                    'option_price' => 'float',
                ),
                array(
                    $perk_id,
                    $option["option_expiry_length"],
                    $option["option_price"],
                )
            , array('perk_id', 'option_expiry_length'));
        }
    }

    $smcFunc['db_transaction']('commit');
    $context["shop_transaction_done"] = true;
}
?>