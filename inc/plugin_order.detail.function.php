<?php


/*----------------------------------------------------------------------
   GLPI - Gestionnaire Libre de Parc Informatique
   Copyright (C) 2003-2008 by the INDEPNET Development Team.

   http://indepnet.net/   http://glpi-project.org/
   ----------------------------------------------------------------------
   LICENSE

   This file is part of GLPI.

   GLPI is free software; you can redistribute it and/or modify
   it under the terms of the GNU General Public License as published by
   the Free Software Foundation; either version 2 of the License, or
   (at your option) any later version.

   GLPI is distributed in the hope that it will be useful,
   but WITHOUT ANY WARRANTY; without even the implied warranty of
   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
   GNU General Public License for more details.

   You should have received a copy of the GNU General Public License
   along with GLPI; if not, write to the Free Software
   Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
   ----------------------------------------------------------------------*/
/*----------------------------------------------------------------------
    Original Author of file: Benjamin Fontan
    Purpose of file:
    ----------------------------------------------------------------------*/

function getQuantity($FK_order, $FK_reference) {
	global $CFG_GLPI, $DB;
	$query = "	SELECT count(*) AS quantity FROM glpi_plugin_order_detail
									WHERE FK_order=$FK_order
									AND FK_reference=$FK_reference";
	$result = $DB->query($query);
	return ($DB->result($result, 0, 'quantity'));
}

function getDelivredQuantity($FK_order, $FK_reference) {
	global $CFG_GLPI, $DB;
	$query = "	SELECT count(*) AS delivredquantity FROM glpi_plugin_order_detail
									WHERE FK_order=$FK_order
									AND FK_reference=$FK_reference
									AND status='1'";
	$result = $DB->query($query);
	return ($DB->result($result, 0, 'delivredquantity'));
}

function getPrices($FK_order) {
	global $CFG_GLPI, $DB;
	$query = "SELECT SUM(price_ati) as priceTTC, SUM(price_discounted) as priceHT FROM `glpi_plugin_order_detail` WHERE FK_order=$FK_order";
	$result = $DB->query($query);
	return $DB->fetch_array($result);
}

function getPriceTaxIncluded($priceHT, $taxes) {
	if (!$priceHT)
		return 0;
	else
		return $priceHT + (($priceHT * $taxes) / 100);
}

function addDetails($referenceID, $orderID, $quantity, $price, $discounted_price, $taxes) {
	global $LANG;
	if (referenceExistsInOrder($orderID, $referenceID))
		addMessageAfterRedirect($LANG['plugin_order']['detail'][28], false, ERROR);
	else {
		if ($quantity > 0) {
			$detail = new PluginOrderDetail;
			for ($i = 0; $i < $quantity; $i++) {
				$input["FK_order"] = $orderID;
				$input["FK_reference"] = $referenceID;
				$input["price_taxfree"] = $price;
				$input["price_discounted"] = $price - ($price * ($discounted_price / 100));
				$input["status"] = ORDER_STATUS_DRAFT;
				$input["price_ati"] = getPriceTaxIncluded($input["price_discounted"], getDropdownName("glpi_dropdown_plugin_order_taxes", $taxes));
				$detail->add($input);
			}
		}
	}
}

function referenceExistsInOrder($orderID, $referenceID) {
	global $DB;
	$query = "SELECT ID FROM `glpi_plugin_order_detail` WHERE FK_order=$orderID AND FK_reference=$referenceID";
	$result = $DB->query($query);
	if ($DB->numrows($result))
		return true;
	else
		return false;
}
function deleteDetails($referenceID, $orderID) {
	global $DB;

	$query = " DELETE FROM `glpi_plugin_order_detail`
					WHERE FK_order=$orderID 
					AND FK_reference=$referenceID";
	$DB->query($query);

	$query = " DELETE FROM `glpi_plugin_order_device`
					WHERE FK_order=$orderID ";
	$DB->query($query);

}

/* show details of orders */
function showDetail($target, $ID) {
	$plugin_order_detail = new PluginOrderDetail();
	$plugin_order_detail->showFormDetail($target, $ID);
	$plugin_order_detail->showAddForm($target, $ID);
}

/* show form of linking order to glpi items */
function showItem($instID, $search = '') {
	global $DB, $CFG_GLPI, $LANG, $INFOFORM_PAGES, $LINK_ID_TABLE;
	if (!plugin_order_haveRight("order", "r"))
		return false;
	$rand = mt_rand();
	$plugin_order = new PluginOrder();
	if ($plugin_order->getFromDB($instID)) {
		$canedit = $plugin_order->can($instID, 'w');
		$query = "SELECT DISTINCT device_type 
								FROM `glpi_plugin_order_device` 
								WHERE FK_order = '$instID' 
								ORDER BY device_type";
		$result = $DB->query($query);
		$number = $DB->numrows($result);
		$i = 0;
		if (isMultiEntitiesMode()) {
			$colsup = 1;
		} else {
			$colsup = 0;
		}
		echo "<form method='post' name='order_form$rand' id='order_form$rand'  action=\"" . $CFG_GLPI["root_doc"] . "/plugins/order/front/plugin_order.form.php\">";
		echo "<div class='center'><table class='tab_cadre_fixe'>";
		if ($number == 0) {
			echo "<tr><th colspan='" . ($canedit ? (5 + $colsup) : (4 + $colsup)) . "'>" . $LANG['plugin_order']['item'][3] . "</th></tr></table></div></form>";
		} else {
			echo "<tr><th colspan='" . ($canedit ? (5 + $colsup) : (4 + $colsup)) . "'>" . $LANG['plugin_order']['item'][0] . ":</th></tr><tr>";

			echo "<th>" . $LANG['common'][17] . "</th>";
			echo "<th>" . $LANG['common'][16] . "</th>";
			if (isMultiEntitiesMode())
				echo "<th>" . $LANG['entity'][0] . "</th>";
			echo "<th>" . $LANG['common'][19] . "</th>";
			echo "<th>" . $LANG['common'][20] . "</th>";
			echo "</tr>";

			$ci = new CommonItem();
			while ($i < $number) {
				$type = $DB->result($result, $i, "device_type");
				if (haveTypeRight($type, "r")) {
					$column = "name";
					if ($type == TRACKING_TYPE)
						$column = "ID";
					if ($type == KNOWBASE_TYPE)
						$column = "question";

					$query = "SELECT " . $LINK_ID_TABLE[$type] . ".*, glpi_plugin_order_device.ID AS IDD, glpi_entities.ID AS entity " .
					" FROM glpi_plugin_order_device, " . $LINK_ID_TABLE[$type] .
					" LEFT JOIN glpi_entities ON (glpi_entities.ID=" . $LINK_ID_TABLE[$type] . ".FK_entities) " .
					" WHERE " . $LINK_ID_TABLE[$type] . ".ID = glpi_plugin_order_device.FK_device 
											AND glpi_plugin_order_device.device_type='$type' 
											AND glpi_plugin_order_device.FK_order = '$instID' " . getEntitiesRestrictRequest(" AND ", $LINK_ID_TABLE[$type], '', '', isset ($CFG_GLPI["recursive_type"][$type]));

					if (in_array($LINK_ID_TABLE[$type], $CFG_GLPI["template_tables"])) {
						$query .= " AND " . $LINK_ID_TABLE[$type] . ".is_template='0'";
					}
					$query .= " ORDER BY glpi_entities.completename, " . $LINK_ID_TABLE[$type] . ".$column";

					if ($result_linked = $DB->query($query))
						if ($DB->numrows($result_linked)) {
							$ci->setType($type);
							while ($data = $DB->fetch_assoc($result_linked)) {
								$ID = "";
								if ($type == TRACKING_TYPE)
									$data["name"] = $LANG['job'][38] . " " . $data["ID"];
								if ($type == KNOWBASE_TYPE)
									$data["name"] = $data["question"];

								if ($_SESSION["glpiview_ID"] || empty ($data["name"]))
									$ID = " (" . $data["ID"] . ")";
								$name = "<a href=\"" . $CFG_GLPI["root_doc"] . "/" . $INFOFORM_PAGES[$type] . "?ID=" . $data["ID"] . "\">" .
								$data["name"] . "$ID</a>";

								echo "<tr class='tab_bg_1'>";
								echo "<td class='center'>" . $ci->getType() . "</td>";

								echo "<td class='center' " . (isset ($data['deleted']) && $data['deleted'] ? "class='tab_bg_2_2'" : "") . ">" . $name . "</td>";
								if (isMultiEntitiesMode())
									echo "<td class='center'>" . getDropdownName("glpi_entities", $data['entity']) . "</td>";
								echo "<td class='center'>" . (isset ($data["serial"]) ? "" . $data["serial"] . "" : "-") . "</td>";
								echo "<td class='center'>" . (isset ($data["otherserial"]) ? "" . $data["otherserial"] . "" : "-") . "</td>";
								echo "</tr>";
							}
						}
				}
				$i++;
			}

			echo "</table></div>";
			echo "</form>";
		}
	}
}
?>