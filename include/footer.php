<?php

/**
 * Just a little page footer, tells how many registered members
 * there are, how many users currently logged in and viewing site,
 * and how many guests viewing site. 
 */
if (isset($database)) {
    echo ""
    . "<table width=100% "
    . "style=\"padding:1px;background-color:#fff1b8;border:1px dashed #c9a34a;\">\n"
    . "<tr align=\"center\"><td>\n"
    . "<b>Registruotų vartotojų kiekis: </b> " . $database->getNumMembers() . ".&nbsp"
    . "<b>Dabar prisijungę: </b> " . $database->num_active_users . ".&nbsp"
    . "<b>Svečiai: </b> " . $database->num_active_guests . ".&nbsp"
    . "</td></tr>\n"
    . "</table>\n"
    ;
}
