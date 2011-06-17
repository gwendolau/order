<?php
/*
 * @version $Id: HEADER 2011-03-23 15:41:26 tsmr $
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2010 by the INDEPNET Development Team.

 http://indepnet.net/   http://glpi-project.org
 -------------------------------------------------------------------------

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
 --------------------------------------------------------------------------
// ----------------------------------------------------------------------
// Original Authors of file: 
// NOUH Walid & FONTAN Benjamin & CAILLAUD Xavier & François Legastelois
// Purpose of file: plugin order v1.4.0 - GLPI 0.80
// ----------------------------------------------------------------------
// ---------------------------------------------------------------------- */

function plugin_order_install() {
   global $DB,$LANG;

   include_once(GLPI_ROOT."/plugins/order/inc/profile.class.php");

   $install   = false;
   $update110 = false;
   $update120 = false;
   $update130 = false;
   $update140 = false;
   $update150 = false;
   
   $migration = new Migration("1.5.0");
   
   if (TableExists("glpi_plugin_order_detail")) {
      if (!FieldExists("glpi_plugin_order_detail","discount")) { // version 1.1.0

         //----------------------------------------------------------------------//
         //-----------------------Version 1.1.0 migration -----------------------//
         //----------------------------------------------------------------------//
         $migration->displayMessage("Update to version 1.1.0");

         $update110=true;

         //Running SQL update file
         $DB->runFile(GLPI_ROOT ."/plugins/order/sql/update-1.1.0.sql");

         $migration->displayMessage($LANG['update'][141] . ' - glpi_dropdown_plugin_order_taxes');
         $query = "SELECT `name` FROM `glpi_dropdown_plugin_order_taxes` ";
         foreach ($DB->request($query) as $data) {
            if(strpos($data["name"], ',')) {
               $name= str_replace(',', '.', $data["name"]);
               $query = "UPDATE `glpi_dropdown_plugin_order_taxes`
                         SET `name` = '".$name."'
                         WHERE `name`= '".$data["name"]."'";
               $DB->query($query) or die($DB->error());
            }
         }

         $migration->displayMessage($LANG['update'][141] . ' - glpi_plugin_order');
         if (FieldExists("glpi_plugin_order", "numordersupplier")) {
            $query = "SELECT `numordersupplier`,`numbill`,`ID` FROM `glpi_plugin_order` ";
            foreach ($DB->request($query) as $data) {
               $query = "INSERT INTO  `glpi_plugin_order_suppliers`
                         (`ID`, `FK_order`, `numorder`, `numbill`) VALUES
                         (NULL, '".$data["ID"]."', '".$data["numordersupplier"]."', '".$data["numbill"]."') ";
               $DB->query($query) or die($DB->error());
               
            }
         
         }

         if (FieldExists('glpi_plugin_order', 'numordersupplier')) {
            $query = "ALTER TABLE `glpi_plugin_order` DROP `numordersupplier`";
            $DB->query($query) or die($DB->error());
               
         }

         if (FieldExists('glpi_plugin_order', 'numbill')) {
            $query = "ALTER TABLE `glpi_plugin_order` DROP `numbill`";
            $DB->query($query) or die($DB->error());
               
         }
      }
      

         //----------------------------------------------------------------------//
         //-----------------------Version 1.1.2 migration -----------------------//
         //----------------------------------------------------------------------//

      $migration->displayMessage("Update to version 1.1.2");
      
      if (!FieldExists("glpi_plugin_order_detail","delivery_status")) {
         $query = "ALTER TABLE `glpi_plugin_order_detail` ADD `delivery_status` int(1) NOT NULL default '0'";
         $DB->query($query) or die($DB->error());

      }
      
      if (!FieldExists("glpi_plugin_order_detail","delivery_comments")) {
         $query = "ALTER TABLE `glpi_plugin_order_detail` ADD `delivery_comments` TEXT";
         $DB->query($query) or die($DB->error());

      }
      
      if (!TableExists("glpi_dropdown_plugin_order_deliverystate")) {
         $query = "CREATE TABLE IF NOT EXISTS `glpi_dropdown_plugin_order_deliverystate` (
                     `ID` int(11) NOT NULL auto_increment,
                     `name` varchar(255) collate utf8_unicode_ci NOT NULL default '',
                     `comments` text,
                     PRIMARY KEY  (`ID`),
                     KEY `name` (`name`)
                  ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
         $DB->query($query) or die($DB->error());
      }
      
      if (TableExists("glpi_dropdown_plugin_order_status")) {
         $query = "DROP TABLE `glpi_dropdown_plugin_order_status`";
         $DB->query($query) or die($DB->error());
      }
      
      if (!TableExists("glpi_plugin_order_surveysuppliers")) {
         $query = "CREATE TABLE IF NOT EXISTS `glpi_plugin_order_surveysuppliers` (
                     `ID` int(11) NOT NULL auto_increment,
                     `FK_order` int(11) NOT NULL default 0,
                     `FK_enterprise` INT(11) NOT NULL DEFAULT 0,
                     `answer1` int(11) NOT NULL default 0,
                     `answer2` int(11) NOT NULL default 0,
                     `answer3` int(11) NOT NULL default 0,
                     `answer4` int(11) NOT NULL default 0,
                     `answer5` int(11) NOT NULL default 0,
                     `comment` varchar(255) collate utf8_unicode_ci NOT NULL default '',
                     PRIMARY KEY  (`ID`)
                  ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
         $DB->query($query) or die($DB->error());
      }
      
      if (!TableExists("glpi_plugin_order_preferences")) {
         $query = "CREATE TABLE `glpi_plugin_order_preferences` (
                     `ID` int(11) NOT NULL auto_increment,
                     `user_id` int(11) NOT NULL,
                     `template` varchar(255) NOT NULL,
                     `sign` varchar(255) NOT NULL,
                     PRIMARY KEY  (`ID`)
                  ) ENGINE=MyISAM;";

         $DB->query($query) or die($DB->error());
      }

      //Post 1.1.0
      $update120=true;
      $DB->runFile(GLPI_ROOT ."/plugins/order/sql/update-1.2.0.sql");
      
      if (!countElementsInTable("glpi_plugin_order_configs")) {
         $query = "INSERT INTO `glpi_plugin_order_configs`(id,use_validation,default_taxes) VALUES (1,0,0);";
         $DB->query($query) or die($DB->error());
      }
      
      $query = "SELECT `suppliers_id`, `entities_id`,`is_recursive`,`id` FROM `glpi_plugin_order_orders` ";
      foreach ($DB->request($query) as $data) {
         $query = "UPDATE `glpi_plugin_order_orders_suppliers`
                   SET `suppliers_id` = '".$data["suppliers_id"]."'
                   WHERE `plugin_order_orders_id` = '".$data["id"]."' ";
         $DB->query($query) or die($DB->error());

         $query = "UPDATE `glpi_plugin_order_orders_suppliers`
                   SET `entities_id` = '".$data["entities_id"]."',`is_recursive` = '".$data["is_recursive"]."'
                   WHERE `plugin_order_orders_id` = '".$data["id"]."' ";
         $DB->query($query) or die($DB->error());

         $query = "UPDATE `glpi_plugin_order_surveysuppliers`
                   SET `entities_id` = '".$data["entities_id"]."',`is_recursive` = '".$data["is_recursive"]."'
                   WHERE `plugin_order_orders_id` = '".$data["id"]."' ";
         $DB->query($query) or die($DB->error());
      }

      $query = "SELECT `entities_id`,`is_recursive`,`id` FROM `glpi_plugin_order_references` ";
      foreach ($DB->request($query) as $data) {
         $query = "UPDATE `glpi_plugin_order_references_suppliers`
                   SET `entities_id` = '".$data["entities_id"]."',`is_recursive` = '".$data["is_recursive"]."'
                   WHERE `plugin_order_references_id` = '".$data["id"]."' ";
         $DB->query($query) or die($DB->error());
      }
      
      $query="ALTER TABLE `glpi_plugin_order_profiles`
               DROP `name` ;";
      $result=$DB->query($query) or die($DB->error());

      Plugin::migrateItemType(array(3150 => 'PluginOrderOrder', 3151 => 'PluginOrderReference',
                                    3152 => 'PluginOrderReference_Supplier',
                                    3153 => 'PluginOrderBudget', 3154 => 'PluginOrderOrder_Supplier',
                                    3155 => 'PluginOrderReception'),
                              array("glpi_bookmarks", "glpi_bookmarks_users", 
                                    "glpi_displaypreferences", "glpi_documents_items", 
                                    "glpi_infocoms", "glpi_logs", "glpi_tickets"),
                              array("glpi_plugin_order_orders_items", 
                                    "glpi_plugin_order_references"));

   }

   if (TableExists("glpi_plugin_order_configs") 
      && !FieldExists("glpi_plugin_order_configs", "generate_assets")) { // version 1.3.0
      $update130 = true;
      $migration->displayMessage("Update to version 1.3.0");
      $DB->runFile(GLPI_ROOT ."/plugins/order/sql/update-1.3.0.sql");
   }
   
   if (TableExists("glpi_plugin_order_budgets")) { // version 1.4.0
      $update140 = true;
      $migration->displayMessage("Update to version 1.4.0");

      $DB->runFile(GLPI_ROOT ."/plugins/order/sql/update-1.4.0.sql");
   }
   
   if (FieldExists("glpi_plugin_order_orders_items", "plugin_order_ordertaxes_id")
         && !TableExists("glpi_plugin_order_orderstates")) { // version 1.5.0
      $update150 = true;
      $migration->displayMessage("Update to version 1.5.0");
      $DB->runFile(GLPI_ROOT ."/plugins/order/sql/update-1.5.0.sql");
   }
      
   if (!TableExists("glpi_plugin_order_orders")) { // not installed
      $install = true;
      $migration->displayMessage("Install version 1.5.0");
      if (!$DB->runFile(GLPI_ROOT ."/plugins/order/sql/empty-1.5.0.sql")) {
         die ("Install failed !");
      }
   }

   if($install || $update130) {
      $migration->displayMessage("Add order reception notification template");

      $query_id = "SELECT `id` 
                   FROM `glpi_notificationtemplates` 
                   WHERE `itemtype`='PluginOrderOrder_Item' 
                      AND `name` = 'Order Reception'";
      $result   = $DB->query($query_id) or die ($DB->error());
      $notifications_id = $DB->result($result, 0, 'id');
      
      if ($DB->numrows($result)) {
         $query="INSERT INTO `glpi_notificationtemplatetranslations`
                  VALUES(NULL, ".$notifications_id.",'','##lang.reception.title##','##lang.reception.title## 
   
   ##reception.orderurl## 
   
   ##lang.reception.ordername## : 
   ##reception.ordername## 
   ##lang.reception.orderdate## : 
   ##reception.orderdate## 
   
   ##lang.reception.orderentity## : 
   ##reception.orderentity## 
   ##lang.reception.orderstate## : 
   ##reception.orderstate## 
   
   ##lang.reception.ordernumber## : 
   ##reception.ordernumber## 
   ##lang.reception.orderbudget## : 
   ##reception.orderbudget## 
   
   ##lang.reception.orderlocation## : 
   ##reception.orderlocation## 
   ##lang.reception.ordertaxe## : 
   ##reception.ordertaxe## 
   
   ##lang.reception.ordersupplier## : 
   ##reception.ordersupplier## 
   ##lang.reception.orderpayment## : 
   ##reception.orderpayment## 
   
   ##lang.reception.ordercontact## : 
   ##reception.ordercontact## 
   ##lang.reception.orderport## : 
   ##reception.orderport## 
   
   ##lang.reception.ordercomment## : 
   ##reception.ordercomment## 
   ##lang.reception.ordernote## : 
   ##reception.ordernote## 
   
   Informations sur la r&#233;f&#233;rence r&#233;ceptionn&#233;e 
   
   ##reception.deliveryreference_url## 
   
   ##lang.reception.deliveryreference_name## : 
   ##reception.deliveryreference_name## 
   ##lang.reception.deliveryreference_itemtype## : 
   ##reception.deliveryreference_itemtype## 
   
   ##lang.reception.deliveryreference_type## : 
   ##reception.deliveryreference_type## 
   ##lang.reception.deliveryreference_model## : 
   ##reception.deliveryreference_model## 
   
   ##lang.reception.deliveryreference_manufacturer## : 
   ##reception.deliveryreference_manufacturer## 
   
   ##lang.reception.deliveryreference_comment## : 
   ##reception.deliveryreference_comment## 
   ##lang.reception.deliveryreference_note## : 
   ##reception.deliveryreference_note## 
   
   Informations sur la r&#233;ception 
   
   ##reception.deliveryurl## 
   
   ##lang.reception.deliverydate## : ##reception.deliverydate## 
   ##lang.reception.deliverystate## : ##reception.deliverystate## 
   ##lang.reception.deliverynumber## : ##reception.deliverynumber## 
   
   ##IFreception.associateditems_url##
   Informations sur le mat&#233;riel associ&#233; 
   
   ##reception.associateditems_url## 
   
   ##lang.reception.associateditems_name## : ##reception.associateditems_name## 
   ##lang.reception.associateditems_serial## : ##reception.associateditems_serial## 
   ##lang.reception.associateditems_otherserial## : ##reception.associateditems_otherserial## 
   ##lang.reception.associateditems_state## : ##reception.associateditems_state##
   ##ENDIFreception.associateditems_url##','&lt;table style=\"border: 1px solid black; border-collapse: collapse;\"&gt;
   &lt;tbody&gt;
   &lt;tr&gt;
   &lt;th style=\"background-color: #f2f2f2;\" colspan=\"4\"&gt;Informations sur la commande&lt;/th&gt;
   &lt;/tr&gt;
   &lt;tr&gt;
   &lt;td style=\"border: 1px solid black; background-color: #f2f2f2;\" colspan=\"4\"&gt;##reception.orderurl##&lt;/td&gt;
   &lt;/tr&gt;
   &lt;tr&gt;
   &lt;td style=\"border: 1px solid black;\"&gt;&lt;strong&gt;##lang.reception.ordername## :&lt;/strong&gt;&lt;/td&gt;
   &lt;td style=\"border: 1px solid black;\"&gt;##reception.ordername##&lt;/td&gt;
   &lt;td style=\"border: 1px solid black;\"&gt;&lt;strong&gt;##lang.reception.orderdate## :&lt;/strong&gt;&lt;/td&gt;
   &lt;td style=\"border: 1px solid black;\"&gt;##reception.orderdate##&lt;/td&gt;
   &lt;/tr&gt;
   &lt;tr&gt;
   &lt;td style=\"border: 1px solid black;\"&gt;&lt;strong&gt;##lang.reception.orderentity## :&lt;/strong&gt;&lt;/td&gt;
   &lt;td style=\"border: 1px solid black;\"&gt;##reception.orderentity##&lt;/td&gt;
   &lt;td style=\"border: 1px solid black;\"&gt;&lt;strong&gt;##lang.reception.orderstate## :&lt;/strong&gt;&lt;/td&gt;
   &lt;td style=\"border: 1px solid black;\"&gt;##reception.orderstate##&lt;/td&gt;
   &lt;/tr&gt;
   &lt;tr&gt;
   &lt;td style=\"border: 1px solid black;\"&gt;&lt;strong&gt;##lang.reception.ordernumber## :&lt;/strong&gt;&lt;/td&gt;
   &lt;td style=\"border: 1px solid black;\"&gt;##reception.ordernumber##&lt;/td&gt;
   &lt;td style=\"border: 1px solid black;\"&gt;&lt;strong&gt;##lang.reception.orderbudget## :&lt;/strong&gt;&lt;/td&gt;
   &lt;td style=\"border: 1px solid black;\"&gt;##reception.orderbudget##&lt;/td&gt;
   &lt;/tr&gt;
   &lt;tr&gt;
   &lt;td style=\"border: 1px solid black;\"&gt;&lt;strong&gt;##lang.reception.orderlocation## :&lt;/strong&gt;&lt;/td&gt;
   &lt;td style=\"border: 1px solid black;\"&gt;##reception.orderlocation##&lt;/td&gt;
   &lt;td style=\"border: 1px solid black;\"&gt;&lt;strong&gt;##lang.reception.ordertaxe## :&lt;/strong&gt;&lt;/td&gt;
   &lt;td style=\"border: 1px solid black;\"&gt;##reception.ordertaxe##&lt;/td&gt;
   &lt;/tr&gt;
   &lt;tr&gt;
   &lt;td style=\"border: 1px solid black;\"&gt;&lt;strong&gt;##lang.reception.ordersupplier## :&lt;/strong&gt;&lt;/td&gt;
   &lt;td style=\"border: 1px solid black;\"&gt;##reception.ordersupplier##&lt;/td&gt;
   &lt;td style=\"border: 1px solid black;\"&gt;&lt;strong&gt;##lang.reception.orderpayment## :&lt;/strong&gt;&lt;/td&gt;
   &lt;td style=\"border: 1px solid black;\"&gt;##reception.orderpayment##&lt;/td&gt;
   &lt;/tr&gt;
   &lt;tr&gt;
   &lt;td style=\"border: 1px solid black;\"&gt;&lt;strong&gt;##lang.reception.ordercontact## :&lt;/strong&gt;&lt;/td&gt;
   &lt;td style=\"border: 1px solid black;\"&gt;##reception.ordercontact##&lt;/td&gt;
   &lt;td style=\"border: 1px solid black;\"&gt;&lt;strong&gt;##lang.reception.orderport## :&lt;/strong&gt;&lt;/td&gt;
   &lt;td style=\"border: 1px solid black;\"&gt;##reception.orderport##&lt;/td&gt;
   &lt;/tr&gt;
   &lt;tr&gt;
   &lt;td style=\"border: 1px solid black;\"&gt;&lt;strong&gt;##lang.reception.ordercomment## :&lt;/strong&gt;&lt;/td&gt;
   &lt;td style=\"border: 1px solid black;\"&gt;##reception.ordercomment##&lt;/td&gt;
   &lt;td style=\"border: 1px solid black;\"&gt;&lt;strong&gt;##lang.reception.ordernote## :&lt;/strong&gt;&lt;/td&gt;
   &lt;td style=\"border: 1px solid black;\"&gt;##reception.ordernote##&lt;/td&gt;
   &lt;/tr&gt;
   &lt;/tbody&gt;
   &lt;/table&gt;
   &lt;hr /&gt;
   &lt;table style=\"border: 1px solid black; border-collapse: collapse;\"&gt;
   &lt;tbody&gt;
   &lt;tr&gt;
   &lt;th style=\"background-color: #f2f2f2;\" colspan=\"4\"&gt;Informations sur la r&#233;f&#233;rence r&#233;ceptionn&#233;e&lt;/th&gt;
   &lt;/tr&gt;
   &lt;tr&gt;
   &lt;td style=\"border: 1px solid black; background-color: #f2f2f2;\" colspan=\"4\"&gt;##reception.deliveryreference_url##&lt;/td&gt;
   &lt;/tr&gt;
   &lt;tr&gt;
   &lt;td style=\"border: 1px solid black;\"&gt;&lt;strong&gt;##lang.reception.deliveryreference_name## :&lt;/strong&gt;&lt;/td&gt;
   &lt;td style=\"border: 1px solid black;\"&gt;##reception.deliveryreference_name##&lt;/td&gt;
   &lt;td style=\"border: 1px solid black;\"&gt;&lt;strong&gt;##lang.reception.deliveryreference_itemtype## :&lt;/strong&gt;&lt;/td&gt;
   &lt;td style=\"border: 1px solid black;\"&gt;##reception.deliveryreference_itemtype##&lt;/td&gt;
   &lt;/tr&gt;
   &lt;tr&gt;
   &lt;td style=\"border: 1px solid black;\"&gt;&lt;strong&gt;##lang.reception.deliveryreference_type## :&lt;/strong&gt;&lt;/td&gt;
   &lt;td style=\"border: 1px solid black;\"&gt;##reception.deliveryreference_type##&lt;/td&gt;
   &lt;td style=\"border: 1px solid black;\"&gt;&lt;strong&gt;##lang.reception.deliveryreference_model## :&lt;/strong&gt;&lt;/td&gt;
   &lt;td style=\"border: 1px solid black;\"&gt;##reception.deliveryreference_model##&lt;/td&gt;
   &lt;/tr&gt;
   &lt;tr&gt;
   &lt;td style=\"border: 1px solid black;\" colspan=\"2\"&gt;&lt;strong&gt;##lang.reception.deliveryreference_manufacturer## :&lt;/strong&gt;&lt;/td&gt;
   &lt;td style=\"border: 1px solid black;\" colspan=\"2\"&gt;##reception.deliveryreference_manufacturer##&lt;/td&gt;
   &lt;/tr&gt;
   &lt;tr&gt;
   &lt;td style=\"border: 1px solid black;\"&gt;&lt;strong&gt;##lang.reception.deliveryreference_comment## :&lt;/strong&gt;&lt;/td&gt;
   &lt;td style=\"border: 1px solid black;\"&gt;##reception.deliveryreference_comment##&lt;/td&gt;
   &lt;td style=\"border: 1px solid black;\"&gt;&lt;strong&gt;##lang.reception.deliveryreference_note## :&lt;/strong&gt;&lt;/td&gt;
   &lt;td style=\"border: 1px solid black;\"&gt;##reception.deliveryreference_note##&lt;/td&gt;
   &lt;/tr&gt;
   &lt;/tbody&gt;
   &lt;/table&gt;
   &lt;hr /&gt;
   &lt;table style=\"border: 1px solid black; border-collapse: collapse;\"&gt;
   &lt;tbody&gt;
   &lt;tr&gt;
   &lt;th style=\"background-color: #f2f2f2;\"&gt;Informations sur la r&#233;ception&lt;/th&gt;
   &lt;/tr&gt;
   &lt;tr&gt;
   &lt;td style=\"border: 1px solid black; background-color: #f2f2f2;\"&gt;##reception.deliveryurl##&lt;/td&gt;
   &lt;/tr&gt;
   &lt;tr&gt;
   &lt;td style=\"border: 1px solid black;\"&gt;&lt;strong&gt;##lang.reception.deliverydate## : &lt;/strong&gt;##reception.deliverydate##             &lt;br /&gt;&lt;strong&gt;##lang.reception.deliverystate## : &lt;/strong&gt;##reception.deliverystate##             &lt;br /&gt;&lt;strong&gt;##lang.reception.deliverynumber## : &lt;/strong&gt;##reception.deliverynumber##&lt;/td&gt;
   &lt;/tr&gt;
   &lt;/tbody&gt;
   &lt;/table&gt;
   ##IFreception.associateditems_url##
   &lt;hr /&gt;
   &lt;table style=\"border: 1px solid black; border-collapse: collapse;\"&gt;
   &lt;tbody&gt;
   &lt;tr&gt;
   &lt;th style=\"background-color: #f2f2f2;\"&gt;Informations sur le mat&#233;riel associ&#233;&lt;/th&gt;
   &lt;/tr&gt;
   &lt;tr&gt;
   &lt;td style=\"border: 1px solid black; background-color: #f2f2f2;\"&gt;##reception.associateditems_url##&lt;/td&gt;
   &lt;/tr&gt;
   &lt;tr&gt;
   &lt;td style=\"border: 1px solid black;\"&gt;&lt;strong&gt;##lang.reception.associateditems_name## : &lt;/strong&gt;##reception.associateditems_name##             &lt;br /&gt;&lt;strong&gt;##lang.reception.associateditems_serial## : &lt;/strong&gt;##reception.associateditems_serial##             &lt;br /&gt;&lt;strong&gt;##lang.reception.associateditems_otherserial## : &lt;/strong&gt;##reception.associateditems_otherserial##             &lt;br /&gt;&lt;strong&gt;##lang.reception.associateditems_state## : &lt;/strong&gt;##reception.associateditems_state##&lt;/td&gt;
   &lt;/tr&gt;
   &lt;/tbody&gt;
   &lt;/table&gt;
   ##ENDIFreception.associateditems_url##')";
               
         $result=$DB->query($query) or die($DB->error());

         $migration->displayMessage("Add order reception notification");
   
         $query = "INSERT INTO `glpi_notifications`
                    VALUES (NULL, 'Taken delivery', 0, 'PluginOrderOrder_Item', 'delivered',
                           'mail',$notifications_id, '', 1, 1, NOW());";
         $result=$DB->query($query) or die($DB->error());
      }

      }   
      
   
   if($update140) {
      /* Migrate VAT */
      foreach ($DB->request("glpi_plugin_order_orders") as $data) {
         $query = "UPDATE `glpi_plugin_order_orders_items`
                   SET `plugin_order_ordertaxes_id` = '" . $data["plugin_order_ordertaxes_id"] . "'
                   WHERE `plugin_order_orders_id` = '" . $data["id"] . "'";
         $result = $DB->query($query);
      }

   }

   $migration->displayMessage("Update profile for current user");
   PluginOrderProfile::createFirstAccess($_SESSION['glpiactiveprofile']['id']);

   /* Insert Status */
   if($update150 || $install) {

      $migration->displayMessage("Add order validation notification template");

      //Do One time on 0.80
      $query_id = "SELECT `id` FROM `glpi_notificationtemplates` 
                   WHERE `itemtype`='PluginOrderOrder' 
                      AND `name` = 'Order Validation'";
      $result   = $DB->query($query_id) or die ($DB->error());
      if ($DB->numrows($result) > 0) {
         $notifications_id = $DB->result($result, 0, 'id');
         
         $query = "INSERT INTO `glpi_notificationtemplatetranslations` 
                  (`notificationtemplates_id`, `language`, `subject`, `content_text`, `content_html`)
                  VALUES($notifications_id, '','##lang.ordervalidation.title##',
                           '##lang.ordervalidation.url## : ##ordervalidation.url##
   ##lang.ordervalidation.entity## : ##ordervalidation.entity##
   ##IFordervalidation.name####lang.ordervalidation.name## : ##ordervalidation.name##
   ##ENDIFordervalidation.name##
   ##IFordervalidation.numorder####lang.ordervalidation.numorder## : ##ordervalidation.numorder##
   ##ENDIFordervalidation.numorder##
   ##IFordervalidation.orderdate####lang.ordervalidation.orderdate##  : ##ordervalidation.orderdate####ENDIFordervalidation.orderdate##
   ##IFordervalidation.state####lang.ordervalidation.state## : ##ordervalidation.state####ENDIFordervalidation.state##
   ##IFordervalidation.users####lang.ordervalidation.users## : ##ordervalidation.users####ENDIFordervalidation.users##
   
   ##IFordervalidation.comment####lang.ordervalidation.comment## : ##ordervalidation.comment####ENDIFordervalidation.comment##',
                           '&lt;p&gt;&lt;strong&gt;##lang.ordervalidation.url##&lt;/strong&gt; : &lt;a href=\"##ordervalidation.url##\"&gt;##ordervalidation.url##&lt;/a&gt;&lt;br /&gt;&lt;br /&gt;&lt;strong&gt;##lang.ordervalidation.entity##&lt;/strong&gt; : ##ordervalidation.entity##&lt;br /&gt; ##IFordervalidation.name##&lt;strong&gt;##lang.ordervalidation.name##&lt;/strong&gt; : ##ordervalidation.name####ENDIFordervalidation.name##&lt;br /&gt;##IFordervalidation.numorder##&lt;strong&gt;##lang.ordervalidation.numorder##&lt;/strong&gt; : ##ordervalidation.numorder####ENDIFordervalidation.numorder##&lt;br /&gt;##IFordervalidation.orderdate##&lt;strong&gt;##lang.ordervalidation.orderdate##&lt;/strong&gt; : ##ordervalidation.orderdate####ENDIFordervalidation.orderdate##&lt;br /&gt;##IFordervalidation.state##&lt;strong&gt;##lang.ordervalidation.state##&lt;/strong&gt; : ##ordervalidation.state####ENDIFordervalidation.state##&lt;br /&gt;##IFordervalidation.users##&lt;strong&gt;##lang.ordervalidation.users##&lt;/strong&gt; : ##ordervalidation.users####ENDIFordervalidation.users##&lt;br /&gt;&lt;br /&gt;##IFordervalidation.comment##&lt;strong&gt;##lang.ordervalidation.comment##&lt;/strong&gt; : ##ordervalidation.comment####ENDIFordervalidation.comment##&lt;/p&gt;');";
         $result=$DB->query($query) or die($DB->error());

         $migration->displayMessage("Add order validation notifications");
         
         $query = "INSERT INTO `glpi_notifications`
                                      VALUES (NULL, 'New Order Validation', 0, 'PluginOrderOrder', 'ask',
                                             'mail',".$notifications_id.",
                                             '', 1, 1, NOW());";
         $result=$DB->query($query) or die($DB->error());
         $query = "INSERT INTO `glpi_notifications`
                                      VALUES (NULL, 'Confirm Order Validation', 0, 'PluginOrderOrder', 'validation',
                                             'mail',".$notifications_id.",
                                             '', 1, 1, NOW());";
         $result=$DB->query($query) or die($DB->error());
         $query = "INSERT INTO `glpi_notifications`
                                      VALUES (NULL, 'Cancel Order Validation', 0, 'PluginOrderOrder', 'undovalidation',
                                             'mail',".$notifications_id.",
                                             '', 1, 1, NOW());";
         $result=$DB->query($query) or die($DB->error());
         $query = "INSERT INTO `glpi_notifications`
                                      VALUES (NULL, 'Cancel Order', 0, 'PluginOrderOrder', 'cancel',
                                             'mail',".$notifications_id.",
                                             '', 1, 1, NOW());";
         $result=$DB->query($query) or die($DB->error());

      }
      if ($update150) {
         $migration->displayMessage("Replace states_id byplugin_order_orderstates_id in glpi_plugin_order_orders");
         $query = "ALTER TABLE `glpi_plugin_order_orders` 
                   CHANGE `states_id` `plugin_order_orderstates_id` INT( 11 ) NOT NULL DEFAULT '1'";
         $result=$DB->query($query) or die($DB->error());

      }

      $migration->displayMessage("Convert order states as dropdown");
      $query = "INSERT INTO `glpi_plugin_order_orderstates` 
                  VALUES   (1, '".addslashes_deep($LANG['plugin_order']['status'][9])."', ''),
                           (2, '".addslashes_deep($LANG['plugin_order']['status'][7])."', ''),
                           (3, '".addslashes_deep($LANG['plugin_order']['status'][12])."', ''),
                           (4, '".addslashes_deep($LANG['plugin_order']['status'][1])."', ''),
                           (5, '".addslashes_deep($LANG['plugin_order']['status'][2])."', ''),
                           (6, '".addslashes_deep($LANG['plugin_order']['status'][10])."', '')";
      $result = $DB->query($query);
      
      $migration->displayMessage("Add default order state workflow");
      $query = "UPDATE `glpi_plugin_order_configs` 
                  SET   `order_status_draft` = 1,
                        `order_status_waiting_approval` = 2,
                        `order_status_approved` = 3,
                        `order_status_partially_delivred` = 4,
                        `order_status_completly_delivered` = 5,
                        `order_status_canceled`= 6 
                  WHERE `id` = 1";
      $result = $DB->query($query);
      
      $migration->displayMessage("Update orders with new status");
      $query = "UPDATE `glpi_plugin_order_orders` 
                  SET `plugin_order_orderstates_id` = 6 WHERE  `plugin_order_orderstates_id` = 5";
      $result = $DB->query($query);
      
      $query = "UPDATE `glpi_plugin_order_orders` 
                  SET `plugin_order_orderstates_id` = 5 WHERE  `plugin_order_orderstates_id` = 4";
      $result = $DB->query($query);
      
      $query = "UPDATE `glpi_plugin_order_orders` 
                  SET `plugin_order_orderstates_id` = 4 WHERE  `plugin_order_orderstates_id` = 3";
      $result = $DB->query($query);
      
      $query = "UPDATE `glpi_plugin_order_orders` 
                  SET `plugin_order_orderstates_id` = 3 WHERE  `plugin_order_orderstates_id` = 2";
      $result = $DB->query($query);
      
      $query = "UPDATE `glpi_plugin_order_orders` 
                  SET `plugin_order_orderstates_id` = 2 WHERE  `plugin_order_orderstates_id` = 1";
      $result = $DB->query($query);
      
      $query = "UPDATE `glpi_plugin_order_orders` 
                  SET `plugin_order_orderstates_id` = 1 WHERE  `plugin_order_orderstates_id` = 0";
      $result = $DB->query($query);
      
      $migration->displayMessage("Add new right 'bill' to profile");
      $query = "ALTER TABLE `glpi_plugin_order_profiles` ADD `bill` CHAR( 1 ) COLLATE utf8_unicode_ci DEFAULT NULL";
      $result = $DB->query($query);
      
      PluginOrderProfile::addRightToProfile($_SESSION['glpiactiveprofile']['id'], 'bill' , 'w');
   }
   
   return true;
}

function plugin_order_uninstall() {
   global $DB;

   /* drop all the plugin tables */
   $tables = array ("glpi_plugin_order_orders", "glpi_plugin_order_orders_items",
                    "glpi_plugin_order_profiles", "glpi_plugin_order_ordertaxes",
                    "glpi_plugin_order_orderpayments", "glpi_plugin_order_ordertypes",
                    "glpi_plugin_order_orderstates", "glpi_plugin_order_references",
                    "glpi_plugin_order_references_suppliers", "glpi_plugin_order_configs",
                    "glpi_plugin_order_orders_suppliers", "glpi_plugin_order_others",
                    "glpi_plugin_order_othertypes", "glpi_plugin_order_bills",
                    "glpi_plugin_order_deliverystates");

   foreach ($tables as $table)
      $DB->query("DROP TABLE IF EXISTS `$table`;");

   //old tables
   $tables = array ("glpi_plugin_order", "glpi_plugin_order_detail",
                    "glpi_plugin_order_device", "glpi_plugin_order_profiles",
                    "glpi_dropdown_plugin_order_status", "glpi_dropdown_plugin_order_taxes",
                    "glpi_dropdown_plugin_order_payment", "glpi_plugin_order_references",
                    "glpi_plugin_order_references_manufacturers", "glpi_plugin_order_config",
                    "glpi_plugin_order_budgets", "glpi_plugin_order_suppliers",
                    "glpi_plugin_order_mailing");

   foreach ($tables as $table)
      $DB->query("DROP TABLE IF EXISTS `$table`;");

   $in = "IN (" . implode(',', array ("'PluginOrderOrder'", "'PluginOrderReference'",
                                      "'PluginOrderReference_Supplier'")) . ")";

   $tables = array ("glpi_displaypreferences", "glpi_documents_items", "glpi_bookmarks",
                    "glpi_logs");

   foreach ($tables as $table) {
      $query = "DELETE FROM `$table` WHERE (`itemtype` " . $in." ) ";
      $DB->query($query);
   }
   
   $notif = new Notification();
   
   $options = array('itemtype' => 'PluginOrderOrder',
                    'event'    => 'ask',
                    'FIELDS'   => 'id');
   foreach ($DB->request('glpi_notifications', $options) as $data) {
      $notif->delete($data);
   }
   $options = array('itemtype' => 'PluginOrderOrder',
                    'event'    => 'validation',
                    'FIELDS'   => 'id');
   foreach ($DB->request('glpi_notifications', $options) as $data) {
      $notif->delete($data);
   }
   $options = array('itemtype' => 'PluginOrderOrder',
                    'event'    => 'cancel',
                    'FIELDS'   => 'id');
   foreach ($DB->request('glpi_notifications', $options) as $data) {
      $notif->delete($data);
   }
   $options = array('itemtype' => 'PluginOrderOrder',
                    'event'    => 'undovalidation',
                    'FIELDS'   => 'id');
   foreach ($DB->request('glpi_notifications', $options) as $data) {
      $notif->delete($data);
   }
   $options = array('itemtype' => 'PluginOrderOrder_Item',
                    'event'    => 'delivered',
                    'FIELDS'   => 'id');
   foreach ($DB->request('glpi_notifications', $options) as $data) {
      $notif->delete($data);
   }
   
   //templates
   $template = new NotificationTemplate();
   $translation = new NotificationTemplateTranslation();
   $options = array('itemtype' => 'PluginOrderOrder',
                    'FIELDS'   => 'id');
   foreach ($DB->request('glpi_notificationtemplates', $options) as $data) {
      $options_template = array('notificationtemplates_id' => $data['id'],
                    'FIELDS'   => 'id');
   
         foreach ($DB->request('glpi_notificationtemplatetranslations', 
                               $options_template) as $data_template) {
            $translation->delete($data_template);
         }
      $template->delete($data);
   }
   
   //templates
   $template = new NotificationTemplate();
   $translation = new NotificationTemplateTranslation();
   $options = array('itemtype' => 'PluginOrderOrder_Item',
                    'FIELDS'   => 'id');
   foreach ($DB->request('glpi_notificationtemplates', $options) as $data) {
      $options_template = array('notificationtemplates_id' => $data['id'],
                    'FIELDS'   => 'id');
   
         foreach ($DB->request('glpi_notificationtemplatetranslations', 
                               $options_template) as $data_template) {
            $translation->delete($data_template);
         }
      $template->delete($data);
   }

   return true;
}

/* define dropdown tables to be manage in GLPI : */
function plugin_order_getDropdown() {
   /* table => name */
   global $LANG;

   $plugin = new Plugin();
   if ($plugin->isActivated("order")) {
      return array ('PluginOrderOrderTaxe'     => $LANG['plugin_order'][25],
                    'PluginOrderOrderPayment'  => $LANG['plugin_order'][32],
                    'PluginOrderOrderType'     => $LANG['common'][17],
                    'PluginOrderOrderState'    => $LANG['plugin_order']['status'][0],
                    'PluginOrderOtherType'     => $LANG['plugin_order'][9],
                    'PluginOrderDeliveryState' => $LANG['plugin_order']['status'][3],
                    'PluginOrderBillState'     => $LANG['plugin_order']['bill'][2],
                    'PluginOrderBillType'      => $LANG['plugin_order']['bill'][1]);
   }
   else {
      return array ();
   }
}

/* define dropdown relations */
function plugin_order_getDatabaseRelations() {
   $plugin = new Plugin();
   if ($plugin->isActivated("order"))
      return array (
         "glpi_plugin_order_orderpayments" => array (
            "glpi_plugin_order_orders" => "plugin_order_orderpayments_id"
         ),
         "glpi_plugin_order_ordertaxes" => array (
            "glpi_plugin_order_orders" => "plugin_order_ordertaxes_id"
         ),
         "glpi_plugin_order_ordertypes" => array (
            "glpi_plugin_order_orders" => "plugin_order_ordertypes_id"
         ),
         "glpi_plugin_order_orderstates" => array (
            "glpi_plugin_order_orders" => "plugin_order_orderstates_id"
         ),
         "glpi_plugin_order_deliverystates" => array (
            "glpi_plugin_order_orders_items" => "plugin_order_deliverystates_id"
         ),
         "glpi_plugin_order_orders" => array (
            "glpi_plugin_order_orders_items" => "plugin_order_orders_id",
            "glpi_plugin_order_orders_suppliers" => "plugin_order_orders_id"
         ),
         "glpi_plugin_order_references" => array (
            "glpi_plugin_order_orders_items" => "plugin_order_references_id",
            "glpi_plugin_order_references_suppliers" => "plugin_order_references_id"
         ),
         "glpi_entities" => array (
            "glpi_plugin_order_orders" => "entities_id",
            "glpi_plugin_order_references" => "entities_id",
            "glpi_plugin_order_others" => "entities_id"
         ),
         "glpi_budgets" => array ("glpi_plugin_order_orders" => "budgets_id"),
         "glpi_plugin_order_othertypes" => array ("glpi_plugin_order_others" => "othertypes_id"),
         "glpi_suppliers" => array (
            "glpi_plugin_order_orders" => "suppliers_id",
            "glpi_plugin_order_orders_suppliers" => "suppliers_id",
            "glpi_plugin_order_references_suppliers" => "suppliers_id"
         ),
         "glpi_manufacturers" => array (
            "glpi_plugin_order_references" => "manufacturers_id"
         ),
         "glpi_contacts" => array (
            "glpi_plugin_order_orders" => "contacts_id"
         ),
         "glpi_locations" => array (
            "glpi_plugin_order_orders" => "locations_id"
         ),
         "glpi_profiles" => array (
            "glpi_plugin_order_profiles" => "profiles_id"
         )
      );
   else
      return array ();
}

////// SEARCH FUNCTIONS ///////(){

// Define search option for types of the plugins
function plugin_order_getAddSearchOptions($itemtype) {
   global $LANG;

   $sopt = array();
   if (plugin_order_haveRight("order","r")) {
      if (in_array($itemtype, PluginOrderOrder_Item::getClasses(true))) {
         $sopt[3160]['table']         = 'glpi_plugin_order_orders';
         $sopt[3160]['field']         = 'name';
         $sopt[3160]['linkfield']     = '';
         $sopt[3160]['name']          = $LANG['plugin_order'][39];
         $sopt[3160]['forcegroupby']  = true;
         $sopt[3160]['datatype']      = 'itemlink';
         $sopt[3160]['itemlink_type'] = 'PluginOrderOrder';

         $sopt[3161]['table']        = 'glpi_plugin_order_orders';
         $sopt[3161]['field']        = 'num_order';
         $sopt[3161]['linkfield']    = '';
         $sopt[3161]['name']         = $LANG['plugin_order'][0];
         $sopt[3161]['forcegroupby'] =  true;
         $sopt[3161]['datatype']      = 'itemlink';
         $sopt[3161]['itemlink_type'] = 'PluginOrderOrder';
      }
   }
   return $sopt;
}

function plugin_order_forceGroupBy($type){

   return true;
   /*
   switch ($type){
      case 'PluginOrderOrder':
         return true;
         break;

   }
   return false;*/
}

function plugin_order_addSelect($type, $ID, $num) {

   $searchopt = &Search::getOptions($type);
   $table     = $searchopt[$ID]["table"];
   $field     = $searchopt[$ID]["field"];

   if ($table == "glpi_plugin_order_references" && $num!=0) {
      return "`$table`.`itemtype`, `$table`.`$field` AS `ITEM_$num`, ";
   } else {
      return "";
   }

}

function plugin_order_addLeftJoin($type,$ref_table,$new_table,$linkfield,
                                       &$already_link_tables) {

   switch ($new_table){
      case "glpi_plugin_order_orders" : // From items
         $out = " LEFT JOIN `glpi_plugin_order_orders_items` ON (`$ref_table`.`id` = `glpi_plugin_order_orders_items`.`items_id` AND `glpi_plugin_order_orders_items`.`itemtype` = '$type') ";
         $out.= " LEFT JOIN `glpi_plugin_order_orders` ON (`glpi_plugin_order_orders`.`id` = `glpi_plugin_order_orders_items`.`plugin_order_orders_id`) ";
         return $out;
         break;
      case "glpi_budgets" : // From order list
         $out = " LEFT JOIN `glpi_budgets` ON (`glpi_plugin_order_orders`.`budgets_id` = `glpi_budgets`.`id`) ";
         return $out;
         break;
      case "glpi_contacts" : // From order list
         $out = " LEFT JOIN `glpi_contacts` ON (`glpi_plugin_order_orders`.`contacts_id` = `glpi_contacts`.`id`) ";
         return $out;
         break;
   }

   return "";
}

/* display custom fields in the search */
function plugin_order_giveItem($type, $ID, $data, $num) {
   global $CFG_GLPI, $LANG;

   $searchopt = &Search::getOptions($type);
   $table = $searchopt[$ID]["table"];
   $field = $searchopt[$ID]["field"];

   $PluginOrderReference = new PluginOrderReference();

   switch ($table . '.' . $field) {
      /* display associated items with order */
      case "glpi_plugin_order_references.types_id" :
         if (file_exists(GLPI_ROOT."/inc/".strtolower($data["itemtype"])."type.class.php")) {
            return Dropdown::getDropdownName(getTableForItemType($data["itemtype"]."Type"), 
                                             $data["ITEM_" . $num]);
         } else {
            return " ";
         }
         break;
      case "glpi_plugin_order_references.models_id" :
         if (file_exists(GLPI_ROOT."/inc/".strtolower($data["itemtype"])."model.class.php")) {
            return Dropdown::getDropdownName(getTableForItemType($data["itemtype"]."Model"), 
                                             $data["ITEM_" . $num]);
 
         } else {
            return " ";
         }
         break;
      case "glpi_plugin_order_references.templates_id" :
         if (!$data["ITEM_" . $num]) {
            return " ";

         } else {
            return $PluginOrderReference->getTemplateName($data["itemtype"], $data["ITEM_" . $num]);
         }
         break;
   }
   return "";
}

////// SPECIFIC MODIF MASSIVE FUNCTIONS ///////

function plugin_order_MassiveActions($type) {
   global $LANG;

   switch ($type) {
      case 'PluginOrderOrder' :
         return array ("plugin_order_transfert" => $LANG['buttons'][48]);
         break;
   }
   return array ();
}

function plugin_order_MassiveActionsDisplay($options=array()) {
   global $LANG;

   switch ($options['itemtype']) {
      case 'PluginOrderOrder' :
         switch ($options['action']) {
            // No case for add_document : use GLPI core one
            case "plugin_order_transfert" :
               Dropdown::show('Entity');
               echo "&nbsp;<input type=\"submit\" name=\"massiveaction\" class=\"submit\" value=\"" . 
                  $LANG['buttons'][2] . "\" >";
               break;
         }
         break;
   }
   return "";
}

function plugin_order_MassiveActionsProcess($data) {
   global $LANG, $DB;

   switch ($data['action']) {
      case "plugin_order_transfert" :
         if ($data['itemtype'] == 'PluginOrderOrder') {
            foreach ($data["item"] as $key => $val) {
               if ($val == 1) {
                  $PluginOrderOrder = new PluginOrderOrder();
                  $PluginOrderOrder->transfer($key,$data['entities_id']);
               }
            }
         }
         break;
   }
}

/* hook done on purge item case */
function plugin_item_purge_order($item) {

   $type = get_class($item);
   $temp = new PluginOrderOrder_Item();
   $temp->deleteByCriteria(array('itemtype' => $type, 'items_id' => $item->getField('id')));

   return true;
}

// Define headings added by the plugin
function plugin_get_headings_order($item,$withtemplate) {
   global $LANG;

   $type = get_Class($item);
   if ($type == 'Profile') {
      if ($item->getField('id') && $item->getField('interface')!='helpdesk') {
         return array(1 => $LANG['plugin_order']['title'][1]);
      }
   } else if (in_array($type, PluginOrderOrder_Item::getClasses(true)) 
               || $type == 'Supplier' 
                  || $type == 'Budget') {
      if ($item->getField('id') && !$withtemplate) {
         // Non template case
         return array(1 => $LANG['plugin_order']['title'][1]);
      }
   } else if ($type == 'Preference') {
      // Non template case
      return array(1 => $LANG['plugin_order']['title'][1]);
   }
   return false;
}

// Define headings actions added by the plugin
function plugin_headings_actions_order($item) {

   if (in_array(get_class($item),PluginOrderOrder_Item::getClasses(true))||
      get_class($item)=='Profile' 
         || get_class($item)=='Supplier' 
            || get_class($item)=='Budget' 
               || get_class($item)=='Preference') {
      return array(1 => "plugin_headings_order");
   }
   return false;
}

/* action heading */
function plugin_headings_order($item) {
   global $CFG_GLPI;

   $PluginOrderProfile        = new PluginOrderProfile();
   $PluginOrderOrder_Item     = new PluginOrderOrder_Item();
   $PluginOrderOrder          = new PluginOrderOrder();
   $PluginOrderReference      = new PluginOrderReference();
   $PluginOrderOrder_Supplier = new PluginOrderOrder_Supplier();
   $PluginOrderSurveySupplier = new PluginOrderSurveySupplier();

   switch (get_class($item)) {
      case 'Profile' :
         if (!$PluginOrderProfile->getFromDBByProfile($item->getField('id'))) {
            $PluginOrderProfile->createAccess($item->getField('id'));

         }
         $PluginOrderProfile->showForm($item->getField('id'), 
                                       array('target' => $CFG_GLPI["root_doc"].
                                                            "/plugins/order/front/profile.form.php"));
         break;
      case 'Supplier' :
         $PluginOrderReference->showReferencesFromSupplier($item->getField('id'));
         $PluginOrderOrder_Supplier->showDeliveries($item->getField('id'));
         $PluginOrderSurveySupplier->showGlobalNotation($item->getField('id'));
         break;
      case 'Budget' :
         $PluginOrderOrder->getAllOrdersByBudget($_POST["id"]);
         break;
      case "Preference" :
         $pref    = new PluginOrderPreference();
         $pref_ID = $pref->checkIfPreferenceExists(getLoginUserID());
         if (!$pref_ID) {
            $pref_ID = $pref->addDefaultPreference(getLoginUserID());

         }
         $pref->showForm($CFG_GLPI['root_doc']."/plugins/order/front/preference.form.php", $pref_ID,
                         getLoginUserID());
         break;
      default :
         if (in_array(get_class($item), PluginOrderOrder_Item::getClasses(true))) {
            $PluginOrderOrder_Item->showPluginFromItems(get_class($item),$item->getField('id'));
         }
         break;
   }
}

?>