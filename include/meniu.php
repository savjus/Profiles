<?php
if (isset($session)) {
    $path = isset($_SESSION['path']) ? $_SESSION['path'] : "";
    unset($_SESSION['path']);
    ?>
    <table width="100%" border="0" cellspacing="1" cellpadding="3" class="meniu">
        <tr>
            <td>
                <?php if ($session->logged_in) { ?>
                    Prisijungęs vartotojas: <b><?php echo htmlspecialchars($session->username, ENT_QUOTES, "UTF-8"); ?></b>
                <?php } else { ?>
                    Svečias
                <?php } ?>
            </td>
        </tr>
        <tr>
            <td>
                [<a href="<?php echo $path; ?>index.php"><?php echo $session->logged_in ? "Pradžia" : "Prisijungti"; ?></a>]
                [<a href="<?php echo $path; ?>social.php?tab=feed">Įrašai</a>]
                [<a href="<?php echo $path; ?>social.php?tab=search">Vartotojų paieška</a>]
                <?php if ($session->logged_in) { ?>
                    [<a href="<?php echo $path; ?>social.php?tab=following">Sekami vartotojai</a>]
                    [<a href="<?php echo $path; ?>social.php?tab=messages">Žinutės</a>]
                    [<a href="<?php echo $path; ?>social.php?tab=profile">Mano profilis</a>]
                    [<a href="<?php echo $path; ?>userinfo.php?user=<?php echo urlencode($session->username); ?>">Mano paskyra</a>]
                    [<a href="<?php echo $path; ?>useredit.php">Redaguoti paskyrą</a>]
                    <?php if ($session->isManager() || $session->isAdmin()) { ?>
                        [<a href="<?php echo $path; ?>social.php?tab=moderation">Turinio priežiūra</a>]
                    <?php } ?>
                    <?php if ($session->isAdmin()) { ?>
                        [<a href="<?php echo $path; ?>admin/admin.php">Administratoriaus sąsaja</a>]
                    <?php } ?>
                    [<a href="<?php echo $path; ?>process.php">Atsijungti</a>]
                <?php } else { ?>
                    [<a href="<?php echo $path; ?>register.php">Registruotis</a>]
                <?php } ?>
            </td>
        </tr>
    </table>
    <?php
}
?>
