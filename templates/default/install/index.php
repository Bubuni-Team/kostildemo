<?php /** @noinspection PhpUndefinedVariableInspection */

$games = json_encode(iterator_to_array(App\Data\InstallMapName::getAvailableDictionaries()));

$containerVariables = $this->container['page_container.vars'];

$containerVariables['headAdditionalCode']['Vue.JS'] = <<<HTML
<!-- development version, includes helpful console warnings -->
<!-- do not forget to replace ok version. TODO. -->
<script src="https://cdn.jsdelivr.net/npm/vue@2/dist/vue.js"></script>
HTML;

$containerVariables['headAdditionalCode']['Install'] = <<<HTML
<script type="text/javascript" src="assets/js/install.js"></script>
<script type="text/javascript">
    window.initializeTemplateRelatedComponents = function () {
        Vue.component('hero-section', {
            props: ['title', 'subtitle', 'row-class'],

            template: `<section :class="['hero', ...(rowClass || [])]"> 
                    <div class="hero-head" v-if="title.length || subtitle.length"> 
                        <p class="title" v-if="title.length" v-html="title"></p> 
                        <p class="subtitle" v-if="subtitle.length" v-html="subtitle"></p> 
                    </div>
                    <div class="hero-body">
                        <slot></slot> 
                    </div> 
                </section>`
        });
    };
</script>
HTML;

$containerVariables['headAdditionalCode']['Installer Game Presets'] = <<<HTML
<script type="application/json" id="game-presets">{$games}</script>
HTML;


?>

<div id="install-root">
    <form @submit.prevent="install">
        <!-- Базовые настройки -->
        <hero-section title="Базовые настройки"
            subtitle="Примитивные настройки, по типу заголовка системы и частоты отрабатывания задания на удаление устаревших демок."
            :row-class="['is-small']">
            <div class="field is-horizontal">
                <div class="field-label is-normal">
                    <label class="label">Заголовок</label>
                </div>
                <div class="field-body">
                    <div class="field">
                        <p class="control is-expanded has-icons-left">
                            <input class="input" type="text" placeholder="Демо-система для моего проекта"
                                v-model="options.title" required />
                            <span class="icon is-small is-left">
                                <ion-icon name="information-outline"></ion-icon>
                            </span>
                        </p>

                        <p class="help">Этот текст будет выводиться в шапке сайта, а так же в качестве имени вкладки</p>
                    </div>
                </div>
            </div>

            <div class="field is-horizontal">
                <div class="field-label is-normal">
                    <label class="label">URL-адрес сайта</label>
                </div>
                <div class="field-body">
                    <div class="field">
                        <p class="control is-expanded has-icons-left">
                            <input class="input" type="text" placeholder="https://demo.bubuni.com"
                                v-model="options.publicUrl" required />
                            <span class="icon is-small is-left">
                                <ion-icon name="link-outline"></ion-icon>
                            </span>
                        </p>

                        <p class="help">Адрес, при обращении к которому будет открываться данный веб-скрипт</p>
                    </div>
                </div>
            </div>

            <div class="field is-horizontal">
                <div class="field-label is-normal">
                    <label class="label">Размер чанка</label>
                </div>
                <div class="field-body">
                    <div class="field has-addons">
                        <p class="control">
                            <span class="select">
                                <select v-model="options.chunkSize.method">
                                    <option value="K">КБайт</option>
                                    <option value="M">МБайт</option>
                                    <option value="G">ГБайт</option>
                                    <option value="-" disabled>-</option>
                                    <option value="auto">Автоматически</option>
                                </select>
                            </span>
                        </p>

                        <p class="control is-expanded is-fullwidth has-icons-left">
                            <input class="input" type="text" placeholder="Размер в указанной ЕИ"
                                v-model="options.chunkSize.count" :disabled="options.chunkSize.method == 'auto'" />
                            <span class="icon is-small is-left">
                                <ion-icon name="globe-outline"></ion-icon>
                            </span>
                        </p>
                    </div>
                </div>
            </div>
        </hero-section>
        <!-- /Базовые настройки -->

        <!-- База данных -->
        <hero-section title="База данных" subtitle="Настройка подключения к хранилищу данных."
            :row-class="['is-small']">
            <div class="field is-horizontal">
                <div class="field-label is-normal">
                    <label class="label">Сервер</label>
                </div>

                <div class="field-body">
                    <div class="field">
                        <p class="control is-expanded has-icons-left">
                            <input class="input" type="text" placeholder="IP-адрес / доменное имя" v-model="db.host"
                                @change.lazy="checkDatabaseCredentials"
                                :class="{'is-danger': db.isSuccess === false, 'is-success': db.isSuccess === true}"
                                required />
                            <span class="icon is-small is-left">
                                <ion-icon name="server-outline"></ion-icon>
                            </span>
                        </p>
                    </div>
                    <div class="field">
                        <p class="control is-expanded has-icons-left">
                            <input class="input" type="numeric" placeholder="Порт (по-умолчанию, 3306)"
                                v-model="db.port" @change.lazy="checkDatabaseCredentials"
                                :class="{'is-danger': db.isSuccess === false, 'is-success': db.isSuccess === true}" />
                            <span class="icon is-small is-left">
                                <ion-icon name="keypad-outline"></ion-icon>
                            </span>
                        </p>
                    </div>
                </div>
            </div>

            <div class="field is-horizontal">
                <div class="field-label is-normal">
                    <label class="label">Аутентификация</label>
                </div>

                <div class="field-body">
                    <div class="field">
                        <p class="control is-expanded has-icons-left">
                            <input class="input" type="text" placeholder="Пользователь" v-model="db.user"
                                @change.lazy="checkDatabaseCredentials"
                                :class="{'is-danger': db.isSuccess === false, 'is-success': db.isSuccess === true}"
                                required />
                            <span class="icon is-small is-left">
                                <ion-icon name="person-outline"></ion-icon>
                            </span>
                        </p>
                    </div>
                    <div class="field">
                        <p class="control is-expanded has-icons-left">
                            <input class="input" type="password" placeholder="Пароль" v-model="db.password"
                                @change.lazy="checkDatabaseCredentials"
                                :class="{'is-danger': db.isSuccess === false, 'is-success': db.isSuccess === true}" />
                            <span class="icon is-small is-left">
                                <ion-icon name="key-outline"></ion-icon>
                            </span>
                        </p>
                    </div>
                </div>
            </div>

            <div class="field is-horizontal">
                <div class="field-label is-normal">
                    <label class="label">База данных</label>
                </div>
                <div class="field-body">
                    <div class="field">
                        <div class="control is-expanded has-icons-left">
                            <input class="input"
                                :class="{'is-danger': db.isSuccess === false, 'is-success': db.isSuccess === true}"
                                type="text" placeholder="Имя" v-model="db.dbname"
                                @change.lazy="checkDatabaseCredentials" required />
                            <span class="icon is-small is-left">
                                <ion-icon name="document-outline"></ion-icon>
                            </span>
                        </div>
                        <p class="help" v-if="db.isSuccess === false">{{ db.errorMessage }}</p>
                    </div>
                </div>
            </div>
        </hero-section>
        <!-- /База данных -->

        <!-- Карты -->
        <hero-section title="Карты"
            subtitle="Вместо отображения имен файлов карт, мы можем показывать их человекопонятные имена. Например, вместо <code>de_dust2</code> - <code>Dust II</code>. Этот раздел предназначен для базовой конфигурации этого аспекта."
            :row-class="['is-small']">
            <div class="field is-horizontal">
                <div class="field-label is-normal">
                    <label class="label">Пресеты</label>
                </div>

                <div class="field-body">
                    <div class="field" v-for="(title, game) in metadata.mapPresets" :key="game">
                        <label class="checkbox">
                            <input type="checkbox" v-model="options.mapPresets" :value="game" />
                            {{ title }}
                        </label>
                    </div>
                </div>
            </div>

            <div class="field is-horizontal" v-for="(row, index) in mapDict" :key="index">
                <div class="field-body">
                    <div class="field">
                        <p class="control is-expanded">
                            <input class="input" type="text" placeholder="Внутреннее название карты" v-model="row.name"
                                @input.lazy="checkFields(mapDict, index, {name: '', title: ''}, false)" />
                        </p>
                    </div>
                    <div class="field">
                        <p class="control is-expanded">
                            <input class="input" type="text" placeholder="Как надо отображать?" v-model="row.title"
                                @input.lazy="checkFields(mapDict, index, {name: '', title: ''}, false)" />
                        </p>
                    </div>
                </div>
            </div>
        </hero-section>
        <!-- /Карты -->

        <!-- Администрация -->
        <hero-section title="Администрация"
            subtitle="Пользователи, которым доступен расширенный функционал после авторизации. Например, они могут удалять выборочно демки до истечения их срока или выполнять операцию обновления веб-части."
            :row-class="['is-small']">

            <div class="field is-horizontal" v-for="(admin, index) in administrators" :key="index">
                <div class="field-body">
                    <div class="field">
                        <p class="control is-expanded has-icons-left">
                            <input class="input" type="text" placeholder="STEAM_0:0:55665612" v-model="admin.value"
                                @input.lazy="checkFields(administrators, index, {}, true)" />
                            <span class="icon is-small is-left">
                                <ion-icon name="person-outline"></ion-icon>
                            </span>
                        </p>
                    </div>
                </div>
            </div>
        </hero-section>
        <!-- /Администрация -->

        <!-- Сервера -->
        <hero-section title="Сервера"
            subtitle="Magna qui esse ut sunt. Ullamco veniam ullamco fugiat aliqua enim elit est velit anim ut pariatur sunt mollit ipsum. Commodo dolore labore nostrud ipsum adipisicing amet non reprehenderit minim proident velit consectetur enim. Ipsum pariatur officia Lorem ipsum ea occaecat fugiat et cillum irure ad. Enim proident aute anim veniam eu. Lorem ipsum et dolore commodo dolore ad nisi fugiat non nostrud ullamco ut. Magna commodo consequat exercitation ea."
            :row-class="['is-small']">

            <div class="field is-horizontal" v-for="(server, index) in servers" :key="index">
                <div class="field-body">
                    <div class="field">
                        <p class="control is-expanded has-icons-left">
                            <input class="input" type="text" placeholder="Имя" v-model="server.name"
                                @input.lazy="checkFields(servers, index, {name: '', address: '', key: generateKey()}, false, ['key'])" />
                            <span class="icon is-small is-left">
                                <ion-icon name="star-outline"></ion-icon>
                            </span>
                        </p>
                    </div>
                    <div class="field">
                        <p class="control is-expanded has-icons-left">
                            <input class="input" type="text" placeholder="Адрес" v-model="server.address"
                                @input.lazy="checkFields(servers, index, {name: '', address: '', key: generateKey()}, false, ['key'])" />
                            <span class="icon is-small is-left">
                                <ion-icon name="pencil-outline"></ion-icon>
                            </span>
                        </p>
                    </div>
                    <div class="field">
                        <p class="control is-expanded has-icons-left">
                            <input class="input" type="text" v-model="server.key" readonly disabled />
                            <span class="icon is-small is-left">
                                <ion-icon name="key-outline"></ion-icon>
                            </span>
                        </p>
                    </div>
                </div>
            </div>
        </hero-section>
        <!-- /Сервера -->

        <!-- Установка -->
        <div class="field is-grouped is-grouped-right">
            <div class="control">
                <button type="submit" class="button is-link">Установить</button>
            </div>
        </div>
        <!-- /Установка -->
    </form>
</div>