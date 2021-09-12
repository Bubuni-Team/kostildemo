<section class="section">
    <div class="container">
        <?php if (empty($demoList)): ?>
            Пока что здесь нет демо-записей
        <?php else: ?>
            <?php foreach ($demoList as $demo): ?>
                <?= $this->renderTemplate('demo/entry', [
                        'demo' => $demo,
                        'server' => $this->config()['servers'][(int) $demo['server_id']],
                        'playerId' => $playerId
                ]);
                ?>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</section>