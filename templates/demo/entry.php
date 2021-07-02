<?php
/**
 * @noinspection PhpUndefinedClassInspection
 * @noinspection PhpUndefinedVariableInspection
 * @noinspection PhpUnhandledExceptionInspection
 */

$demo['uploaded_at'] = "@{$demo['uploaded_at']}";
$demo['started_at'] = "@{$demo['started_at']}";
$demo['finished_at'] = "@{$demo['finished_at']}";

$demoMapName = $demo['map'];
$mapNameLastSlashIndex = strrpos($demoMapName, '/');

$mapName = $mapNameLastSlashIndex === FALSE ? $demoMapName : substr($demoMapName, $mapNameLastSlashIndex + 1);

$prettyMapName = self::$config['mapNames'][$mapName] ?? $mapName;
$mapImageFullFileName = sprintf('%s/assets/maps/%s.png', App::$dir, $demo['map']);
$mapImage = file_exists($mapImageFullFileName) ?
            './assets/maps/' . $demo['map'] . '.png' :
            './assets/maps/nomap.png';

$playerIds = ',' . implode(',', array_keys($demo['players'])) . ',';
?>

<article class="media demoRecord" data-map="<?= htmlspecialchars($demo['map']) ?>" data-server="<?= $demo['server_id'] ?>"
         data-demo-id="<?= $demo['record_id'] ?>" data-player-ids="<?= $playerIds ?>">
    <figure class="media-left">
        <p class="image is-64x64">
            <img src="<?= $mapImage ?>" title="<?= $prettyMapName ?>" alt="<?= $prettyMapName ?>" />
        </p>
    </figure>

    <div class="media-content">
        <div class="content">
            <p>
                <strong class="mapName"><?= $prettyMapName ?></strong>
                <?php if (isset($server['address'])): ?>
                    <small class="serverName">
                        <a href="steam://connect/<?= $server['address'] ?>"><?= $server['name'] ?></a>
                    </small>
                <?php else: ?>
                    <small class="serverName"><?= $server['name'] ?></small>
                <?php endif; ?>
            </p>
            <div class="tags players">
                <?php foreach ($demo['players'] as $player): ?>
                    <div class="tag player<?= $player['account_id'] == $playerId ? ' is-warning' : '' ?>"
                         data-account-id="<?= $player['account_id'] ?>">
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
                <a class="level-item demo-download" href="./data/demos/<?= $demo['demo_id'] ?>.dem">
                    <span class="icon is-small"><ion-icon name="cloud-download-outline"></ion-icon></span>
                </a>
                <div class="level-item demoLength">
                    <span class="icon is-small"><ion-icon name="time-outline"></ion-icon></span>
                    <?php
                        $diff = (new DateTime($demo['finished_at']))->diff(new DateTime($demo['started_at']));
                        $format = $diff->h > 0 ? '%H:%I:%S' : '%I:%S';
                    ?>
                    <?= $diff->format($format) ?>
                </div>
                <div class="level-item demoRecordedAt">
                    <span class="icon is-small"><ion-icon name="calendar-outline"></ion-icon></span>
                    <?= (new DateTime($demo['uploaded_at']))->format('d.m H:i') ?>
                </div>
            </div>
        </nav>
    </div>
    <?php if (false): /** Стаб, возвращающий всегда false, ибо логина нет. TODO? */ ?>
    <div class="media-right">
        <button class="delete"></button>
    </div>
    <?php endif; ?>
</article>
