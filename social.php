<?php
include("include/session.php");
include("include/social.php");

$tabs = array("feed", "search", "following", "messages", "profile", "moderation");
$tab = isset($_GET['tab']) && is_string($_GET['tab']) && in_array($_GET['tab'], $tabs) ? $_GET['tab'] : "feed";
if ($tab == "moderation" && !socialIsModerator()) {
    header("Location: social.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == "POST") {
    socialRequireLogin();
    socialRequireToken();
    $action = isset($_POST['action']) ? $_POST['action'] : "";
    $username = $session->username;

    if ($action == "post") {
        $content = trim(isset($_POST['content']) ? $_POST['content'] : "");
        if ($content === "" || strlen($content) > 5000) {
            die("Įrašo tekstas privalo būti nuo 1 iki 5000 simbolių.");
        }
        socialExecute("INSERT INTO social_posts (username, content, created_at) VALUES (?, ?, ?)",
                "ssi", array($username, $content, time()));
        $tab = "feed";
    } else if ($action == "comment") {
        $postId = isset($_POST['post_id']) ? (int) $_POST['post_id'] : 0;
        $content = trim(isset($_POST['content']) ? $_POST['content'] : "");
        if ($postId < 1 || $content === "" || strlen($content) > 2000 ||
                !socialFetchOne("SELECT id FROM social_posts WHERE id = ?", "i", array($postId))) {
            die("Komentaro duomenys netinkami.");
        }
        socialExecute("INSERT INTO social_comments (post_id, username, content, created_at) VALUES (?, ?, ?, ?)",
                "issi", array($postId, $username, $content, time()));
        $tab = "feed";
    } else if ($action == "react") {
        $postId = isset($_POST['post_id']) ? (int) $_POST['post_id'] : 0;
        if ($postId < 1 ||
                !socialFetchOne("SELECT id FROM social_posts WHERE id = ?", "i", array($postId))) {
            die("Įrašas nerastas.");
        }
        socialExecute("INSERT INTO social_reactions (post_id, username, reaction, created_at) VALUES (?, ?, 'patinka', ?) "
                . "ON DUPLICATE KEY UPDATE reaction = VALUES(reaction), created_at = VALUES(created_at)",
                "isi", array($postId, $username, time()));
        $tab = "feed";
    } else if ($action == "follow" || $action == "unfollow") {
        $target = isset($_POST['target']) ? trim($_POST['target']) : "";
        if ($target === "" || $target == $username ||
                !socialFetchOne("SELECT username FROM users WHERE username = ?", "s", array($target))) {
            die("Vartotojas nerastas arba jo sekti negalima.");
        }
        if ($action == "follow") {
            socialExecute("INSERT IGNORE INTO social_follows (follower, followed, created_at) VALUES (?, ?, ?)",
                    "ssi", array($username, $target, time()));
        } else {
            socialExecute("DELETE FROM social_follows WHERE follower = ? AND followed = ?",
                    "ss", array($username, $target));
        }
        header("Location: userinfo.php?user=" . urlencode($target));
        exit;
    } else if ($action == "profile") {
        $firstName = trim(isset($_POST['first_name']) ? $_POST['first_name'] : "");
        $lastName = trim(isset($_POST['last_name']) ? $_POST['last_name'] : "");
        $bio = trim(isset($_POST['bio']) ? $_POST['bio'] : "");
        if (strlen($firstName) > 80 || strlen($lastName) > 80 || strlen($bio) > 1000) {
            die("Profilio laukai per ilgi.");
        }
        socialExecute("UPDATE users SET first_name = ?, last_name = ?, bio = ? WHERE username = ?",
                "ssss", array($firstName, $lastName, $bio, $username));
        $tab = "profile";
    } else if ($action == "message") {
        $recipient = isset($_POST['recipient']) ? trim($_POST['recipient']) : "";
        $content = trim(isset($_POST['content']) ? $_POST['content'] : "");
        if ($recipient === "" || $recipient == $username || $content === "" ||
                strlen($content) > 5000 ||
                !socialFetchOne("SELECT username FROM users WHERE username = ?", "s", array($recipient))) {
            die("Žinutės duomenys netinkami.");
        }
        socialExecute("INSERT INTO social_messages (sender, recipient, content, created_at) VALUES (?, ?, ?, ?)",
                "sssi", array($username, $recipient, $content, time()));
        $tab = "messages";
    } else if ($action == "delete_post") {
        $postId = isset($_POST['post_id']) ? (int) $_POST['post_id'] : 0;
        $post = socialFetchOne("SELECT username FROM social_posts WHERE id = ?", "i", array($postId));
        if (!$post || ($post['username'] != $username && !socialIsModerator())) {
            header("HTTP/1.1 403 Forbidden");
            die("Neturite teisės pašalinti šio įrašo.");
        }
        socialExecute("DELETE FROM social_comments WHERE post_id = ?", "i", array($postId));
        socialExecute("DELETE FROM social_reactions WHERE post_id = ?", "i", array($postId));
        socialExecute("DELETE FROM social_posts WHERE id = ?", "i", array($postId));
        $tab = socialIsModerator() ? "moderation" : "feed";
    } else if ($action == "delete_comment") {
        $commentId = isset($_POST['comment_id']) ? (int) $_POST['comment_id'] : 0;
        $comment = socialFetchOne("SELECT username FROM social_comments WHERE id = ?", "i", array($commentId));
        if (!$comment || ($comment['username'] != $username && !socialIsModerator())) {
            header("HTTP/1.1 403 Forbidden");
            die("Neturite teisės pašalinti šio komentaro.");
        }
        socialExecute("DELETE FROM social_comments WHERE id = ?", "i", array($commentId));
        $tab = socialIsModerator() ? "moderation" : "feed";
    } else if ($action == "delete_message") {
        $messageId = isset($_POST['message_id']) ? (int) $_POST['message_id'] : 0;
        $message = socialFetchOne("SELECT sender, recipient FROM social_messages WHERE id = ?", "i", array($messageId));
        if (!$message || ($message['sender'] != $username && $message['recipient'] != $username &&
                !socialIsModerator())) {
            header("HTTP/1.1 403 Forbidden");
            die("Neturite teisės pašalinti šios žinutės.");
        }
        socialExecute("DELETE FROM social_messages WHERE id = ?", "i", array($messageId));
        $tab = socialIsModerator() ? "moderation" : "messages";
    } else {
        header("HTTP/1.1 400 Bad Request");
        die("Nežinomas veiksmas.");
    }

    header("Location: social.php?tab=" . urlencode($tab) . "&saved=1");
    exit;
}

$pageTitle = "Socialinis tinklas";
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title><?php echo socialEscape($pageTitle); ?></title>
    <link href="include/styles.css" rel="stylesheet" type="text/css">
</head>
<body>
<table class="center"><tr><td>
    <center><img src="pictures/top.png" alt=""></center>
    <?php include("include/meniu.php"); ?>
    <nav class="social-nav">
        <a href="social.php?tab=feed">Įrašai</a> |
        <a href="social.php?tab=search">Vartotojų paieška</a>
        <?php if ($session->logged_in) { ?>
            | <a href="social.php?tab=following">Sekami vartotojai</a>
            | <a href="social.php?tab=messages">Žinutės</a>
            | <a href="social.php?tab=profile">Mano profilis</a>
            <?php if (socialIsModerator()) { ?>
                | <a href="social.php?tab=moderation">Turinio priežiūra</a>
            <?php } ?>
        <?php } ?>
    </nav>
    <?php if (isset($_GET['saved'])) { ?><p class="social-notice">Pakeitimai išsaugoti.</p><?php } ?>
    <?php if ($tab == "feed" || $tab == "following") { ?>
        <h1><?php echo $tab == "following" ? "Sekamų vartotojų įrašai" : "Naujausi įrašai"; ?></h1>
        <?php if ($tab == "following" && !$session->logged_in) { ?>
            <p>Norėdami peržiūrėti sekamų vartotojų įrašus, prisijunkite.</p>
        <?php } else { ?>
            <?php if ($session->logged_in) { ?>
                <section class="social-card">
                    <h2>Sukurti įrašą</h2>
                    <?php socialPostForm("post", "feed", array()); ?>
                        <textarea name="content" maxlength="5000" required></textarea><br>
                        <button type="submit">Skelbti</button>
                    </form>
                </section>
            <?php } ?>
            <?php
            if ($tab == "following") {
                $posts = socialFetchAll("SELECT p.id, p.username, p.content, p.created_at, u.first_name, u.last_name, "
                        . "(SELECT COUNT(*) FROM social_reactions r WHERE r.post_id = p.id) AS reaction_count "
                        . "FROM social_posts p JOIN users u ON u.username = p.username "
                        . "WHERE p.username = ? OR p.username IN (SELECT followed FROM social_follows WHERE follower = ?) "
                        . "ORDER BY p.created_at DESC LIMIT 50",
                        "ss", array($session->username, $session->username));
            } else {
                $posts = socialFetchAll("SELECT p.id, p.username, p.content, p.created_at, u.first_name, u.last_name, "
                        . "(SELECT COUNT(*) FROM social_reactions r WHERE r.post_id = p.id) AS reaction_count "
                        . "FROM social_posts p JOIN users u ON u.username = p.username ORDER BY p.created_at DESC LIMIT 50",
                        "", array());
            }
            if (!count($posts)) {
                echo "<p>Įrašų dar nėra.</p>";
            }
            foreach ($posts as $post) {
                $comments = socialFetchAll("SELECT id, username, content, created_at FROM social_comments "
                        . "WHERE post_id = ? ORDER BY created_at ASC LIMIT 50", "i", array($post['id']));
                $displayName = trim($post['first_name'] . " " . $post['last_name']);
                ?>
                <article class="social-card">
                    <p><a href="userinfo.php?user=<?php echo urlencode($post['username']); ?>">
                        <?php echo socialEscape($displayName != "" ? $displayName : $post['username']); ?></a>
                        · <?php echo date("Y-m-d H:i", $post['created_at']); ?></p>
                    <p class="social-content"><?php echo nl2br(socialEscape($post['content'])); ?></p>
                    <p><?php echo (int) $post['reaction_count']; ?> patinka</p>
                    <?php if ($session->logged_in) { ?>
                        <?php socialPostForm("react", "feed", array("post_id" => $post['id'])); ?>
                            <button type="submit">Patinka</button>
                        </form>
                    <?php } ?>
                    <?php foreach ($comments as $comment) { ?>
                        <div class="social-comment">
                            <a href="userinfo.php?user=<?php echo urlencode($comment['username']); ?>"><?php echo socialEscape($comment['username']); ?></a>:
                            <?php echo nl2br(socialEscape($comment['content'])); ?>
                            <?php if ($session->logged_in &&
                                    ($comment['username'] == $session->username || socialIsModerator())) { ?>
                                <?php socialPostForm("delete_comment", $tab, array("comment_id" => $comment['id'])); ?>
                                    <button type="submit">Pašalinti</button>
                                </form>
                            <?php } ?>
                        </div>
                    <?php } ?>
                    <?php if ($session->logged_in) { ?>
                        <?php socialPostForm("comment", "feed", array("post_id" => $post['id'])); ?>
                            <input type="text" name="content" maxlength="2000" placeholder="Komentaras" required>
                            <button type="submit">Komentuoti</button>
                        </form>
                    <?php } ?>
                    <?php if ($session->logged_in &&
                            ($post['username'] == $session->username || socialIsModerator())) { ?>
                        <?php socialPostForm("delete_post", $tab, array("post_id" => $post['id'])); ?>
                            <button type="submit" onclick="return confirm('Pašalinti įrašą?');">Pašalinti įrašą</button>
                        </form>
                    <?php } ?>
                </article>
            <?php } ?>
        <?php } ?>
    <?php } else if ($tab == "search") { ?>
        <h1>Vartotojų paieška</h1>
        <form method="get" action="social.php">
            <input type="hidden" name="tab" value="search">
            <label>Vardas <input type="text" name="first_name" value="<?php echo socialEscape(isset($_GET['first_name']) ? $_GET['first_name'] : ""); ?>"></label>
            <label>Pavardė <input type="text" name="last_name" value="<?php echo socialEscape(isset($_GET['last_name']) ? $_GET['last_name'] : ""); ?>"></label>
            <button type="submit">Ieškoti</button>
        </form>
        <?php
        $firstName = trim(isset($_GET['first_name']) ? $_GET['first_name'] : "");
        $lastName = trim(isset($_GET['last_name']) ? $_GET['last_name'] : "");
        if ($firstName !== "" || $lastName !== "") {
            $users = socialFetchAll("SELECT username, first_name, last_name FROM users "
                    . "WHERE first_name LIKE ? AND last_name LIKE ? ORDER BY first_name, last_name LIMIT 100",
                    "ss", array("%" . $firstName . "%", "%" . $lastName . "%"));
            if (!count($users)) {
                echo "<p>Vartotojų nerasta.</p>";
            }
            foreach ($users as $user) {
                $name = trim($user['first_name'] . " " . $user['last_name']);
                echo '<p><a href="userinfo.php?user=' . urlencode($user['username']) . '">'
                        . socialEscape($name != "" ? $name : $user['username']) . '</a></p>';
            }
        }
        ?>
    <?php } else if ($tab == "following") { ?>
        <?php socialRequireLogin(); ?>
    <?php } else if ($tab == "messages") { ?>
        <?php if (!$session->logged_in) { ?>
            <p>Norėdami siųsti ir skaityti privačias žinutes, prisijunkite.</p>
        <?php } else { ?>
            <h1>Privačios žinutės</h1>
            <section class="social-card">
                <h2>Rašyti žinutę</h2>
                <?php socialPostForm("message", "messages", array()); ?>
                    <label>Gavėjas (vartotojo vardas)
                        <input type="text" name="recipient" maxlength="30" required
                               value="<?php echo socialEscape(isset($_GET['to']) ? $_GET['to'] : ""); ?>">
                    </label><br>
                    <textarea name="content" maxlength="5000" required></textarea><br>
                    <button type="submit">Siųsti</button>
                </form>
            </section>
            <?php
            $messages = socialFetchAll("SELECT id, sender, recipient, content, created_at FROM social_messages "
                    . "WHERE sender = ? OR recipient = ? ORDER BY created_at DESC LIMIT 100",
                    "ss", array($session->username, $session->username));
            if (!count($messages)) {
                echo "<p>Žinučių nėra.</p>";
            }
            foreach ($messages as $message) {
                ?>
                <article class="social-card">
                    <p><?php echo $message['sender'] == $session->username ? "Išsiųsta" : "Gauta"; ?>
                        · <a href="userinfo.php?user=<?php echo urlencode($message['sender'] == $session->username ? $message['recipient'] : $message['sender']); ?>">
                            <?php echo socialEscape($message['sender'] == $session->username ? $message['recipient'] : $message['sender']); ?></a>
                        · <?php echo date("Y-m-d H:i", $message['created_at']); ?></p>
                    <p class="social-content"><?php echo nl2br(socialEscape($message['content'])); ?></p>
                    <?php socialPostForm("delete_message", "messages", array("message_id" => $message['id'])); ?>
                        <button type="submit">Pašalinti žinutę</button>
                    </form>
                </article>
            <?php } ?>
        <?php } ?>
    <?php } else if ($tab == "profile") { ?>
        <?php if (!$session->logged_in) { ?>
            <p>Norėdami sukurti profilį, prisijunkite.</p>
        <?php } else { ?>
            <?php $profile = $database->getUserInfo($session->username); ?>
            <h1>Mano profilis</h1>
            <section class="social-card">
                <?php socialPostForm("profile", "profile", array()); ?>
                    <label>Vardas <input type="text" name="first_name" maxlength="80" value="<?php echo socialEscape($profile['first_name']); ?>"></label><br>
                    <label>Pavardė <input type="text" name="last_name" maxlength="80" value="<?php echo socialEscape($profile['last_name']); ?>"></label><br>
                    <label>Apie mane<br><textarea name="bio" maxlength="1000"><?php echo socialEscape($profile['bio']); ?></textarea></label><br>
                    <button type="submit">Išsaugoti profilį</button>
                </form>
            </section>
            <p><a href="userinfo.php?user=<?php echo urlencode($session->username); ?>">Peržiūrėti viešą profilį</a></p>
        <?php } ?>
    <?php } else if ($tab == "moderation") { ?>
        <h1>Turinio priežiūra</h1>
        <h2>Vieši įrašai</h2>
        <?php
        $posts = socialFetchAll("SELECT id, username, content, created_at FROM social_posts ORDER BY created_at DESC LIMIT 100", "", array());
        foreach ($posts as $post) {
            echo '<article class="social-card"><p><a href="userinfo.php?user=' . urlencode($post['username']) . '">'
                    . socialEscape($post['username']) . '</a> · ' . date("Y-m-d H:i", $post['created_at'])
                    . '</p><p>' . nl2br(socialEscape($post['content'])) . '</p>';
            socialPostForm("delete_post", "moderation", array("post_id" => $post['id']));
            echo '<button type="submit">Pašalinti įrašą</button></form></article>';
        }
        ?>
        <h2>Komentarai</h2>
        <?php
        $comments = socialFetchAll("SELECT id, post_id, username, content, created_at FROM social_comments ORDER BY created_at DESC LIMIT 100", "", array());
        foreach ($comments as $comment) {
            echo '<article class="social-card"><p>' . socialEscape($comment['username']) . ' · įrašas #' . (int) $comment['post_id']
                    . ' · ' . date("Y-m-d H:i", $comment['created_at']) . '</p><p>' . nl2br(socialEscape($comment['content'])) . '</p>';
            socialPostForm("delete_comment", "moderation", array("comment_id" => $comment['id']));
            echo '<button type="submit">Pašalinti komentarą</button></form></article>';
        }
        ?>
        <h2>Žinutės</h2>
        <?php
        $messages = socialFetchAll("SELECT id, sender, recipient, content, created_at FROM social_messages ORDER BY created_at DESC LIMIT 100", "", array());
        foreach ($messages as $message) {
            echo '<article class="social-card"><p>' . socialEscape($message['sender']) . ' → '
                    . socialEscape($message['recipient']) . ' · ' . date("Y-m-d H:i", $message['created_at'])
                    . '</p><p>' . nl2br(socialEscape($message['content'])) . '</p>';
            socialPostForm("delete_message", "moderation", array("message_id" => $message['id']));
            echo '<button type="submit">Pašalinti žinutę</button></form></article>';
        }
        ?>
    <?php } ?>
    <?php include("include/footer.php"); ?>
</td></tr></table>
</body>
</html>
