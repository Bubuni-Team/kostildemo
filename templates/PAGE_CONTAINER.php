<!DOCTYPE html>
<html class="demoSystem">
<head>
    <?php
        $siteName = $options['siteName'];
        $pageTitle = isset($secondaryTitle) ? "$siteName | $secondaryTitle" : $siteName;
    ?>
    <title><?= $pageTitle ?></title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bulma@0.9.2/css/bulma.min.css">
    <script src="https://unpkg.com/ionicons@5.4.0/dist/ionicons.js"></script>

    <link rel="stylesheet" href="assets/css/main.css" />
</head>
<body>
<div class="main-content">
    <section class="section">
        <div class="container">
            <h1 class="title"><?= $pageTitle ?></h1>
        </div>
    </section>
    <section class="section">
        <div class="container">
            <?= $pageContent ?>
        </div>
    </section>
</div>

<footer class="footer">
    <div class="content has-text-centered">
        <p>
            <strong>Demo System</strong> by <a href="https://github.com/Bubuni-Team" target="_blank" rel="noopener">Bubuni Team</a>. <br>
            Наполнено силой Bulma и PHP
        </p>
    </div>
</footer>
</body>
</html>