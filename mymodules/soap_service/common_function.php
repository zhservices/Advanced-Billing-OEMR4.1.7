<?php
// +-----------------------------------------------------------------------------+ 
// Copyright (C) 2011 Z&H Consultancy Services Private Limited <sam@zhservices.com>
//
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.
//
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
//
// A copy of the GNU General Public License is included along with this program:
// openemr/interface/login/GnuGPL.html
// For more information write to the Free Software
// Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
// 
// Author:   Eldho Chacko <eldho@zhservices.com>
//           Jacob T Paul <jacob@zhservices.com>
//
// +------------------------------------------------------------------------------+
//SANITIZE ALL ESCAPES
$sanitize_all_escapes=true;
//

//STOP FAKE REGISTER GLOBALS
$fake_register_globals=false;
//

require_once(dirname(__FILE__)."/../../interface/globals.php");

function UpdateDeletedEncounter($pid,$encounter){
    $mainCode = 'ENCOUNTER';
    $subCode = 'ENCT_DELETE';
    $main = sqlQuery("SELECT cl_list_slno FROM customlists WHERE cl_list_item_short IN ('".$mainCode."') AND cl_list_type IN ('15')");
    $sub = sqlQuery("SELECT cl_list_slno FROM customlists WHERE cl_list_item_short IN ('".$subCode."') AND cl_list_type IN ('20')");
        $count=sqlQuery("SELECT ac_count FROM arr_status WHERE ac_pid='".$pid."' AND ac_encounter='".$encounter."' AND ac_status='".$sub['cl_list_slno']."' ORDER BY ac_id DESC LIMIT 1");
        if(!$count['ac_count']) $count['ac_count']=0;
        $statusId = sqlInsert("INSERT INTO arr_status (ac_uid,ac_pid,ac_encounter,ac_master_status,ac_status,ac_count,ac_comment,ac_reason) VALUES ('".$_SESSION['authId']."','".$pid."','".$encounter."','".$main['cl_list_slno']."','".$sub['cl_list_slno']."','".$count['ac_count']."','".$comment."','".$reason."')");
        $master = sqlQuery("select am_id from arr_master where am_pid='$pid' and am_encounter='$encounter'");
        $masterId = $master['am_id'];
        if($masterId != '' && $masterId != 0){
            sqlQuery("UPDATE arr_master SET am_statustrack='".$statusId."', am_currentstatus='".$sub['cl_list_slno']."' WHERE am_pid='".$pid."' AND am_encounter='".$encounter."'");
        }
        else{
            $masterId = sqlInsert("INSERT INTO arr_master (am_uid,am_pid,am_encounter,am_statustrack,am_currentstatus,am_ins1,am_ins2,am_ins3,am_inslevel) VALUES ('".$_SESSION['authId']."','".$pid."','".$encounter."','".$statusId."','".$sub['cl_list_slno']."','$am_ins1','$am_ins2','$am_ins3','$am_inslevel')");
        }
    sqlQuery("UPDATE arr_status SET ac_arr_id='".$masterId."' WHERE ac_pid='".$pid."' AND ac_encounter='".$encounter."'");
}
?>