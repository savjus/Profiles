<?php

function socialEscape($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function socialFailure($message) {
    global $database;
    error_log("Social network database error: " . mysqli_error($database->connection));
    die(socialEscape($message));
}

function socialPrepare($sql, $types, $params) {
    global $database;
    $statement = mysqli_prepare($database->connection, $sql);
    if (!$statement) {
        socialFailure("Nepavyko paruošti duomenų bazės užklausos.");
    }
    if ($types !== "") {
        $arguments = array($statement, $types);
        for ($i = 0; $i < count($params); $i++) {
            $arguments[] = &$params[$i];
        }
        if (!call_user_func_array("mysqli_stmt_bind_param", $arguments)) {
            socialFailure("Nepavyko susieti užklausos duomenų.");
        }
    }
    if (!mysqli_stmt_execute($statement)) {
        socialFailure("Nepavyko įvykdyti duomenų bazės užklausos.");
    }
    return $statement;
}

function socialExecute($sql, $types, $params) {
    $statement = socialPrepare($sql, $types, $params);
    $affected = mysqli_stmt_affected_rows($statement);
    mysqli_stmt_close($statement);
    return $affected;
}

function socialFetchAll($sql, $types, $params) {
    $statement = socialPrepare($sql, $types, $params);
    $metadata = mysqli_stmt_result_metadata($statement);
    if (!$metadata) {
        mysqli_stmt_close($statement);
        return array();
    }

    $fields = mysqli_fetch_fields($metadata);
    $values = array_fill(0, count($fields), null);
    $arguments = array($statement);
    foreach ($values as $index => $value) {
        $arguments[] = &$values[$index];
    }
    if (!call_user_func_array("mysqli_stmt_bind_result", $arguments)) {
        socialFailure("Nepavyko perskaityti duomenų bazės atsakymo.");
    }

    $rows = array();
    while (mysqli_stmt_fetch($statement)) {
        $row = array();
        foreach ($fields as $index => $field) {
            $row[$field->name] = $values[$index];
        }
        $rows[] = $row;
    }
    mysqli_free_result($metadata);
    mysqli_stmt_close($statement);
    return $rows;
}

function socialFetchOne($sql, $types, $params) {
    $rows = socialFetchAll($sql, $types, $params);
    return count($rows) ? $rows[0] : null;
}

function socialIsModerator() {
    global $session;
    return $session->isManager() || $session->isAdmin();
}

function socialIsAdmin() {
    global $session;
    return $session->isAdmin();
}

function socialRequireLogin() {
    global $session;
    if (!$session->logged_in) {
        header("Location: index.php");
        exit;
    }
}

function socialToken() {
    if (!isset($_SESSION['social_token'])) {
        $_SESSION['social_token'] = md5(uniqid(mt_rand(), true));
    }
    return $_SESSION['social_token'];
}

function socialRequireToken() {
    if (!isset($_POST['token']) || !isset($_SESSION['social_token']) ||
            !is_string($_POST['token']) ||
            $_SESSION['social_token'] !== $_POST['token']) {
        header("HTTP/1.1 403 Forbidden");
        die("Neteisingas saugumo žetonas. Atnaujinkite puslapį ir bandykite dar kartą.");
    }
}

function socialPostForm($action, $tab, $fields) {
    echo '<form method="post" action="social.php?tab=' . socialEscape($tab) . '">';
    echo '<input type="hidden" name="token" value="' . socialEscape(socialToken()) . '">';
    echo '<input type="hidden" name="action" value="' . socialEscape($action) . '">';
    foreach ($fields as $name => $value) {
        echo '<input type="hidden" name="' . socialEscape($name) . '" value="' . socialEscape($value) . '">';
    }
}

?>
