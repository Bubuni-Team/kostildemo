<section class="section">
    <div class="container">
        <?php if (empty($demoList)): ?>
            Пока что здесь нет демо-записей
        <?php else: ?>
            <?php foreach ($demoList as $demo): ?>
                <?= $this->renderTemplate('demo_entry', [
                        'demo' => $demo,
                        'server' => self::$config['servers'][(int) $demo['server_id']]])
                ?>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</section>