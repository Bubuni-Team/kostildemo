<?php
$demo['uploaded_at'] = "@{$demo['uploaded_at']}";
$demo['started_at'] = "@{$demo['started_at']}";
$demo['finished_at'] = "@{$demo['finished_at']}";
?>

<article class="media demoRecord" data-map="<?= htmlspecialchars($demo['map']) ?>" data-server="<?= $demo['server_id'] ?>"
         data-demo-id="<?= $demo['record_id'] ?>">
    <figure class="media-left">
        <p class="image is-64x64">
            <img src="assets/maps/<?= $demo['map'] ?>.png" title="<?= $demo['map'] ?>" />
        </p>
    </figure>

    <div class="media-content">
        <div class="content">
            <p>
                <strong class="mapName"><?= $demo['map'] ?></strong>
                <small class="serverName"><?= $server['name'] ?></small>
            </p>
            <div class="tags players">
                <?php foreach ($demo['players'] as $player): ?>
                    <div class="tag player" data-account-id="<?= $player['account_id'] ?>">
                        <a href="https://steamcommunity.com/profiles/[U:1:<?= $player['account_id'] ?>]/"
                           target="_blank" class="profile-link">
                            <?= htmlspecialchars($player['username']) ?>
                        </a>

                        <a title="Найти демо-записи с участием <?= htmlspecialchars($player['username']) ?>"
                           href="?find=<?= $player['account_id'] ?>" class="find-link">
                            <span class="icon is-small">
                                <ion-icon name="search-outline"></ion-icon>
                            </span>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <nav class="level is-mobile">
            <div class="level-left">
                <a class="level-item demo-download" href="?id=<?= $demo['record_id'] ?>">
                    <span class="icon is-small"><ion-icon name="cloud-download-outline"></ion-icon></span>
                </a>
                <div class="level-item demoLength">
                    <span class="icon is-small"><ion-icon name="time-outline"></ion-icon></span>
                    <?= (new DateTime($demo['finished_at']))->diff(new DateTime($demo['started_at']))->format('%I:%S') ?>
                </div>
                <div class="level-item demoRecordedAt">
                    <span class="icon is-small"><ion-icon name="calendar-outline"></ion-icon></span>
                    <?= (new DateTime($demo['uploaded_at']))->format('d.m h:i') ?>
                </div>
            </div>
        </nav>
    </div>
    <?php if (false): /** Стаб, возвращающий всегда false, ибо логина нет. TODO? */ ?>
    <div class="media-right">
        <button class="delete"></button>
    </div>
<?php endif; ?>