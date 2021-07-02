<?php /** @noinspection PhpUndefinedVariableInspection */

$validationRow = function($name, $value, $isSuccess)
{
    return $this->renderTemplate('install/validation_row', [
        'name' => $name,
        'value' => $value,
        'isSuccess' => $isSuccess
    ]);
}

?><section class="section">
    <div class="container">
        <?php if ($isInstalled): ?>
            <p>
                Похоже, что скрипт уже установлен. Удалите <code>/src/config.php</code> для запуска установки.
                <br />
                Или, если хотите запустить процесс обновления, <a href="?controller=install&action=upgrade">щёлкните сюда</a>.
            </p>
        <?php else: ?>
            <table class="table is-bordered is-fullwidth">
                <thead>
                    <tr>
                        <th>Критерий</th>
                        <th>Результат</th>
                    </tr>
                </thead>
                <tbody>
                    <?= $validationRow(
                            'Данные от БД',
                            empty($dbError) ? 'OK' : $dbError,
                            empty($dbError)
                    ) ?>
                    <?= $validationRow(
                            'Наличие администраторов',
                            count($config['administrators'] ?? []) . ' администраторов',
                            !empty($config['administrators'] ?? [])
                    ) ?>
                    <?= $validationRow(
                            'Корректность заполнения серверов',
                            empty($serverErrorExplain) ? 'OK' : $serverErrorExplain,
                            empty($serverErrorExplain)
                    ) ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</section>