<?php
include("include/session.php");
include("include/social.php");

$requestedUser = isset($_GET['user']) && is_string($_GET['user']) ? trim($_GET['user']) : "";
$profile = $requestedUser !== "" ? socialFetchOne("SELECT username, first_name, last_name, bio FROM users WHERE username = ?",
        "s", array($requestedUser)) : null;
if (!$profile) {
    header("HTTP/1.1 404 Not Found");
    ?>
    <!DOCTYPE html>
    <html><head><meta charset="utf-8"><title>Profilis nerastas</title>
        <link href="include/styles.css" rel="stylesheet" type="text/css"></head>
    <body><p>Vartotojo profilis nerastas. <a href="social.php">Grįžti į socialinį tinklą</a></p></body></html>
    <?php
    exit;
}

$posts = socialFetchAll("SELECT id, content, created_at FROM social_posts WHERE username = ? ORDER BY created_at DESC LIMIT 50",
        "s", array($profile['username']));
$followers = socialFetchOne("SELECT COUNT(*) AS total FROM social_follows WHERE followed = ?",
        "s", array($profile['username']));
$following = socialFetchOne("SELECT COUNT(*) AS total FROM social_follows WHERE follower = ?",
        "s", array($profile['username']));
$followerList = socialFetchAll("SELECT follower FROM social_follows WHERE followed = ? ORDER BY created_at DESC LIMIT 100",
        "s", array($profile['username']));
$followingList = socialFetchAll("SELECT followed FROM social_follows WHERE follower = ? ORDER BY created_at DESC LIMIT 100",
        "s", array($profile['username']));
$isFollowing = false;
if ($session->logged_in && $session->username != $profile['username']) {
    $isFollowing = socialFetchOne("SELECT follower FROM social_follows WHERE follower = ? AND followed = ?",
            "ss", array($session->username, $profile['username'])) != null;
}
$displayName = trim($profile['first_name'] . " " . $profile['last_name']);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title><?php echo socialEscape($displayName != "" ? $displayName : $profile['username']); ?></title>
    <link href="include/styles.css" rel="stylesheet" type="text/css">
</head>
<body>
<table class="center"><tr><td>
    <center><img src="pictures/top.png" alt=""></center>
    <?php include("include/meniu.php"); ?>
    <p><a href="social.php">Grįžti į socialinį tinklą</a></p>
    <section class="social-card">
        <h1><?php echo socialEscape($displayName != "" ? $displayName : $profile['username']); ?></h1>
        <?php if ($displayName != "") { ?><p>Vartotojo vardas: <?php echo socialEscape($profile['username']); ?></p><?php } ?>
        <?php if ($profile['bio'] != "") { ?><p><?php echo nl2br(socialEscape($profile['bio'])); ?></p><?php } ?>
        <p><?php echo (int) $followers['total']; ?> sekėjų · seka <?php echo (int) $following['total']; ?> vartotojų</p>
        <p><b>Sekėjai:</b>
            <?php if (!count($followerList)) { echo " dar nėra"; } ?>
            <?php foreach ($followerList as $index => $follower) {
                if ($index > 0) { echo ", "; }
                echo '<a href="userinfo.php?user=' . urlencode($follower['follower']) . '">' . socialEscape($follower['follower']) . '</a>';
            } ?>
        </p>
        <p><b>Sekami vartotojai:</b>
            <?php if (!count($followingList)) { echo " dar nėra"; } ?>
            <?php foreach ($followingList as $index => $followed) {
                if ($index > 0) { echo ", "; }
                echo '<a href="userinfo.php?user=' . urlencode($followed['followed']) . '">' . socialEscape($followed['followed']) . '</a>';
            } ?>
        </p>
        <?php if ($session->logged_in && $session->username != $profile['username']) { ?>
            <?php socialPostForm($isFollowing ? "unfollow" : "follow", "feed", array("target" => $profile['username'])); ?>
                <button type="submit"><?php echo $isFollowing ? "Nebesekti" : "Sekti"; ?></button>
            </form>
            <a href="social.php?tab=messages&amp;to=<?php echo urlencode($profile['username']); ?>">Siųsti privačią žinutę</a>
        <?php } ?>
    </section>
    <h2>Vieši įrašai</h2>
    <?php if (!count($posts)) { ?><p>Šis vartotojas dar nepaskelbė įrašų.</p><?php } ?>
    <?php foreach ($posts as $post) { ?>
        <article class="social-card">
            <p><?php echo date("Y-m-d H:i", $post['created_at']); ?></p>
            <p><?php echo nl2br(socialEscape($post['content'])); ?></p>
            <a href="social.php?tab=feed">Peržiūrėti komentarus ir reakcijas</a>
        </article>
    <?php } ?>
    <?php include("include/footer.php"); ?>
</td></tr></table>
</body>
</html>
